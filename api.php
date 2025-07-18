<?php
/**
 * API REST pour la gestion d'événements et d'utilisateurs.
 *
 * Cette API gère les routes pour les entités.
 * Elle nécessite une authentification via un hash pour la plupart des actions.
 *
 * @version 1.0
 */


include_once("libs/maLibUtils.php");
include_once("libs/modele.php");

// Configuration des en-têtes CORS pour autoriser les requêtes cross-domain
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-control-allow-headers: Content-Type, Authorization, Hash");
header("Content-Type: application/json; charset=UTF-8");

$data = array(
    "version" => 1.0,
    "success" => false,
    "status" => 400 // Bad Request
);


if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    die();
}

$method = $_SERVER["REQUEST_METHOD"];
$request = valider("request");

$requestParts = explode("/", $request);
$entity = array_shift($requestParts); // L'entité (ex: "users")
$id = array_shift($requestParts);    // L'ID si présent (ex: "1" pour users/1)

// Routeur principal de l'API
switch ($entity) {
    case "users":
        switch ($method) {
            case "GET":
                $search = valider("search");

                $users = getUsers($search);

                if ($users !== false) {
                    $data["success"] = true;
                    $data["status"] = 200;
                    $data["users"] = $users;
                } else {
                    $data["status"] = 500;
                    $data["message"] = "Erreur lors de la récupération des utilisateurs.";
                }
                break;
            default:
                break;
        }
        break;

    case "fetch_events":
        $selectedTagIds = isset($_POST['tags']) ? array_map('intval', $_POST['tags']) : [];

        $allEvents = getEvents(0, false, $selectedTagIds);

        $now = date('Y-m-d H:i:s');
        $upcoming = array_filter($allEvents, fn($e) => $e['start_time'] > $now);
        $history = array_filter($allEvents, fn($e) => $e['start_time'] <= $now);

        $data["success"] = true;
        $data["status"] = 200;
        $data["upcoming"] = array_values($upcoming);
        $data["history"] = array_values($history);
        break;

    case "toggle_interest":
        session_start();
        if (!valider("connecte", "SESSION")) {
            $data["status"] = 401; // Non autorisé
            $data["message"] = "Vous devez être connecté pour participer à un événement.";
            break;
        }

        $idUser = valider("idUser", "SESSION");
        $eventId = valider("event_id");

        $new_interested = toggleInvolvement($idUser, $eventId, "interested");

        $data["success"] = true;
        $data["status"] = 200;
        $data["message"] = "Participation mise à jour avec succès.";
        $data["new_interested"] = $new_interested;
        break;

    case "toggle_participation":
        session_start();
        if (!valider("connecte", "SESSION")) {
            $data["status"] = 401; // Non autorisé
            $data["message"] = "Vous devez être connecté pour participer à un événement.";
            break;
        }

        $idUser = valider("idUser", "SESSION");
        $eventId = valider("event_id");

        $new_participate = toggleInvolvement($idUser, $eventId);

        $data["success"] = true;
        $data["status"] = 200;
        $data["message"] = "Participation mise à jour avec succès.";
        $data["new_participate"] = $new_participate;
        break;

    case "get_participants":
        $eventId = valider("event_id");

        $participants = getEventParticipants($eventId);
        $data["success"] = true;
        $data["status"] = 200;
        $data["participants"] = $participants;
        break;

    case "get_interested":
        $eventId = valider("event_id");

        $interested = getEventInterested($eventId);
        $data["success"] = true;
        $data["status"] = 200;
        $data["interested"] = $interested;
        break;

    default:
        $data["status"] = 404; // Non trouvé
        $data["message"] = "Entité '" . $entity . "' non reconnue.";
        break;
}

// --- ENVOI DE LA RÉPONSE ---
// On définit le code de statut HTTP final en fonction du déroulement
switch ($data["status"]) {
    case 200:
        header("HTTP/1.1 200 OK");
        break;
    case 201:
        header("HTTP/1.1 201 Created");
        break;
    case 202:
        header("HTTP/1.1 202 Accepted");
        break;
    case 400:
        header("HTTP/1.1 400 Bad Request");
        break;
    case 401:
        header("HTTP/1.1 401 Unauthorized");
        break;
    case 403:
        header("HTTP/1.1 403 Forbidden");
        break;
    case 404:
        header("HTTP/1.1 404 Not Found");
        break;
    case 500:
        header("HTTP/1.1 500 Internal Server Error");
        break;
    default:
        header("HTTP/1.1 200 OK");
}

// On encode le tableau de réponse en JSON et on l'affiche
echo json_encode($data);
?>