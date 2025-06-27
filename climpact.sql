-- phpMyAdmin SQL Dump
-- version 5.2.2-1.fc42
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mar. 24 juin 2025 à 09:30
-- Version du serveur : 8.0.41
-- Version de PHP : 8.4.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `climpact`
--

-- --------------------------------------------------------

--
-- Structure de la table `associations`
--

CREATE TABLE `associations` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `admin` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `associations`
--

INSERT INTO `associations` (`id`, `name`, `description`, `website`, `admin`) VALUES
(1, 'BDE', 'Le Bureau des Eleves', 'https://bde-centralelille.fr/', 1),
(2, 'Rézoléo', 'La co', 'https://rezoleo.fr/', 1);

-- --------------------------------------------------------

--
-- Structure de la table `badges`
--

CREATE TABLE `badges` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `emoji` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `badges`
--

INSERT INTO `badges` (`id`, `name`, `display_name`, `description`, `emoji`) VALUES
(1, 'newcomer', 'Nouveau venu', 'Première connexion à CLimpact\n“C’est parti pour l’engagement !”', '🎯'),
(2, 'curious', 'Curieux.se', 'S’être intéressé(e) à 3 événements différents\n“Toujours à l’affût des bonnes initiatives.”', '🧩'),
(3, 'active', 'Actif.ve', 'Avoir participé à 3 événements\n“Engagé.e dans l’action !”', '💬'),
(4, 'super_participant', 'Super participant.e', 'Avoir participé à 10 événements\n“Pilier des événements CLimpact.”', '💥'),
(5, 'organizer', 'Organisateur.rice', 'Avoir organisé au moins 1 événement\n“Tu lances les initiatives, bravo !”', '🛠️'),
(6, 'ambassador', 'Ambassadeur.rice', 'Avoir organisé 5 événements ou plus\n“Tu fais vivre CLimpact au quotidien.”', '🌱'),
(7, 'loyal', 'Fidèle', 'Avoir participé à des événements sur 3 mois différents\n“L’engagement, c’est dans la durée.”', '🔁'),
(8, 'gold', 'Engagé.e d’Or', 'Avoir participé à 20 événements ou plus\n“Un.e véritable moteur de la transition !”', '💎'),
(9, 'leader', 'Leader d’impact', 'Avoir organisé 10 événements ou plus\n“Ta vision transforme ton campus !”', '🧠'),
(10, 'pioneer', 'Pionnier.ère', 'Avoir été parmi les 10 premiers utilisateurs actifs de CLimpact\n“Un.e des tout premiers engagés !”', '🧭');

-- --------------------------------------------------------

--
-- Structure de la table `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `association` int NOT NULL,
  `author` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `event_tags`
--

CREATE TABLE `event_tags` (
  `id` int NOT NULL,
  `event` int NOT NULL,
  `tag` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `involvements`
--

CREATE TABLE `involvements` (
  `id` int NOT NULL,
  `event` int NOT NULL,
  `user` int NOT NULL,
  `type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tags`
--

CREATE TABLE `tags` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tags`
--

INSERT INTO `tags` (`id`, `name`) VALUES
(1, 'Environnement'),
(2, 'Climat'),
(3, 'Biodiversité'),
(4, 'Mobilité durable'),
(5, 'Alimentation responsable'),
(6, 'Économie circulaire'),
(7, 'Énergie / Sobriété énergétique'),
(8, 'Déchets / Zéro déchet'),
(9, 'Inclusion'),
(10, 'Égalité des chances'),
(11, 'Lutte contre les discriminations'),
(12, 'Santé & bien-être'),
(13, 'Solidarité'),
(14, 'Accessibilité'),
(15, 'Citoyenneté');

-- --------------------------------------------------------

--
-- Structure de la table `themes`
--

CREATE TABLE `themes` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `role` varchar(255) NOT NULL,
  `cursus` varchar(255) NOT NULL,
  `picture` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `theme` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `email`, `token_hash`, `role`, `cursus`, `picture`, `theme`, `created_at`) VALUES
(1, 'david.marembert', 'David', 'Marembert', 'david.marembert@centrale.centralelille.fr', 'azerty', 'admin', 'G2', NULL, NULL, '2025-06-22 13:08:12');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `associations`
--
ALTER TABLE `associations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_association_admin` (`admin`);

--
-- Index pour la table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `association` (`association`),
  ADD KEY `author` (`author`);

--
-- Index pour la table `event_tags`
--
ALTER TABLE `event_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_2` (`event`,`tag`),
  ADD KEY `event` (`event`),
  ADD KEY `tag` (`tag`);

--
-- Index pour la table `involvements`
--
ALTER TABLE `involvements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_involvement` (`event`,`user`,`type`),
  ADD KEY `event` (`event`),
  ADD KEY `user` (`user`);

--
-- Index pour la table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `theme` (`theme`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `associations`
--
ALTER TABLE `associations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `event_tags`
--
ALTER TABLE `event_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `involvements`
--
ALTER TABLE `involvements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `themes`
--
ALTER TABLE `themes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `associations`
--
ALTER TABLE `associations`
  ADD CONSTRAINT `fk_association_admin` FOREIGN KEY (`admin`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_event_association` FOREIGN KEY (`association`) REFERENCES `associations` (`id`),
  ADD CONSTRAINT `fk_event_author` FOREIGN KEY (`author`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `event_tags`
--
ALTER TABLE `event_tags`
  ADD CONSTRAINT `fk_event` FOREIGN KEY (`event`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tag` FOREIGN KEY (`tag`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `involvements`
--
ALTER TABLE `involvements`
  ADD CONSTRAINT `fk_event_2` FOREIGN KEY (`event`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_involvement_user` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_theme` FOREIGN KEY (`theme`) REFERENCES `themes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


DELETE FROM nom_de_votre_table WHERE id = 6;