<?php

include_once("libs/maLibSQL.pdo.php");

// ---- Utilisateurs ---- //

function getUserById($idUser)
{
	$SQL = "SELECT id, username, first_name AS firstName, last_name AS lastName, email, role, cursus, picture, theme FROM users WHERE id='$idUser'";
	$listUsers = parcoursRs(SQLSelect($SQL));

	if (count($listUsers) == 0)
		return false;
	else
		return $listUsers[0];
}

function getUserByUsername($username)
{
	$SQL = "SELECT id, username, first_name AS firstName, last_name AS lastName, email, role, cursus, picture, theme FROM users WHERE username='$username'";
	$listUsers = parcoursRs(SQLSelect($SQL));

	if (count($listUsers) == 0)
		return false;
	else
		return $listUsers[0];
}

function getUsers($search = "")
{
	$SQL = "SELECT id, username, first_name AS firstName, last_name AS lastName, email, role, cursus, picture, theme FROM users";

	if ($search != "") {
		$search = htmlspecialchars($search);
		$SQL .= " WHERE username LIKE '%$search%'";
	}

	return parcoursRs(SQLSelect($SQL));
}

function insertUser($username, $firstName, $lastName, $email, $cursus)
{
	$token_hash = sha1(rand()); // Génère un hash simple pour le token (à améliorer en production)
	$SQL = "INSERT INTO users (username, first_name, last_name, email, token_hash, role, cursus) VALUES ('$username', '$firstName', '$lastName', '$email', '$token_hash', 'user', '$cursus')";
	return SQLInsert($SQL);
}

function updateUser($idUser, $firstName, $lastName, $email, $cursus, $picture = NULL, $theme = NULL)
{
	$SQL = "UPDATE users SET first_name='$firstName', last_name='$lastName', email='$email', cursus='$cursus'";
	if ($picture !== NULL) {
		$SQL .= ", picture='$picture'";
	}
	if ($theme !== NULL) {
		$SQL .= ", theme='$theme'";
	}
	$SQL .= " WHERE id='$idUser'";
	return SQLUpdate($SQL);
}

// ---- Associations ---- //

function getAssociations($nb = 0)
{
	$SQL = "SELECT id, name, description, website, admin FROM associations";
	if ($nb > 0) {
		$SQL .= " LIMIT $nb";
	}
	return parcoursRs(SQLSelect($SQL));
}

function getAssociationById($id)
{
	$SQL = "SELECT id, name, description, website, admin FROM associations WHERE id='$id'";
	$listAssos = parcoursRs(SQLSelect($SQL));
	return count($listAssos) > 0 ? $listAssos[0] : false;
}

function getUserAssociations($idUser)
{
	// Cette fonction suppose une table de liaison ou un champ dans `associations` pour l'admin
	$SQL = "SELECT id, name FROM associations WHERE admin='$idUser'";
	return parcoursRs(SQLSelect($SQL));
}

// ---- Badges ---- //

function getBadges()
{
	$SQL = "SELECT id, name, display_name AS displayName, description, emoji FROM badges ORDER BY id ASC";
	return parcoursRs(SQLSelect($SQL));
}

function getBadgeByName($name)
{
	$SQL = "SELECT id, name, display_name AS displayName, description, emoji FROM badges WHERE name='$name'";
	$listBadges = parcoursRs(SQLSelect($SQL));
	return count($listBadges) > 0 ? $listBadges[0] : false;
}

// ---- Événements ---- //

