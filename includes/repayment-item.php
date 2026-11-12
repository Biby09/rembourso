<?php
/*
    Élément d'accordéon d'une demande de remboursement (tableau de bord et page « Mes demandes »).

    L'image de la quittance a loading="lazy" : dans un accordéon fermé (masqué), le navigateur ne la télécharge pas ;
    elle se charge à l'ouverture. La galerie (toutes les quittances) s'ouvre au clic (scripts/components/receipt-gallery.js).

    Prérequis : $request (RepaymentRequest), $accordionId (id de l'accordéon parent), $currency, $currentUserId.
    Optionnel  : $showOrganisation (true : nom de l'organisation sous le titre, tableau de bord) ;
                 $editable (true : boutons Modifier / Supprimer, data-action="edit" / "delete").
*/
$collapseId = 'repayment-' . $request->getId() . '-collapse';
$receiptCount = max(1, $request->getReceiptCount());

// « Pour » : demande saisie pour quelqu'un d'autre ; « Saisie par » : saisie par quelqu'un d'autre pour moi
$details = [
    'Pour' => $request->getUserId() !== $currentUserId ? $request->getBeneficiaryName() : null,
    'Saisie par' => $request->getCreatedBy() !== null && $request->getCreatedBy() !== $currentUserId ? $request->getCreatedByName() : null,
    'Montant' => format_amount($request->getAmount(), $currency),
    'Catégorie' => $request->getCategoryName(),
    'Sous-catégorie' => $request->getSubcategoryName(),
    "Date de l'achat" => format_date($request->getTransactionDate()),
    'Date de dépôt de la demande' => format_date($request->getRequestedAt()),
    'Date de traitement' => format_date($request->getBatchDate()),
];
?>
<div class="accordion-item" data-repayment-id="<?= $request->getId() ?>">
    <h3 class="accordion-header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
            data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/repayment-status-badge.php'; ?>
            <?= e($request->getLabel()) ?>
        </button>
    </h3>
    <div id="<?= $collapseId ?>" class="accordion-collapse collapse" data-bs-parent="#<?= e($accordionId) ?>">
        <div class="accordion-body container">
            <div class="row mb-3">
                <div class="col-lg-6 mt-3">
                    <h4><?= e($request->getLabel()) ?></h4>
                    <?php if (!empty($showOrganisation)): ?>
                        <h5 class="text-muted mb-3"><?= e($request->getOrganisationName()) ?></h5>
                    <?php endif; ?>
                    <?php foreach ($details as $label => $value): ?>
                        <?php if ($value !== null && $value !== ''): ?>
                            <p><strong><?= e($label) ?> :</strong> <?= e($value) ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="col-lg-6 d-flex justify-content-center">
                    <div class="text-center">
                        <?php if ($receiptCount > 1): ?>
                            <!-- Au-dessus de l'aperçu, sur sa propre ligne (div) : ouvre la galerie de toutes les quittances -->
                            <div class="mb-2">
                                <button type="button" class="btn btn-link btn-sm p-0 receipt-gallery-trigger"
                                    data-repayment-id="<?= $request->getId() ?>" data-receipt-count="<?= $receiptCount ?>">Voir les autres quittances (<?= $receiptCount ?>)</button>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="receipt-gallery-trigger border-0 bg-transparent p-0"
                            data-repayment-id="<?= $request->getId() ?>" data-receipt-count="<?= $receiptCount ?>">
                            <img class="receipt_image mw-100" style="max-height: 400px; object-fit: contain;"
                                src="/api/repayment/get-receipt.php?repayment_id=<?= $request->getId() ?>&amp;receipt_index=0"
                                alt="Quittance" loading="lazy">
                        </button>
                    </div>
                </div>
            </div>
            <?php if (!empty($editable)): ?>
                <div class="d-flex justify-content-start gap-2">
                    <button type="button" class="btn btn-primary" data-action="edit">Modifier</button>
                    <button type="button" class="btn btn-danger" data-action="delete">Supprimer</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
