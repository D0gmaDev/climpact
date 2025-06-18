<?php
session_start();

include_once "libs/maLibUtils.php";
include_once "libs/maLibSQL.pdo.php";
include_once "libs/maLibSecurisation.php";
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
	}
}

// On redirige toujours vers la page index, mais on ne connait pas le répertoire de base
// On l'extrait donc du chemin du script courant : $_SERVER["PHP_SELF"]
// Par exemple, si $_SERVER["PHP_SELF"] vaut /chat/data.php, dirname($_SERVER["PHP_SELF"]) contient /chat

$urlBase = dirname($_SERVER["PHP_SELF"]) . "index.php";
// On redirige vers la page index avec les bons arguments
//die($urlBase);
header("Location:" . $urlBase . $qs);
//qs doit contenir le symbole '?'

// On écrit seulement après cette entête
ob_end_flush();

?>