function getEvents($nb = 0, $activeOnly = true, $tagIds = [], $associationIds = [])
{
	$SQL = "SELECT E.*,
            GROUP_CONCAT(DISTINCT T.name ORDER BY T.name SEPARATOR ',') AS tagNames,
            GROUP_CONCAT(DISTINCT ET.tag ORDER BY ET.tag SEPARATOR ',') AS tagIds,
            GROUP_CONCAT(DISTINCT CASE WHEN I.type = 'orga' THEN U.username END) AS organizers,
            GROUP_CONCAT(DISTINCT CASE WHEN I.type = 'participant' THEN U.username END) AS participants,
            GROUP_CONCAT(DISTINCT CASE WHEN I.type = 'interested' THEN U.username END) AS interested
            FROM events E
            LEFT JOIN event_tags ET ON E.id = ET.event
            LEFT JOIN tags T ON ET.tag = T.id
            LEFT JOIN involvements I ON E.id = I.event
            LEFT JOIN users U ON I.user = U.id";

	$conditions = [];

	if ($activeOnly) {
		$conditions[] = "E.end_time >= NOW()";
	}

	// Filter by tag IDs
	if (!empty($tagIds)) {
		// Ensure tagIds are integers for security
		$tagIds = array_map('intval', $tagIds);
		// Utilise IN pour filtrer par les tags sélectionnés
		$conditions[] = "E.id IN (SELECT event FROM event_tags WHERE tag IN (" . implode(',', $tagIds) . "))";
	}

	// Filter by association IDs
	if (!empty($associationIds)) {
		// Ensure associationIds are integers for security
		$associationIds = array_map('intval', $associationIds);
		$conditions[] = "E.association IN (" . implode(',', $associationIds) . ")";
	}

	if (!empty($conditions)) {
		$SQL .= " WHERE " . implode(' AND ', $conditions);
	}

	$SQL .= " GROUP BY E.id";
	$SQL .= " ORDER BY E.start_time ASC";

	if ($nb > 0) {
		$SQL .= " LIMIT $nb";
	}

	$result = parcoursRs(SQLSelect($SQL));

	foreach ($result as &$event) {
		// Assurez-vous que les clés existent avant d'appeler explode
		$event['organizers'] = isset($event['organizers']) && $event['organizers'] ? explode(',', $event['organizers']) : [];
		$event['participants'] = isset($event['participants']) && $event['participants'] ? explode(',', $event['participants']) : [];
		$event['interested'] = isset($event['interested']) && $event['interested'] ? explode(',', $event['interested']) : [];
		$event['tagIds'] = isset($event['tagIds']) && $event['tagIds'] ? explode(',', $event['tagIds']) : [];
		$event['tagNames'] = isset($event['tagNames']) && $event['tagNames'] ? explode(',', $event['tagNames']) : [];
	}

	return $result;
}

function getEventById($id)
{
	$events = getEvents(1, false, [], [], $id); // Récupère un seul événement, même s'il est passé
	return count($events) > 0 ? $events[0] : false;
}

function insertEvent($title, $content, $start_time, $end_time, $location, $image, $association_id, $author_id, $organizers = [], $tags = [])
{
	// Insertion de l'événement principal
	$SQL = "INSERT INTO events (title, content, start_time, end_time, location, image, association, author) VALUES ('$title', '$content', '$start_time', '$end_time', '$location', '$image', '$association_id', '$author_id')";
	$eventId = SQLInsert($SQL);

	if ($eventId) {
		// Insertion des tags associés
		foreach ($tags as $tagId) {
			$tagId = intval($tagId);
			SQLInsert("INSERT IGNORE INTO event_tags (event, tag) VALUES ('$eventId', '$tagId')");
		}

		// Insertion des organisateurs dans la table involvements
		foreach ($organizers as $organizerId) {
			$organizerId = intval($organizerId);
			SQLInsert("INSERT INTO involvements (event, user, type) VALUES ('$eventId', '$organizerId', 'orga')");
		}
	}
	return $eventId;
}

// ---- Involvements ---- //

function insertInvolvement($eventId, $userId, $type)
{
	// Empêcher les duplicatas si l'utilisateur est déjà impliqué de ce type
	$SQL = "INSERT INTO involvements (event, user, type) VALUES ('$eventId', '$userId', '$type') ON DUPLICATE KEY UPDATE type=type"; // La clause ON DUPLICATE KEY UPDATE ne fait rien si la ligne existe déjà
	return SQLInsert($SQL);
}

