-- Migration : demandes de remboursement urgentes
-- Date      : 2026-11-11
-- Cible     : MariaDB 10.11 (production Infomaniak)
--
-- Une demande peut être marquée « urgente » (au dépôt ou en la modifiant) :
-- les caissiers de l'organisation reçoivent alors un e-mail et la voient en tête de liste.
--
-- À exécuter AVANT de déployer le code correspondant (le nouveau code lit cette colonne ;
-- l'ancien code fonctionne avec ce schéma). Le script peut être rejoué sans erreur.

ALTER TABLE `repayment_requests`
  ADD COLUMN IF NOT EXISTS `rep_urgent` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `rep_status`;
