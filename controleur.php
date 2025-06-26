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

			// Récupération des tableaux de tags et d'organisateurs
			$tags = valider("tags") ?: []; // Si rien n'est envoyé, on a un tableau vide
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
				$qs = "?view=event&id=" . $newEventId;
			} else {
				$qs = "?view=create&error=eventcreation";
			}
			break;

		case 'toggle_involvement':
			if (!valider("connecte", "SESSION")) {
				// Si l'utilisateur n'est pas connecté, on le redirige.
				// L'affichage des boutons sera géré côté vue (accueil.php ou event.php)
				// pour ne pas les montrer si l'utilisateur n'est pas connecté.
				$qs = "?view=accueil&error=notconnected"; // Ou la vue d'origine
				break;
			}

			$idUser = valider("idUser", "SESSION");
			$idEvent = valider("idEvent");
			$newType = valider("type"); // Le type d'implication souhaité ('interested' ou 'participate')
			$redirect_view = valider("redirect_view") ?: "accueil"; // Vue vers laquelle rediriger après l'action

			if (empty($idEvent) || empty($newType) || !in_array($newType, ['interested', 'participate'])) {
				$qs = "?view=$redirect_view&error=missinginvolvementdata";
				break;
			}

			// Récupérer l'implication actuelle de l'utilisateur pour cet événement (interested ou participate)
			$currentInvolvement = getInvolvementStatus($idUser, $idEvent);

			if ($currentInvolvement == $newType) {
				// L'utilisateur est déjà impliqué avec le type souhaité, on annule l'implication
				deleteInvolvement($idUser, $idEvent, $newType);
				$qs = "?view=$redirect_view&success=involvementremoved";
			} else {
				// L'utilisateur n'est pas impliqué avec ce type, ou est impliqué avec l'autre type mutuellement exclusif

				// 1. Si une implication existait (interested ou participate), la supprimer d'abord
				if ($currentInvolvement) {
					deleteInvolvement($idUser, $idEvent, $currentInvolvement);
				}

				// 2. Insérer la nouvelle implication
				insertInvolvement($idUser, $idEvent, $newType);
				$qs = "?view=$redirect_view&success=involvementadded"; // Ou updated si on a supprimé avant
			}
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
			$eventId = valider("eventId");
			$username = valider("username");

			if ($eventId && $username) {
				addOrganizerToEvent($eventId, $username);
			}

			$qs = "?view=event&event=$eventId";
			break;

		case 'removeOrganizer':
			$eventId = valider("eventId");
			$username = valider("username");

			if ($eventId && $username) {
				removeOrganizerFromEvent($eventId, $username);
			}

			$qs = "?view=event&event=$eventId";
			break;

	}
}

// On redirige toujours vers la page index, mais on ne connait pas le répertoire de base
// On l'extrait donc du chemin du script courant : $_SERVER["PHP_SELF"]
// Par exemple, si $_SERVER["PHP_SELF"] vaut /chat/data.php, dirname($_SERVER["PHP_SELF"]) contient /chat

$urlBase = dirname($_SERVER["PHP_SELF"]) . "/index.php";
// On redirige vers la page index avec les bons arguments
//die($urlBase);
header("Location:" . $urlBase . $qs);
//qs doit contenir le symbole '?'

// On écrit seulement après cette entête
ob_end_flush();

?>