<?php
/*
    Choix de la devise de l'organisation (page Paramètres et assistant de création, étape 1).
    Enregistrée dès le changement par scripts/pages/organisation/currency.js.
    Prérequis : $organisation.
*/
?>
<div id="currency-message"></div>
<label for="organisation-currency" class="form-label">Devise des remboursements</label>
<select id="organisation-currency" class="form-select" style="max-width: 20rem;">
    <?php foreach (Organisation::CURRENCIES as $code => $label): ?>
        <option value="<?= e($code) ?>" <?= $organisation->getCurrency() === $code ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
</select>
<small class="form-text text-muted">Les QR-factures de paiement sont générées dans cette devise.</small>
