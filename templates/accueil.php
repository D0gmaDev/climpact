<?php

if (basename($_SERVER["PHP_SELF"]) != "index.php") {
	header("Location:../index.php?view=accueil");
	die("");
}

?>

<div id="corps">

	<h1>Accueil</h1>

	Bienvenue sur notre site DDRS

</div>