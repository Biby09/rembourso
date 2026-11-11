<?php
/*
    Modal de modification d'une demande de remboursement (renvoyé par api/repayment/edit-dialog.php,
    comportement : scripts/components/repayment-edit-dialog.js).

    Prérequis : $request (RepaymentRequest), $organisation, $receipts (RepaymentRequestManager::getReceipts),
                $categories (Category[], vide si l'organisation n'utilise pas de catégories),
                $subcategories (Subcategory[] de la catégorie de la demande).
*/
?>
<div class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Modifier la demande de remboursement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div data-edit-message></div>
                <form id="edit-repayment-form">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <input type="hidden" name="repayment_id" value="<?= $request->getId() ?>">

                    <div class="mb-3">
                        <label for="edit-label" class="form-label">Libellé</label>
                        <input type="text" class="form-control" name="label" id="edit-label" value="<?= e($request->getLabel()) ?>" maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-amount" class="form-label">Montant [<?= e($organisation->getCurrency()) ?>]</label>
                        <input type="number" class="form-control" name="amount" id="edit-amount" step="0.01" min="0.01" max="99999999.99" value="<?= e($request->getAmount()) ?>" required>
                    </div>

                    <?php if ($categories): ?>
                        <div class="mb-3">
                            <label for="edit-category" class="form-label">Catégorie</label>
                            <select class="form-select" name="category" id="edit-category" required>
                                <option value="">Choisissez une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category->getId() ?>" <?= $request->getCategoryId() === $category->getId() ? 'selected' : '' ?>><?= e($category->getName()) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($organisation->areSubcategoriesEnabled()): ?>
                            <div class="mb-3<?= $subcategories ? '' : ' d-none' ?>" data-subcategory-container>
                                <label for="edit-subcategory" class="form-label">Sous-catégorie</label>
                                <select class="form-select" name="subcategory" id="edit-subcategory">
                                    <option value="" <?= $request->getSubcategoryId() === null ? 'selected' : '' ?> disabled hidden>Choisissez une sous-catégorie</option>
                                    <?php foreach ($subcategories as $subcategory): ?>
                                        <option value="<?= $subcategory->getId() ?>" <?= $request->getSubcategoryId() === $subcategory->getId() ? 'selected' : '' ?>><?= e($subcategory->getName()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="edit-date" class="form-label">Date de l'achat</label>
                        <input type="date" class="form-control" name="date" id="edit-date" value="<?= e($request->getTransactionDate()) ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>

                    <?php $urgentFieldId = 'edit-urgent'; $urgentChecked = $request->isUrgent(); include $_SERVER['DOCUMENT_ROOT'] . '/includes/urgent-field.php'; ?>

                    <div class="mb-3">
                        <label class="form-label">Quittances</label>
                        <ul class="list-group mb-2" data-receipt-list>
                            <?php foreach ($receipts as $index => $receipt): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-link p-0 text-start receipt-gallery-trigger"
                                        data-repayment-id="<?= $request->getId() ?>" data-receipt-count="<?= count($receipts) ?>"
                                        data-receipt-index="<?= $index ?>"><?= e($receipt['original_name']) ?></button>
                                    <input type="hidden" name="retained_receipt_ids[]" value="<?= $receipt['id'] ?? 'legacy' ?>">
                                    <button type="button" class="btn-close" data-receipt-remove aria-label="Supprimer"></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="file" class="d-none" name="receipts[]" data-receipt-input accept="image/png,image/jpeg,application/pdf" multiple>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-receipts>Ajouter des quittances</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary" form="edit-repayment-form">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
