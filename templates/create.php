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
    /* --- IMPORTATION DE LA POLICE --- */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    /* --- STYLES GÉNÉRAUX ET RESET --- */
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #f4f7f9;
        /* Fond gris clair pour la page */
        color: #333;
        line-height: 1.6;
        padding: 2rem 1rem;
    }

    /* --- CONTENEUR PRINCIPAL DU FORMULAIRE --- */
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        background-color: #ffffff;
        padding: 2.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(14, 21, 47, 0.08);
    }

    /* --- GROUPES DE CHAMPS ET LABELS --- */
    .form-group {
        margin-bottom: 1.5rem;
    }

    label {
        display: block;
        font-weight: 600;
        font-size: 0.9rem;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    /* Classe pour les labels destinés uniquement aux lecteurs d'écran */
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


    /* --- STYLE UNIFIÉ POUR LES CHAMPS DE TEXTE, URL, DATE, ETC. --- */
    input[type="text"],
    input[type="url"],
    input[type="date"],
    input[type="time"],
    textarea {
        width: 100%;
        padding: 0.8rem 1rem;
        font-size: 1rem;
        font-family: 'Inter', sans-serif;
        border: 1px solid #ced4da;
        border-radius: 8px;
        background-color: #f8f9fa;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    input[type="text"]:focus,
    input[type="url"]:focus,
    input[type="date"]:focus,
    input[type="time"]:focus,
    textarea:focus {
        outline: none;
        border-color: #007bff;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
    }

    textarea {
        resize: vertical;
        min-height: 120px;
    }

    ::placeholder {
        color: #868e96;
        opacity: 1;
    }

    /* --- GRILLE POUR LES DATES (DÉBUT/FIN) --- */
    .date-time-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .date-time-group {
        display: flex;
        gap: 0.5rem;
    }

    /* --- BOUTON DE SOUMISSION --- */
    .form-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 2rem;
    }

    .btn-poster {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.8rem 1.8rem;
        background: linear-gradient(45deg, #007bff, #0056b3);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
    }

    .btn-poster:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
    }

    .btn-poster svg {
        margin-right: 0.5rem;
    }

    /* --- STYLES PERSONNALISÉS POUR SELECT2 --- */

    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        border-radius: 8px;
        background-color: #f8f9fa;
        font-family: 'Inter', sans-serif;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    /* Conteneur single-select (Association) - Pas de changement ici */
    .select2-container--default .select2-selection--single {
        height: calc(1.6em + 1.6rem + 2px);
        padding: 0.8rem 1rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.6;
        padding-left: 0;
        color: #495057;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.6em + 1.6rem);
        right: 0.5rem;
    }

    /* * CORRECTION POUR LES CONTENEURS MULTI-SELECT (Tags, Organisateurs)
 */
    .select2-container--default .select2-selection--multiple {
        /* On supprime la hauteur minimale fixe (min-height) pour laisser le conteneur s'adapter. */
        /* On applique un padding qui fonctionnera bien quand des éléments sont ajoutés. */
        padding: 0.4rem 0.5rem 0;
        cursor: text;
    }

    /* C'est la clé : On cible le champ de recherche interne pour contrôler la hauteur "vide" */
    .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
        /* On lui donne le padding vertical pour simuler la hauteur d'un input normal */
        padding: 0.45rem 0.5rem;
        margin-top: 0;
        /* On reset la marge pour un meilleur contrôle */
        width: 100% !important;
        /* S'assure qu'il prend la largeur disponible */
    }

    /* Style des "pilules" (tags sélectionnés) */
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff;
        border-color: #006fe6;
        color: white;
        border-radius: 5px;
        padding: 5px 8px;
        margin: 4px !important;
        /* Marge uniforme pour un espacement propre */
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.1rem;
        margin-right: 3px;
        transition: color 0.2s;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: white;
    }


    /* Style du conteneur en état de focus */
    .select2-container--default.select2-container--open .select2-selection,
    .select2-container--default .select2-selection:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
    }

    /* Style de la liste déroulante (identique) */
    .select2-dropdown {
        border: 1px solid #ced4da;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        /* Assure que les coins arrondis s'appliquent bien */
    }

    .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff;
    }

    /* --- ALERTES (POUR LES ERREURS) --- */
    .alert.alert-danger {
        padding: 1rem;
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }


    /* --- RESPONSIVE DESIGN --- */
    @media (max-width: 768px) {
        .form-container {
            padding: 1.5rem;
        }

        /* La grille des dates passe sur une seule colonne */
        .date-time-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
            /* Le gap vertical reste le même que celui des form-group */
        }

        .form-actions {
            justify-content: center;
            /* Centre le bouton sur mobile */
        }

        .btn-poster {
            width: 100%;
        }
    }
</style>