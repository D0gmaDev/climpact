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