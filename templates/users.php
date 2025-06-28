<?php

if (basename($_SERVER["PHP_SELF"]) != "index.php") {
	header("Location:../index.php?view=users");
	die("");
}

include_once("libs/modele.php");
include_once("libs/maLibUtils.php"); // tprint
include_once("libs/maLibForms.php"); // mkTable, mkSelect

?>

<link rel="stylesheet" type="text/css" href="css/users.css">

<h1>Liste des utilisateurs</h1>

<div class="user-list">
    <?php
    $users = getUsers();

    foreach ($users as $user) {
        $qs = "index.php?view=user&username=" . urlencode($user['username']);
        $fullName = htmlspecialchars($user['firstName'] . " " . $user['lastName']);
        $badges = getUserBadges($user['id']);
    ?>
        <div class="user-card">
            <a href="<?= $qs ?>" class="user-link">
                <?= $fullName ?>
                <?php foreach ($badges as $badge): ?>
                    <span class="badge"><?= htmlspecialchars($badge['emoji']) ?></span>
                <?php endforeach; ?>
            </a>
        </div>
    <?php } ?>
</div>
