<?php
/*
    Case « Demande urgente » des formulaires de demande (dépôt et modification) :
    cochée, elle envoie un e-mail aux caissiers quand la demande devient urgente (api/repayment/…).

    Optionnel : $urgentFieldId (id du champ, 'urgent' par défaut), $urgentChecked (true : case cochée).
*/
$urgentFieldId ??= 'urgent';
?>
<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" role="switch" name="urgent" value="1" id="<?= e($urgentFieldId) ?>" <?= !empty($urgentChecked) ? 'checked' : '' ?>>
    <label class="form-check-label" for="<?= e($urgentFieldId) ?>">Demande urgente</label>
    <small class="form-text text-muted d-block">Les caissiers de l'organisation sont prévenus par e-mail.</small>
</div>
