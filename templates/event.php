<?php

if (basename($_SERVER["PHP_SELF"]) != "index.php") {
    header("Location:../index.php?view=create");
    die("");
}

if (!valider("connecte", "SESSION")) {
    header("Location: controleur.php?action=login");
    die("");
}

require_once("libs/modele.php");

$associations = [];
$existingTags = [];
$errorMessage = null;

$userId = valider('idUser', 'SESSION');

$eventId = valider("event");
$event = getEvent($eventId);

if(!$event){
    header("Location: index.php?view=accueil");
    die("");
}

if($event["author"] != $userId && !isUserInvolvedInEvent($userId, $eventId, "orga")){
    header("Location: index.php?view=accueil");
    die("");
}
?>

<link rel="stylesheet" href="css/event.css">

<h2>Éditer l'événement</h2>

<form method="POST" action="controleur.php" class="event-edit-form">
    <input type="hidden" name="action" value="updateEvent">
    <input type="hidden" name="eventId" value="<?php echo $event['id']; ?>">

    <label for="title">Titre :</label>
    <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>

    <label for="content">Description :</label>
    <textarea name="content" id="content" required><?php echo htmlspecialchars($event['content']); ?></textarea>

    <label for="start_time">Début :</label>
    <input type="datetime-local" name="start_time" id="start_time" value="<?php echo date('Y-m-d\TH:i', strtotime($event['start_time'])); ?>">

    <label for="end_time">Fin :</label>
    <input type="datetime-local" name="end_time" id="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($event['end_time'])); ?>">

    <label for="location">Lieu :</label>
    <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($event['location']); ?>">

    <label for="image">Image (URL) :</label>
    <input type="text" name="image" id="image" value="<?php echo htmlspecialchars($event['image']); ?>">

    <button type="submit">Enregistrer les modifications</button>
</form>

<form method="POST" action="controleur.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.');" style="margin-bottom:2rem;">
    <input type="hidden" name="action" value="deleteEvent">
    <input type="hidden" name="eventId" value="<?php echo $event['id']; ?>">
    <button type="submit" class="danger-button">Supprimer l’événement</button>
</form>


<form method="POST" action="controleur.php" class="add-organizer-form">
    <input type="hidden" name="action" value="addOrganizer">
    <input type="hidden" name="eventId" value="<?php echo $event['id']; ?>">

    <label for="new_organizer">Ajouter un organisateur :</label>
    <input type="text" name="username" id="new_organizer" placeholder="Nom d'utilisateur">

    <button type="submit">Ajouter</button>
</form>

<h2>Organisateurs actuels</h3>
<ul class = "event-list">
<?php foreach ($event["organizers"] as $orga): ?>
    <li>
        <?php echo htmlspecialchars($orga); ?>
        <form method="POST" action="controleur.php" style="display:inline;">
            <input type="hidden" name="action" value="removeOrganizer">
            <input type="hidden" name="eventId" value="<?php echo $event['id']; ?>">
            <input type="hidden" name="username" value="<?php echo htmlspecialchars($orga); ?>">
            <button type="submit">❌</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

<h2>Participants</h3>
<ul class = "event-list">
<?php foreach ($event["participants"] as $participant): ?>
    <li><?php echo htmlspecialchars($participant); ?></li>
<?php endforeach; ?>
</ul>

<h2>Intéressés</h3>
<ul class = "event-list">
<?php foreach ($event["interested"] as $interested): ?>
    <li><?php echo htmlspecialchars($interested); ?></li>
<?php endforeach; ?>
</ul>
