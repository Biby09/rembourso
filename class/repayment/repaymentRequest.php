<?php

/**
 * Demande de remboursement (table repayment_requests).
 * Les colonnes « jointes » (noms de catégorie, du bénéficiaire, date du lot…) sont lues en même temps
 * par RepaymentRequestManager : pas de requête supplémentaire pour les afficher.
 */
class RepaymentRequest
{
    public const STATUS_PENDING = 0;
    public const STATUS_ACCEPTED = 1;
    public const STATUS_REJECTED = 2;

    private const STATUS_LABELS = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_ACCEPTED => 'Acceptée',
        self::STATUS_REJECTED => 'Refusée',
    ];

    private int $id;
    private int $userId;              // bénéficiaire
    private ?int $createdBy;          // caissier qui a saisi la demande pour quelqu'un d'autre (null = le bénéficiaire)
    private int $organisationId;
    private ?int $categoryId;
    private ?int $subcategoryId;
    private string $label;
    private string $amount;           // décimal en texte (« 42.50 ») : pas d'arrondi de nombre à virgule
    private int $status;
    private bool $urgent;             // les caissiers ont été prévenus par e-mail
    private string $transactionDate;  // AAAA-MM-JJ
    private string $requestedAt;      // date et heure du dépôt
    private ?int $batchId;
    private ?string $legacyReceiptPath; // ancienne colonne : première quittance (avant la table repayment_receipts)

    // Colonnes jointes (null si la requête ne les a pas lues)
    private ?string $categoryName;
    private ?string $subcategoryName;
    private ?string $beneficiaryName;
    private ?string $createdByName;
    private ?string $batchDate;
    private int $receiptCount;
    private ?string $organisationName;
    private ?string $currency;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_repayment'];
        $this->userId = (int) $ligne['idx_user'];
        $this->createdBy = isset($ligne['idx_created_by']) ? (int) $ligne['idx_created_by'] : null;
        $this->organisationId = (int) $ligne['idx_organisation'];
        $this->categoryId = isset($ligne['idx_category']) ? (int) $ligne['idx_category'] : null;
        $this->subcategoryId = isset($ligne['idx_subcategory']) ? (int) $ligne['idx_subcategory'] : null;
        $this->label = $ligne['rep_label'];
        $this->amount = (string) $ligne['rep_amount'];
        $this->status = (int) $ligne['rep_status'];
        $this->urgent = (bool) ($ligne['rep_urgent'] ?? false);
        $this->transactionDate = substr((string) $ligne['rep_transaction_date'], 0, 10);
        $this->requestedAt = (string) $ligne['rep_repayment_date'];
        $this->batchId = isset($ligne['idx_batch']) ? (int) $ligne['idx_batch'] : null;
        $this->legacyReceiptPath = $ligne['rep_receipt_path'] ?? null;

        $this->categoryName = $ligne['cat_name'] ?? null;
        $this->subcategoryName = $ligne['sub_name'] ?? null;
        $this->beneficiaryName = $ligne['beneficiary_name'] ?? null;
        $this->createdByName = $ligne['created_by_name'] ?? null;
        $this->batchDate = $ligne['batch_date'] ?? null;
        $this->receiptCount = (int) ($ligne['receipt_count'] ?? 0);
        $this->organisationName = $ligne['org_name'] ?? null;
        $this->currency = $ligne['org_currency'] ?? null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getSubcategoryId(): ?int
    {
        return $this->subcategoryId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Inconnue';
    }

    public function isUrgent(): bool
    {
        return $this->urgent;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getTransactionDate(): string
    {
        return $this->transactionDate;
    }

    public function getRequestedAt(): string
    {
        return $this->requestedAt;
    }

    public function getBatchId(): ?int
    {
        return $this->batchId;
    }

    public function getLegacyReceiptPath(): ?string
    {
        return $this->legacyReceiptPath;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function getSubcategoryName(): ?string
    {
        return $this->subcategoryName;
    }

    public function getBeneficiaryName(): ?string
    {
        return $this->beneficiaryName;
    }

    public function getCreatedByName(): ?string
    {
        return $this->createdByName;
    }

    /** Date de traitement (lot), AAAA-MM-JJ HH:MM:SS, ou null si la demande n'est pas encore traitée */
    public function getBatchDate(): ?string
    {
        return $this->batchDate;
    }

    public function getReceiptCount(): int
    {
        return $this->receiptCount;
    }

    public function getOrganisationName(): ?string
    {
        return $this->organisationName;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }
}
