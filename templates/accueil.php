<?php
if (basename($_SERVER["PHP_SELF"]) != "index.php") {
    header("Location:../index.php?view=accueil");
    die("");
}

include_once("libs/modele.php");

// Récupérer tous les tags disponibles pour le filtre
$allTags = getTags();

// Récupérer les tags sélectionnés depuis l'URL (ex: ?view=accueil&tags[]=1&tags[]=3)
$selectedTagIds = valider("tags") ?: [];
// Assurez-vous que $selectedTagIds est un tableau d'entiers
if (!is_array($selectedTagIds)) {
    $selectedTagIds = [];
}
$selectedTagIds = array_map('intval', $selectedTagIds);
$selectedTagIds = array_filter($selectedTagIds); // Supprime les 0 si la conversion a échoué

// Filtrer les événements si des tags sont sélectionnés - evenements à venir
$events = getEvents(10, true, $selectedTagIds);
// Filtrer les événements si des tags sont sélectionnés - tous les evenements
$eventsHistory = getEvents(10, false, $selectedTagIds);

// Définir les noms des mois en français (déplacé ici pour être disponible dans le script)
$mois_fr = [
    1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
    'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
];

// Fonction pour formater les dates (si elle n'est pas déjà dans maLibUtils ou autre)
// Si cette fonction n'existe pas, vous devrez l'ajouter dans un fichier inclus (par exemple maLibUtils.php)
// ou directement ici pour les tests.
if (!function_exists('format_date_heure')) {
    function format_date_heure($datetime_str, $mois_fr) {
        $timestamp = strtotime($datetime_str);
        if (!$timestamp) {
            return htmlspecialchars($datetime_str); // Retourne la chaîne originale si le format est invalide
        }
        $jour = date('d', $timestamp);
        $mois_num = date('n', $timestamp);
        $annee = date('Y', $timestamp);
        $heure = date('H', $timestamp);
        $minute = date('i', $timestamp);
        return sprintf("%d %s %d à %s:%s", $jour, $mois_fr[$mois_num], $annee, $heure, $minute);
    }
}
?>

