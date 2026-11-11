<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/database/transactional.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/receipts.php';

/**
 * Organisations de l'utilisateur connecté : lecture (seulement celles dont il est membre),
 * rôles, membres et réglages.
 */
class OrganisationManager
{
    use Transactional;

    private PDO $pdo;
    private User $user;

    /** Rôles déjà lus : [id organisation][id utilisateur] => rôle ou null (évite une requête par contrôle) */
    private array $roleCache = [];

    public function __construct(PDO $pdo, User $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    /**
     * Organisation dont l'utilisateur connecté est membre (exception sinon).
     */
    public function getOrganisationById(int $id): Organisation
    {
        $stmt = $this->pdo->prepare('SELECT organisations.*, users_organisations.org_role FROM organisations
            JOIN users_organisations ON users_organisations.id_idx_organisation = organisations.id_organisation
            WHERE organisations.id_organisation = :id AND users_organisations.id_idx_user = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $this->user->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception('Organisation not found with ID: ' . $id);
        }

        $this->roleCache[$id][$this->user->getId()] = $ligne['org_role'];
        $organisation = new Organisation();
        $organisation->hydrate($ligne);
        return $organisation;
    }

    /**
     * Organisation par son identifiant, SANS vérifier l'appartenance de l'utilisateur (null si elle n'existe pas).
     * Pour les pages accessibles sans en être membre (lien de création de compte, invitation).
     * static : s'appelle sans utilisateur connecté, OrganisationManager::findById($pdo, $id)
     */
    public static function findById(PDO $pdo, int $id): ?Organisation
    {
        $stmt = $pdo->prepare('SELECT * FROM organisations WHERE id_organisation = :id');
        $stmt->execute(['id' => $id]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            return null;
        }
        $organisation = new Organisation();
        $organisation->hydrate($ligne);
        return $organisation;
    }

    /**
     * Organisation d'une catégorie, d'une demande ou d'un lot, seulement si l'utilisateur connecté en est membre
     * (sinon exception : on ne révèle pas l'existence d'un objet d'une autre organisation).
     */
    public function getOrganisationOfCategory(int $categoryId): Organisation
    {
        return $this->getOrganisationOf('SELECT idx_organisation FROM categories WHERE id_category = :id', $categoryId);
    }

    public function getOrganisationOfRequest(int $requestId): Organisation
    {
        return $this->getOrganisationOf('SELECT idx_organisation FROM repayment_requests WHERE id_repayment = :id', $requestId);
    }

    public function getOrganisationOfBatch(int $batchId): Organisation
    {
        return $this->getOrganisationOf('SELECT idx_organisation FROM repayment_batches WHERE id_batch = :id', $batchId);
    }

    /** $sql : requête qui renvoie l'identifiant de l'organisation de l'objet :id */
    private function getOrganisationOf(string $sql, int $id): Organisation
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $organisationId = $stmt->fetchColumn();

        if ($organisationId === false) {
            throw new Exception('Object not found with ID: ' . $id);
        }
        return $this->getOrganisationById((int) $organisationId);
    }

    /**
     * Organisations de l'utilisateur connecté, par nom : Organisation[]
     */
    public function getAllOrganisationsForUser(): array
    {
        $stmt = $this->pdo->prepare('SELECT organisations.* FROM organisations
            JOIN users_organisations ON users_organisations.id_idx_organisation = organisations.id_organisation
            WHERE users_organisations.id_idx_user = :user_id
            ORDER BY organisations.org_name');
        $stmt->execute(['user_id' => $this->user->getId()]);
        $organisations = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $organisation = new Organisation();
            $organisation->hydrate($ligne);
            $organisations[] = $organisation;
        }

        return $organisations;
    }

    /**
     * Crée l'organisation ; l'utilisateur connecté en devient administrateur. Tout ou rien.
     */
    public function createOrganisation(string $name): Organisation
    {
        $organisationId = $this->transaction(function () use ($name) {
            $stmt = $this->pdo->prepare('INSERT INTO organisations (org_name) VALUES (:name)');
            $stmt->execute(['name' => $name]);
            $organisationId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("INSERT INTO users_organisations (id_idx_user, id_idx_organisation, org_role) VALUES (:user_id, :organisation_id, 'admin')");
            $stmt->execute(['user_id' => $this->user->getId(), 'organisation_id' => $organisationId]);
            return $organisationId;
        });

        return $this->getOrganisationById($organisationId);
    }