function getInvolvements($eventId = null, $userId = null)
{
	$SQL = "SELECT id, event, user, type FROM involvements";
	$conditions = [];
	if ($eventId !== null) {
		$conditions[] = "event='$eventId'";
	}
	if ($userId !== null) {
		$conditions[] = "user='$userId'";
	}
	if (!empty($conditions)) {
		$SQL .= " WHERE " . implode(' AND ', $conditions);
	}
	return parcoursRs(SQLSelect($SQL));
}

// ---- Tags ---- //

/**
 * Récupère tous les tags de la base de données.
 * @return array Tableau d'objets ou de tableaux associatifs représentant les tags.
 */
function getTags()
{
	$SQL = "SELECT id, name FROM tags ORDER BY name ASC";
	return parcoursRs(SQLSelect($SQL));
}


// ---- Thèmes ---- //

function getThemes()
{
	$SQL = "SELECT id, name, display_name FROM themes ORDER BY name ASC";
	return parcoursRs(SQLSelect($SQL));
}

// ---- Fonctions de calcul de badges (exemple) ---- //

function getUserEngagementData($idUser)
{
	$SQL = "SELECT
                COUNT(DISTINCT CASE WHEN type='participant' THEN event END) AS participated,
                COUNT(DISTINCT CASE WHEN type='interested' THEN event END) AS interested,
                COUNT(DISTINCT CASE WHEN type='orga' THEN event END) AS organized
            FROM involvements
            WHERE user='$idUser'";
	$result = parcoursRs(SQLSelect($SQL));
	return $result ? $result[0] : ['participated' => 0, 'interested' => 0, 'organized' => 0];
}

function getUserBadges($idUser)
{
	$badges = [];
	// Badge Newcomer (assumé donné à la première connexion)
	$badge = getBadgeByName("newcomer");
	if ($badge)
		$badges[] = $badge;

	$involvementData = getUserEngagementData($idUser);

	if (!$involvementData)
		return $badges;

	$participated = intval($involvementData["participated"]);
	$interested = intval($involvementData["interested"]);
	$organized = intval($involvementData["organized"]);

	if ($interested >= 3) {
		$badge = getBadgeByName("curious"); // 🧩 Curieux.se
		if ($badge)
			$badges[] = $badge;
	}

	if ($participated >= 3) {
		$badge = getBadgeByName("active"); // 💬 Actif.ve
		if ($badge)
			$badges[] = $badge;
	}
	if ($participated >= 10) {
		$badge = getBadgeByName("super_participant"); // 💥 Super participant.e
		if ($badge)
			$badges[] = $badge;
	}
	if ($participated >= 20) {
		$badge = getBadgeByName("gold"); // 💎 Engagé.e d’Or
		if ($badge)
			$badges[] = $badge;
	}

	if ($organized >= 1) {
		$badge = getBadgeByName("organizer"); // 🛠️ Organisateur.rice
		if ($badge)
			$badges[] = $badge;
	}
	if ($organized >= 5) {
		$badge = getBadgeByName("ambassador"); // 🌱 Ambassadeur.rice
		if ($badge)
			$badges[] = $badge;
	}
	if ($organized >= 10) {
		$badge = getBadgeByName("leader"); // 🧠 Leader d’impact
		if ($badge)
			$badges[] = $badge;
	}

	// Pour les badges 'loyal' et 'pioneer', cela nécessite des logiques plus complexes,
	// par ex. stocker la date de première activité ou le numéro d'ordre d'inscription.
	// Ces badges sont laissés pour implémentation future ou logique plus avancée.

	return $badges;
}

// Fonction pour l'authentification CAS
function validerUser($auth_token)
{
	// Vérifier si un utilisateur avec ce token existe
	$SQL = "SELECT id, username, role FROM users WHERE token_hash='$auth_token'";
	$user = parcoursRs(SQLSelect($SQL));

	if (count($user) > 0) {
		$_SESSION["idUser"] = $user[0]["id"];
		$_SESSION["username"] = $user[0]["username"];
		$_SESSION["role"] = $user[0]["role"];
		$_SESSION["connecte"] = true; // Indique que l'utilisateur est connecté
		return true;
	}
	return false;
}