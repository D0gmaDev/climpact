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

	$hash = generateToken($username);
	$cursus = str_replace("'", "''", $cursus);


	$SQL = "INSERT INTO users(username, first_name, last_name, email, token_hash, role, cursus)";
	$SQL .= " VALUES('$username', '$firstName', '$lastName', '$email', '$hash', 'student', '$cursus')";

	return SQLInsert($SQL);
}


function generateToken($username)
{
	$payload = $username . date("H:i:s");
	$hash = md5($payload);
	return $hash;
}

function getTokenById($idUser)
{
	$SQL = "SELECT token_hash FROM users WHERE id='$idUser'";
	return SQLGetChamp($SQL);
}

function isAdminById($idUser)
{
	$SQL = "SELECT role FROM users WHERE id='$idUser'";
	return SQLGetChamp($SQL) == "admin";
}

function getPictureById($idUser)
{
	$SQL = "SELECT picture FROM users WHERE id='$idUser'";
	$picture = SQLGetChamp($SQL);
	return $picture ? $picture : "media/default-avatar.png";
}

function updateCursus($idUser, $cursus)
{
	$SQL = "UPDATE users SET cursus='$cursus' WHERE id='$idUser'";
	return SQLUpdate($SQL);
}

function updatePicture($idUser, $picture)
{
	if (empty($picture)) {
		$SQL = "UPDATE users SET picture=NULL WHERE id='$idUser'";
		return SQLUpdate($SQL);
	} else {
		$SQL = "UPDATE users SET picture='$picture' WHERE id='$idUser'";
		return SQLUpdate($SQL);
	}
}

// ---- Evénements ---- //

function insertEvent($title, $content, $startTime, $endTime, $location, $image, $association, $author, $organizerIds, $tagsIds)
{
	$SQL = "INSERT INTO events(title, content, start_time, end_time, location, image, association, author) 
			VALUES('$title', '$content', '$startTime', '$endTime', '$location', '$image', '$association', '$author')";
	$idEvent = SQLInsert($SQL);

	if ($idEvent && is_array($organizerIds)) {
		foreach ($organizerIds as $organizer) {
			insertInvolvement($organizer, $idEvent, "orga");
		}
	}

	if ($idEvent && is_array($tagsIds)) {
		foreach ($tagsIds as $tagId) {
			$SQL = "INSERT INTO event_tags(event, tag) VALUES('$idEvent', '$tagId')";
			SQLInsert($SQL);
		}
	}

	return $idEvent;
}

function updateEvent($eventId, $title, $content, $start, $end, $location, $image)
{
	$SQL = "UPDATE events SET title='$title', content='$content', start_time='$start', end_time='$end', location='$location', image='$image' WHERE id='$eventId'";
	return SQLUpdate($SQL);
}

function deleteEvent($eventId)
{
	$SQL = "DELETE FROM events WHERE id='$eventId'";
	return SQLDelete($SQL);
}

function addOrganizerToEvent($eventId, $username)
{
	$user = getUserByUsername($username);
	if (!$user) {
		return false;
	}

	$SQL = "INSERT INTO involvements(user, event, type) VALUES(" . intval($user['id']) . ", " . intval($eventId) . ", 'orga')";
	return SQLInsert($SQL);
}

function removeOrganizerFromEvent($eventId, $username)
{
	$user = getUserByUsername($username);
	if (!$user) {
		return false;
	}

	$SQL = "DELETE FROM involvements WHERE user = " . intval($user['id']) . " AND event = " . intval($eventId) . " AND type = 'orga'";
	return SQLDelete($SQL);
}

