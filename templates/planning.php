<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planning - CLImpact</title>
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
                <span class="current-period-display" id="currentPeriodDisplay">12 - 18 Mai 2025</span>
                <button class="nav-arrow" id="nextPeriod">&gt;</button>
            </div>
            <div class="planning-view-switcher">
                <button class="view-button active" data-view="week">Semaine</button>
                <button class="view-button" data-view="day">Jour</button>
                <button class="view-button" data-view="month">Mois</button>
            </div>
        </div>

        <div class="planning-grid-container">
            <div class="time-axis">
                </div>
            <div class="days-container">
                <div class="day-headers-row">
                    </div>
                <div class="events-grid">
                    </div>
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

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/fr.min.js"></script>
    
    <script src="js/planning.js"></script> </body>
</html>