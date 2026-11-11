<?php
/**
 * Champs d'une personne sans compte (noms « person[...] »).
 * Variables :
 * - $personIdPrefix : préfixe des id (pour pouvoir inclure le partiel deux fois) ;
 * - $personFormId   : id du formulaire auquel rattacher les champs quand ils sont
 *                     placés hors de celui-ci (ex. dans une modale), ou null.
 * Lus côté serveur par accountless_person_input() (config/accountless.php).
 */
$personIdPrefix ??= 'person';
$personFormId ??= null;
$fieldId = fn(string $name) => htmlspecialchars($personIdPrefix . '-' . $name, ENT_QUOTES, 'UTF-8');
$formAttr = $personFormId ? ' form="' . htmlspecialchars($personFormId, ENT_QUOTES, 'UTF-8') . '"' : '';
?>
<div class="row">
    <div class="mb-3 col-md-6">
        <label for="<?= $fieldId('first_name') ?>" class="form-label">Prénom</label>
        <input type="text" class="form-control" id="<?= $fieldId('first_name') ?>" <?= $formAttr ?> name="person[first_name]" maxlength="64" required>
    </div>
    <div class="mb-3 col-md-6">
        <label for="<?= $fieldId('last_name') ?>" class="form-label">Nom</label>
        <input type="text" class="form-control" id="<?= $fieldId('last_name') ?>" <?= $formAttr ?> name="person[last_name]" maxlength="64" required>
    </div>
</div>
<div class="mb-3">
    <label for="<?= $fieldId('address') ?>" class="form-label">Rue et n°</label>
    <input type="text" class="form-control" id="<?= $fieldId('address') ?>" <?= $formAttr ?> name="person[address]" maxlength="256" required>
</div>
<div class="row">
    <div class="mb-3 col-md-4">
        <label for="<?= $fieldId('postal_code') ?>" class="form-label">Code postal</label>
        <input type="text" class="form-control" id="<?= $fieldId('postal_code') ?>" <?= $formAttr ?> name="person[postal_code]" maxlength="16" required>
    </div>
    <div class="mb-3 col-md-4">
        <label for="<?= $fieldId('city') ?>" class="form-label">Ville</label>
        <input type="text" class="form-control" id="<?= $fieldId('city') ?>" <?= $formAttr ?> name="person[city]" maxlength="64" required>
    </div>
    <div class="mb-3 col-md-4">
        <label for="<?= $fieldId('country') ?>" class="form-label">Pays</label>
        <select class="form-select country-select" id="<?= $fieldId('country') ?>" <?= $formAttr ?> name="person[country]" data-country="CH" required>
            <option value="">Sélectionnez un pays</option>
        </select>
    </div>
</div>
<div class="row">
    <div class="mb-3 col-md-6">
        <label for="<?= $fieldId('iban') ?>" class="form-label">IBAN</label>
        <input type="text" class="form-control" id="<?= $fieldId('iban') ?>" <?= $formAttr ?> name="person[iban]" maxlength="64" placeholder="CH00 0000 0000 0000 0000 0" required>
    </div>
    <div class="mb-3 col-md-6">
        <label for="<?= $fieldId('email') ?>" class="form-label">E-mail <span class="text-muted">(facultatif)</span></label>
        <input type="email" class="form-control" id="<?= $fieldId('email') ?>" <?= $formAttr ?> name="person[email]" maxlength="128">
        <small class="text-muted">Nécessaire pour lui envoyer un lien de création de compte.</small>
    </div>
</div>
