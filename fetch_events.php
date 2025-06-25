<?php

// Récupère les dates de début et de fin passées par AJAX
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('monday this week'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('sunday this week'));

// Exemples d'évènements
$allEvents = [
    [
        'id' => 1,
        'title' => 'Test',
        'content' => 'Un super event (surement)',
        'start_time' => '2025-06-25 10:00:00',
        'end_time' => '2025-06-25 12:00:00',
        'location' => 'B7',
        'image' => '',
        'association' => 2,
        'author' => 1,
        'created_at' => '2025-06-24 11:26:22',
        'orgnizers' => [1],
        'participants' => [],
        'interested' => [],
        'tagIds' => []
    ],
    [
        'id' => 2,
        'title' => 'Conférence IA',
        'content' => 'Découverte de l\'IA générative',
        'start_time' => '2025-06-26 14:00:00',
        'end_time' => '2025-06-26 16:30:00',
        'location' => 'Amphi A',
        'image' => '',
        'association' => 1,
        'author' => 2,
        'created_at' => '2025-06-24 11:26:22',
        'orgnizers' => [2],
        'participants' => [],
        'interested' => [],
        'tagIds' => []
    ],
    [
        'id' => 3,
        'title' => 'Nettoyage Campus',
        'content' => 'Action écologique annuelle',
        'start_time' => '2025-06-27 09:00:00',
        'end_time' => '2025-06-27 11:00:00',
        'location' => 'Parc Central',
        'image' => '',
        'association' => 3,
        'author' => 1,
        'created_at' => '2025-06-24 11:26:22',
        'orgnizers' => [1, 3],
        'participants' => [],
        'interested' => [],
        'tagIds' => []
    ],
    [
        'id' => 4,
        'title' => 'Atelier Cuisine',
        'content' => 'Apprendre à cuisiner végétarien',
        'start_time' => '2025-06-28 17:00:00',
        'end_time' => '2025-06-28 19:00:00',
        'location' => 'Cuisine Communaute',
        'image' => '',
        'association' => 4,
        'author' => 3,
        'created_at' => '2025-06-24 11:26:22',
        'orgnizers' => [4],
        'participants' => [],
        'interested' => [],
        'tagIds' => []
    ]
];

// Filtrer les événements pour ne renvoyer que ceux qui se trouvent dans la plage de dates demandée
$filteredEvents = array_filter($allEvents, function($event) use ($startDate, $endDate) {
    $eventStartTime = new DateTime($event['start_time']);
    $eventEndTime = new DateTime($event['end_time']);
    $queryStartDate = new DateTime($startDate . ' 00:00:00');
    $queryEndDate = new DateTime($endDate . ' 23:59:59');

    // Vérifie si l'événement chevauche la période demandée
    return ($eventStartTime <= $queryEndDate && $eventEndTime >= $queryStartDate);
});

// Réindexer le tableau après le filtrage
$filteredEvents = array_values($filteredEvents);


header('Content-Type: application/json');
echo json_encode($filteredEvents);
?>