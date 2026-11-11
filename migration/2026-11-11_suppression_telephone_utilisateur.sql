-- Migration : suppression du numéro de téléphone des utilisateurs
-- Date      : 2026-11-11
-- Cible     : MariaDB 10.11 (production Infomaniak)
--
-- Le champ téléphone n'est plus demandé à l'inscription ni modifiable dans « Mon compte ».
-- À exécuter AU MOMENT du déploiement du code correspondant : l'ancien code
-- écrit encore dans `use_phone` et échouerait une fois la colonne supprimée.
--
-- ⚠️ Opération irréversible : les numéros existants sont définitivement perdus.
--    Faire une sauvegarde de la table `users` avant exécution.
--
-- Le script peut être rejoué sans erreur (IF EXISTS).

ALTER TABLE `users` DROP COLUMN IF EXISTS `use_phone`;
