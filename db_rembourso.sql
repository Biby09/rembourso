-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : em9jnz.myd.infomaniak.com
-- Généré le : mer. 09 sep. 2026 à 14:21
-- Version du serveur : 10.11.15-MariaDB-deb11-log
-- Version de PHP : 8.3.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `em9jnz_rembourso`
--

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id_category` smallint(5) UNSIGNED NOT NULL,
  `cat_name` varchar(64) NOT NULL,
  `idx_organisation` smallint(5) UNSIGNED NOT NULL,
  `cat_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emails`
--

CREATE TABLE `emails` (
  `id_email` smallint(5) UNSIGNED NOT NULL,
  `ema_to` varchar(50) NOT NULL,
  `ema_subject` varchar(100) NOT NULL,
  `ema_content` text NOT NULL,
  `idx_author` smallint(5) UNSIGNED DEFAULT NULL,
  `ema_send_date` datetime NOT NULL DEFAULT current_timestamp(),
  `idx_token` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `invitations`
--

CREATE TABLE `invitations` (
  `id_invitation` smallint(5) UNSIGNED NOT NULL,
  `idx_organisation` smallint(5) UNSIGNED NOT NULL,
  `idx_user` smallint(5) UNSIGNED NOT NULL,
  `inv_role` enum('member','cashier','admin','') NOT NULL,
  `idx_token` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `organisations`
--

CREATE TABLE `organisations` (
  `id_organisation` smallint(5) UNSIGNED NOT NULL,
  `org_name` varchar(128) NOT NULL,
  `org_use_cat` tinyint(3) UNSIGNED DEFAULT 0,
  `org_use_subcat` tinyint(3) UNSIGNED DEFAULT 0,
  `org_currency` varchar(5) NOT NULL DEFAULT 'CHF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `idx_user` smallint(5) UNSIGNED NOT NULL,
  `idx_token` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `rate_key` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL,
  `window_started_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `repayment_batches`
--

CREATE TABLE `repayment_batches` (
  `id_batch` smallint(5) UNSIGNED NOT NULL,
  `idx_cashier` smallint(5) UNSIGNED NOT NULL,
  `idx_organisation` smallint(5) UNSIGNED NOT NULL,
  `batch_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `repayment_receipts`
--

CREATE TABLE `repayment_receipts` (
  `id_receipt` smallint(5) UNSIGNED NOT NULL,
  `idx_repayment` smallint(5) UNSIGNED NOT NULL,
  `rec_path` varchar(256) NOT NULL,
  `rec_original_name` varchar(255) NOT NULL,
  `rec_position` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `repayment_requests`
--

CREATE TABLE `repayment_requests` (
  `id_repayment` smallint(5) UNSIGNED NOT NULL,
  `idx_user` smallint(5) UNSIGNED NOT NULL,
  `idx_organisation` smallint(5) UNSIGNED NOT NULL,
  `idx_category` smallint(5) UNSIGNED DEFAULT NULL,
  `idx_subcategory` smallint(5) UNSIGNED DEFAULT NULL,
  `rep_label` varchar(100) NOT NULL,
  `rep_amount` decimal(10,2) UNSIGNED NOT NULL,
  `rep_receipt_path` varchar(256) NOT NULL,
  `rep_transaction_date` date NOT NULL,
  `rep_repayment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `rep_status` enum('0','1','2') NOT NULL,
  `idx_batch` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subcategories`
--

CREATE TABLE `subcategories` (
  `id_subcategory` smallint(5) UNSIGNED NOT NULL,
  `idx_category` smallint(5) UNSIGNED NOT NULL,
  `sub_name` varchar(64) NOT NULL,
  `sub_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tokens`
--

CREATE TABLE `tokens` (
  `id_token` smallint(5) UNSIGNED NOT NULL,
  `tok_token` char(64) NOT NULL,
  `tok_user` smallint(5) UNSIGNED DEFAULT NULL,
  `tok_used` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `tok_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id_user` smallint(5) UNSIGNED NOT NULL,
  `use_last_name` varchar(64) NOT NULL,
  `use_first_name` varchar(64) NOT NULL,
  `use_address` varchar(256) NOT NULL,
  `use_postal_code` varchar(16) NOT NULL,
  `use_city` varchar(64) NOT NULL,
  `use_country` varchar(64) NOT NULL,
  `use_iban` varchar(64) NOT NULL,
  `use_email` varchar(128) NOT NULL,
  `use_phone` varchar(32) NOT NULL,
  `use_password` varchar(512) NOT NULL,
  `use_refund_first_name` varchar(64) NOT NULL,
  `use_refund_last_name` varchar(64) NOT NULL,
  `use_refund_address` varchar(256) NOT NULL,
  `use_refund_postal_code` varchar(16) NOT NULL,
  `use_refund_city` varchar(64) NOT NULL,
  `use_refund_country` varchar(64) NOT NULL,
  `use_active` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users_organisations`
--

CREATE TABLE `users_organisations` (
  `id_idx_user` smallint(5) UNSIGNED NOT NULL,
  `id_idx_organisation` smallint(5) UNSIGNED NOT NULL,
  `org_role` enum('admin','cashier','member') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_category`),
  ADD KEY `cat_organisation_id` (`idx_organisation`);

--
-- Index pour la table `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`id_email`),
  ADD KEY `ema_author_id` (`idx_author`),
  ADD KEY `idx_token` (`idx_token`);

--
-- Index pour la table `invitations`
--
ALTER TABLE `invitations`
  ADD PRIMARY KEY (`id_invitation`),
  ADD KEY `inv_organisation` (`idx_organisation`),
  ADD KEY `inv_user` (`idx_user`),
  ADD KEY `inv_token` (`idx_token`);

--
-- Index pour la table `organisations`
--
ALTER TABLE `organisations`
  ADD PRIMARY KEY (`id_organisation`);

--
-- Index pour la table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`idx_user`,`idx_token`),
  ADD KEY `idx_token` (`idx_token`);