<div id="corps">
    <h1>Accueil</h1>
    <p>Bienvenue sur notre site CLimpact</p>

    <h2>Filtrer par tags</h2>
    <div class="tag-filter-container">
        <select id="tagSelect" multiple="multiple" style="width: 100%;">
            <?php foreach ($allTags as $tag): ?>
                <option value="<?= htmlspecialchars($tag['id']) ?>"
                    <?= in_array($tag['id'], $selectedTagIds) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tag['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div id="selectedTagsDisplay" style="margin-top: 10px;">
        </div>
        <button id="resetFilterBtn" style="margin-top: 10px; margin-left: 0px;">Réinitialiser</button>
    </div>

    <h2>Événements à venir</h2>

    <?php if (empty($events)): ?>
        <p>Aucun événement à venir pour le moment, ou aucun événement ne correspond aux critères de filtre.</p>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="event" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
                
            <?php
                $author_username = htmlspecialchars($event['author_username'] ?? 'Inconnu');
                $author_firstName = htmlspecialchars($event['author_firstName'] ?? ''); // Ajouté
                $author_lastName = htmlspecialchars($event['author_lastName'] ?? '');   // Ajouté
                $author_display_name = trim($author_firstName . ' ' . $author_lastName); // Ajouté
                if (empty($author_display_name)) { // Ajouté
                    $author_display_name = $author_username; // Fallback au pseudo si nom/prénom vides
                }
                $author_picture = htmlspecialchars($event['author_picture'] ?? '');
                $default_avatar = 'media/default-avatar.png'; // Chemin vers votre avatar par défaut
                $avatar_src = !empty($author_picture) ? $author_picture : $default_avatar;
                ?>
                <div class="event-author" style="display: flex; align-items: center; margin-bottom: 10px;">
                    <a href="index.php?view=user&username=<?= urlencode($author_username) ?>" style="text-decoration: none; color: inherit;">
                        <img src="<?= $avatar_src ?>" alt="Photo de profil de <?= $author_display_name ?>" 
                             style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; object-fit: cover; vertical-align: middle;">
                        <strong style="vertical-align: middle;"><?= $author_display_name ?></strong> </a>
                </div>

            <h3><?= htmlspecialchars($event['title']) ?></h3>

                <?php if (!empty($event['tagNames'])): ?>
                    <p>
                        <strong>Tags:</strong>
                        <?= implode(', ', array_map('htmlspecialchars', $event['tagNames'])) ?>
                    </p>
                <?php endif; ?>

                <p><?= nl2br(htmlspecialchars($event['content'])) ?></p>
                <p><strong>Début :</strong> <?= format_date_heure($event['start_time'], $mois_fr) ?></p>
                <p><strong>Fin :</strong> <?= format_date_heure($event['end_time'], $mois_fr) ?></p>
                <p><strong>Lieu :</strong> <?= htmlspecialchars($event['location']) ?></p>

                <?php if (!empty($event['image'])): ?>
                    <img src="<?= htmlspecialchars($event['image']) ?>" alt="Image événement" style="max-width: 200px; height: auto; display: block; margin-top: 10px;">
                <?php endif; ?>

                <?php if (valider("connecte", "SESSION")): ?>
                    <?php
                        $idUser = valider("idUser", "SESSION");
                        $idEvent = $event['id'];
                        $currentInvolvement = getInvolvementStatus($idUser, $idEvent);
                    ?>

                    <?php if ($currentInvolvement == 'participate'): ?>
                        <a href="controleur.php?action=toggle_involvement&idEvent=<?= $idEvent ?>&type=participate&redirect_view=accueil" class="button">Je ne participe plus</a>
                        <?php else: // Pas 'participate' ?>
                        <a href="controleur.php?action=toggle_involvement&idEvent=<?= $idEvent ?>&type=participate&redirect_view=accueil" class="button">Je participe</a>

                        <?php if ($currentInvolvement == 'interested'): ?>
                            <a href="controleur.php?action=toggle_involvement&idEvent=<?= $idEvent ?>&type=interested&redirect_view=accueil" class="button">Je ne suis plus intéressé</a>
                        <?php else: // Pas 'participate' et pas 'interested' ?>
                            <a href="controleur.php?action=toggle_involvement&idEvent=<?= $idEvent ?>&type=interested&redirect_view=accueil" class="button">Je suis intéressé</a>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php else: ?>
                    <p>Connectez-vous pour vous impliquer dans cet événement.</p>
                <?php endif; ?>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Historique des événements</h2>

    <?php if (empty($eventsHistory)): ?>
        <p>Aucun événement passé à afficher pour le moment, ou aucun événement ne correspond aux critères de filtre.</p>
    <?php else: ?>
        <?php foreach ($eventsHistory as $event): ?>
            <div class="event" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
                
                <?php
                $author_username = htmlspecialchars($event['author_username'] ?? 'Inconnu');
                $author_firstName = htmlspecialchars($event['author_firstName'] ?? ''); // Ajouté
                $author_lastName = htmlspecialchars($event['author_lastName'] ?? '');   // Ajouté
                $author_display_name = trim($author_firstName . ' ' . $author_lastName); // Ajouté
                if (empty($author_display_name)) { // Ajouté
                    $author_display_name = $author_username; // Fallback au pseudo si nom/prénom vides
                }
                $author_picture = htmlspecialchars($event['author_picture'] ?? '');
                $default_avatar = 'media/default-avatar.png'; // Chemin vers votre avatar par défaut
                $avatar_src = !empty($author_picture) ? $author_picture : $default_avatar;
                ?>
                <div class="event-author" style="display: flex; align-items: center; margin-bottom: 10px;">
                    <a href="index.php?view=user&username=<?= urlencode($author_username) ?>" style="text-decoration: none; color: inherit;">
                        <img src="<?= $avatar_src ?>" alt="Photo de profil de <?= $author_display_name ?>" 
                             style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; object-fit: cover; vertical-align: middle;">
                        <strong style="vertical-align: middle;"><?= $author_display_name ?></strong> </a>
                </div>
            
                <h3><?= htmlspecialchars($event['title']) ?></h3>

                <?php if (!empty($event['tagNames'])): ?>
                    <p>
                        <strong>Tags:</strong>
                        <?= implode(', ', array_map('htmlspecialchars', $event['tagNames'])) ?>
                    </p>
                <?php endif; ?>

                <p><?= nl2br(htmlspecialchars($event['content'])) ?></p>
                <p><strong>Début :</strong> <?= format_date_heure($event['start_time'], $mois_fr) ?></p>
                <p><strong>Fin :</strong> <?= format_date_heure($event['end_time'], $mois_fr) ?></p>
                <p><strong>Lieu :</strong> <?= htmlspecialchars($event['location']) ?></p>

                <?php if (!empty($event['image'])): ?>
                    <img src="<?= htmlspecialchars($event['image']) ?>" alt="Image événement" style="max-width: 200px; height: auto; display: block; margin-top: 10px;">
                <?php endif; ?>

                <?php if (valider("connecte", "SESSION")): ?>
                    <?php
                        $idUser = valider("idUser", "SESSION");
                        $idEvent = $event['id'];
                        $currentInvolvement = getInvolvementStatus($idUser, $idEvent);
                    ?>

                    <?php if ($currentInvolvement == 'participate'): ?>
                        <a href="controleur.php?action=toggle_involvement&idEvent=<?= $idEvent ?>&type=participate&redirect_view=accueil" class="button">Je ne participe plus</a>
                        <?php else: // Pas 'participate' ?>
                        <a href="controleur.php?action=toggle_involvement&idEvent=<?= $idEvent ?>&type=participate&redirect_view=accueil" class="button">Je participe</a>

                        <?php if ($currentInvolvement == 'interested'): ?>
                            <a href="controleur.php?action=toggle_involvement&idEvent=<?= $idEvent ?>&type=interested&redirect_view=accueil" class="button">Je ne suis plus intéressé</a>
                        <?php else: // Pas 'participate' et pas 'interested' ?>
                            <a href="controleur.php?action=toggle_involvement&idEvent=<?= $idEvent ?>&type=interested&redirect_view=accueil" class="button">Je suis intéressé</a>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php else: ?>
                    <p>Connectez-vous pour vous impliquer dans cet événement.</p>
                <?php endif; ?>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tagSelect').select2({
            placeholder: "Sélectionnez des tags",
            allowClear: true
        });

        function updateSelectedTagsDisplay() {
            var selectedTags = $('#tagSelect').select2('data');
            var displayDiv = $('#selectedTagsDisplay');
            displayDiv.empty();
            if (selectedTags.length > 0) {
                selectedTags.forEach(function(tag) {
                    displayDiv.append(
                        '<span class="selected-tag" data-tag-id="' + tag.id + '">' +
                        htmlspecialchars(tag.text) +
                        ' <span class="remove-tag">x</span></span>'
                    );
                });
            }
        }

        function applyFilters() {
            var selectedTagIds = $('#tagSelect').val();
            var newUrl = window.location.origin + window.location.pathname + '?view=accueil';
            if (selectedTagIds && selectedTagIds.length > 0) {
                selectedTagIds.forEach(function(tagId) {
                    newUrl += '&tags[]=' + tagId;
                });
            }
            window.location.href = newUrl;
        }

        // Initialiser l'affichage des tags sélectionnés au chargement de la page
        updateSelectedTagsDisplay();

        // Gérer la sélection/désélection des tags dans le sélecteur (APPLIQUE LE FILTRE IMMEDIATEMENT)
        $('#tagSelect').on('change', function() {
            updateSelectedTagsDisplay(); // Met à jour les badges affichés
            applyFilters(); // Applique le filtre immédiatement
        });

        // Gérer la désélection d'un tag en cliquant sur le badge (APPLIQUE LE FILTRE IMMEDIATEMENT)
        $(document).on('click', '.selected-tag', function() {
            var tagIdToRemove = $(this).data('tag-id');
            // Désélectionner l'option dans le <select>
            $('#tagSelect option[value=\"' + tagIdToRemove + '\"]').prop('selected', false);
            // Mettre à jour l'affichage
            updateSelectedTagsDisplay();
            // Appliquer le filtre après désélection
            applyFilters();
        });

        // Gérer le clic sur le bouton "Réinitialiser"
        $('#resetFilterBtn').on('click', function() {
            window.location.href = window.location.origin + window.location.pathname + '?view=accueil';
        });

        // Fonction d'échappement HTML pour le JS
        function htmlspecialchars(str) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    });
</script>

<style>
    .selected-tag {
        display: inline-block;
        background-color: #e0e0e0;
        border-radius: 5px;
        padding: 5px 10px;
        margin-right: 5px;
        margin-bottom: 5px;
        cursor: pointer;
    }
    .remove-tag {
        margin-left: 5px;
        color: #888;
    }
    .button {
        display: inline-block;
        padding: 8px 15px;
        margin-top: 10px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin-right: 5px; /* Pour espacer les boutons */
    }
    .button:hover {
        background-color: #0056b3;
    }
</style>