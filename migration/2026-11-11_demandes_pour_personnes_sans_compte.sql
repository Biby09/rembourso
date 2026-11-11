-- Migration : demandes de remboursement créées par un caissier/admin pour une autre personne
-- Date      : 2026-11-11
-- Cible     : MariaDB 10.11 (production Infomaniak)
--
-- - Les personnes « sans compte » sont stockées dans `users` avec use_registered = 0,
--   sans mot de passe et avec un e-mail facultatif.
-- - `repayment_requests.idx_created_by` mémorise qui a saisi la demande (NULL = le bénéficiaire lui-même).
-- - `account_claims` relie un lien de création de compte (token) à une personne sans compte.
--
-- À exécuter AVANT de déployer le code correspondant (le nouveau code lit ces colonnes ;
-- l'ancien code continue de fonctionner avec ce schéma).
-- Le script peut être rejoué sans erreur.

ALTER TABLE `users`
  MODIFY `use_email` varchar(128) DEFAULT NULL,
  MODIFY `use_password` varchar(512) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `use_registered` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 AFTER `use_active`;

ALTER TABLE `repayment_requests`
  ADD COLUMN IF NOT EXISTS `idx_created_by` smallint(5) UNSIGNED DEFAULT NULL AFTER `idx_user`,
  ADD KEY IF NOT EXISTS `rep_created_by` (`idx_created_by`);

ALTER TABLE `repayment_requests`
  DROP FOREIGN KEY IF EXISTS `rep_created_by`;
ALTER TABLE `repayment_requests`
  ADD CONSTRAINT `rep_created_by` FOREIGN KEY (`idx_created_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `account_claims` (
  `idx_user` smallint(5) UNSIGNED NOT NULL,
  `idx_token` smallint(5) UNSIGNED NOT NULL,
  `idx_organisation` smallint(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`idx_user`, `idx_token`),
  KEY `acc_token` (`idx_token`),
  KEY `acc_organisation` (`idx_organisation`),
  CONSTRAINT `acc_user` FOREIGN KEY (`idx_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `acc_token` FOREIGN KEY (`idx_token`) REFERENCES `tokens` (`id_token`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `acc_organisation` FOREIGN KEY (`idx_organisation`) REFERENCES `organisations` (`id_organisation`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
