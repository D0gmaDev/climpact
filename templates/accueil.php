<div id="corps">
    <h1>Accueil</h1>
    <p>Bienvenue sur notre site CLimpact</p>

    <?php
    // Définir les noms des mois en français
    $mois_fr = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ];
    ?>

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

    <h2>Evénements à venir</h2>

    <?php if (empty($events)): ?>
        <p>Aucun événement à afficher pour le moment, ou aucun événement ne correspond aux critères de filtre.</p>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="event" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
                <h3><?= htmlspecialchars($event['title']) ?></h3>

                <?php if (!empty($event['tagNames'])): ?>
                    <p>
                        <strong>Tags :</strong>
                        <?php foreach ($event['tagNames'] as $tagName): ?>
                            <span class="event-tag" style="background-color: #e0e0e0; padding: 3px 8px; border-radius: 5px; margin-right: 5px; display: inline-block;"><?= htmlspecialchars($tagName) ?></span>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <p><?= nl2br(htmlspecialchars($event['content'])) ?></p>
                <p>
                    <strong>Début :</strong> le 
                    <?= date('d', strtotime($event['start_time'])) ?> 
                    <?= $mois_fr[date('n', strtotime($event['start_time']))] ?> 
                    <?= date('Y', strtotime($event['start_time'])) ?> à 
                    <?= date('H:i', strtotime($event['start_time'])) ?>
                </p>
                <p>
                    <strong>Fin :</strong> le 
                    <?= date('d', strtotime($event['end_time'])) ?> 
                    <?= $mois_fr[date('n', strtotime($event['end_time']))] ?> 
                    <?= date('Y', strtotime($event['end_time'])) ?> à 
                    <?= date('H:i', strtotime($event['end_time'])) ?>
                </p>
                <p><strong>Lieu :</strong> <?= htmlspecialchars($event['location']) ?></p>
                <?php if (!empty($event['image'])): ?>
                    <img src="<?= htmlspecialchars($event['image']) ?>" alt="Image événement" style="max-width: 200px; height: auto; display: block; margin-top: 10px;">
                <?php endif; ?>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Historique des événements</h2>

    <?php if (empty($events)): ?>
        <p>Aucun événement à afficher pour le moment, ou aucun événement ne correspond aux critères de filtre.</p>
    <?php else: ?>
        <?php foreach ($eventsHistory as $event): ?>
            <div class="event" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
                <h3><?= htmlspecialchars($event['title']) ?></h3>

                <?php if (!empty($event['tagNames'])): ?>
                    <p>
                        <strong>Tags :</strong>
                        <?php foreach ($event['tagNames'] as $tagName): ?>
                            <span class="event-tag" style="background-color: #e0e0e0; padding: 3px 8px; border-radius: 5px; margin-right: 5px; display: inline-block;"><?= htmlspecialchars($tagName) ?></span>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <p><?= nl2br(htmlspecialchars($event['content'])) ?></p>
                <p>
                    <strong>Début :</strong> le 
                    <?= date('d', strtotime($event['start_time'])) ?> 
                    <?= $mois_fr[date('n', strtotime($event['start_time']))] ?> 
                    <?= date('Y', strtotime($event['start_time'])) ?> à 
                    <?= date('H:i', strtotime($event['start_time'])) ?>
                </p>
                <p>
                    <strong>Fin :</strong> le 
                    <?= date('d', strtotime($event['end_time'])) ?> 
                    <?= $mois_fr[date('n', strtotime($event['end_time']))] ?> 
                    <?= date('Y', strtotime($event['end_time'])) ?> à 
                    <?= date('H:i', strtotime($event['end_time'])) ?>
                </p>
                <p><strong>Lieu :</strong> <?= htmlspecialchars($event['location']) ?></p>
                <?php if (!empty($event['image'])): ?>
                    <img src="<?= htmlspecialchars($event['image']) ?>" alt="Image événement" style="max-width: 200px; height: auto; display: block; margin-top: 10px;">
                <?php endif; ?>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Fonction pour mettre à jour l'affichage des tags sélectionnés
    function updateSelectedTagsDisplay() {
        $('#selectedTagsDisplay').empty();
        $('#tagSelect option:selected').each(function() {
            var tagId = $(this).val();
            var tagName = $(this).text();
            $('#selectedTagsDisplay').append(
                '<span class="selected-tag" data-tag-id="' + tagId + '" ' +
                'style="background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; ' +
                'padding: 5px 10px; border-radius: 5px; margin: 3px; cursor: pointer; display: inline-block;">' +
                htmlspecialchars(tagName) + ' <span style="font-weight: bold;">x</span></span>'
            );
        });
    }

    // Fonction pour mettre à jour l'URL et recharger la page avec les filtres
    function applyFilters() {
        var selectedTags = $('#tagSelect').val(); // Récupère un tableau des valeurs sélectionnées
        var baseUrl = window.location.origin + window.location.pathname;
        var newUrl = baseUrl + '?view=accueil';

        if (selectedTags && selectedTags.length > 0) {
            selectedTags.forEach(function(tagId) {
                newUrl += '&tags[]=' + encodeURIComponent(tagId);
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
        $('#tagSelect option[value="' + tagIdToRemove + '"]').prop('selected', false);
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