    public function renameOrganisation(Organisation $organisation, string $name): Organisation
    {
        $stmt = $this->pdo->prepare('UPDATE organisations SET org_name = :name WHERE id_organisation = :id');
        $stmt->execute(['name' => $name, 'id' => $organisation->getId()]);

        return $this->getOrganisationById($organisation->getId());
    }

    /**
     * Active ou désactive les catégories. Les désactiver désactive aussi les sous-catégories.
     */
    public function setCategoriesEnabled(Organisation $organisation, bool $enabled): Organisation
    {
        $sql = $enabled
            ? 'UPDATE organisations SET org_use_cat = 1 WHERE id_organisation = :id'
            : 'UPDATE organisations SET org_use_cat = 0, org_use_subcat = 0 WHERE id_organisation = :id';
        $this->pdo->prepare($sql)->execute(['id' => $organisation->getId()]);

        return $this->getOrganisationById($organisation->getId());
    }

    /**
     * Active ou désactive les sous-catégories. Les activer active aussi les catégories.
     */
    public function setSubcategoriesEnabled(Organisation $organisation, bool $enabled): Organisation
    {
        $sql = $enabled
            ? 'UPDATE organisations SET org_use_subcat = 1, org_use_cat = 1 WHERE id_organisation = :id'
            : 'UPDATE organisations SET org_use_subcat = 0 WHERE id_organisation = :id';
        $this->pdo->prepare($sql)->execute(['id' => $organisation->getId()]);

        return $this->getOrganisationById($organisation->getId());
    }

