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

	if($idEvent && is_array($tagsIds)) {
		foreach ($tagsIds as $tagId) {
			$SQL = "INSERT INTO event_tags(event, tag) VALUES('$idEvent', '$tagId')";
			SQLInsert($SQL);
		}
	}

	return $idEvent;
}

function getEvents($limit = 10, $whereClause = "")
{
	$SQL = "SELECT 
            e.*,
			GROUP_CONCAT(DISTINCT CASE WHEN i.type = 'orga' THEN i.user END) AS organizers,
            GROUP_CONCAT(DISTINCT CASE WHEN i.type = 'participate' THEN i.user END) AS participants,
            GROUP_CONCAT(DISTINCT CASE WHEN i.type = 'interested' THEN i.user END) AS interested,
            GROUP_CONCAT(DISTINCT et.tag) AS tagIds
        FROM events e
        LEFT JOIN involvements i ON e.id = i.event
        LEFT JOIN event_tags et ON e.id = et.event
        $whereClause
        GROUP BY e.id
        ORDER BY e.start_time
        LIMIT $limit
    ";

	$result = parcoursRs(SQLSelect($SQL));

	foreach ($result as &$event) {
		$event['orgnizers'] = $event['orgnizers'] ? explode(',', $event['orgnizers']) : [];
		$event['participants'] = $event['participants'] ? explode(',', $event['participants']) : [];
		$event['interested'] = $event['interested'] ? explode(',', $event['interested']) : [];
		$event['tagIds'] = $event['tagIds'] ? explode(',', $event['tagIds']) : [];
	}
	return $result;
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
	$SQL = "SELECT user FROM involvements WHERE event = " . intval($id) . " AND type = 'participate'";
	return parcoursRs(SQLSelect($SQL));
}

function getEventInterested($id)
{
	$SQL = "SELECT user FROM involvements WHERE event = " . intval($id) . " AND type = 'interested'";
	return parcoursRs(SQLSelect($SQL));
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

function getUserEventInvolvementIds($idUser, $type = "participate")
{
	$SQL = "SELECT event FROM involvements WHERE user = '$idUser' AND type = '$type'";
	return parcoursRs(SQLSelect($SQL));
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