--
-- Index pour la table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`rate_key`);

--
-- Index pour la table `repayment_batches`
--
ALTER TABLE `repayment_batches`
  ADD PRIMARY KEY (`id_batch`),
  ADD KEY `repayment_batches_ibfk_1` (`idx_cashier`),
  ADD KEY `repayment_batches_ibfk_2` (`idx_organisation`);

--
-- Index pour la table `repayment_receipts`
--
ALTER TABLE `repayment_receipts`
  ADD PRIMARY KEY (`id_receipt`),
  ADD UNIQUE KEY `receipt_position` (`idx_repayment`,`rec_position`);

--
-- Index pour la table `repayment_requests`
--
ALTER TABLE `repayment_requests`
  ADD PRIMARY KEY (`id_repayment`),
  ADD KEY `rep_user` (`idx_user`),
  ADD KEY `rep_organisation` (`idx_organisation`),
  ADD KEY `rep_category` (`idx_category`),
  ADD KEY `rep_subcategory` (`idx_subcategory`),
  ADD KEY `rep_batch` (`idx_batch`) USING BTREE;

--
-- Index pour la table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id_subcategory`),
  ADD KEY `sub_organisation_id` (`idx_category`);

--
-- Index pour la table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id_token`),
  ADD KEY `tok_user` (`tok_user`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- Index pour la table `users_organisations`
--
ALTER TABLE `users_organisations`
  ADD PRIMARY KEY (`id_idx_user`,`id_idx_organisation`),
  ADD KEY `use_org_organisation_id` (`id_idx_organisation`),
  ADD KEY `id_idx_user` (`id_idx_user`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_category` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `emails`
--
ALTER TABLE `emails`
  MODIFY `id_email` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `invitations`
--
ALTER TABLE `invitations`
  MODIFY `id_invitation` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `organisations`
--
ALTER TABLE `organisations`
  MODIFY `id_organisation` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `repayment_batches`
--
ALTER TABLE `repayment_batches`
  MODIFY `id_batch` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `repayment_receipts`
--
ALTER TABLE `repayment_receipts`
  MODIFY `id_receipt` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `repayment_requests`
--
ALTER TABLE `repayment_requests`
  MODIFY `id_repayment` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id_subcategory` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id_token` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `cat_organisation_id` FOREIGN KEY (`idx_organisation`) REFERENCES `organisations` (`id_organisation`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `emails`
--
ALTER TABLE `emails`
  ADD CONSTRAINT `ema_author_id` FOREIGN KEY (`idx_author`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `emails_ibfk_1` FOREIGN KEY (`idx_token`) REFERENCES `tokens` (`id_token`);

--
-- Contraintes pour la table `invitations`
--
ALTER TABLE `invitations`
  ADD CONSTRAINT `inv_organisation` FOREIGN KEY (`idx_organisation`) REFERENCES `organisations` (`id_organisation`) ON DELETE CASCADE,
  ADD CONSTRAINT `inv_token` FOREIGN KEY (`idx_token`) REFERENCES `tokens` (`id_token`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inv_user` FOREIGN KEY (`idx_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Contraintes pour la table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `password_reset_requests_ibfk_1` FOREIGN KEY (`idx_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `password_reset_requests_ibfk_2` FOREIGN KEY (`idx_token`) REFERENCES `tokens` (`id_token`);

--
-- Contraintes pour la table `repayment_batches`
--
ALTER TABLE `repayment_batches`
  ADD CONSTRAINT `repayment_batches_ibfk_1` FOREIGN KEY (`idx_cashier`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE,
  ADD CONSTRAINT `repayment_batches_ibfk_2` FOREIGN KEY (`idx_organisation`) REFERENCES `organisations` (`id_organisation`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `repayment_receipts`
--
ALTER TABLE `repayment_receipts`
  ADD CONSTRAINT `receipt_repayment` FOREIGN KEY (`idx_repayment`) REFERENCES `repayment_requests` (`id_repayment`) ON DELETE CASCADE;

--
-- Contraintes pour la table `repayment_requests`
--
ALTER TABLE `repayment_requests`
  ADD CONSTRAINT `rep_category` FOREIGN KEY (`idx_category`) REFERENCES `categories` (`id_category`) ON DELETE SET NULL,
  ADD CONSTRAINT `rep_organisation` FOREIGN KEY (`idx_organisation`) REFERENCES `organisations` (`id_organisation`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rep_subcategory` FOREIGN KEY (`idx_subcategory`) REFERENCES `subcategories` (`id_subcategory`) ON DELETE SET NULL,
  ADD CONSTRAINT `repayment_requests_ibfk_1` FOREIGN KEY (`idx_batch`) REFERENCES `repayment_batches` (`id_batch`) ON DELETE SET NULL;

--
-- Contraintes pour la table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `sub_organisation_id` FOREIGN KEY (`idx_category`) REFERENCES `categories` (`id_category`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `tokens_idfk_1` FOREIGN KEY (`tok_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Contraintes pour la table `users_organisations`
--
ALTER TABLE `users_organisations`
  ADD CONSTRAINT `use_org_organisation_id` FOREIGN KEY (`id_idx_organisation`) REFERENCES `organisations` (`id_organisation`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `use_org_user_id` FOREIGN KEY (`id_idx_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