    /**
     * Supprime l'organisation. Les tables liées (membres, catégories, demandes, quittances, invitations, lots)
     * sont supprimées en cascade par la base (ON DELETE CASCADE) ; les fichiers des quittances sont effacés ensuite.
     */
    public function deleteOrganisation(Organisation $organisation): void
    {
        $id = $organisation->getId();

        // Chemins des quittances AVANT la suppression, pour effacer les fichiers ensuite
        $stmt = $this->pdo->prepare('SELECT rec_path FROM repayment_receipts
            JOIN repayment_requests ON repayment_requests.id_repayment = repayment_receipts.idx_repayment
            WHERE repayment_requests.idx_organisation = :id
            UNION SELECT rep_receipt_path FROM repayment_requests WHERE idx_organisation = :id AND rep_receipt_path IS NOT NULL');
        $stmt->execute(['id' => $id]);
        $receiptPaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->pdo->prepare('DELETE FROM organisations WHERE id_organisation = :id')->execute(['id' => $id]);

        delete_receipt_files($receiptPaths);
    }

    // ---- Rôles ----

    /**
     * Rôle d'un utilisateur (par défaut : l'utilisateur connecté) : 'admin', 'cashier', 'member' ou null s'il n'est pas membre.
     */
    public function getRole(Organisation $organisation, ?int $userId = null): ?string
    {
        $userId ??= $this->user->getId();
        $organisationId = $organisation->getId();

        if (!array_key_exists($userId, $this->roleCache[$organisationId] ?? [])) {
            $stmt = $this->pdo->prepare('SELECT org_role FROM users_organisations WHERE id_idx_organisation = :organisation AND id_idx_user = :user');
            $stmt->execute(['organisation' => $organisationId, 'user' => $userId]);
            $role = $stmt->fetchColumn();
            $this->roleCache[$organisationId][$userId] = $role === false ? null : $role;
        }

        return $this->roleCache[$organisationId][$userId];
    }

    public function isMember(Organisation $organisation, ?int $userId = null): bool
    {
        return $this->getRole($organisation, $userId) !== null;
    }

    /** Caissier ou administrateur */
    public function isCashier(Organisation $organisation, ?int $userId = null): bool
    {
        return in_array($this->getRole($organisation, $userId), ['cashier', 'admin'], true);
    }

    public function isAdmin(Organisation $organisation, ?int $userId = null): bool
    {
        return $this->getRole($organisation, $userId) === 'admin';
    }

    /**
     * L'utilisateur connecté a-t-il au moins ce rôle ? ('member' < 'cashier' < 'admin')
     */
    public function hasRole(Organisation $organisation, string $role): bool
    {
        return match ($role) {
            'admin' => $this->isAdmin($organisation),
            'cashier' => $this->isCashier($organisation),
            default => $this->isMember($organisation),
        };
    }

    // ---- Membres ----

    /**
     * Membres actifs de l'organisation (comptes et personnes sans compte), triés par nom :
     * [['user' => User, 'role' => 'admin' | 'cashier' | 'member'], ...]
     */
    public function getMembers(Organisation $organisation): array
    {
        $stmt = $this->pdo->prepare('SELECT users.*, users_organisations.org_role FROM users
            JOIN users_organisations ON users_organisations.id_idx_user = users.id_user
            WHERE users_organisations.id_idx_organisation = :organisation AND users.use_active = 1
            ORDER BY users.use_last_name, users.use_first_name');
        $stmt->execute(['organisation' => $organisation->getId()]);
        $members = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $member = new User();
            $member->hydrate($ligne);
            $this->roleCache[$organisation->getId()][$member->getId()] = $ligne['org_role'];
            $members[] = ['user' => $member, 'role' => $ligne['org_role']];
        }

        return $members;
    }

    /**
     * Personnes sans compte de l'organisation, avec la date du dernier lien de création de compte envoyé :
     * [['user' => User, 'last_claim_sent_at' => ?string], ...]
     */
    public function getAccountlessMembers(Organisation $organisation): array
    {
        $stmt = $this->pdo->prepare('SELECT users.*, MAX(emails.ema_send_date) AS last_claim_sent_at
            FROM users_organisations
            JOIN users ON users.id_user = users_organisations.id_idx_user
            LEFT JOIN account_claims ON account_claims.idx_user = users.id_user AND account_claims.idx_organisation = users_organisations.id_idx_organisation
            LEFT JOIN emails ON emails.idx_token = account_claims.idx_token
            WHERE users_organisations.id_idx_organisation = :organisation AND users.use_registered = 0 AND users.use_active = 1
            GROUP BY users.id_user
            ORDER BY users.use_last_name, users.use_first_name');
        $stmt->execute(['organisation' => $organisation->getId()]);
        $people = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $person = new User();
            $person->hydrate($ligne);
            $people[] = ['user' => $person, 'last_claim_sent_at' => $ligne['last_claim_sent_at']];
        }

        return $people;
    }

    /**
     * Adresses e-mail des caissiers de l'organisation (comptes actifs), sans celle de $exceptUserId.
     * S'il n'y a aucun caissier, celles des administrateurs (ils ont aussi accès à la page « À rembourser »).
     */
    public function getCashierEmails(Organisation $organisation, ?int $exceptUserId = null): array
    {
        $stmt = $this->pdo->prepare('SELECT users.use_email, users_organisations.org_role FROM users
            JOIN users_organisations ON users_organisations.id_idx_user = users.id_user
            WHERE users_organisations.id_idx_organisation = :organisation AND users_organisations.org_role IN (\'cashier\', \'admin\')
                AND users.use_active = 1 AND users.use_registered = 1 AND users.use_email IS NOT NULL AND users.id_user <> :except');
        $stmt->execute(['organisation' => $organisation->getId(), 'except' => $exceptUserId ?? 0]);

        $emails = ['cashier' => [], 'admin' => []];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $emails[$row['org_role']][] = $row['use_email'];
        }
        return $emails['cashier'] ?: $emails['admin'];
    }

    public function addMember(Organisation $organisation, int $userId, string $role): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO users_organisations (id_idx_user, id_idx_organisation, org_role) VALUES (:user, :organisation, :role)');
        $stmt->execute(['user' => $userId, 'organisation' => $organisation->getId(), 'role' => $role]);
        $this->roleCache[$organisation->getId()][$userId] = $role;
    }

    public function changeMemberRole(Organisation $organisation, int $userId, string $role): void
    {
        $stmt = $this->pdo->prepare('UPDATE users_organisations SET org_role = :role WHERE id_idx_user = :user AND id_idx_organisation = :organisation');
        $stmt->execute(['role' => $role, 'user' => $userId, 'organisation' => $organisation->getId()]);
        $this->roleCache[$organisation->getId()][$userId] = $role;
    }

    public function removeMember(Organisation $organisation, int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users_organisations WHERE id_idx_user = :user AND id_idx_organisation = :organisation');
        $stmt->execute(['user' => $userId, 'organisation' => $organisation->getId()]);
        $this->roleCache[$organisation->getId()][$userId] = null;
    }
}
