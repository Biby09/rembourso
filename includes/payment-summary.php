<?php
/*
    Récapitulatif du paiement affiché au caissier avant validation : bénéficiaire, montant et QR-facture
    (renvoyé par api/repayment/process.php, placé dans le modal de scripts/pages/organisation/cashier.js).

    Prérequis : $beneficiary (User), $amount (float), $currency, $qrCodeDataUri (image du code QR).
*/
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Bénéficiaire</h6>
            <p class="mb-0"><?= e($beneficiary->getRefundFullName()) ?></p>
            <small class="text-muted"><?= e($beneficiary->getRefundAddress()) ?></small><br>
            <small class="text-muted"><?= e($beneficiary->getRefundPostalCode() . ' ' . $beneficiary->getRefundCity()) ?></small><br>
            <small class="text-muted"><?= e(iban_to_human_format($beneficiary->getIban())) ?></small>
            <div class="mt-4 pt-3 border-top">
                <h6 class="fw-bold">Montant à rembourser</h6>
                <h4 class="text-primary"><?= e(format_amount($amount, $currency)) ?></h4>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <h6 class="fw-bold mb-3">Code QR</h6>
            <img src="<?= e($qrCodeDataUri) ?>" alt="QR Code de paiement" class="img-fluid" style="max-width: 200px;">
        </div>
    </div>
</div>
