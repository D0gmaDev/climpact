<?php
// Si la page est appelée directement par son adresse, on redirige en passant pas la page index
if (basename($_SERVER["PHP_SELF"]) != "index.php") {
	header("Location:../index.php");
	die("");
}

// On envoie l'entête Content-type correcte avec le bon charset
header('Content-Type: text/html; charset=utf-8');

// On récupère la vue actuelle pour savoir quelle page est active
$current_view = valider("view") ?: 'accueil';

?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Climpact</title>
	<link rel="icon" type="image/x-icon" href="media/favicon.ico" />
	<link rel="stylesheet" href="css/colors.css" />
	<link rel="stylesheet" href="css/style.css" />
	<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<script>
	$(function () {
		const searchInput = $('#user-search');
		const resultsContainer = $('#search-results');

		searchInput.on('keyup', function () {
			const query = $(this).val();

			// On n'envoie la requête que si la recherche contient au moins 2 caractères
			if (query.length < 2) {
				resultsContainer.hide().empty(); // On cache et on vide les résultats
				return;
			}

			$.ajax({
				url: 'api.php?request=users',
				type: 'GET',
				dataType: 'json',
				data: {
					search: query
				},
				success: function (response) {
					resultsContainer.empty();

					const users = response.users || [];

					if (users.length > 0) {
						$.each(users, function (index, user) {
							const fullName = `${user.firstName} ${user.lastName}`;
							const userLink = $('<a></a>')
								.attr('href', `index.php?view=user&username=${user.username}`)
								.text(fullName);

							resultsContainer.append(userLink);
						});
					} else {
						resultsContainer.append('<p style="padding: 10px; margin: 0; color: #6c757d;">Aucun utilisateur trouvé.</p>');
					}

					resultsContainer.show();
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error('Erreur lors de la recherche AJAX:', textStatus, errorThrown);
					resultsContainer.empty().append('<p style="padding: 10px; margin: 0; color: red;">Erreur de recherche.</p>').show();
				}
			});

		});

		// Optionnel : Cacher les résultats si on clique en dehors
		$(document).on('click', function (event) {
			if (!$(event.target).closest('.search-container').length) {
				resultsContainer.hide();
			}
		});
	});
</script>

<style>
	
</style>

<body>

	<div class="page-container">
		<aside class="sidebar">
			<div class="sidebar-top">
				<div class="logo-container">
					<a href="index.php?view=accueil">
						<img src="media/climpact-logo.png" alt="Logo Climpact">
					</a>
				</div>

				<div class="search-container">
					<i class="fas fa-search search-icon"></i>
					<input type="text" id="user-search" placeholder="Rechercher...">
				</div>
				<div id="search-results"></div>
				<nav class="main-nav">
					<p class="menu-title">MENU</p>
					<ul>
						<li class="<?php echo ($current_view == 'accueil') ? 'active' : ''; ?>">
							<a href="index.php?view=accueil">
								<i class="fas fa-newspaper"></i> Fil d'actualité
							</a>
						</li>
						<li class="<?php echo ($current_view == 'planning') ? 'active' : ''; ?>">
							<a href="index.php?view=planning">
								<i class="fas fa-calendar-alt"></i> Planning
							</a>
						</li>
						<li class="<?php echo ($current_view == 'about') ? 'active' : ''; ?>">
							<a href="index.php?view=about">
								<i class="fas fa-info-circle"></i> À propos de la DDRS
							</a>
						</li>
					</ul>
				</nav>
			</div>

			<div class="sidebar-bottom">
				<a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
				<a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
			</div>
		</aside>

		<div class="main-wrapper">
			<header class="top-bar">
				<?php if (valider("connecte", "SESSION")): ?>
					<div class="user-actions">
						<?php if (valider("hasAssociation", "SESSION") == true): ?>
							<a href="index.php?view=create" class="btn btn-primary">
								<i class="fas fa-plus"></i> Créer un événement
							</a>
						<?php endif; ?>
						<a href="index.php?view=notifications" class="icon-button">
							<i class="fas fa-bell"></i>
						</a>
						<a href="index.php?view=user&username=<?php echo valider('username', 'SESSION'); ?>"
							class="icon-button">
							<img src="<?php echo valider('avatar', 'SESSION') ? valider('avatar', 'SESSION') : 'media/default-avatar.png'; ?>"
								alt="Mon profil" class="profile-pic">
						</a>
					</div>
				<?php else: ?>
					<div class="login-action">
						<a href="controleur.php?action=login" class="btn btn-secondary">Se Connecter</a>
					</div>
				<?php endif; ?>
			</header>

			<main class="content">