function getEvents($nb = 0, $activeOnly = true, $tagIds = [], $associationIds = [])
{
	$SQL = "SELECT E.*,
                   AU.username AS author_username,
                   AU.first_name AS author_firstName,
                   AU.last_name AS author_lastName,
                   AU.picture AS author_picture,
				   ASS.name AS association_name,
                   GROUP_CONCAT(DISTINCT T.name ORDER BY T.name SEPARATOR ',') AS tagNames,
                   GROUP_CONCAT(DISTINCT ET.tag ORDER BY ET.tag SEPARATOR ',') AS tagIds,
                   GROUP_CONCAT(DISTINCT CASE WHEN I.type = 'orga' THEN U.username END) AS organizers,
                   GROUP_CONCAT(DISTINCT CASE WHEN I.type = 'participate' THEN U.username END) AS participants,
                   GROUP_CONCAT(DISTINCT CASE WHEN I.type = 'interested' THEN U.username END) AS interested
            FROM events E
            LEFT JOIN users AU ON E.author = AU.id
            LEFT JOIN event_tags ET ON E.id = ET.event
            LEFT JOIN tags T ON ET.tag = T.id
            LEFT JOIN involvements I ON E.id = I.event
			LEFT JOIN associations ASS ON E.association = ASS.id
            LEFT JOIN users U ON I.user = U.id";

	$conditions = [];

	if ($activeOnly) {
		$conditions[] = "E.end_time >= NOW()";
	}

	if (!empty($tagIds)) {
		$tagIds = array_map('intval', $tagIds);
		$conditions[] = "E.id IN (SELECT event FROM event_tags WHERE tag IN (" . implode(',', $tagIds) . "))";
	}

	if (!empty($associationIds)) {
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
		$event['organizers'] = isset($event['organizers']) && $event['organizers'] ? explode(',', $event['organizers']) : [];
		$event['participants'] = isset($event['participants']) && $event['participants'] ? explode(',', $event['participants']) : [];
		$event['interested'] = isset($event['interested']) && $event['interested'] ? explode(',', $event['interested']) : [];
		$event['tagIds'] = isset($event['tagIds']) && $event['tagIds'] ? explode(',', $event['tagIds']) : [];
		$event['tagNames'] = isset($event['tagNames']) && $event['tagNames'] ? explode(',', $event['tagNames']) : [];
	}

	return $result;
}

function getEventById($id) {
    $SQL = "SELECT E.*,
                   AU.username AS author_username,
                   AU.first_name AS author_firstName,
                   AU.last_name AS author_lastName,
                   AU.picture AS author_picture,
                   ASS.name AS association_name,
                   GROUP_CONCAT(DISTINCT T.name ORDER BY T.name SEPARATOR ',') AS tagNames,
                   GROUP_CONCAT(DISTINCT ET.tag ORDER BY ET.tag SEPARATOR ',') AS tagIds,
                   GROUP_CONCAT(DISTINCT CASE WHEN I.type = 'orga' THEN U.username END) AS organizers,
                   GROUP_CONCAT(DISTINCT CASE WHEN I.type = 'participate' THEN U.username END) AS participants,
                   GROUP_CONCAT(DISTINCT CASE WHEN I.type = 'interested' THEN U.username END) AS interested
            FROM events E
            LEFT JOIN users AU ON E.author = AU.id
            LEFT JOIN event_tags ET ON E.id = ET.event
            LEFT JOIN tags T ON ET.tag = T.id
            LEFT JOIN involvements I ON E.id = I.event
            LEFT JOIN associations ASS ON E.association = ASS.id
            LEFT JOIN users U ON I.user = U.id
            WHERE E.id = " . intval($id) . "
            GROUP BY E.id LIMIT 1";

    $result = parcoursRs(SQLSelect($SQL));

    if (count($result) > 0) {
        $event = $result[0];
        $event['organizers'] = isset($event['organizers']) && $event['organizers'] ? explode(',', $event['organizers']) : [];
        $event['participants'] = isset($event['participants']) && $event['participants'] ? explode(',', $event['participants']) : [];
        $event['interested'] = isset($event['interested']) && $event['interested'] ? explode(',', $event['interested']) : [];
        $event['tagIds'] = isset($event['tagIds']) && $event['tagIds'] ? explode(',', $event['tagIds']) : [];
        $event['tagNames'] = isset($event['tagNames']) && $event['tagNames'] ? explode(',', $event['tagNames']) : [];
        return $event;
    }
    return null;
}

