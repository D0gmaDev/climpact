<?php

if (basename($_SERVER["PHP_SELF"]) != "index.php") {
	header("Location:../index.php?view=users");
	die("");
}

include_once("libs/modele.php");
include_once("libs/maLibUtils.php"); // tprint
include_once("libs/maLibForms.php"); // mkTable, mkSelect

?>

<h1>Administration du site</h1>

<h2>Liste des utilisateurs </h2>

<?php

$users = getUsers();
mkTable($users, array("id", "username", "first_name", "last_name", "cursus"));

?>