<?php

include_once("libs/modele.php");

?>

<div class="container">
    <h1>Fil d'Actualité DD & RS</h1>

    <div id="tagButtons">
        <?php foreach (getTags() as $tag): ?>
            <button class="tag-toggle-btn" data-id="<?= htmlspecialchars($tag['id']) ?>">
                <?= htmlspecialchars($tag['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <h2>Événements à venir</h2>
    <div id="eventContainer"></div>

    <h2>Historique des événements</h2>
    <div id="historyContainer"></div>
</div>

<link rel="stylesheet" href="css/accueil.css" />

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

    const currentUsername = <?= valider("connecte", "SESSION") ? json_encode($_SESSION["username"]) : 'null' ?>;

    $(function () {

        let selectedTags = [];

        $("#tagButtons").on("click", ".tag-toggle-btn", function () {
            const id = $(this).data("id").toString();
            $(this).toggleClass("active");

            if ($(this).hasClass("active")) {
                selectedTags.push(id);
            } else {
                selectedTags = selectedTags.filter(t => t !== id);
            }

            fetchEvents();
        });

        fetchEvents();

        function fetchEvents() {
            console.log("Fetching events with tags:", selectedTags);
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
                    renderEvents("#historyContainer", response.history, true);
                },
                error: function (error) {
                    console.error("Erreur lors du chargement des événements :", error);
                }
            });
        }

        function renderEvents(containerId, events, $disabled = false) {
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
                <div class="event ${event.image ? 'has-background' : ''}" 
                    data-event-id="${event.id}" 
                    style="${event.image ? `--event-bg: url('${event.image}')` : ''}">
                    <h3>${event.title}</h3>
                    <div class="event-meta">
                        <span class="event-association">${event.association_name || 'Association'}</span>
                        <span class="separator">|</span>
                        <a href="index.php?view=user&username=${encodeURIComponent(event.author_username)}" class="event-author-link">
                            <img src="${event.author_picture || 'media/default-avatar.png'}" alt="Photo de ${event.author_firstName}">
                            <span class="event-author-name">${event.author_firstName} ${event.author_lastName}</span>
                        </a>
                    </div>

                    ${tagsHtml}
                    <p>${event.content}</p>
                    
                    <p><strong>Date :</strong> ${new Date(event.start_time).toLocaleString('fr-FR')}</p>
                    <p><strong>Lieu :</strong> ${event.location}</p>
                `;

                if (currentUsername) {
                    // On utilise les classes 'active' pour gérer le style
                    html += `
                        <div class="actions">
                            <button class="btn-interesse ${interestedClass}" ${$disabled ? "disabled" : ""} data-id="${event.id}">
                                ${isInterested ? "Ne plus être intéressé" : "Intéressé"}
                            </button>
                            <button class="btn-participe ${participatingClass}" ${$disabled ? "disabled" : ""} data-id="${event.id}">
                                ${isParticipating ? "Ne plus participer" : "Je participe"}
                            </button>
                        </div>
                    `;
                } else {
                    html += `
                    <div class="actions">
                        <button class="btn-interesse ${interestedClass}" disabled data-id="${event.id}">
                            ${isInterested ? "Ne plus être intéressé" : "Intéressé"}
                        </button>
                        <button class="btn-participe ${participatingClass}" disabled data-id="${event.id}">
                            ${isParticipating ? "Ne plus participer" : "Je participe"}
                        </button>
                    </div>
                `;
                }

                html += `</div>`;
                $container.append(html);
            });

            // Gestion "Intéressé"
            $(".btn-interesse").off("click").on("click", function () {
                const $btn = $(this);
                const eventId = $btn.data("id");

                $.post("api.php", { request: "toggle_interest", event_id: eventId }, function (res) {
                    const interested = res["new_interested"];
                    $btn.text(interested ? "Ne plus être intéressé" : "Intéressé");
                    $btn.toggleClass("active", interested);
                    $btn.trigger("mouseleave");
                    $btn.trigger("mouseenter");
                });
            });

            // Gestion "Je participe"
            $(".btn-participe").off("click").on("click", function () {
                const $btn = $(this);
                const eventId = $btn.data("id");

                $.post("api.php", { request: "toggle_participation", event_id: eventId }, function (res) {
                    const participate = res["new_participate"];
                    $btn.text(participate ? "Ne plus participer" : "Je participe");
                    $btn.toggleClass("active", participate);
                    $btn.trigger("mouseleave");
                    $btn.trigger("mouseenter");
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