function getFutureEvents($limit = 10)
{
	return getEvents($limit, "WHERE e.end_time > NOW()");
}

function getEvent($id)
{
	$results = getEvents(1, "WHERE e.id = " . intval($id));
	return count($results) > 0 ? $results[0] : null;
}

function getEventTags($id)
{
	$SQL = "SELECT tag FROM event_tags WHERE event = " . intval($id);
	return parcoursRs(SQLSelect($SQL));
}

function getEventOrgnizers($id)
{
	$SQL = "SELECT user FROM involvements WHERE event = " . intval($id) . " AND type = 'orga'";
	return parcoursRs(SQLSelect($SQL));
}

function getEventParticipants($id)
{
	$SQL = "SELECT u.id, u.username, u.first_name AS firstName, u.last_name AS lastName, u.email, u.role, u.cursus, u.picture, u.theme FROM involvements i JOIN users u ON u.id = i.user WHERE i.event = $id AND i.type = 'participate'";
	return parcoursRs(SQLSelect($SQL));
}

function getEventInterested($id)
{
	$SQL = "SELECT u.id, u.username, u.first_name AS firstName, u.last_name AS lastName, u.email, u.role, u.cursus, u.picture, u.theme FROM involvements i JOIN users u ON u.id = i.user WHERE i.event = $id AND i.type = 'interested'";
	return parcoursRs(SQLSelect($SQL));
}

function isAuthorOfEvent($idUser, $idEvent)
{
	$SQL = "SELECT COUNT(*) FROM events WHERE id = " . intval($idEvent) . " AND author = " . intval($idUser);
	$count = SQLGetChamp($SQL);
	return $count > 0;
}

// ---- Associations ---- //

function getAssociation($id)
{
	$SQL = "SELECT * FROM associations WHERE id = " . intval($id);
	$results = parcoursRs(SQLSelect($SQL));
	return count($results) > 0 ? $results[0] : null;
}

function getAssociations($search = "")
{
	$SQL = "SELECT * FROM associations";

	if ($search != "") {
		$SQL .= " WHERE name LIKE '%$search%'";
	}

	return parcoursRs(SQLSelect($SQL));
}

function getUserAssociations($idUser)
{
	$SQL = "SELECT * FROM associations WHERE admin = " . intval($idUser);
	return parcoursRs(SQLSelect($SQL));
}

function hasAssociation($idUser)
{
	$SQL = "SELECT COUNT(*) FROM associations WHERE admin = " . intval($idUser);
	$count = SQLGetChamp($SQL);
	return $count > 0;
}

// ---- Themes ---- //

function getTheme($userId)
{
	$SQL = "SELECT theme FROM users WHERE id = " . intval($userId);
	$theme = SQLGetChamp($SQL);
	return $theme ? $theme : "default";
}

function setTheme($userId, $theme)
{
	$theme = htmlspecialchars($theme);
	$SQL = "UPDATE users SET theme = '$theme' WHERE id = " . intval($userId);
	return SQLUpdate($SQL);
}

// ---- Tags ---- //

function getTagById($id)
{
	$SQL = "SELECT * FROM tags WHERE id = " . intval($id);
	$results = parcoursRs(SQLSelect($SQL));
	return count($results) > 0 ? $results[0] : null;
}

function getTags($search = "")
{
	$SQL = "SELECT * FROM tags";

	if ($search != "") {
		$SQL .= " WHERE name LIKE '%$search%'";
	}

	return parcoursRs(SQLSelect($SQL));
}

// ---- Involvements ---- //

function insertInvolvement($idUser, $idEvent, $type = "participate")
{
	$SQL = "INSERT INTO involvements(user, event, type) VALUES('$idUser', '$idEvent', '$type')";
	return SQLInsert($SQL);
}

