<?php

/**
 * Lot de remboursement (table repayment_batches) : demandes d'un même membre traitées ensemble par un caissier.
 * Le caissier, le bénéficiaire et le total sont lus en même temps par RepaymentBatchManager.
 */
class RepaymentBatch
{
    private int $id;
    private int $organisationId;
    private int $cashierId;
    private string $date;              // date et heure du traitement
    private ?string $cashierName;
    private ?int $beneficiaryId;
    private ?string $beneficiaryName;
    private string $totalAmount;       // somme des demandes acceptées
    private int $acceptedCount;
    private int $rejectedCount;
    // Coordonnées de paiement copiées à la validation du lot (null pour les lots plus anciens que cette copie)
    private ?string $payeeName;
    private ?string $payeeAddress;
    private ?string $payeePostalCode;
    private ?string $payeeCity;
    private ?string $payeeCountry;
    private ?string $payeeIban;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_batch'];
        $this->organisationId = (int) $ligne['idx_organisation'];
        $this->cashierId = (int) $ligne['idx_cashier'];
        $this->date = (string) $ligne['batch_date'];
        $this->cashierName = $ligne['cashier_name'] ?? null;
        $this->beneficiaryId = isset($ligne['beneficiary_id']) ? (int) $ligne['beneficiary_id'] : null;
        $this->beneficiaryName = $ligne['beneficiary_name'] ?? null;
        $this->totalAmount = (string) ($ligne['total_amount'] ?? '0');
        $this->acceptedCount = (int) ($ligne['accepted_count'] ?? 0);
        $this->rejectedCount = (int) ($ligne['rejected_count'] ?? 0);
        $this->payeeName = $ligne['batch_payee_name'] ?? null;
        $this->payeeAddress = $ligne['batch_payee_address'] ?? null;
        $this->payeePostalCode = $ligne['batch_payee_postal_code'] ?? null;
        $this->payeeCity = $ligne['batch_payee_city'] ?? null;
        $this->payeeCountry = $ligne['batch_payee_country'] ?? null;
        $this->payeeIban = $ligne['batch_payee_iban'] ?? null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }

    public function getCashierId(): int
    {
        return $this->cashierId;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getCashierName(): ?string
    {
        return $this->cashierName;
    }

    public function getBeneficiaryId(): ?int
    {
        return $this->beneficiaryId;
    }

    public function getBeneficiaryName(): ?string
    {
        return $this->beneficiaryName;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getAcceptedCount(): int
    {
        return $this->acceptedCount;
    }

    public function getRejectedCount(): int
    {
        return $this->rejectedCount;
    }

    /** Les coordonnées de paiement ont-elles été enregistrées à la validation ? */
    public function hasPayeeSnapshot(): bool
    {
        return $this->payeeIban !== null;
    }

    public function getPayeeName(): ?string
    {
        return $this->payeeName;
    }

    public function getPayeeAddress(): ?string
    {
        return $this->payeeAddress;
    }

    public function getPayeePostalCode(): ?string
    {
        return $this->payeePostalCode;
    }

    public function getPayeeCity(): ?string
    {
        return $this->payeeCity;
    }

    public function getPayeeCountry(): ?string
    {
        return $this->payeeCountry;
    }

    public function getPayeeIban(): ?string
    {
        return $this->payeeIban;
    }
}
