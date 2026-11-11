-- Migration : coordonnées de paiement enregistrées dans chaque lot de remboursement
-- Date      : 2026-11-11
-- Cible     : MariaDB 10.11 (production Infomaniak)
--
-- Au moment de la validation d'un lot, on copie les coordonnées du bénéficiaire du paiement
-- (nom, adresse, IBAN) : la confirmation PDF affiche ainsi celles réellement utilisées,
-- même si le membre les modifie plus tard. Les lots existants restent à NULL : leur PDF
-- affiche les coordonnées actuelles du membre, avec une mention.
--
-- À exécuter AVANT de déployer le code correspondant. Le script peut être rejoué sans erreur.

ALTER TABLE `repayment_batches`
  ADD COLUMN IF NOT EXISTS `batch_payee_name` varchar(130) DEFAULT NULL AFTER `batch_date`,
  ADD COLUMN IF NOT EXISTS `batch_payee_address` varchar(256) DEFAULT NULL AFTER `batch_payee_name`,
  ADD COLUMN IF NOT EXISTS `batch_payee_postal_code` varchar(16) DEFAULT NULL AFTER `batch_payee_address`,
  ADD COLUMN IF NOT EXISTS `batch_payee_city` varchar(64) DEFAULT NULL AFTER `batch_payee_postal_code`,
  ADD COLUMN IF NOT EXISTS `batch_payee_country` varchar(64) DEFAULT NULL AFTER `batch_payee_city`,
  ADD COLUMN IF NOT EXISTS `batch_payee_iban` varchar(64) DEFAULT NULL AFTER `batch_payee_country`;
