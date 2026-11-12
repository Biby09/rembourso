<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequest.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/database/transactional.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/receipts.php';

/**
 * Demandes de remboursement d'UNE organisation, vues par l'utilisateur connecté ($user) :
 * « mes demandes » sont celles dont il est le bénéficiaire ou qu'il a saisies pour quelqu'un d'autre.
 */
class RepaymentRequestManager
{
    use Transactional;

    public const PAGE_SIZE = 10;

    /**
     * Colonnes de la demande et colonnes jointes (voir RepaymentRequest::hydrate).
     * receipt_count : nombre de quittances ; une ancienne demande sans ligne dans repayment_receipts
     * a une seule quittance, dans la colonne rep_receipt_path.
     */
    private const COLUMNS = "r.*, c.cat_name, s.sub_name, b.batch_date,
            CONCAT(u.use_first_name, ' ', u.use_last_name) AS beneficiary_name,
            CONCAT(cb.use_first_name, ' ', cb.use_last_name) AS created_by_name,
            COALESCE(NULLIF((SELECT COUNT(*) FROM repayment_receipts rr WHERE rr.idx_repayment = r.id_repayment), 0),
                IF(r.rep_receipt_path IS NULL OR r.rep_receipt_path = '', 0, 1)) AS receipt_count";
    private const JOINS = 'FROM repayment_requests r
        JOIN users u ON u.id_user = r.idx_user
        LEFT JOIN users cb ON cb.id_user = r.idx_created_by
        LEFT JOIN categories c ON c.id_category = r.idx_category
        LEFT JOIN subcategories s ON s.id_subcategory = r.idx_subcategory
        LEFT JOIN repayment_batches b ON b.id_batch = r.idx_batch';
    private const SELECT = 'SELECT ' . self::COLUMNS . ' ' . self::JOINS;

    private PDO $pdo;
    private Organisation $organisation;
    private User $user;

