
<head>
    <link rel="stylesheet" href="css/planning.css">
</head>
<body>

<h1>Planning Général</h1>

<div class="planning-section">
    <div class="planning-header">
        <div class="planning-navigation">
            <button class="nav-arrow" id="prevPeriod">&lt;</button>
            <span class="current-period-display" id="currentPeriodDisplay"></span>
            <button class="nav-arrow" id="nextPeriod">&gt;</button>
        </div>
        <div class="planning-view-switcher">
            <button class="view-button active" data-view="week">Semaine</button>
            <button class="view-button" data-view="day">Jour</button>
            <button class="view-button" data-view="month">Mois</button>
        </div>
    </div>

    <div class="planning-grid-container">
        <div class="time-axis"></div>
        <div class="days-container">
            <div class="day-headers-row"></div>
            <div class="events-grid"></div>
        </div>
    </div>
</div>

<template id="event-template">
    <div class="event-card" data-event-id="">
        <div class="event-time-range">
            <span class="event-start-time"></span> - <span class="event-end-time"></span>
        </div>
        <h4 class="event-title"></h4>
        <p class="event-location"></p>
        <p class="event-content"></p>
    </div>
</template>

<!-- Export des événements PHP vers JS -->
<script>
    const allEvents = <?= json_encode($events, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
</script>

<!-- Moment.js et jQuery pour la gestion des dates et des événements -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/fr.min.js"></script>

<script>
    const currentUsername = <?= valider("connecte", "SESSION") ? json_encode($_SESSION["username"]) : 'null' ?>;

$(function() {
    moment.locale('fr');
    let currentMoment = moment();
    let currentView = 'week';
    const timeSlotHeightPx = 48;
    const pixelsPerMinute = timeSlotHeightPx / 60;

    function generateTimeAxis() {
        const $ta = $('.time-axis').empty();
        for (let i = 7; i < 24; i++) {
            const label = moment().startOf('day').add(i, 'hours').format('HH:mm');
            $ta.append(`<div class="time-slot">${label}</div>`);
        }
    }

    function generateDayGrid(startDate, days) {
        const $hdr = $('.day-headers-row').empty();
        const $grid = $('.events-grid').empty().css('grid-template-columns', `repeat(${days},1fr)`);
        for (let i = 0; i < days; i++) {
            const d = moment(startDate).add(i, 'days');
            $hdr.append(`<div class="day-header">${d.format('ddd DD/MM')}</div>`);
            $grid.append(`<div class="day-column" data-date="${d.format('YYYY-MM-DD')}" style="position:relative; min-height:${17*timeSlotHeightPx}px"></div>`);
        }
    }

    function renderEvents(startDate, endDate) {
        $('.day-column').empty();
        allEvents.forEach(ev => {
            const s = moment(ev.start_time);
            const e = moment(ev.end_time);
            if (e.isBefore(startDate) || s.isAfter(endDate, 'day')) return;
            const day = s.format('YYYY-MM-DD');
            const $col = $(`.day-column[data-date="${day}"]`);
            if (!$col.length) return;
            const eventMinutes = s.hours() * 60 + s.minutes();
            const top = (eventMinutes - 420) * pixelsPerMinute; // 420 = 7 * 60 car on commence à 7h
            const height = (e.diff(s, 'minutes')) * pixelsPerMinute;
            if (top + height < 0) return; // Trop tôt, on n'affiche pas
            const $card = $('#event-template').contents().clone();
            $card.attr('data-event-id', ev.id);
            $card.find('.event-start-time').text(s.format('HH:mm'));
            $card.find('.event-end-time').text(e.format('HH:mm'));
            $card.find('.event-title').text(ev.title);
            $card.find('.event-location').text(ev.location);
            $card.find('.event-content').text(ev.content);
            $card.css({ position: 'absolute', top: `${top}px`, height: `${height}px`, width: '100%' });
            $col.append($card);
        });
    }

    // Fonction pour le rendu de l'event dans le popup, basée sur la fonction de rendu des évènements dans accueil.php

    function renderSingleEvent(event, $disabled = false) {
        const $container = $("#event");
        $container.empty();

        const isInterested = currentUsername && event.interested.includes(currentUsername);
        const isParticipating = currentUsername && event.participants.includes(currentUsername);
        const interestedClass = isInterested ? 'active' : '';
        const participatingClass = isParticipating ? 'active' : '';

        let tagsHtml = event.tagNames && event.tagNames.length > 0 ?
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
            <div class="actions">
                <button class="btn-interesse ${interestedClass}" ${$disabled ? "disabled" : ""} data-id="${event.id}">
                    ${isInterested ? "Ne plus être intéressé" : "Intéressé"}
                </button>
                <button class="btn-participe ${participatingClass}" ${$disabled ? "disabled" : ""} data-id="${event.id}">
                    ${isParticipating ? "Ne plus participer" : "Je participe"}
                </button>
            </div>
        </div>`;

        $container.append(html);

        // Gestion des clics
        $(".btn-interesse").off("click").on("click", function () {
            const $btn = $(this);
            const eventId = $btn.data("id");

            $.post("api.php", { request: "toggle_interest", event_id: eventId }, function (res) {
                const interested = res["new_interested"];
                $btn.text(interested ? "Ne plus être intéressé" : "Intéressé");
                $btn.toggleClass("active", interested);
                $btn.trigger("mouseleave").trigger("mouseenter");
            });
        });

        $(".btn-participe").off("click").on("click", function () {
            const $btn = $(this);
            const eventId = $btn.data("id");

            $.post("api.php", { request: "toggle_participation", event_id: eventId }, function (res) {
                const participate = res["new_participate"];
                $btn.text(participate ? "Ne plus participer" : "Je participe");
                $btn.toggleClass("active", participate);
                $btn.trigger("mouseleave").trigger("mouseenter");
            });
        });

        // Information au survol des boutons
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

    // Fonction spécifique pour le rendu de la vue mois
    
    function renderMonthView() {
        const startOfMonth = currentMoment.clone().startOf('month');
        const endOfMonth = currentMoment.clone().endOf('month');
        const startWeek = startOfMonth.clone().startOf('week');
        const endWeek = endOfMonth.clone().endOf('week');
        const totalDays = endWeek.diff(startWeek, 'days') + 1;
        
        $('.time-axis').hide();
        // Réinitialiser les conteneurs
        const $hdr = $('.day-headers-row').empty().css('grid-template-columns', 'repeat(7, 1fr)');
        const $grid = $('.events-grid').empty().css('grid-template-columns', 'repeat(7, 1fr)');
        
        // On génère les en-têtes de jour pour la semaine
        for (let i = 0; i < 7; i++) {
            $hdr.append(`<div class="day-header-month">${moment().startOf('week').add(i, 'days').format('ddd')}</div>`);
        }
        
        // On génère les cellules de jour pour le mois
        for (let i = 0; i < totalDays; i++) {
            const day = startWeek.clone().add(i, 'days');
            const dayFormatted = day.format('YYYY-MM-DD');
            const isCurrentMonth = day.isSame(currentMoment, 'month');
            
            const $dayCell = $(`<div class="day-cell ${isCurrentMonth ? 'current-month-day' : 'other-month-day'}" data-date="${dayFormatted}">
                <div class="day-number">${day.format('DD')}</div>
                <div class="event-dots-container"></div>
            </div>`);
            
            $grid.append($dayCell);

            // Gestion du clic sur la cellule d'un jour
            $dayCell.on('click', function() {
                const selectedDate = $(this).data('date');
                currentMoment = moment(selectedDate);
                currentView = 'week';
                $('.view-button[data-view="week"]').click();
            });
        }

        // Ajouts de points pour les événements
        allEvents.forEach(ev => {
            const s = moment(ev.start_time);
            const day = s.format('YYYY-MM-DD');
            const $cell = $(`.day-cell[data-date="${day}"]`);
            if ($cell.length) {
                const $dotContainer = $cell.find('.event-dots-container');
                $dotContainer.append('<div class="event-dot"></div>');
            }
        });
    }
    
    function renderPlanning() {
        let sd, ed, label;
        $('.view-button').removeClass('active');
        $(`.view-button[data-view="${currentView}"]`).addClass('active');

        if (currentView === 'week') {
            $('.planning-grid-container').css('grid-template-columns', '70px 1fr');
            sd = currentMoment.clone().startOf('week');
            ed = currentMoment.clone().endOf('week');
            generateDayGrid(sd, 7);
            $('.current-time-line').remove(); // Avant d’en ajouter une on évite les doublons
            $('.events-grid').append('<div class="current-time-line"></div>');
            label = `${sd.format('DD MMMM')} - ${ed.format('DD MMMM YYYY')}`;
            renderEvents(sd, ed);
            $('.time-axis').show();
            updateTimeLine(); // Appel après le rendu de la grille
        } else if (currentView === 'day') {
            $('.planning-grid-container').css('grid-template-columns', '70px 1fr');
            sd = currentMoment.clone().startOf('day');
            ed = currentMoment.clone().endOf('day');
            generateDayGrid(sd, 1);
            $('.current-time-line').remove();
            $('.events-grid').append('<div class="current-time-line"></div>');
            label = currentMoment.format('dddd DD MMMM YYYY');
            renderEvents(sd, ed);
            $('.time-axis').show();
            updateTimeLine(); 
        } else if (currentView === 'month') {
            $('.planning-grid-container').css('grid-template-columns', '1fr');
            renderMonthView();
            label = currentMoment.format('MMMM YYYY');
            // Pas de time-line pour le mois
            $('.current-time-line').hide(); 
        }
        $('#currentPeriodDisplay').text(label);
    }

    // Fonction pour mettre à jour la time-ligne
    function updateTimeLine() {
        const now = moment();
        const todayDate = now.format('YYYY-MM-DD');
        const startOfDay = moment(todayDate).startOf('day');
        const minutesPassed = now.diff(startOfDay, 'minutes');

        const headerHeight = $('.day-headers-row').outerHeight();
        const topPosition = ((minutesPassed -420) * pixelsPerMinute);

        const $timeLine = $('.current-time-line');

        if (currentView === 'week' || (currentView === 'day' && todayDate === currentMoment.format('YYYY-MM-DD'))) {
            $timeLine.css({
                top: `${topPosition}px`,
                display: 'block'
            });
        } else {
            $timeLine.hide();
        }
    }

    $('#prevPeriod').click(() => {
        currentMoment.subtract(1, currentView);
        renderPlanning();
    });
    $('#nextPeriod').click(() => {
        currentMoment.add(1, currentView);
        renderPlanning();
    });
    $('.view-button').click(function() {
        currentView = $(this).data('view');
        renderPlanning();
    });
    // Gestion du clic sur les événements pour afficher le popup
    $(document).on('click', '.event-card', function() {
        const eventId = $(this).data('event-id');
        const event = allEvents.find(ev => ev.id == eventId);
        
        if (event) {
            renderSingleEvent(event);
            $('#popup').show();
        } else {
            console.warn("Événement non trouvé :", eventId);
        }
    });


    $('#closePopup').on('click', function() {
        $('#popup').hide();
    });

    generateTimeAxis();
    renderPlanning();

    // Update la time-line toutes les minutes
    setInterval(updateTimeLine, 60 * 1000); 

    updateTimeLine();
});
</script>

<div id="popup">
    <button id="closePopup" style="float:right;">X</button>
    <div id="event"></div>
</div>

</body>
</html>
