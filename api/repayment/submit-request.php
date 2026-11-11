<?php
// Dépôt d'une demande de remboursement (page « Nouvelle demande », scripts/pages/organisation/submit.js)
// POST (multipart) : organisation-id, label, amount, date, category, subcategory, receipts[], urgent (« 1 » : prévient les caissiers)
// Champ « beneficiary » (caissiers et admins uniquement) :
// - absent ou « self » : pour soi ;
// - identifiant d'un membre : pour ce membre de l'organisation (avec ou sans compte) ;
// - « new » + person[...] : pour une nouvelle personne sans compte, ajoutée à l'organisation.

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/repayment-api.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/accountless.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

api_require_method('POST');
$input = api_form_input();
$organisation = api_organisation($organisationManager, $input['organisation-id'] ?? null);

[$label, $amount, $date] = repayment_fields_input($input);
[$category, $subcategory] = repayment_category_input(new CategoryManager($mysqlClient, $organisation), $organisation, $input);

if (empty($_FILES['receipts'])) {
    json_error('Veuillez joindre au moins une quittance.');
}

// Bénéficiaire de la demande
$beneficiaryMode = (string) ($input['beneficiary'] ?? 'self');
$beneficiary = $user;
$newPerson = null;

if ($beneficiaryMode !== 'self') {
    if (!$organisationManager->isCashier($organisation)) {
        json_error('Accès refusé.', 403);
    }

    if ($beneficiaryMode === 'new') {
        $newPerson = accountless_person_input($input['person'] ?? null);
        accountless_assert_email_usable($newPerson['email'], $organisation, $organisationManager, $userManager);
    } else {
        $beneficiary = api_find(fn() => $userManager->getUserById(api_id($beneficiaryMode, 'Bénéficiaire introuvable.')), 'Bénéficiaire introuvable.');
        if (!$beneficiary->isActive() || !$organisationManager->isMember($organisation, $beneficiary->getId())) {
            json_error("Le bénéficiaire ne fait pas partie de l'organisation.");
        }
    }
}

try {
    $receipts = store_uploaded_receipts($_FILES['receipts']);
} catch (ReceiptUploadException $e) {
    json_error($e->getMessage());
}
if (!$receipts) {
    json_error('Au moins une quittance est requise.');
}

$repaymentRequestManager = new RepaymentRequestManager($mysqlClient, $organisation, $user);

// Nouvelle personne + demande : tout ou rien
$mysqlClient->beginTransaction();
try {
    if ($newPerson !== null) {
        $beneficiary = $userManager->createAccountless($newPerson);
        $organisationManager->addMember($organisation, $beneficiary->getId(), 'member');
    }

    $request = $repaymentRequestManager->createRequest($beneficiary->getId(), $label, $amount, $date, $receipts, $category?->getId(), $subcategory?->getId(), repayment_urgent_input($input));
    $mysqlClient->commit();
} catch (Exception $e) {
    $mysqlClient->rollBack();
    delete_receipt_files(array_column($receipts, 'path'));
    log_server_exception($e, "Erreur lors de la création d'une demande de remboursement");
    json_error('Erreur lors de la création de la demande de remboursement.', 500);
}

// E-mail aux caissiers après l'enregistrement (la demande existe quand ils cliquent sur le lien)
if ($request->isUrgent()) {
    repayment_notify_urgent($organisationManager, $repaymentRequestManager, $organisation, $user, $request);
}

// La nouvelle personne est renvoyée pour pouvoir la resélectionner sans recharger la page
json_success($newPerson !== null ? ['beneficiary' => ['id' => $beneficiary->getId(), 'name' => $beneficiary->getFullName()]] : []);
