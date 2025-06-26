<?php

session_start();

include_once "libs/maLibUtils.php";
include_once "libs/maLibSQL.pdo.php";
include_once "libs/modele.php";

if (valider("connecte", "SESSION")) {
    header('Location: index.php');
    exit;
}

$idUser = $_GET['user'] ?? null;
if (!$idUser) {
    die("user manquant.");
}

if ($user = getUserById($idUser)) {

    // Ouvrir une session

    $_SESSION['connecte'] = true;
    $_SESSION['idUser'] = $idUser;

    $_SESSION['token'] = getTokenById($idUser);
    $_SESSION['isAdmin'] = isAdminById($idUser);

    $_SESSION['username'] = $user['username'];
    $_SESSION['firstName'] = $user['firstName'];
    $_SESSION['lastName'] = $user['lastName'];
    $_SESSION['cursus'] =   $user['cursus'];

    // Redirection post-login
    header("Location: /index.php");
    exit;

} else {
    die("L'utilisateur n'existe pas.");
}
?>