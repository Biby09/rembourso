<?php
/**
 * Code commun aux endpoints des demandes de remboursement (api/repayment/) :
 * chargement d'une demande modifiable et validation des champs (dépôt et modification).
 * Répond par une erreur JSON en cas de problème. À inclure après config/api-auth.php.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

/**
 * Demande que l'utilisateur connecté peut modifier ou supprimer (en attente, et dont il est le bénéficiaire
 * ou l'auteur de la saisie) ; sinon 404 ou 403.
 *
 * @return array{0: Organisation, 1: RepaymentRequestManager, 2: RepaymentRequest}
 */
function repayment_load_editable(OrganisationManager $organisationManager, PDO $pdo, User $user, mixed $id): array
{
    $requestId = api_id($id, 'Demande de remboursement non trouvée.');
    $organisation = api_find(fn() => $organisationManager->getOrganisationOfRequest($requestId), 'Demande de remboursement non trouvée.');
    $repaymentRequestManager = new RepaymentRequestManager($pdo, $organisation, $user);
    $request = api_find(fn() => $repaymentRequestManager->getRequestById($requestId), 'Demande de remboursement non trouvée.');

    if (!$repaymentRequestManager->isEditable($request)) {
        json_error('Seule une demande en attente peut être modifiée, par son bénéficiaire ou par la personne qui l\'a saisie.', 403);
    }
    return [$organisation, $repaymentRequestManager, $request];
}

/**
 * Libellé, montant (texte décimal « 42.50 ») et date de la dépense (AAAA-MM-JJ, pas dans le futur).
 *
 * @return array{0: string, 1: string, 2: string} [libellé, montant, date]
 */
function repayment_fields_input(array $input): array
{
    $label = trim((string) ($input['label'] ?? ''));
    $amount = $input['amount'] ?? null;
    $date = parse_date((string) ($input['date'] ?? ''));

    if ($label === '' || mb_strlen($label) > 100) {
        json_error('Le libellé est requis (100 caractères maximum).');
    }
    if (!is_numeric($amount) || (float) $amount <= 0 || (float) $amount > 99999999.99) {
        json_error('Montant invalide.');
    }
    if ($date === null) {
        json_error('Date invalide.');
    }
    if ($date > date('Y-m-d')) {
        json_error('La date de la dépense ne peut pas être dans le futur.');
    }

    return [$label, number_format((float) $amount, 2, '.', ''), $date];
}

/**
 * Catégorie et sous-catégorie choisies, si l'organisation les utilise (null sinon).
 * La sous-catégorie n'est exigée que si la catégorie en a.
 *
 * @return array{0: ?Category, 1: ?Subcategory}
 */
function repayment_category_input(CategoryManager $categoryManager, Organisation $organisation, array $input): array
{
    if (!$organisation->areCategoriesEnabled() || !$categoryManager->getCategories()) {
        return [null, null];
    }

    $category = api_find(fn() => $categoryManager->getCategoryById(api_id($input['category'] ?? null, 'Catégorie invalide.')), 'Catégorie invalide.');

    $subcategory = null;
    if ($organisation->areSubcategoriesEnabled() && $categoryManager->getSubcategories($category)) {
        $subcategory = api_find(fn() => $categoryManager->getSubcategoryById(api_id($input['subcategory'] ?? null, 'Sous-catégorie invalide.')), 'Sous-catégorie invalide.');
        if ($subcategory->getCategoryId() !== $category->getId()) {
            json_error('Sous-catégorie invalide.');
        }
    }

    return [$category, $subcategory];
}

/**
 * Case « Urgent » du formulaire (cochée : valeur « 1 »).
 */
function repayment_urgent_input(array $input): bool
{
    return ($input['urgent'] ?? '') === '1';
}

/**
 * Prévient les caissiers qu'une demande vient d'être marquée urgente.
 * Une erreur d'envoi est journalisée sans faire échouer l'enregistrement de la demande.
 */
function repayment_notify_urgent(OrganisationManager $organisationManager, RepaymentRequestManager $repaymentRequestManager, Organisation $organisation, User $user, RepaymentRequest $request): void
{
    try {
        $repaymentRequestManager->notifyUrgentRequest($request, $organisationManager->getCashierEmails($organisation, $user->getId()));
    } catch (Exception $e) {
        log_server_exception($e, "Erreur lors de l'envoi de l'e-mail d'une demande urgente");
    }
}
