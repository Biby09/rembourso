<?php

/**
 * Utilisateur (table users).
 * Deux sortes d'utilisateurs :
 * - les comptes (registered = true) : e-mail et mot de passe, peuvent se connecter ;
 * - les personnes « sans compte » (registered = false) : bénéficiaires saisis par un caissier,
 *   sans mot de passe, e-mail facultatif (voir AccountClaimManager pour la création de leur compte).
 */
class User
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private string $address;
    private string $postalCode;
    private string $city;
    private string $country;          // code ISO à 2 lettres (ex. « CH »)
    private string $iban;             // format machine (sans espaces)
    private ?string $email;           // null possible pour une personne sans compte
    private ?string $passwordHash;    // null pour une personne sans compte
    // Coordonnées du bénéficiaire des remboursements (QR-facture) : souvent identiques aux coordonnées personnelles
    private string $refundFirstName;
    private string $refundLastName;
    private string $refundAddress;
    private string $refundPostalCode;
    private string $refundCity;
    private string $refundCountry;
    private bool $active;             // false : compte supprimé (conservé pour l'historique des organisations)
    private bool $registered;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_user'];
        $this->firstName = $ligne['use_first_name'];
        $this->lastName = $ligne['use_last_name'];
        $this->address = $ligne['use_address'];
        $this->postalCode = $ligne['use_postal_code'];
        $this->city = $ligne['use_city'];
        $this->country = $ligne['use_country'];
        $this->iban = $ligne['use_iban'];
        $this->email = $ligne['use_email'] ?? null;
        $this->passwordHash = $ligne['use_password'] ?? null;
        $this->refundFirstName = $ligne['use_refund_first_name'];
        $this->refundLastName = $ligne['use_refund_last_name'];
        $this->refundAddress = $ligne['use_refund_address'];
        $this->refundPostalCode = $ligne['use_refund_postal_code'];
        $this->refundCity = $ligne['use_refund_city'];
        $this->refundCountry = $ligne['use_refund_country'];
        $this->active = (bool) $ligne['use_active'];
        $this->registered = (bool) ($ligne['use_registered'] ?? true);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getRefundFirstName(): string
    {
        return $this->refundFirstName;
    }

    public function getRefundLastName(): string
    {
        return $this->refundLastName;
    }

    public function getRefundFullName(): string
    {
        return trim($this->refundFirstName . ' ' . $this->refundLastName);
    }

    public function getRefundAddress(): string
    {
        return $this->refundAddress;
    }

    public function getRefundPostalCode(): string
    {
        return $this->refundPostalCode;
    }

    public function getRefundCity(): string
    {
        return $this->refundCity;
    }

    public function getRefundCountry(): string
    {
        return $this->refundCountry;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isRegistered(): bool
    {
        return $this->registered;
    }

    /**
     * Le bénéficiaire des remboursements est-il la personne elle-même ?
     * (case « même adresse » de la page Mon compte)
     */
    public function hasSameBeneficiary(): bool
    {
        return $this->refundFirstName === $this->firstName
            && $this->refundLastName === $this->lastName
            && $this->refundAddress === $this->address
            && $this->refundPostalCode === $this->postalCode
            && $this->refundCity === $this->city
            && $this->refundCountry === $this->country;
    }

    /**
     * Une personne sans compte n'a pas de mot de passe : la vérification échoue toujours.
     */
    public function verifyPassword(string $password): bool
    {
        return $this->registered && $this->passwordHash !== null && password_verify($password, $this->passwordHash);
    }
}
