<?php

include_once("libs/modele.php");

?>

<div class="container">
    <h1>Fil d'Actualité DD & RS</h1>

    <!-- Filtres -->
    <form id="filterForm">
        <label for="tagSelect">Filtrer par tags :</label>
        <select name="tags[]" id="tagSelect" multiple>
            <?php foreach (getTags() as $tag): ?>
                <option value="<?= htmlspecialchars($tag['id']) ?>"><?= htmlspecialchars($tag['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" id="resetFilterBtn">Réinitialiser</button>
    </form>
    <div id="selectedTagsDisplay" style="margin-top: 10px;"></div>

    <!-- Résultats dynamiques -->
    <h2>Événements à venir</h2>
    <div id="eventContainer"></div>

    <h2>Historique des événements</h2>
    <div id="historyContainer"></div>
</div>

<link rel="stylesheet" href="css/accueil.css" />

<!-- jQuery et JS custom -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

    const currentUsername = <?= valider("connecte", "SESSION") ? json_encode($_SESSION["username"]) : 'null' ?>;

    $(function () {
        updateSelectedTags();
        fetchEvents(); // Chargement initial

        $("#tagSelect").change(function () {
            updateSelectedTags();
            fetchEvents();
        });

        $("#resetFilterBtn").click(function () {
            $("#tagSelect option").prop("selected", false);
            updateSelectedTags();
            fetchEvents();
        });

        function updateSelectedTags() {
            let selected = $("#tagSelect option:selected").map(function () {
                return $(this).text();
            }).get();

            if (selected.length > 0) {
                $("#selectedTagsDisplay").html("<strong>Tags sélectionnés :</strong> " + selected.join(", "));
            } else {
                $("#selectedTagsDisplay").empty();
            }
        }

        function fetchEvents() {
            let selectedTags = $("#tagSelect").val() || [];

            $.ajax({
                url: "api.php",
                method: "POST",
                data: {
                    request: "fetch_events",
                    tags: selectedTags
                },
                dataType: "json",
                success: function (response) {
                    renderEvents("#eventContainer", response.upcoming);
                    renderEvents("#historyContainer", response.history);
                },
                error: function (error) {
                    console.error("Erreur lors du chargement des événements :", error);
                }
            });
        }

        function renderEvents(containerId, events) {
            const $container = $(containerId);
            $container.empty();

            if (!events || events.length === 0) {
                $container.append("<p>Aucun événement trouvé.</p>");
                return;
            }


            events.forEach(event => {
                const isInterested = currentUsername && event.interested.includes(currentUsername);
                const isParticipating = currentUsername && event.participants.includes(currentUsername);

                // Applique les classes CSS en fonction de l'état
                const interestedClass = isInterested ? 'active' : '';
                const participatingClass = isParticipating ? 'active' : '';

                // Pour une meilleure sémantique et un meilleur ciblage CSS
                let tagsHtml = event.tagNames.length > 0 ?
                    `<div class="tags-list">` +
                    event.tagNames.map(tag => `<span>${tag}</span>`).join('') +
                    `</div>` : "";

                let html = `
<div class="event" data-event-id="${event.id}">
    <div class="event-author">
        <a href="index.php?view=user&username=${encodeURIComponent(event.author_username)}">
            <img src="${event.author_picture || 'media/default-avatar.png'}" alt="Photo de ${event.author_firstName}">
            <strong>${event.author_firstName} ${event.author_lastName}</strong>
        </a>
    </div>
    
    <h3>${event.title}</h3>
    ${tagsHtml}
    <p>${event.content}</p>
    
    <p><strong>Début :</strong> ${new Date(event.start_time).toLocaleString('fr-FR')}</p>
    <p><strong>Fin :</strong> ${new Date(event.end_time).toLocaleString('fr-FR')}</p>
    <p><strong>Lieu :</strong> ${event.location}</p>
    
    ${event.image ? `<img src="${event.image}" class="event-image" alt="Image de l'événement">` : ""}
`;

                if (currentUsername) {
                    // On utilise les classes 'active' pour gérer le style
                    html += `
    <div class="actions">
        <button class="btn-interesse ${interestedClass}" data-id="${event.id}">
            ${isInterested ? "Ne plus être intéressé" : "Intéressé"}
        </button>
        <button class="btn-participe ${participatingClass}" data-id="${event.id}">
            ${isParticipating ? "Ne plus participer" : "Je participe"}
        </button>
    </div>
`;
                }

                html += `</div>`;
                $container.append(html);
            });

            // MODIFICATION dans la gestion des clics pour utiliser les classes
            // Gestion "Intéressé"
            $(".btn-interesse").off("click").on("click", function () {
                const $btn = $(this);
                const eventId = $btn.data("id");

                $.post("api.php", { request: "toggle_interest", event_id: eventId }, function (res) {
                    const interested = res["new_interested"];
                    $btn.text(interested ? "Ne plus être intéressé" : "Intéressé");
                    $btn.toggleClass("active", interested); // On bascule la classe
                    // Plus besoin de gérer le style en JS !
                });
            });

            // Gestion "Je participe"
            $(".btn-participe").off("click").on("click", function () {
                const $btn = $(this);
                const eventId = $btn.data("id");

                $.post("api.php", { request: "toggle_participation", event_id: eventId }, function (res) {
                    const participate = res["new_participate"];
                    $btn.text(participate ? "Ne plus participer" : "Je participe");
                    $btn.toggleClass("active", participate); // On bascule la classe
                });
            });

            // Fonction utilitaire pour créer l'affichage des utilisateurs
            function createHoverInfo(users, max = 5) {
                if (!users || users.length === 0) {
                    return "<div class='hover-info-box'><em>Aucun</em></div>";
                }

                let html = "<div class='hover-info-box'>";
                users.slice(0, max).forEach(user => {
                    html += `<div>${user.firstName} ${user.lastName}</div>`;
                });
                if (users.length > max) {
                    html += `<div><em>et ${users.length - max} autres</em></div>`;
                }
                html += "</div>";
                return html;
            }

            $(".btn-participe").off("mouseenter mouseleave")
                .on("mouseenter", function () {
                    const $btn = $(this);
                    const eventId = $btn.data("id");

                    if ($btn.data("hover-loaded")) return;
                    $btn.data("hover-loaded", true);

                    $.post("api.php", { request: "get_participants", event_id: eventId }, function (res) {
                        console.log(res)
                        const html = createHoverInfo(res.participants);
                        const $info = $(html).css({
                            position: "absolute",
                            background: "#f0f0f0",
                            border: "1px solid #ccc",
                            padding: "5px",
                            fontSize: "0.9em",
                            zIndex: 1000,
                            display: "none"
                        });

                        $("body").append($info);
                        const offset = $btn.offset();
                        $info.css({ top: offset.top - $info.outerHeight() - 5, left: offset.left });
                        $info.fadeIn(100);

                        $btn.data("hover-box", $info);
                    });
                })
                .on("mouseleave", function () {
                    const $btn = $(this);
                    const $info = $btn.data("hover-box");
                    if ($info) {
                        $info.fadeOut(100, function () { $(this).remove(); });
                        $btn.removeData("hover-box").removeData("hover-loaded");
                    }
                });


            $(".btn-interesse").off("mouseenter mouseleave")
                .on("mouseenter", function () {
                    const $btn = $(this);
                    const eventId = $btn.data("id");

                    if ($btn.data("hover-loaded")) return;
                    $btn.data("hover-loaded", true);

                    $.post("api.php", { request: "get_interested", event_id: eventId }, function (res) {
                        const html = createHoverInfo(res.interested);
                        const $info = $(html).css({
                            position: "absolute",
                            background: "#f0f0f0",
                            border: "1px solid #ccc",
                            padding: "5px",
                            fontSize: "0.9em",
                            zIndex: 1000,
                            display: "none"
                        });

                        $("body").append($info);
                        const offset = $btn.offset();
                        $info.css({ top: offset.top - $info.outerHeight() - 5, left: offset.left });
                        $info.fadeIn(100);

                        $btn.data("hover-box", $info);
                    });
                })
                .on("mouseleave", function () {
                    const $btn = $(this);
                    const $info = $btn.data("hover-box");
                    if ($info) {
                        $info.fadeOut(100, function () { $(this).remove(); });
                        $btn.removeData("hover-box").removeData("hover-loaded");
                    }
                });
        }
    });
</script>