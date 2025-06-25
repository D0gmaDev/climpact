<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
Cette page génère les différentes vues de l'application en utilisant des templates situés dans le répertoire "templates". Un template ou 'gabarit' est un fichier php qui génère une partie de la structure XHTML d'une page. 

La vue à afficher dans la page index est définie par le paramètre "view" qui doit être placé dans la chaîne de requête. En fonction de la valeur de ce paramètre, on doit vérifier que l'on a suffisamment de données pour inclure le template nécessaire, puis on appelle le template à l'aide de la fonction include

Les formulaires de toutes les vues générées enverront leurs données vers la page data.php pour traitement. La page data.php redirigera alors vers la page index pour réafficher la vue pertinente, généralement la vue dans laquelle se trouvait le formulaire. 
*/

include_once "libs/maLibUtils.php";
include_once "libs/modele.php"; // Inclure le modèle pour accéder à getEvents() et getTags()

// HEADER
include("templates/header.php");

// TEMPLATE
$view = valider("view");
if (!$view)
    $view = "accueil";

// Logique du contrôleur pour la vue "accueil"
if ($view == "accueil") {
    // Récupérer tous les tags disponibles pour le filtre
    $allTags = getTags();

    // Récupérer les tags sélectionnés depuis l'URL (ex: ?view=accueil&tags[]=1&tags[]=3)
    $selectedTagIds = valider("tags") ?: [];
    // Assurez-vous que $selectedTagIds est un tableau d'entiers
    if (!is_array($selectedTagIds)) {
        $selectedTagIds = [];
    }
    $selectedTagIds = array_map('intval', $selectedTagIds);
    $selectedTagIds = array_filter($selectedTagIds); // Supprime les 0 si la conversion a échoué

    // Filtrer les événements si des tags sont sélectionnés
    $events = getEvents(10, true, $selectedTagIds);
}

if (file_exists("templates/$view.php")) {
    include("templates/$view.php"); // Inclut le TEMPLATE HTML correspondant
} else {
    // Gérer le cas où la vue n'existe pas (ex: page 404)
    include("templates/error404.php"); // Assure-toi d'avoir ce fichier
}

// FOOTER
include("templates/footer.php");

?>