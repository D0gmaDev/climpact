<?php
session_start();

include_once "libs/maLibUtils.php";
include_once "libs/maLibSQL.pdo.php";
include_once "libs/modele.php";

$qs = "";

if ($action = valider("action")) {
	ob_start();

	switch ($action) {
		case 'logout':
			session_destroy();
			break;

		case 'login':
			if (valider("connecte", "SESSION")) {
				header('Location: accueil.php');
				ob_end_flush();
				exit;
			}

			$url = "https://centralelilleassos.fr/authentification/climpact";
			header("Location: $url");
			ob_end_flush();
			exit();
			break;

		case "create_event":

			if (!valider("connecte", "SESSION")) {
				$qs = "?view=create&error=notconnected";
				break;
			}

			$idUser = valider("idUser", "SESSION");

			$title = valider("title");
			$content = valider("content");
			$association_id = valider("association_id");
			$start_date = valider("start_date");
			$start_time = valider("start_time");
			$end_date = valider("end_date");
			$end_time = valider("end_time");
			$lieu = valider("lieu");
			$image_url = valider("image_url"); // Champ optionnel

			$tags = valider("tags") ?: [];
			$organizers = valider("organizers") ?: [];

			if (in_array($association_id, getUserAssociations($idUser))) {
				$qs = "?view=create&error=associationnotallowed";
				break;
			}

			if (empty($title) || empty($content) || empty($association_id) || empty($start_date) || empty($start_time) || empty($end_date) || empty($end_time) || empty($lieu)) {
				$qs = "?view=create&error=missingfields";
				break;
			}

			// Combinaison des dates et heures pour le format DATETIME de SQL
			$startDateTime = "$start_date $start_time:00";
			$endDateTime = "$end_date $end_time:00";

			$newEventId = insertEvent($title, $content, $startDateTime, $endDateTime, $lieu, $image_url, $association_id, $idUser, $organizers, $tags);

			if ($newEventId) {
				$_SESSION['hasAssociation'] = true; // Mettre à jour l'état de l'utilisateur
			} else {
				$qs = "?view=create&error=eventcreation";
			}
			break;

		case 'deleteEvent':
			if (!valider("connecte", "SESSION")) {
				$qs = "?view=accueil&error=notconnected";
				break;
			}

			$eventId = valider("eventId");
			$idUser = valider("idUser", "SESSION");

			if (!$eventId) {
				$qs = "?view=accueil&error=eventidmissing";
				break;
			}

			if (!isAuthorOfEvent($idUser, $eventId) && !isUserInvolvedInEvent($idUser, $eventId, "orga")) {
				$qs = "?view=accueil&error=notauthorized";
				break;
			}

			deleteEvent($eventId);
			break;

		case 'toggle_involvement':
			if (!valider("connecte", "SESSION")) {
				$qs = "?view=accueil&error=notconnected";
				break;
			}

			$idUser = valider("idUser", "SESSION");
			$idEvent = valider("idEvent");
			$type = valider("type");
			$redirect_view = valider("redirect_view") ?: "accueil";

			if (empty($idEvent) || empty($newType) || !in_array($type, ['interested', 'participate'])) {
				$qs = "?view=$redirect_view&error=missinginvolvementdata";
				break;
			}

			toggleInvolvement($idUser, $idEvent, $type);
			$qs = "?view=$redirect_view&success=involvementtoggled";
			break;

		case 'updateEvent':
			$eventId = valider("eventId");
			$title = valider("title");
			$content = valider("content");
			$start = valider("start_time");
			$end = valider("end_time");
			$location = valider("location");
			$image = valider("image");

			if ($eventId && $title && $content) {
				updateEvent($eventId, $title, $content, $start, $end, $location, $image);
			}

			$qs = "?view=event&event=$eventId";
			break;

		case 'addOrganizer':
			if (!valider("connecte", "SESSION")) {
				$qs = "?view=accueil&error=notconnected";
				break;
			}

			$eventId = valider("eventId");
			$username = valider("username");

			if (!isAuthorOfEvent($idUser, $eventId) && !isUserInvolvedInEvent($idUser, $eventId, "orga")) {
				$qs = "?view=accueil&error=notauthorized";
				break;
			}

			if ($eventId && $username) {
				addOrganizerToEvent($eventId, $username);
			}

			$qs = "?view=event&event=$eventId";
			break;

		case 'removeOrganizer':
			if (!valider("connecte", "SESSION")) {
				$qs = "?view=accueil&error=notconnected";
				break;
			}

			$eventId = valider("eventId");
			$username = valider("username");

			if (!isAuthorOfEvent($idUser, $eventId) && !isUserInvolvedInEvent($idUser, $eventId, "orga")) {
				$qs = "?view=accueil&error=notauthorized";
				break;
			}

			if ($eventId && $username) {
				removeOrganizerFromEvent($eventId, $username);
			}

			$qs = "?view=event&event=$eventId";
			break;

	}
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER["PHP_SELF"]), "/\\");
$urlBase = "$protocol://$host$basePath/index.php";

header("Location:" . $urlBase . $qs);

ob_end_flush();

?>