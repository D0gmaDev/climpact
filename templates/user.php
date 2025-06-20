<?php

if (basename($_SERVER["PHP_SELF"]) != "index.php") {
	header("Location:../index.php?view=user");
	die("");
}

include_once("libs/modele.php");
include_once("libs/maLibUtils.php"); // tprint
include_once("libs/maLibForms.php"); // mkTable, mkSelect

?>

<h1>Utilisateur</h1>

<?php

$username = valider("username");

if(!$username) {
	echo "<p>Veuillez spécifier un nom d'utilisateur.</p>";
	exit;
}

$user = getUserByUsername($username);

if (!$user) {
	echo "<p>Aucun utilisateur trouvé avec le nom d'utilisateur : $username</p>";
	exit;
}

tprint($user);

echo "<h2>Détails de l'utilisateur : $username</h2>";
echo "<p>ID : " . $user['id'] . "</p>";
echo "<p>Nom : " . $user['firstName'] . " " . $user['lastName'] . "</p>";
echo "<p>Cursus : " . $user['cursus'] . "</p>";

?>