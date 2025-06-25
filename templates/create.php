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
$associations = getUserAssociations($userId);


$existingTags = getTags();

$defaultAssociationId = valider("association");

?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="form-container">

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php else: ?>

        <form method="post" action="controleur.php">
            <input type="hidden" name="action" value="create_event">

            <div class="form-group">
                <label for="association_select">Association organisatrice</label>
                <select id="association_select" name="association_id" required style="width:100%;">
                    <option></option> <?php foreach ($associations as $asso): ?>
                        <option value="<?= htmlspecialchars($asso['id']) ?>" <?= ($asso['id'] == $defaultAssociationId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($asso['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="title" class="sr-only">Titre de l'événement</label>
                <input type="text" id="title" name="title" placeholder="Titre de l'événement" required>
            </div>

            <div class="form-group">
                <label for="content" class="sr-only">Description de l'événement</label>
                <textarea id="content" name="content" placeholder="Description de l'événement..." required></textarea>
            </div>

            <div class="date-time-grid">
                <div class="form-group">
                    <label for="start_date">Début de l'événement</label>
                    <div class="date-time-group">
                        <input type="date" id="start_date" name="start_date" required>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="end_date">Fin de l'événement</label>
                    <div class="date-time-group">
                        <input type="date" id="end_date" name="end_date" required>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="lieu" class="sr-only">Lieu</label>
                <input type="text" id="lieu" name="lieu" placeholder="Lieu de l'événement" required>
            </div>

            <div class="form-group">
                <label for="image_url" class="sr-only">URL de l'image</label>
                <input type="url" id="image_url" name="image_url" placeholder="Lien de l'image (https://...)">
            </div>

            <div class="form-group">
                <label for="tags_select">Tags associés à l'événement</label>
                <select id="tags_select" name="tags[]" multiple="multiple" style="width:100%;">
                    <?php foreach ($existingTags as $tag): ?>
                        <option value="<?= htmlspecialchars($tag['id']) ?>"><?= htmlspecialchars($tag['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="organizers_select">Ajouter des organisateurs</label>
                <select id="organizers_select" name="organizers[]" multiple="multiple" style="width:100%;"></select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-poster">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"
                        style="margin-right: 8px; vertical-align: middle;">
                        <path
                            d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576zm6.787-8.201L1.591 6.602l4.339 2.76z" />
                    </svg>
                    Poster l'événement
                </button>
            </div>

        </form>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function () {
        // --- INITIALISATION DES SÉLECTEURS SELECT2 ---

        // Initialisation pour les associations (sélection unique)
        $('#association_select').select2({
            placeholder: "Choisissez une association",
            allowClear: false // Requis, donc pas de possibilité de vider
        });

        // Initialisation pour les tags (sélection multiple)
        $('#tags_select').select2({
            placeholder: "Sélectionnez un ou plusieurs tags",
            allowClear: true
        });

        // Initialisation pour les organisateurs avec recherche AJAX
        $('#organizers_select').select2({
            placeholder: "Rechercher et ajouter des utilisateurs...",
            minimumInputLength: 2, // L'utilisateur doit taper au moins 2 caractères
            multiple: true,
            language: {
                inputTooShort: () => "Entrez au moins 2 caractères pour rechercher.",
                noResults: () => "Aucun utilisateur trouvé.",
                searching: () => "Recherche en cours..."
            },
            ajax: {
                url: 'api.php?request=users', // L'endpoint de votre API qui retourne les utilisateurs
                dataType: 'json',
                delay: 250, // Délai avant d'envoyer la requête après la frappe
                data: (params) => ({ search: params.term }), // Paramètre de recherche envoyé à l'API
                processResults: (data) => {
                    // Transforme la réponse de l'API au format attendu par Select2
                    const users = data.users || [];
                    return {
                        results: users.map(user => ({
                            id: user.id,
                            text: `${user.firstName} ${user.lastName}`
                        }))
                    };
                },
                cache: true // Met en cache les résultats pour éviter les appels répétés
            }
        });

        // --- LOGIQUE ADDITIONNELLE DU FORMULAIRE ---

        /**
         * Pré-remplit automatiquement la date et l'heure de fin
         * en se basant sur la date/heure de début + 2 heures.
         */
        $('#start_date, #start_time').on('change', function () {
            const startDateValue = $('#start_date').val();
            const startTimeValue = $('#start_time').val();

            // On ne procède que si la date et l'heure de début sont remplies
            if (startDateValue && startTimeValue) {
                const startDateTime = new Date(`${startDateValue}T${startTimeValue}`);

                // Vérifie si la date créée est valide
                if (!isNaN(startDateTime.getTime())) {
                    // Ajoute 2 heures à la date de début
                    startDateTime.setHours(startDateTime.getHours() + 2);

                    // Formate la nouvelle date et heure au format YYYY-MM-DD et HH:MM
                    const endDate = startDateTime.toISOString().slice(0, 10);
                    const endTime = startDateTime.toTimeString().slice(0, 5);

                    // Met à jour les champs de fin
                    $('#end_date').val(endDate);
                    $('#end_time').val(endTime);
                }
            }
        });
    });
</script>

<style>
/*
================================================================
| STYLES POUR LES FORMULAIRES (EX: PAGE CRÉATION D'ÉVÉNEMENT)  |
================================================================
*/

/* --- Réinitialisation et règle d'or du layout --- */
*,
*::before,
*::after {
    box-sizing: border-box;
}

/* --- Conteneur principal du formulaire --- */
.form-container {
    /* On utilise la largeur disponible, avec un padding pour l'aération */
    width: 100%; 
    background-color: #ffffff;
    padding: 2rem 2.5rem;
    border-radius: 12px;
    border: 1px solid var(--border-grey);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}

/* --- Groupes de champs et labels --- */
.form-group {
    margin-bottom: 1.75rem; /* On augmente un peu l'espace */
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-color);
    margin-bottom: 0.6rem;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* --- Style unifié pour les champs de texte, etc. --- */
input[type="text"],
input[type="url"],
input[type="date"],
input[type="time"],
textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    font-family: inherit; /* Hérite de la police du body */
    border: 1px solid var(--border-grey);
    border-radius: 8px;
    background-color: var(--light-grey);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

input[type="text"]:focus,
input[type="url"]:focus,
input[type="date"]:focus,
input[type="time"]:focus,
textarea:focus {
    outline: none;
    border-color: var(--primary-color); /* Vert Climpact au focus */
    background-color: #fff;
    box-shadow: 0 0 0 3px rgba(45, 140, 77, 0.2); /* Ombre verte transparente */
}

textarea {
    resize: vertical;
    min-height: 120px;
}

::placeholder {
    color: #999;
    opacity: 1;
}

/* --- Grille pour les dates --- */
.date-time-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.date-time-group {
    display: flex;
    gap: 0.5rem;
}

/* --- Bouton de soumission --- */
.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 2rem;
    border-top: 1px solid var(--border-grey);
    padding-top: 1.5rem;
}

/* On réutilise les classes de bouton existantes pour la cohérence */
/* Assurez-vous que votre bouton dans le HTML a bien la classe "btn btn-primary" */
/* Si vous gardez .btn-poster, ce style s'appliquera : */
.btn-poster {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    background-color: var(--primary-color);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.2s ease;
}

.btn-poster:hover {
    background-color: #226d3d; /* Version plus sombre du vert */
    transform: translateY(-2px);
}

/* ================================================================
| HARMONISATION DE SELECT2 AVEC LE THÈME CLIMPACT               |
================================================================
*/

/* --- Style général des conteneurs Select2 --- */
.select2-container--default .select2-selection--single,
.select2-container--default .select2-selection--multiple {
    border: 1px solid var(--border-grey);
    border-radius: 8px;
    background-color: var(--light-grey);
    font-family: inherit;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.select2-container--default.select2-container--focus .select2-selection--multiple,
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(45, 140, 77, 0.2);
}

/* Style pour la sélection unique (Association) */
.select2-container--default .select2-selection--single {
    height: auto;
    padding: 0.75rem 1rem;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
    padding-left: 0;
    color: var(--text-color);
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100%;
    top: 0;
    right: 0.5rem;
}

/* Style pour la sélection multiple (Tags, Organisateurs) */
.select2-container--default .select2-selection--multiple {
    padding: 0.3rem 0.5rem 0;
    cursor: text;
}
.select2-container--default .select2-selection--multiple .select2-search__field {
    padding: 0.4rem 0.5rem;
    margin-top: 0;
    width: 100% !important;
}

/* Style des "pilules" (tags sélectionnés) */
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: var(--primary-color);
    border: 1px solid #226d3d;
    color: white;
    border-radius: 6px;
    margin: 4px !important;
    
    /* --- AJOUTS POUR CORRIGER LA SUPERPOSITION --- */
    display: inline-flex;       /* On utilise flex pour aligner le texte et la croix */
    align-items: center;       /* On centre verticalement les éléments */
    padding: 5px 6px 5px 10px; /* On ajuste le padding (plus à gauche, moins à droite) */
}

/* --- ✨ CORRECTION DU BUG DE LA CROIX ✨ --- */
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    margin-left: 5px; /* On met la marge à gauche de la croix */
    border: none !important; /* On supprime toute bordure qui pourrait causer le trait vertical */
    background-color: transparent !important; /* On assure qu'il n'y a pas de fond */
    transition: color 0.2s;

    margin-left: 6px;  /* Espace entre le texte et la croix */
    padding: 0 4px;     /* Ajoute une zone de clic plus confortable */
    order: 2;           /* S'assure que la croix est bien l'élément de droite */
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: white;
}

/* Style de la liste déroulante qui s'ouvre */
.select2-dropdown {
    border: 1px solid var(--border-grey);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    background-color: #fff;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--primary-color);
    color: white;
}

.select2-results__option[aria-selected=true] {
    background-color: var(--active-bg); /* Fond vert clair pour un élément déjà sélectionné */
    color: var(--primary-color);
    font-weight: 600;
}

/* --- Styles pour les écrans plus petits --- */
@media (max-width: 768px) {
    .form-container {
        padding: 1.5rem;
    }
    .date-time-grid {
        grid-template-columns: 1fr;
    }
    .form-actions {
        justify-content: center;
    }
    .btn-poster {
        width: 100%;
    }
}
</style>