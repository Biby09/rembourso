<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/batch/repaymentBatch.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequest.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/database/transactional.php';

/**
 * Lots de remboursement d'UNE organisation (historique et validation par un caissier).
 */
class RepaymentBatchManager
{
    use Transactional;

    public const PAGE_SIZE = 10;

    // Bénéficiaire : toutes les demandes d'un lot appartiennent au même membre, on lit celui de la première
    private const SELECT = "SELECT b.*,
            CONCAT(cashier.use_first_name, ' ', cashier.use_last_name) AS cashier_name,
            beneficiary.id_user AS beneficiary_id,
            CONCAT(beneficiary.use_first_name, ' ', beneficiary.use_last_name) AS beneficiary_name,
            (SELECT COALESCE(SUM(rep_amount), 0) FROM repayment_requests WHERE idx_batch = b.id_batch AND rep_status = '1') AS total_amount,
            (SELECT COUNT(*) FROM repayment_requests WHERE idx_batch = b.id_batch AND rep_status = '1') AS accepted_count,
            (SELECT COUNT(*) FROM repayment_requests WHERE idx_batch = b.id_batch AND rep_status = '2') AS rejected_count
        FROM repayment_batches b
        JOIN users cashier ON cashier.id_user = b.idx_cashier
        LEFT JOIN users beneficiary ON beneficiary.id_user = (SELECT idx_user FROM repayment_requests WHERE idx_batch = b.id_batch LIMIT 1)";

    private PDO $pdo;
    private Organisation $organisation;

    public function __construct(PDO $pdo, Organisation $organisation)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
    }

    public function getBatchById(int $id): RepaymentBatch
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE b.id_batch = :id AND b.idx_organisation = :organisation');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception('Repayment batch not found with ID: ' . $id);
        }

        $batch = new RepaymentBatch();
        $batch->hydrate($ligne);
        return $batch;
    }

    /**
     * Lots de l'organisation, les plus récents d'abord, par page de PAGE_SIZE : RepaymentBatch[]
     */
    public function getBatches(int $page = 1): array
    {
        // LIMIT/OFFSET : entiers calculés ici, insérés directement dans la requête
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE b.idx_organisation = :organisation ORDER BY b.batch_date DESC
            LIMIT ' . self::PAGE_SIZE . ' OFFSET ' . (max(1, $page) - 1) * self::PAGE_SIZE);
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        $batches = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batch = new RepaymentBatch();
            $batch->hydrate($ligne);
            $batches[] = $batch;
        }

        return $batches;
    }

    public function countBatches(): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM repayment_batches WHERE idx_organisation = :organisation');
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Crée un lot traité par $cashier et y range les demandes avec leur nouveau statut. Tout ou rien.
     * Les coordonnées de paiement de $beneficiary sont copiées dans le lot (confirmation PDF).
     * $decisions : [[RepaymentRequest, RepaymentRequest::STATUS_ACCEPTED | STATUS_REJECTED], ...]
     */
    public function createBatch(User $cashier, User $beneficiary, array $decisions): RepaymentBatch
    {
        $batchId = $this->transaction(function () use ($cashier, $beneficiary, $decisions) {
            $stmt = $this->pdo->prepare('INSERT INTO repayment_batches (idx_organisation, idx_cashier,
                    batch_payee_name, batch_payee_address, batch_payee_postal_code, batch_payee_city, batch_payee_country, batch_payee_iban)
                VALUES (:organisation, :cashier, :name, :address, :postal_code, :city, :country, :iban)');
            $stmt->execute([
                'organisation' => $this->organisation->getId(),
                'cashier' => $cashier->getId(),
                'name' => $beneficiary->getRefundFullName(),
                'address' => $beneficiary->getRefundAddress(),
                'postal_code' => $beneficiary->getRefundPostalCode(),
                'city' => $beneficiary->getRefundCity(),
                'country' => $beneficiary->getRefundCountry(),
                'iban' => $beneficiary->getIban(),
            ]);
            $batchId = (int) $this->pdo->lastInsertId();

            // « AND rep_status = '0' » : une demande déjà traitée (ex. double clic) n'est jamais modifiée
            $stmt = $this->pdo->prepare("UPDATE repayment_requests SET rep_status = :status, idx_batch = :batch
                WHERE id_repayment = :id AND idx_organisation = :organisation AND rep_status = '0'");
            foreach ($decisions as [$request, $status]) {
                $stmt->execute(['status' => (string) $status, 'batch' => $batchId, 'id' => $request->getId(), 'organisation' => $this->organisation->getId()]);
                if ($stmt->rowCount() !== 1) {
                    throw new Exception('Repayment request already processed: ' . $request->getId());
                }
            }
            return $batchId;
        });

        return $this->getBatchById($batchId);
    }
}