    public function __construct(PDO $pdo, Organisation $organisation, User $user)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
        $this->user = $user;
    }

    /**
     * Demande de l'organisation (exception si elle n'existe pas ou appartient à une autre organisation).
     */
    public function getRequestById(int $id): RepaymentRequest
    {
        $requests = $this->fetchRequests(self::SELECT . ' WHERE r.id_repayment = :id AND r.idx_organisation = :organisation',
            ['id' => $id, 'organisation' => $this->organisation->getId()]);

        if (!$requests) {
            throw new Exception('Repayment request not found with ID: ' . $id);
        }
        return $requests[0];
    }

    /**
     * « Mes demandes » (page « Mes demandes »), les plus récentes d'abord, par page de PAGE_SIZE (null = toutes).
     */
    public function getMyRequests(?int $page = null): array
    {
        $sql = self::SELECT . ' WHERE r.idx_organisation = :organisation AND (r.idx_user = :user OR r.idx_created_by = :creator)
            ORDER BY r.rep_repayment_date DESC';
        $params = ['organisation' => $this->organisation->getId(), 'user' => $this->user->getId(), 'creator' => $this->user->getId()];

        if ($page !== null) {
            // LIMIT/OFFSET : entiers calculés ici (jamais une saisie), insérés directement dans la requête
            $sql .= ' LIMIT ' . self::PAGE_SIZE . ' OFFSET ' . (max(1, $page) - 1) * self::PAGE_SIZE;
        }

        return $this->fetchRequests($sql, $params);
    }

    public function countMyRequests(): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM repayment_requests WHERE idx_organisation = :organisation AND (idx_user = :user OR idx_created_by = :creator)');
        $stmt->execute(['organisation' => $this->organisation->getId(), 'user' => $this->user->getId(), 'creator' => $this->user->getId()]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Demandes en attente d'un membre (traitement par un caissier).
     */
    public function getPendingRequestsOfMember(int $userId): array
    {
        return $this->fetchRequests(self::SELECT . " WHERE r.idx_organisation = :organisation AND r.idx_user = :user AND r.rep_status = '0'
            ORDER BY r.rep_urgent DESC, r.rep_repayment_date DESC", ['organisation' => $this->organisation->getId(), 'user' => $userId]);
    }

    /**
     * Membres ayant des demandes en attente (page « À rembourser »), ceux qui ont une demande urgente d'abord :
     * [['id' => …, 'full_name' => …, 'request_count' => …, 'urgent_count' => …, 'total_pending' => …], ...]
     */
    public function getMembersWithPendingRequests(): array
    {
        $stmt = $this->pdo->prepare("SELECT r.idx_user AS id, CONCAT(u.use_first_name, ' ', u.use_last_name) AS full_name,
                COUNT(*) AS request_count, SUM(r.rep_urgent) AS urgent_count, SUM(r.rep_amount) AS total_pending
            FROM repayment_requests r JOIN users u ON u.id_user = r.idx_user
            WHERE r.idx_organisation = :organisation AND r.rep_status = '0'
            GROUP BY r.idx_user, u.use_first_name, u.use_last_name
            ORDER BY SUM(r.rep_urgent) > 0 DESC, u.use_last_name, u.use_first_name");
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Demandes d'un lot de remboursement.
     */
    public function getRequestsOfBatch(int $batchId): array
    {
        return $this->fetchRequests(self::SELECT . ' WHERE r.idx_batch = :batch AND r.idx_organisation = :organisation ORDER BY r.rep_repayment_date',
            ['batch' => $batchId, 'organisation' => $this->organisation->getId()]);
    }

    /**
     * Dernières demandes de l'utilisateur, toutes organisations confondues (tableau de bord),
     * avec le nom et la devise de leur organisation.
     * static : pas limité à une organisation, RepaymentRequestManager::getLatestRequestsOfUser($pdo, $user, 5)
     */
    public static function getLatestRequestsOfUser(PDO $pdo, User $user, int $limit): array
    {
        $stmt = $pdo->prepare('SELECT ' . self::COLUMNS . ', o.org_name, o.org_currency ' . self::JOINS . '
            JOIN organisations o ON o.id_organisation = r.idx_organisation
            WHERE r.idx_user = :user ORDER BY r.rep_repayment_date DESC LIMIT ' . max(1, $limit));
        $stmt->execute(['user' => $user->getId()]);

        $requests = [];
        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $request = new RepaymentRequest();
            $request->hydrate($ligne);
            $requests[] = $request;
        }
        return $requests;
    }

    /**
     * Crée une demande. Si le bénéficiaire n'est pas l'utilisateur connecté, celui-ci est enregistré
     * comme auteur de la saisie. $receipts : [['path' => …, 'original_name' => …], ...] (au moins une).
     */
    public function createRequest(int $beneficiaryId, string $label, string $amount, string $transactionDate, array $receipts, ?int $categoryId, ?int $subcategoryId, bool $urgent = false): RepaymentRequest
    {
        if (!$receipts) {
            throw new Exception('At least one receipt is required');
        }

        $id = $this->transaction(function () use ($beneficiaryId, $label, $amount, $transactionDate, $receipts, $categoryId, $subcategoryId, $urgent) {
            $stmt = $this->pdo->prepare("INSERT INTO repayment_requests (idx_user, idx_created_by, idx_organisation, rep_label, rep_amount, rep_transaction_date,
                    rep_receipt_path, idx_category, idx_subcategory, rep_status, rep_urgent)
                VALUES (:user, :created_by, :organisation, :label, :amount, :transaction_date, :receipt_path, :category, :subcategory, '0', :urgent)");
            $stmt->execute([
                'user' => $beneficiaryId,
                'created_by' => $beneficiaryId !== $this->user->getId() ? $this->user->getId() : null,
                'organisation' => $this->organisation->getId(),
                'label' => $label,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'receipt_path' => $receipts[0]['path'],
                'category' => $categoryId,
                'subcategory' => $subcategoryId,
                'urgent' => (int) $urgent,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->insertReceipts($id, $receipts);
            return $id;
        });

        return $this->getRequestById($id);
    }

    /**
     * Modifie une demande et remplace ses quittances par $receipts (anciennes conservées + nouvelles).
     * Les fichiers des quittances retirées sont effacés après l'enregistrement.
     */
    public function updateRequest(RepaymentRequest $request, string $label, string $amount, string $transactionDate, ?int $categoryId, ?int $subcategoryId, array $receipts, bool $urgent): RepaymentRequest
    {
        if (!$receipts) {
            throw new Exception('At least one receipt is required');
        }

        $previousPaths = array_column($this->getReceipts($request), 'path');

        $this->transaction(function () use ($request, $label, $amount, $transactionDate, $categoryId, $subcategoryId, $receipts, $urgent) {
            $stmt = $this->pdo->prepare('UPDATE repayment_requests SET rep_label = :label, rep_amount = :amount, rep_transaction_date = :transaction_date,
                    idx_category = :category, idx_subcategory = :subcategory, rep_receipt_path = :receipt_path, rep_urgent = :urgent
                WHERE id_repayment = :id AND idx_organisation = :organisation');
            $stmt->execute([
                'label' => $label,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'category' => $categoryId,
                'subcategory' => $subcategoryId,
                'receipt_path' => $receipts[0]['path'],
                'urgent' => (int) $urgent,
                'id' => $request->getId(),
                'organisation' => $this->organisation->getId(),
            ]);

            $this->pdo->prepare('DELETE FROM repayment_receipts WHERE idx_repayment = :id')->execute(['id' => $request->getId()]);
            $this->insertReceipts($request->getId(), $receipts);
        });

        delete_receipt_files(array_diff($previousPaths, array_column($receipts, 'path')));

        return $this->getRequestById($request->getId());
    }

    public function deleteRequest(RepaymentRequest $request): void
    {
        $paths = array_column($this->getReceipts($request), 'path');

        $stmt = $this->pdo->prepare('DELETE FROM repayment_requests WHERE id_repayment = :id AND idx_organisation = :organisation');
        $stmt->execute(['id' => $request->getId(), 'organisation' => $this->organisation->getId()]);

        delete_receipt_files($paths);
    }

    /**
     * Quittances, dans l'ordre d'ajout : [['id' => ?int, 'path' => …, 'original_name' => …], ...]
     * (id null : ancienne quittance unique, stockée dans rep_receipt_path)
     */
    public function getReceipts(RepaymentRequest $request): array
    {
        $stmt = $this->pdo->prepare('SELECT id_receipt, rec_path, rec_original_name FROM repayment_receipts WHERE idx_repayment = :id ORDER BY rec_position');
        $stmt->execute(['id' => $request->getId()]);
        $receipts = array_map(fn(array $row) => [
            'id' => (int) $row['id_receipt'],
            'path' => $row['rec_path'],
            'original_name' => $row['rec_original_name'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));

        if (!$receipts && $request->getLegacyReceiptPath()) {
            $receipts[] = ['id' => null, 'path' => $request->getLegacyReceiptPath(), 'original_name' => basename($request->getLegacyReceiptPath())];
        }
        return $receipts;
    }

    /**
     * Modifiable (et supprimable) tant qu'elle est en attente, par le bénéficiaire ou par la personne qui l'a saisie.
     */
    public function isEditable(RepaymentRequest $request): bool
    {
        $userId = $this->user->getId();
        return $request->isPending() && ($request->getUserId() === $userId || $request->getCreatedBy() === $userId);
    }

    /**
     * Données envoyées au JavaScript (JSON). Les textes ne sont pas échappés :
     * l'échappement HTML est fait à l'affichage (scripts/core/dom.js).
     */
    public function toArray(RepaymentRequest $request): array
    {
        $userId = $this->user->getId();

        return [
            'id' => $request->getId(),
            'user_id' => $request->getUserId(),
            'organisation_id' => $request->getOrganisationId(),
            'label' => $request->getLabel(),
            'category' => $request->getCategoryId() ? ['id' => $request->getCategoryId(), 'name' => $request->getCategoryName()] : null,
            'subcategory' => $request->getSubcategoryId() ? ['id' => $request->getSubcategoryId(), 'name' => $request->getSubcategoryName()] : null,
            'amount' => $request->getAmount(),
            'status' => $request->getStatus(),
            'urgent' => $request->isUrgent(),
            'transaction_date' => $request->getTransactionDate(),
            'requested_at' => substr($request->getRequestedAt(), 0, 10),
            'batch_date' => $request->getBatchDate() ? substr($request->getBatchDate(), 0, 10) : null,
            'editable' => $this->isEditable($request),
            'receipt_count' => $request->getReceiptCount(),
            // Demande saisie pour quelqu'un d'autre : nom du bénéficiaire
            'beneficiary_name' => $request->getUserId() !== $userId ? $request->getBeneficiaryName() : null,
            // Demande saisie par quelqu'un d'autre pour l'utilisateur connecté : nom de l'auteur
            'created_by_name' => $request->getCreatedBy() !== null && $request->getCreatedBy() !== $userId ? $request->getCreatedByName() : null,
        ];
    }

    /**
     * Prévient les caissiers par e-mail qu'une demande est urgente (à appeler quand elle le DEVIENT).
     * $recipients : adresses des caissiers (OrganisationManager::getCashierEmails).
     */
    public function notifyUrgentRequest(RepaymentRequest $request, array $recipients): void
    {
        $currency = $this->organisation->getCurrency();
        $subject = 'Demande de remboursement urgente – ' . $this->organisation->getName();
        $message = $this->user->getFullName() . ' a marqué une demande de remboursement comme urgente dans ' . $this->organisation->getName() . '.';
        $details = [
            'Libellé' => $request->getLabel(),
            'Bénéficiaire' => $request->getBeneficiaryName(),
            'Montant' => format_amount($request->getAmount(), $currency),
            "Date de l'achat" => format_date($request->getTransactionDate()),
        ];
        $action = ['label' => 'Traiter la demande', 'url' => app_url('/dashboard/organisation/cashier/?id=' . $this->organisation->getId())];

        foreach ($recipients as $email) {
            sendMail($email, $subject, $message, details: $details, action: $action);
        }
    }

    private function insertReceipts(int $requestId, array $receipts): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO repayment_receipts (idx_repayment, rec_path, rec_original_name, rec_position) VALUES (:request, :path, :original_name, :position)');
        foreach (array_values($receipts) as $position => $receipt) {
            $stmt->execute(['request' => $requestId, 'path' => $receipt['path'], 'original_name' => $receipt['original_name'], 'position' => $position]);
        }
    }

    private function fetchRequests(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $requests = [];
        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $request = new RepaymentRequest();
            $request->hydrate($ligne);
            $requests[] = $request;
        }
        return $requests;
    }
}
