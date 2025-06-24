<?php

// Si la page est appelée directement par son adresse, on redirige en passant pas la page index
if (basename($_SERVER["PHP_SELF"]) != "index.php") {
	header("Location:../index.php");
	die("");
}

// On envoie l'entête Content-type correcte avec le bon charset
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>Climpact</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<!-- **** F I N **** H E A D **** -->


<!-- **** B O D Y **** -->

<body>

	<div id="banniere">

		<div id="logo">
			<img src="ressources/ec-lille-rect.png" />
		</div>

		<a href="index.php?view=accueil">Accueil</a>
		<a href="index.php?view=users">Utilisateurs</a>
		<a href="index.php?view=conversations">Conversations</a>

		<?php
		// Si l'utilisateur n'est pas connecte, on affiche un lien de connexion 
		if (!valider("connecte", "SESSION"))
			echo "<a href=\"controleur.php?action=login\">Se Connecter</a>";
		?>

		<h1 id="stitre"> Climpact </h1>

	</div>
	