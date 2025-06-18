<?php

// Si la page est appelée directement par son adresse, on redirige en passant pas la page index
if (basename($_SERVER["PHP_SELF"]) != "index.php") {
	header("Location:../index.php");
	die("");
}

?>

<div id="pied">

	<?php
	// Si l'utilisateur est connecte, on affiche un lien de deconnexion 
	if (valider("connecte", "SESSION")) {
		echo "Utilisateur <b>$_SESSION[username]</b> connecté  &nbsp; ";
		echo "<a href=\"controleur.php?action=logout\">Se Déconnecter</a>";
	}
	?>
</div>

</body>

</html>