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
								.attr('href', `index.php?view=user&id=${user.id}`)
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
	/* --- Réinitialisation et polices de base --- */
	:root {
		--primary-color: #2D8C4D;
		/* Vert Climpact */
		--secondary-color: #3B74F2;
		/* Bleu (utilisé pour certains tags) */
		--light-grey: #f8f9fa;
		--border-grey: #e9ecef;
		--text-color: #333;
		--text-light: #6c757d;
		--active-bg: #e8f5e9;
		/* Fond pour l'élément de menu actif */
	}

	body {
		margin: 0;
		font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		background-color: #FFF;
		color: var(--text-color);
	}

	a {
		text-decoration: none;
		color: inherit;
	}

	/* --- Structure principale avec Flexbox --- */
	.page-container {
		display: flex;
		min-height: 100vh;
	}

	.main-wrapper {
		flex-grow: 1;
		display: flex;
		flex-direction: column;
	}

	.content {
		padding: 2rem;
	}

	/* --- Barre latérale (Sidebar) --- */
	.sidebar {
		width: 280px;
		flex-shrink: 0;
		background-color: var(--light-grey);
		border-right: 1px solid var(--border-grey);
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		padding: 1.5rem;
		position: sticky;
		top: 0;
		height: 100vh;
		box-sizing: border-box;
	}

	.logo-container {
		text-align: center;
	}

	.logo-container img {
		max-width: 150px;
		margin-bottom: 2rem;
	}

	.search-container {
		position: relative;
		margin-bottom: 1.5rem;
	}

	.search-container .search-icon {
		position: absolute;
		left: 15px;
		top: 50%;
		transform: translateY(-50%);
		color: var(--text-light);
	}

	#user-search {
		width: 100%;
		padding: 12px 12px 12px 40px;
		border: 1px solid var(--border-grey);
		border-radius: 8px;
		font-size: 1rem;
		box-sizing: border-box;
	}

	#search-results {
		background: white;
		border: 1px solid var(--border-grey);
		border-radius: 8px;
		margin-top: 5px;
	}

	#search-results a {
		display: block;
		padding: 10px;
		border-bottom: 1px solid var(--border-grey);
	}

	#search-results a:last-child {
		border-bottom: none;
	}

	#search-results a:hover {
		background-color: var(--light-grey);
	}


	.main-nav .menu-title {
		font-size: 0.8rem;
		color: var(--text-light);
		text-transform: uppercase;
		letter-spacing: 1px;
		padding: 0 1rem;
		margin-bottom: 0.5rem;
	}

	.main-nav ul {
		list-style-type: none;
		padding: 0;
		margin: 0;
	}

	.main-nav li a {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 12px 1rem;
		border-radius: 8px;
		font-weight: 500;
		color: var(--text-light);
		transition: background-color 0.2s, color 0.2s;
	}

	.main-nav li a i {
		width: 20px;
		/* Pour aligner le texte */
	}

	.main-nav li.active a {
		background-color: var(--active-bg);
		color: var(--primary-color);
		font-weight: bold;
	}

	.main-nav li:not(.active) a:hover {
		background-color: #e9ecef;
	}

	.sidebar-bottom {
		display: flex;
		gap: 1rem;
		padding: 0 1rem;
		justify-content: center;
	}

	.sidebar-bottom a {
		font-size: 1.2rem;
		color: var(--text-light);
	}

	/* --- Barre supérieure (Top Bar) --- */
	.top-bar {
		display: flex;
		justify-content: flex-end;
		align-items: center;
		padding: 1rem 2rem;
		border-bottom: 1px solid var(--border-grey);
		background-color: #fff;
	}

	.user-actions,
	.login-action {
		display: flex;
		align-items: center;
		gap: 1.5rem;
	}

	.profile-pic {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		object-fit: cover;
	}

	.icon-button {
		font-size: 1.4rem;
		color: var(--text-light);
	}

	/* --- Boutons --- */
	.btn {
		padding: 10px 20px;
		border-radius: 8px;
		font-weight: bold;
		border: none;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		gap: 8px;
		transition: background-color 0.2s;
	}

	.btn-primary {
		background-color: var(--primary-color);
		color: white;
	}

	.btn-primary:hover {
		background-color: #226d3d;
	}

	.btn-secondary {
		background-color: transparent;
		border: 2px solid var(--primary-color);
		color: var(--primary-color);
	}

	.btn-secondary:hover {
		background-color: var(--active-bg);
	}
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
						<a href="index.php?view=create" class="btn btn-primary">
							<i class="fas fa-plus"></i> Créer un événement
						</a>
						<a href="index.php?view=notifications" class="icon-button">
							<i class="fas fa-bell"></i>
						</a>
						<a href="index.php?view=profil">
							<img src="<?php echo valider('avatar', 'SESSION') ? valider('avatar', 'SESSION') : 'media/default_avatar.png'; ?>"
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