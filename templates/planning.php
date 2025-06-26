
<head>
    <link rel="stylesheet" href="css/planning.css">
</head>
<body>

<div class="planning-section">
    <div class="planning-header">
        <div class="planning-title-container">
            <h1 class="planning-title">Planning Général</h1>
        </div>
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

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/fr.min.js"></script>

<script>
$(function() {
    moment.locale('fr');
    let currentMoment = moment();
    let currentView = 'week';
    const timeSlotHeightPx = 48;
    const pixelsPerMinute = timeSlotHeightPx / 60;

    function generateTimeAxis() {
        const $ta = $('.time-axis').empty();
        $ta.append('<div class="time-slot"></div>');
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
            const top = (eventMinutes - 420) * pixelsPerMinute; // 420 = 7 * 60
            const height = (e.diff(s, 'minutes')) * pixelsPerMinute;
            if (top + height < 0) return; // Trop tôt, on n'affiche pas
            const $card = $('#event-template').contents().clone();
            $card.find('.event-start-time').text(s.format('HH:mm'));
            $card.find('.event-end-time').text(e.format('HH:mm'));
            $card.find('.event-title').text(ev.title);
            $card.find('.event-location').text(ev.location);
            $card.find('.event-content').text(ev.content);
            $card.css({ position: 'absolute', top: `${top}px`, height: `${height}px`, width: '100%' });
            $col.append($card);
        });
    }

    // New function to render the month view
    function renderMonthView() {
        const startOfMonth = currentMoment.clone().startOf('month');
        const endOfMonth = currentMoment.clone().endOf('month');
        const startWeek = startOfMonth.clone().startOf('week');
        const endWeek = endOfMonth.clone().endOf('week');
        const totalDays = endWeek.diff(startWeek, 'days') + 1;
        
        // Hide time axis
        $('.time-axis').hide();
        // Clear the day grid and set up the month grid
        const $hdr = $('.day-headers-row').empty().css('grid-template-columns', 'repeat(7, 1fr)');
        const $grid = $('.events-grid').empty().css('grid-template-columns', 'repeat(7, 1fr)');
        
        // Generate day of week headers
        for (let i = 0; i < 7; i++) {
            $hdr.append(`<div class="day-header-month">${moment().startOf('week').add(i, 'days').format('ddd')}</div>`);
        }
        
        // Generate day cells for the month grid
        for (let i = 0; i < totalDays; i++) {
            const day = startWeek.clone().add(i, 'days');
            const dayFormatted = day.format('YYYY-MM-DD');
            const isCurrentMonth = day.isSame(currentMoment, 'month');
            
            const $dayCell = $(`<div class="day-cell ${isCurrentMonth ? 'current-month-day' : 'other-month-day'}" data-date="${dayFormatted}">
                <div class="day-number">${day.format('DD')}</div>
                <div class="event-dots-container"></div>
            </div>`);
            
            $grid.append($dayCell);

            // Add click event to navigate to the corresponding week view
            $dayCell.on('click', function() {
                const selectedDate = $(this).data('date');
                currentMoment = moment(selectedDate);
                console.log(`Navigating to week view for date: ${selectedDate}`);
                currentView = 'week';
                $('.view-button[data-view="week"]').click(); // Programmatically click the week button to update the view
            });
        }

        // Add event dots
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
            $('.current-time-line').remove(); // Avant d’en ajouter une
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
            $('.current-time-line').remove(); // Avant d’en ajouter une
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
    
    generateTimeAxis();
    renderPlanning();

    // Update la time-line toutes les minutes
    setInterval(updateTimeLine, 60 * 1000); 

    updateTimeLine();
});
</script>

</body>
</html>
