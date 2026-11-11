<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user/user.php';

/**
 * Lecture et enregistrement des utilisateurs (comptes et personnes sans compte).
 */
class UserManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUserById(int $id): User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id_user = :id');
        $stmt->execute(['id' => $id]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception('User not found with ID: ' . $id);
        }

        $user = new User();
        $user->hydrate($ligne);
        return $user;
    }

    /**
     * Compte (jamais une personne sans compte) associé à cet e-mail, ou null.
     * Un compte actif est préféré à un compte supprimé qui aurait le même e-mail.
     */
    public function findAccountByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE use_email = :email AND use_registered = 1 ORDER BY use_active DESC LIMIT 1');
        $stmt->execute(['email' => $email]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            return null;
        }

        $user = new User();
        $user->hydrate($ligne);
        return $user;
    }

    /**
     * L'e-mail est-il libre pour un nouveau compte ? (les personnes sans compte ne le bloquent pas)
     */
    public function isEmailAvailable(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE use_email = :email AND use_active = 1 AND use_registered = 1');
        $stmt->execute(['email' => $email]);
        return !$stmt->fetchColumn();
    }

    /**
     * Crée un compte. $data : first_name, last_name, address, postal_code, city, country, iban (format machine),
     * email et refund_first_name, refund_last_name, refund_address, refund_postal_code, refund_city, refund_country.
     */
    public function createUser(array $data, string $password): User
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (use_last_name, use_first_name, use_address, use_postal_code, use_city, use_country, use_iban, use_email, use_password,
                use_refund_first_name, use_refund_last_name, use_refund_address, use_refund_postal_code, use_refund_city, use_refund_country)
            VALUES (:last_name, :first_name, :address, :postal_code, :city, :country, :iban, :email, :password,
                :refund_first_name, :refund_last_name, :refund_address, :refund_postal_code, :refund_city, :refund_country)');
        $stmt->execute([
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'address' => $data['address'],
            'postal_code' => $data['postal_code'],
            'city' => $data['city'],
            'country' => $data['country'],
            'iban' => $data['iban'],
            'email' => $data['email'],
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'refund_first_name' => $data['refund_first_name'],
            'refund_last_name' => $data['refund_last_name'],
            'refund_address' => $data['refund_address'],
            'refund_postal_code' => $data['refund_postal_code'],
            'refund_city' => $data['refund_city'],
            'refund_country' => $data['refund_country'],
        ]);

        return $this->getUserById((int) $this->pdo->lastInsertId());
    }

    /**
     * Crée une personne sans compte (bénéficiaire saisi par un caissier).
     * $person : first_name, last_name, address, postal_code, city, country, iban, email (ou null).
     * Les coordonnées de remboursement sont celles de la personne.
     */
    public function createAccountless(array $person): User
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (use_last_name, use_first_name, use_address, use_postal_code, use_city, use_country, use_iban, use_email, use_password,
                use_refund_first_name, use_refund_last_name, use_refund_address, use_refund_postal_code, use_refund_city, use_refund_country, use_registered)
            VALUES (:last_name, :first_name, :address, :postal_code, :city, :country, :iban, :email, NULL,
                :first_name, :last_name, :address, :postal_code, :city, :country, 0)');
        $stmt->execute([
            'last_name' => $person['last_name'],
            'first_name' => $person['first_name'],
            'address' => $person['address'],
            'postal_code' => $person['postal_code'],
            'city' => $person['city'],
            'country' => $person['country'],
            'iban' => $person['iban'],
            'email' => $person['email'] ?: null,
        ]);

        return $this->getUserById((int) $this->pdo->lastInsertId());
    }

    /**
     * Modifie une personne sans compte ($person : mêmes clés que createAccountless).
     */
    public function updateAccountless(User $person, array $data): User
    {
        if ($person->isRegistered()) {
            throw new Exception('Only accountless people can be updated by a cashier');
        }

        $stmt = $this->pdo->prepare('UPDATE users SET use_first_name = :first_name, use_last_name = :last_name, use_address = :address,
                use_postal_code = :postal_code, use_city = :city, use_country = :country, use_iban = :iban, use_email = :email,
                use_refund_first_name = :first_name, use_refund_last_name = :last_name, use_refund_address = :address,
                use_refund_postal_code = :postal_code, use_refund_city = :city, use_refund_country = :country
            WHERE id_user = :id AND use_registered = 0');
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'address' => $data['address'],
            'postal_code' => $data['postal_code'],
            'city' => $data['city'],
            'country' => $data['country'],
            'iban' => $data['iban'],
            'email' => $data['email'] ?: null,
            'id' => $person->getId(),
        ]);

        return $this->getUserById($person->getId());
    }

    /**
     * Informations personnelles (page Mon compte).
     */
    public function updateProfile(User $user, string $firstName, string $lastName, string $email, string $address, string $postalCode, string $city, string $country): User
    {
        $stmt = $this->pdo->prepare('UPDATE users SET use_first_name = :first_name, use_last_name = :last_name, use_email = :email,
                use_address = :address, use_postal_code = :postal_code, use_city = :city, use_country = :country
            WHERE id_user = :id');
        $stmt->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'address' => $address,
            'postal_code' => $postalCode,
            'city' => $city,
            'country' => $country,
            'id' => $user->getId(),
        ]);

        return $this->getUserById($user->getId());
    }

    /**
     * Coordonnées bancaires et bénéficiaire des remboursements (page Mon compte).
     * $refund : first_name, last_name, address, postal_code, city, country.
     */
    public function updateBankingInfo(User $user, string $iban, array $refund): User
    {
        $stmt = $this->pdo->prepare('UPDATE users SET use_iban = :iban,
                use_refund_first_name = :first_name, use_refund_last_name = :last_name, use_refund_address = :address,
                use_refund_postal_code = :postal_code, use_refund_city = :city, use_refund_country = :country
            WHERE id_user = :id');
        $stmt->execute([
            'iban' => $iban,
            'first_name' => $refund['first_name'],
            'last_name' => $refund['last_name'],
            'address' => $refund['address'],
            'postal_code' => $refund['postal_code'],
            'city' => $refund['city'],
            'country' => $refund['country'],
            'id' => $user->getId(),
        ]);

        return $this->getUserById($user->getId());
    }

    public function updatePassword(User $user, string $newPassword): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET use_password = :password WHERE id_user = :id');
        $stmt->execute(['password' => password_hash($newPassword, PASSWORD_BCRYPT), 'id' => $user->getId()]);
    }

    /**
     * Supprime le compte. S'il appartient encore à des organisations, il est seulement désactivé :
     * ses demandes de remboursement restent dans l'historique de ces organisations.
     */
    public function deleteUser(User $user): void
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users_organisations WHERE id_idx_user = :id');
        $stmt->execute(['id' => $user->getId()]);

        $sql = (int) $stmt->fetchColumn() > 0
            ? 'UPDATE users SET use_active = 0 WHERE id_user = :id'
            : 'DELETE FROM users WHERE id_user = :id';
        $this->pdo->prepare($sql)->execute(['id' => $user->getId()]);
    }
}
