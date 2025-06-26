<?php

session_start();

include_once "libs/maLibUtils.php";
include_once "libs/maLibSQL.pdo.php";
include_once "libs/modele.php";

if (valider("connecte", "SESSION")) {
    header('Location: index.php');
    exit;
}

$ticket = $_GET['ticket'] ?? null;
if (!$ticket) {
    die("Ticket manquant.");
}

$url = "https://centralelilleassos.fr/authentification/climpact/" . urlencode($ticket);

$options = [
    'http' => [
        'ignore_errors' => true
    ]
];
$context = stream_context_create($options);
$response = file_get_contents($url, false, $context);
$data = json_decode($response, true);

if (!$data || !$data['success']) {
    die("Échec de l'authentification.");
}

// Information de l'utilisateur

$payload = $data['payload'];
$username = $payload['username'];
$firstName = $payload['firstName'];
$lastName = $payload['lastName'];
$email = $payload['emailSchool'];
$cursus = $payload['cursus'];

if ($user = getUserByUsername($username)) {
    $idUser = $user['id'];
    if ($user['cursus'] != $cursus)
        updateCursus($user['id'], $cursus);
} else {
    $idUser = insertUser($username, $firstName, $lastName, $email, $cursus);
}

// Ouvrir une session

$_SESSION['connecte'] = true;
$_SESSION['idUser'] = $idUser;

$_SESSION['token'] = getTokenById($idUser);
$_SESSION['isAdmin'] = isAdminById($idUser);
$_SESSION['hasAssociation'] = hasAssociation($idUser);

$_SESSION['username'] = $username;
$_SESSION['firstName'] = $firstName;
$_SESSION['lastName'] = $lastName;
$_SESSION['cursus'] = $cursus;

// Redirection post-login
header("Location: /index.php");
exit;

?>