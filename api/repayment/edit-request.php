<?php
// Enregistre la modification d'une demande (modal de modification, scripts/components/repayment-edit-dialog.js)
// POST (multipart) : repayment_id, label, amount, date, category, subcategory, retained_receipt_ids[], receipts[], urgent

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/repayment-api.php';

api_require_method('POST');
$input = api_form_input();
[$organisation, $repaymentRequestManager, $request] = repayment_load_editable($organisationManager, $mysqlClient, $user, $input['repayment_id'] ?? null);

[$label, $amount, $date] = repayment_fields_input($input);
[$category, $subcategory] = repayment_category_input(new CategoryManager($mysqlClient, $organisation), $organisation, $input);

// Quittances conservées (« legacy » : ancienne quittance unique sans identifiant) + nouvelles quittances
$retainedIds = array_map('strval', (array) ($input['retained_receipt_ids'] ?? []));
$receipts = [];
foreach ($repaymentRequestManager->getReceipts($request) as $receipt) {
    if (in_array($receipt['id'] === null ? 'legacy' : (string) $receipt['id'], $retainedIds, true)) {
        $receipts[] = ['path' => $receipt['path'], 'original_name' => $receipt['original_name']];
    }
}

try {
    $newReceipts = store_uploaded_receipts($_FILES['receipts'] ?? null);
} catch (ReceiptUploadException $e) {
    json_error($e->getMessage());
}
$receipts = array_merge($receipts, $newReceipts);

if (!$receipts) {
    json_error('Au moins une quittance est requise.');
}

$wasUrgent = $request->isUrgent();

try {
    $request = $repaymentRequestManager->updateRequest($request, $label, $amount, $date, $category?->getId(), $subcategory?->getId(), $receipts, repayment_urgent_input($input));
} catch (Exception $e) {
    delete_receipt_files(array_column($newReceipts, 'path'));
    log_server_exception($e, "Erreur lors de la modification d'une demande de remboursement");
    json_error("Erreur lors de l'enregistrement de la demande.", 500);
}

// E-mail aux caissiers seulement quand la demande DEVIENT urgente (pas à chaque modification)
if ($request->isUrgent() && !$wasUrgent) {
    repayment_notify_urgent($organisationManager, $repaymentRequestManager, $organisation, $user, $request);
}

json_success(['updated_repayment' => $repaymentRequestManager->toArray($request), 'currency' => $organisation->getCurrency()]);