function deleteInvolvement($idUser, $idEvent, $type = "participate")
{
	$SQL = "DELETE FROM involvements WHERE user = '$idUser' AND event = '$idEvent' AND type = '$type'";
	return SQLDelete($SQL);
}

function toggleInvolvement($idUser, $idEvent, $type = "participate")
{
	if (isUserInvolvedInEvent($idUser, $idEvent, $type)) {
		deleteInvolvement($idUser, $idEvent, $type);
		return false;
	} else {
		insertInvolvement($idUser, $idEvent, $type);
		return true;
	}
}

// Dans modele.php
function getUserEventInvolvementIds($idUser, $type = "participate")
{
    $SQL = "SELECT event FROM involvements WHERE user = '$idUser' AND type = '$type'";
    $results = parcoursRs(SQLSelect($SQL));
    
    $eventIds = [];
    foreach ($results as $row) {
        $eventIds[] = $row['event']; // Extraire seulement l'ID de l'événement
    }
    return $eventIds;
}

function isUserInvolvedInEvent($idUser, $idEvent, $type = "participate")
{
	$SQL = "SELECT COUNT(*) FROM involvements WHERE user = '$idUser' AND event = '$idEvent' AND type = '$type'";
	$count = SQLGetChamp($SQL);
	return $count > 0;
}

function getBadgeByName($name)
{
	$SQL = "SELECT * FROM badges WHERE name = '$name'";
	$results = parcoursRs(SQLSelect($SQL));
	return count($results) > 0 ? $results[0] : null;
}

function getUserInvolvementData($idUser)
{
	$idUser = intval($idUser);

	$SQL = "SELECT
		u.id,
		COUNT(DISTINCT CASE WHEN i.type = 'participate' THEN i.event END) AS participated,
		COUNT(DISTINCT CASE WHEN i.type = 'interested' THEN i.event END) AS interested,
		COUNT(DISTINCT CASE WHEN i.type = 'orga' THEN i.event END) AS organized
	FROM users u
	LEFT JOIN involvements i ON i.user = u.id
	LEFT JOIN events e ON e.author = u.id
	WHERE u.id = $idUser
	GROUP BY u.id
	";

	$result = parcoursRs(SQLSelect($SQL));
	return count($result) > 0 ? $result[0] : null;
}

function getUserDistinctParticipationMonths($idUser)
{
	$SQL = "SELECT COUNT(DISTINCT DATE_FORMAT(e.start_time, '%Y-%m')) AS distinct_months
			FROM involvements i
			JOIN events e ON i.event = e.id
			WHERE i.user = $idUser AND i.type = 'participate'";

	$result = SQLGetChamp($SQL);
	return $result ? intval($result) : 0;
}

// ---- Badges ---- //

function getBadges()
{
	$SQL = "SELECT * FROM badges ORDER BY id";
	return parcoursRs(SQLSelect($SQL));
}

function getBadgeById($idBadge)
{
	$SQL = "SELECT * FROM badges WHERE id = " . intval($idBadge);
	$results = parcoursRs(SQLSelect($SQL));
	return count($results) > 0 ? $results[0] : null;
}

function getUserBadges($idUser)
{
	$badges = [];
	$badge = getBadgeByName("newcomer");  // 🎯 Nouveau venu
	if ($badge)
		$badges[] = $badge;

	$involvementData = getUserInvolvementData($idUser);

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

	// Badge fidélité : événements sur 3 mois différents
	if (getUserDistinctParticipationMonths($idUser) >= 3) {
		$badge = getBadgeByName("loyal"); // 🔁 Fidèle
		if ($badge)
			$badges[] = $badge;
	}

	if ($idUser <= 10) {
		$badge = getBadgeByName("pioneer"); // 🧭 Pionnier.ère
		if ($badge)
			$badges[] = $badge;
	}

	return $badges;
}

?>