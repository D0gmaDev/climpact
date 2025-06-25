$(document).ready(function() {
    moment.locale('fr'); // Configure Moment.js en français

    let currentMoment = moment(); // Le moment actuel, qui sera le point de référence pour la semaine/jour/mois affichée
    let currentView = 'week'; // Vue par défaut

    const timeSlotHeightPx = 48; // Hauteur en pixels pour 1 heure dans la grille
    const minutesPerHour = 60;
    const pixelsPerMinute = timeSlotHeightPx / minutesPerHour; // Pixels par minute (ex: 0.8px/min si 48px/heure)

    // Fonction pour générer la colonne des heures
    function generateTimeAxis() {
        const $timeAxis = $('.time-axis');
        $timeAxis.empty();
        // Ajouter un slot vide pour le coin supérieur gauche (où il n'y a pas d'heure)
        $timeAxis.append('<div class="time-slot" style="height: 40px; border-bottom: none;"></div>'); // Hauteur pour l'alignement avec les headers de jours
        for (let i = 0; i < 24; i++) {
            const hour = moment().startOf('day').add(i, 'hours').format('HH:mm');
            $timeAxis.append(`<div class="time-slot">${hour}</div>`);
        }
    }

    // Fonction pour générer les en-têtes de jour et les colonnes d'événements
    function generateDayGrid(startDate, numberOfDays) {
        const $dayHeadersRow = $('.day-headers-row');
        const $eventsGrid = $('.events-grid');
        $dayHeadersRow.empty();
        $eventsGrid.empty();
        $eventsGrid.css('grid-template-columns', `repeat(${numberOfDays}, 1fr)`);

        for (let i = 0; i < numberOfDays; i++) {
            const day = moment(startDate).add(i, 'days');
            $dayHeadersRow.append(`<div class="day-header">${day.format('ddd DD/MM')}</div>`);
            $eventsGrid.append(`<div class="day-column" data-date="${day.format('YYYY-MM-DD')}"></div>`);
        }
    }

    // Fonction pour charger et afficher les événements
    // Cette fonction prendra les données d'événements (obtenues via AJAX/PHP)
    function loadEvents(eventsData) {
        $('.day-column').empty(); // Nettoyer les événements existants

        eventsData.forEach(event => {
            const startTime = moment(event.start_time);
            const endTime = moment(event.end_time);
            const eventDay = startTime.format('YYYY-MM-DD');

            // Trouver la colonne du jour correspondant
            const $targetDayColumn = $(`.day-column[data-date="${eventDay}"]`);

            if ($targetDayColumn.length > 0) {
                const dayStart = moment(eventDay).startOf('day');
                const minutesFromDayStart = startTime.diff(dayStart, 'minutes');
                const durationMinutes = endTime.diff(startTime, 'minutes');

                const topPosition = minutesFromDayStart * pixelsPerMinute;
                const eventHeight = durationMinutes * pixelsPerMinute;

                const $eventElement = $('#event-template').contents().clone();
                $eventElement.attr('data-event-id', event.id);
                $eventElement.find('.event-start-time').text(startTime.format('HH:mm'));
                $eventElement.find('.event-end-time').text(endTime.format('HH:mm'));
                $eventElement.find('.event-title').text(event.title);
                $eventElement.find('.event-location').text(event.location);
                $eventElement.find('.event-content').text(event.content); // Affiche le contenu

                $eventElement.css({
                    'top': `${topPosition}px`,
                    'height': `${eventHeight}px`
                });

                $targetDayColumn.append($eventElement);
            }
        });
    }

    // Fonction principale pour rendre le planning
    function renderPlanning() {
        let startDate, endDate, periodText;
        $('.time-axis').css('display', 'block'); // Afficher les heures par défaut

        if (currentView === 'week') {
            startDate = moment(currentMoment).startOf('week');
            endDate = moment(currentMoment).endOf('week');
            periodText = `${startDate.format('DD MMMM')} - ${endDate.format('DD MMMM YYYY')}`;
            generateDayGrid(startDate, 7);
            $('.planning-grid-container').css('grid-template-columns', '70px 1fr'); // Standard 7 jours
        } else if (currentView === 'day') {
            startDate = moment(currentMoment).startOf('day');
            endDate = moment(currentMoment).endOf('day');
            periodText = currentMoment.format('dddd DD MMMM YYYY');
            generateDayGrid(startDate, 1); // Un seul jour
            $('.planning-grid-container').css('grid-template-columns', '70px 1fr'); // Une seule colonne large pour le jour
        } else if (currentView === 'month') {
            startDate = moment(currentMoment).startOf('month').startOf('week'); // Débute au début de la semaine du 1er du mois
            endDate = moment(currentMoment).endOf('month').endOf('week'); // Finit à la fin de la semaine du dernier du mois
            periodText = currentMoment.format('MMMM YYYY');

            // Pour la vue mois, nous devons générer une grille de jours différente (6x7 potentiellement)
            // C'est beaucoup plus complexe et sort du cadre d'un exemple simple.
            // Il faudrait calculer le nombre de jours à afficher (y compris les jours des mois précédents/suivants)
            // Créer une grille CSS appropriée (ex: display: grid; grid-template-columns: repeat(7, 1fr);)
            // Chaque cellule représenterait un jour et contiendrait ses événements.
            // Pour l'instant, je vais juste vider la grille et montrer le texte.
            $('.day-headers-row').empty();
            $('.events-grid').empty().append('<p style="text-align: center; padding: 50px;">TODO</p>');
            $('.time-axis').css('display', 'none'); // Pas d'heures en vue mois
            $('.planning-grid-container').css('grid-template-columns', '1fr'); // Pas de colonne d'heures

            $('#currentPeriodDisplay').text(periodText);
            return; // Sortir après avoir géré la vue mois simplifiée
        }

        $('#currentPeriodDisplay').text(periodText);

        // Simuler le chargement des événements (tu remplaceras cela par un appel AJAX)
        fetchEvents(startDate.format('YYYY-MM-DD'), endDate.format('YYYY-MM-DD'));
    }

    // Fonction pour simuler la récupération des événements via AJAX
    function fetchEvents(startDate, endDate) {
        console.log(`Fetching events from ${startDate} to ${endDate}`);
        // Ici, tu ferais une requête AJAX à ton script PHP
        // Exemple avec un appel AJAX (à adapter avec ton URL PHP) :
        $.ajax({
            url: 'fetch_events.php', // Ton script PHP
            method: 'GET',
            data: {
                start: startDate,
                end: endDate
            },
            dataType: 'json', // Attendre une réponse JSON
            success: function(data) {
                console.log("Events received:", data);
                // Exemple de données si le tableau est vide
                if (data === null || data.length === 0) {
                    console.log("No events found for this period.");
                    loadEvents([]); // Charger un tableau vide
                    return;
                }
                loadEvents(data); // Charger les événements reçus
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error fetching events:", textStatus, errorThrown);
                // Si une erreur survient ou que le format n'est pas bon
                // Charger un tableau vide ou un message d'erreur
                loadEvents([]);
            }
        });

        // Pour le test sans PHP, tu peux commenter l'AJAX et décommenter ceci:
        // const dummyEvents = [
        //     {
        //         id: 1,
        //         title: "Test Event",
        //         content: "Un super event (surement)",
        //         start_time: "2025-06-25 10:30:00",
        //         end_time: "2025-06-25 12:00:00",
        //         location: "B7",
        //         association: 2,
        //         author: 1
        //     },
        //     {
        //         id: 2,
        //         title: "Conférence IA",
        //         content: "Découverte de l'IA générative",
        //         start_time: "2025-06-26 14:00:00",
        //         end_time: "2025-06-26 16:30:00",
        //         location: "Amphi A",
        //         association: 1,
        //         author: 2
        //     }
        //     // ... ajoute d'autres événements pour tester
        // ];
        // loadEvents(dummyEvents);
    }

    // --- Gestion des événements UI ---

    // Boutons de navigation (précédent/suivant)
    $('#prevPeriod').on('click', function() {
        if (currentView === 'week') {
            currentMoment.subtract(1, 'week');
        } else if (currentView === 'day') {
            currentMoment.subtract(1, 'day');
        } else if (currentView === 'month') {
            currentMoment.subtract(1, 'month');
        }
        renderPlanning();
    });

    $('#nextPeriod').on('click', function() {
        if (currentView === 'week') {
            currentMoment.add(1, 'week');
        } else if (currentView === 'day') {
            currentMoment.add(1, 'day');
        } else if (currentView === 'month') {
            currentMoment.add(1, 'month');
        }
        renderPlanning();
    });

    // Boutons de changement de vue (Jour/Semaine/Mois)
    $('.view-button').on('click', function() {
        $('.view-button').removeClass('active');
        $(this).addClass('active');
        currentView = $(this).data('view');
        renderPlanning();
    });

    // Initialisation
    generateTimeAxis();
    renderPlanning(); // Afficher la première vue (semaine par défaut)

    // Mettre à jour la barre horaire toutes les minutes
    setInterval(updateTimeLine, 60 * 1000); // Toutes les minutes
});