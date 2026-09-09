<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try{
    $user = new User($_SESSION['user_id']);
}catch(Exception $e){
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['repayment_id']) || !is_numeric($input['repayment_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid repayment ID']);
    exit;
}

try{
    $repayment = new RepaymentRequest($input['repayment_id']);
}catch(Exception $e){
    echo json_encode(['success' => false, 'error' => 'Repayment request not found']);
    exit;
}

try{
    $organisation = new Organisation($repayment->organisation_id);
}catch(Exception $e){
    echo json_encode(['success' => false, 'error' => 'Organisation not found']);
    exit;
}

//Controler que la personne soit dans l'organisation

if (!$organisation->isMember($user->id)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

//Controler que la personne soit le créateur de la demande ou un admin de l'organisation
if ($repayment->user_id !== $user->id && !$organisation->isAdmin($user->id)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}


header('Content-Type: text/html; charset=utf-8');
?>

<div class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Modifier la demande de remboursement</h5>
                <button type="button" class="btn-close btn-close-white" id="close-modal-btn" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div id="messageContainer"></div>
                <form id="edit-repayment-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <div class="mb-3">
                        <label for="label" class="form-label">Libellé</label>
                        <input type="text" class="form-control" name="label" id="label" value="<?=htmlspecialchars($repayment->label)?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Montant [<?= $organisation->currency ?>]</label>
                        <input type="number" class="form-control" name="amount" id="amount" step="0.01" value="<?=$repayment->amount?>" required max="1000000">
                    </div>
                    
                    <?php if($repayment->category !== null):
                        $currentSubcategories = $repayment->category->getSubcategories();
                    ?>
                        <div class="mb-3">
                            <label for="category" class="form-label">Catégorie</label>
                            <select class="form-select" name="category" id="category" required>
                                <option value="">Choisissez une catégorie</option>
                                <?php foreach($organisation->getCategories() as $category): ?>
                                    <option value="<?=$category->id?>" <?=$repayment->category == $category ? 'selected' : ''?>><?=htmlspecialchars($category->name)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="subcategory-container" <?= count($currentSubcategories) > 0 ? '' : 'style="display:none;"' ?>>
                            <label for="subcategory" class="form-label">Sous-catégorie</label>
                            <select class="form-select" name="subcategory" id="subcategory">
                                <option value="" <?= $repayment->subcategory === null ? 'selected' : '' ?> disabled hidden>Choisissez une sous-catégorie</option>
                                <?php foreach($currentSubcategories as $subcategory): ?>
                                    <option value="<?=$subcategory->id?>" <?=($repayment->subcategory !== null && $repayment->subcategory->id == $subcategory->id) ? 'selected' : ''?>><?=htmlspecialchars($subcategory->name)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="date" class="form-label">Date de l'achat</label>
                        <input type="date" class="form-control" name="date" id="date" value="<?=$repayment->transaction_date instanceof DateTime ? $repayment->transaction_date->format('Y-m-d') : (new DateTime($repayment->transaction_date))->format('Y-m-d')?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quittances</label>
                        <ul class="list-group mb-2" id="receipt-list">
                            <?php
                            $receipts = $repayment->getReceipts();
                            $receiptTypes = array_map(fn($receipt) => pathinfo($receipt['path'], PATHINFO_EXTENSION), $receipts);
                            $receiptTypesData = rawurlencode(json_encode($receiptTypes));
                            foreach ($receipts as $index => $receipt): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-link p-0 text-start receipt-gallery-trigger"
                                        data-repayment-id="<?= $repayment->id ?>" data-receipt-count="<?= count($receipts) ?>"
                                        data-receipt-types="<?= htmlspecialchars($receiptTypesData, ENT_QUOTES, 'UTF-8') ?>"
                                        data-receipt-index="<?= $index ?>"><?= htmlspecialchars($receipt['original_name']) ?></button>
                                    <input type="hidden" name="retained_receipt_ids[]" value="<?= $receipt['id'] ?? 'legacy' ?>">
                                    <button type="button" class="btn-close receipt-remove" aria-label="Supprimer"></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="file" class="d-none" name="receipts[]" id="receipts" accept="image/*,application/pdf" multiple>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-receipts-btn">Ajouter des quittances</button>
                    </div>
                    
                    <input type="hidden" name="repayment_id" value="<?=$repayment->id?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-repayment-btn">Annuler</button>
                <button type="button" class="btn btn-primary" id="save-repayment-btn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
