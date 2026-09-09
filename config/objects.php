<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/connect-db.php';


function sendMail($to, $subject, $message, $headers = '', $token = null, $loggedMessage = null)
{
    global $mysqlClient;

    $mailBody = "Bonjour,\n\n" . $message . "\n\nCordialement,\nL'équipe Rembourso";

    if (mail($to, $subject, $mailBody, $headers)) {
        $loggedMessage = $loggedMessage ?? $message;
        $stmt = $mysqlClient->prepare("INSERT INTO emails (ema_to, ema_subject, ema_content, idx_author, idx_token) VALUES (:to, :subject, :message, :author, (SELECT id_token FROM tokens WHERE tok_token = :token OR tok_token = :token_hash LIMIT 1))");
        $stmt->execute([
            ':to' => $to,
            ':subject' => $subject,
            ':message' => $loggedMessage,
            ':author' => $_SESSION['user_id'] ?? null,
            ':token' => $token ?? null,
            ':token_hash' => $token ? hash('sha256', $token) : null
        ]);

        return $mysqlClient->lastInsertId();
    } else {
        return false;
    }
}

function userConnected()
{
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

function getTokenObject($token)
{
    global $mysqlClient;

    $stmt = $mysqlClient->prepare("SELECT i.idx_user as invitation FROM tokens t LEFT JOIN invitations i ON t.id_token = i.idx_token WHERE t.tok_token = :token");
    $stmt->execute([':token' => $token]);
    $data = $stmt->fetch();

    if ($data && $data['invitation']) {
        return ['invitation', new Invitation(token: $token)];
    }

    try {
        return ['password_reset', new PasswordResetRequest(token: $token)];
    } catch (Exception $e) {
        return null;
    }
}


class User
{

    public $id;
    public $last_name;
    public $first_name;
    public $address;
    public $postal_code;
    public $city;
    public $country;
    public $iban;
    public $email;
    public $phone;
    public $password;
    public $refund_first_name;
    public $refund_last_name;
    public $refund_address;
    public $refund_postal_code;
    public $refund_city;
    public $refund_country;
    public $active;

    public function __construct($id = null, $email = null)
    {
        global $mysqlClient;

        if (!$id && !$email) {
            throw new Exception("ID ou email requis pour créer un utilisateur");
        }

        $stmt = $mysqlClient->prepare("SELECT * FROM users WHERE id_user = :id OR use_email = :email");
        $stmt->execute([
            ':id' => $id,
            ':email' => $email
        ]);
        $userData = $stmt->fetch();

        if ($userData) {
            $this->id = $userData['id_user'];
            $this->last_name = $userData['use_last_name'];
            $this->first_name = $userData['use_first_name'];
            $this->address = $userData['use_address'];
            $this->postal_code = $userData['use_postal_code'];
            $this->city = $userData['use_city'];
            $this->country = $userData['use_country'];
            $this->iban = $userData['use_iban'];
            $this->email = $userData['use_email'];
            $this->phone = $userData['use_phone'];
            $this->password = $userData['use_password'];
            $this->refund_first_name = $userData['use_refund_first_name'];
            $this->refund_last_name = $userData['use_refund_last_name'];
            $this->refund_address = $userData['use_refund_address'];
            $this->refund_postal_code = $userData['use_refund_postal_code'];
            $this->refund_city = $userData['use_refund_city'];
            $this->refund_country = $userData['use_refund_country'];
            $this->active = $userData['use_active'];
        } else {
            throw new Exception("Utilisateur non trouvé");
        }
    }

    static function create($last_name, $first_name, $address, $postal_code, $city, $country, $iban, $email, $phone, $password, $refund_last_name, $refund_first_name, $refund_address, $refund_postal_code, $refund_city, $refund_country)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("INSERT INTO users (use_last_name, use_first_name, use_address, use_postal_code, use_city, use_country, use_iban, use_email, use_phone, use_password, use_refund_first_name, use_refund_last_name, use_refund_address, use_refund_postal_code, use_refund_city, use_refund_country) VALUES (:lastName, :firstName, :address, :postalCode, :city, :country, :iban, :email, :phone, :password, :refundFirstName, :refundLastName, :refundAddress, :refundPostalCode, :refundCity, :refundCountry)");
        $stmt->execute([
            ':lastName' => $last_name,
            ':firstName' => $first_name,
            ':address' => $address,
            ':postalCode' => $postal_code,
            ':city' => $city,
            ':country' => $country,
            ':iban' => $iban,
            ':email' => $email,
            ':phone' => $phone,
            ':password' => password_hash($password, PASSWORD_BCRYPT),
            ':refundFirstName' => $refund_first_name,
            ':refundLastName' => $refund_last_name,
            ':refundAddress' => $refund_address,
            ':refundPostalCode' => $refund_postal_code,
            ':refundCity' => $refund_city,
            ':refundCountry' => $refund_country
        ]);

        return new User($mysqlClient->lastInsertId());
    }
    static function isEmailAvailable($email)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT id_user FROM users WHERE use_email = :email AND use_active = 1");
        $stmt->execute([':email' => $email]);
        return !$stmt->fetch();
    }
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    public function updatePassword($newPassword)
    {
        global $mysqlClient;

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $mysqlClient->prepare("UPDATE users SET use_password = :password WHERE id_user = :id");
        $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $this->id
        ]);

        $this->password = $hashedPassword;

        return true;

    }

    public function getOrganisations()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT * FROM users_organisations WHERE id_idx_user = :user_id");
        $stmt->execute([':user_id' => $this->id]);

        $organisations = [];
        foreach ($stmt->fetchAll() as $row) {
            $organisations[] = new Organisation($row['id_idx_organisation']);
        }
        return $organisations;
    }

    public function getRepaymentRequests(array $status = [0, 1, 2], ?int $limit = null)
    {
        global $mysqlClient;

        // Créer des paramètres nommés pour chaque statut
        $statusParams = [];
        $statusPlaceholders = [];
        foreach ($status as $index => $stat) {
            $paramName = ':status_' . $index;
            $statusParams[$paramName] = $stat;
            $statusPlaceholders[] = $paramName;
        }

        $query = "SELECT id_repayment FROM repayment_requests WHERE idx_user = :user_id AND rep_status IN (" . implode(',', $statusPlaceholders) . ") ORDER BY rep_repayment_date DESC";

        if ($limit !== null) {
            $query .= " LIMIT :limit";
        }

        $stmt = $mysqlClient->prepare($query);
        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);

        foreach ($statusParams as $key => $value) {
            $stmt->bindValue($key, (string) $value, PDO::PARAM_STR);
        }

        if ($limit !== null) {
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        $requests = [];
        foreach ($stmt->fetchAll() as $row) {
            $requests[] = new RepaymentRequest($row['id_repayment']);
        }
        return $requests;
    }

    public function sameBeneficiary()
    {
        return $this->refund_first_name === $this->first_name &&
            $this->refund_last_name === $this->last_name &&
            $this->address === $this->refund_address &&
            $this->postal_code === $this->refund_postal_code &&
            $this->city === $this->refund_city &&
            $this->country === $this->refund_country;
    }
    public function update()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("UPDATE users SET use_last_name = :lastName, use_first_name = :firstName, use_address = :address, use_postal_code = :postalCode, use_city = :city, use_country = :country, use_iban = :iban, use_email = :email, use_phone = :phone, use_refund_first_name = :refundFirstName, use_refund_last_name = :refundLastName, use_refund_address = :refundAddress, use_refund_postal_code = :refundPostalCode, use_refund_city = :refundCity, use_refund_country = :refundCountry WHERE id_user = :id");
        $stmt->execute([
            ':lastName' => $this->last_name,
            ':firstName' => $this->first_name,
            ':address' => $this->address,
            ':postalCode' => $this->postal_code,
            ':city' => $this->city,
            ':country' => $this->country,
            ':iban' => $this->iban,
            ':email' => $this->email,
            ':phone' => $this->phone,
            ':refundFirstName' => $this->refund_first_name,
            ':refundLastName' => $this->refund_last_name,
            ':refundAddress' => $this->refund_address,
            ':refundPostalCode' => $this->refund_postal_code,
            ':refundCity' => $this->refund_city,
            ':refundCountry' => $this->refund_country,
            ':id' => $this->id
        ]);
    }
    public function delete()
    {
        global $mysqlClient;


        if (count($this->getOrganisations()) > 0) {
            $stmt = $mysqlClient->prepare("UPDATE users SET use_active = 0 WHERE id_user = :id");
            $stmt->execute([':id' => $this->id]);
        } else {
            $stmt = $mysqlClient->prepare("DELETE FROM users WHERE id_user = :id");
            $stmt->execute([':id' => $this->id]);
        }

        return true;
    }

    public function getInvitations()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT i.id_invitation FROM invitations i JOIN tokens t ON i.id_invitation = t.id_token WHERE i.idx_user = :user_id AND t.tok_used = 0 AND t.tok_expires_at > NOW()");
        $stmt->execute([':user_id' => $this->id]);
        $invitationIds = $stmt->fetchAll();

        $invitations = [];
        foreach ($invitationIds as $row) {
            $invitations[] = new Invitation(id: $row['id_invitation']);
        }

        return $invitations;
    }
}

class Organisation
{
    public $id;
    public $name;
    public $use_cat;
    public $use_subcat;
    public $currency;

    public function __construct($id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT * FROM organisations WHERE id_organisation = :id");
        $stmt->execute([':id' => $id]);
        $orgData = $stmt->fetch();

        if ($orgData) {
            $this->id = $orgData['id_organisation'];
            $this->name = $orgData['org_name'];
            $this->use_cat = $orgData['org_use_cat'];
            $this->use_subcat = $orgData['org_use_subcat'];
            $this->currency = $orgData['org_currency'];
        } else {
            throw new Exception("Organisation non trouvée");
        }
    }

    public function isAdmin($user_id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT org_role FROM users_organisations WHERE id_idx_user = :user_id AND id_idx_organisation = :org_id");
        $stmt->execute([
            ':user_id' => $user_id,
            ':org_id' => $this->id
        ]);

        $data = $stmt->fetch();

        return $data && $data['org_role'] === 'admin';
    }

    public function isCashier($user_id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT org_role FROM users_organisations WHERE id_idx_user = :user_id AND id_idx_organisation = :org_id");
        $stmt->execute([
            ':user_id' => $user_id,
            ':org_id' => $this->id
        ]);

        $data = $stmt->fetch();

        return $data && ($data['org_role'] === 'cashier' || $data['org_role'] === 'admin');
    }

    public function isMember($user_id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT org_role FROM users_organisations WHERE id_idx_user = :user_id AND id_idx_organisation = :org_id");
        $stmt->execute([
            ':user_id' => $user_id,
            ':org_id' => $this->id
        ]);


        return $stmt->fetch() !== false;
    }

    static function create($name)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("INSERT INTO organisations (org_name) VALUES (:name)");
        $stmt->execute([':name' => $name]);

        $orgId = $mysqlClient->lastInsertId();

        // Associer l'organisation à son créateur
        $stmt = $mysqlClient->prepare("INSERT INTO users_organisations (id_idx_user, id_idx_organisation) VALUES (:user_id, :org_id)");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':org_id' => $orgId
        ]);

        return new Organisation($orgId);
    }

    public function getUserRole($user_id, $full = false)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT org_role FROM users_organisations WHERE id_idx_user = :user_id AND id_idx_organisation = :org_id");
        $stmt->execute([
            ':user_id' => $user_id,
            ':org_id' => $this->id
        ]);

        $data = $stmt->fetch();

        if ($full && $data) {
            switch ($data['org_role']) {
                case 'admin':
                    return 'Administrateur';
                case 'cashier':
                    return 'Caissier';
                case 'member':
                    return 'Membre';
                default:
                    return 'Rôle inconnu';
            }
        }
        return $data ? $data['org_role'] : null;
    }
    public function inviteMember($member_id, $role)
    {
        $invitation = Invitation::create($this->id, $member_id, $role);

        return $invitation;
    }

    public function getMembers()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT id_idx_user FROM users_organisations JOIN users ON users_organisations.id_idx_user = users.id_user WHERE id_idx_organisation = :org_id AND users.use_active = 1");
        $stmt->execute([':org_id' => $this->id]);

        $members = [];
        foreach ($stmt->fetchAll() as $row) {
            $members[] = new User($row['id_idx_user']);
        }
        return $members;
    }

    public function getCategories()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT id_category FROM categories WHERE idx_organisation = :org_id AND cat_active = 1");
        $stmt->execute([':org_id' => $this->id]);


        if (!$stmt) {
            throw new Exception("Erreur lors de la récupération des catégories");
        }
        $categories = [];
        foreach ($stmt->fetchAll() as $row) {
            $categories[] = new Category($row['id_category']);
        }
        return $categories;
    }

    public function getSubcategories()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT id_subcategory FROM subcategories JOIN categories ON subcategories.idx_category = categories.id_category WHERE categories.idx_organisation = :org_id AND subcategories.sub_active = 1 AND categories.cat_active = 1");
        $stmt->execute([':org_id' => $this->id]);
        if (!$stmt) {
            throw new Exception("Erreur lors de la récupération des sous-catégories");
        }

        $subcategories = [];
        foreach ($stmt->fetchAll() as $row) {
            $subcategories[] = new Subcategory($row['id_subcategory']);
        }
        return $subcategories;
    }

    public function getUserRepaymentRequests(?int $page = null)
    {
        global $mysqlClient;

        if ($page !== null && $page > 0) {
            $offset = ($page - 1) * 10;
            $limitClause = "LIMIT 10 OFFSET :offset";
        } else {
            $limitClause = "";
        }

        $stmt = $mysqlClient->prepare("SELECT id_repayment FROM repayment_requests WHERE idx_organisation = :org_id AND idx_user = :user_id ORDER BY rep_repayment_date DESC " . $limitClause);

        $stmt->bindValue(':org_id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

        if ($page !== null && $page > 0) {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();

        $requests = [];
        foreach ($stmt->fetchAll() as $row) {
            $request = new RepaymentRequest($row['id_repayment']);
            $requests[] = $request->getSafeData();
        }
        //Ajouter la monnaie à chaque remboursement

        return $requests;
    }

    public function countUserRepaymentRequests()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT COUNT(*) as count FROM repayment_requests WHERE idx_organisation = :org_id AND idx_user = :user_id");
        $stmt->execute([':org_id' => $this->id, ':user_id' => $_SESSION['user_id']]);
        $data = $stmt->fetch();
        return $data ? (int) $data['count'] : 0;
    }

    public function getUsersWithPendingRequests()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT idx_user AS id, CONCAT(users.use_first_name, ' ', users.use_last_name) AS full_name, COUNT(*) AS request_count, SUM(rep_amount) as total_pending FROM repayment_requests JOIN users ON repayment_requests.idx_user = users.id_user WHERE idx_organisation = :org_id AND rep_status = '0' GROUP BY idx_user");
        $stmt->execute([':org_id' => $this->id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getMemberRepaymentRequests($member_id, ?array $status = [1, 2, 0])
    {
        global $mysqlClient;

        // Create named placeholders for status values
        $statusParams = [];
        $statusPlaceholders = [];
        foreach ($status as $index => $stat) {
            $paramName = ':status_' . $index;
            $statusParams[$paramName] = $stat;
            $statusPlaceholders[] = $paramName;
        }

        $stmt = $mysqlClient->prepare("SELECT id_repayment FROM repayment_requests WHERE idx_organisation = :org_id AND idx_user = :user_id AND rep_status IN (" . implode(',', $statusPlaceholders) . ") ORDER BY rep_repayment_date DESC");
        $stmt->execute([':org_id' => $this->id, ':user_id' => $member_id] + $statusParams);

        $requests = [];
        foreach ($stmt->fetchAll() as $row) {
            $requests[] = new RepaymentRequest($row['id_repayment']);
        }
        return $requests;
    }

    public function removeMember($member_id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("DELETE FROM users_organisations WHERE id_idx_user = :user_id AND id_idx_organisation = :org_id");
        return $stmt->execute([':user_id' => $member_id, ':org_id' => $this->id]);
    }

    public function changeMemberRole($member_id, $new_role)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("UPDATE users_organisations SET org_role = :new_role WHERE id_idx_user = :user_id AND id_idx_organisation = :org_id");
        return $stmt->execute([':new_role' => $new_role, ':user_id' => $member_id, ':org_id' => $this->id]);
    }

    public function addMember($member_id, $role)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("INSERT INTO users_organisations (id_idx_user, id_idx_organisation, org_role) VALUES (:user_id, :org_id, :role)");
        return $stmt->execute([':user_id' => $member_id, ':org_id' => $this->id, ':role' => $role]);
    }

    public function getInvitations()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT i.id_invitation FROM invitations i JOIN tokens t ON i.idx_token = t.id_token WHERE i.idx_organisation = :org_id AND t.tok_used = 0 AND t.tok_expires_at > NOW()");
        $stmt->execute([':org_id' => $this->id]);

        $invitations = [];
        foreach ($stmt->fetchAll() as $row) {
            $invitations[] = new Invitation(id: $row['id_invitation']);
        }

        return $invitations;
    }

    public function addCategory($name)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("INSERT INTO categories (cat_name, idx_organisation) VALUES (:name, :org_id)");
        $stmt->execute([':name' => $name, ':org_id' => $this->id]);

        return new Category($mysqlClient->lastInsertId());
    }
    public function enableCategories()
    {
        global $mysqlClient;

        $this->use_cat = true;

        $stmt = $mysqlClient->prepare("UPDATE organisations SET org_use_cat = 1 WHERE id_organisation = :org_id");
        return $stmt->execute([':org_id' => $this->id]);
    }
    public function disableCategories()
    {
        global $mysqlClient;

        $this->use_cat = false;

        $stmt = $mysqlClient->prepare("UPDATE organisations SET org_use_cat = 0, org_use_subcat = 0 WHERE id_organisation = :org_id");

        if ($this->use_subcat) {
            $this->disableSubcategories();
        }
        return $stmt->execute([':org_id' => $this->id]);
    }
    public function enableSubcategories()
    {
        $this->use_subcat = true;

        global $mysqlClient;
        $stmt = $mysqlClient->prepare("UPDATE organisations SET org_use_subcat = 1 WHERE id_organisation = :org_id");

        if (!$this->use_cat) {
            $this->enableCategories();
        }

        return $stmt->execute([':org_id' => $this->id]);

    }
    public function disableSubcategories()
    {
        $this->use_subcat = false;
        global $mysqlClient;
        $stmt = $mysqlClient->prepare("UPDATE organisations SET org_use_subcat = 0 WHERE id_organisation = :org_id");
        return $stmt->execute([':org_id' => $this->id]);
    }

    public function getRepaymentBatches($limit = null, $page = null)
    {
        global $mysqlClient;

        if ($limit !== null && $page !== null) {
            $limit = (int) $limit;
            $page = max(1, (int) $page);
            $offset = ($page - 1) * $limit;
            $stmt = $mysqlClient->prepare("SELECT id_batch FROM repayment_batches WHERE idx_organisation = :org_id ORDER BY batch_date DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':org_id', (int) $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

        } else {
            $stmt = $mysqlClient->prepare("SELECT id_batch FROM repayment_batches WHERE idx_organisation = :org_id ORDER BY batch_date DESC");
            $stmt->execute([':org_id' => $this->id]);
        }

        $batches = [];
        foreach ($stmt->fetchAll() as $row) {
            $batches[] = new RepaymentBatch($row['id_batch']);
        }
        return $batches;
    }

    public function getNumberOfBatches($organisation_id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT COUNT(*) as count FROM repayment_batches WHERE idx_organisation = :org_id");
        $stmt->execute([':org_id' => $organisation_id]);
        $data = $stmt->fetch();
        return $data ? (int) $data['count'] : 0;
    }

    /**
     * Renomme l'organisation.
     */
    public function updateName($newName)
    {
        global $mysqlClient;

        $newName = trim($newName);
        if ($newName === '' || mb_strlen($newName) > 100) {
            throw new Exception("Le nom de l'organisation est invalide (1 à 100 caractères).");
        }

        $stmt = $mysqlClient->prepare("UPDATE organisations SET org_name = :name WHERE id_organisation = :org_id");
        $stmt->execute([
            ':name' => $newName,
            ':org_id' => $this->id
        ]);

        $this->name = $newName;
        return true;
    }

    /**
     * Supprime l'organisation. Les tables liées (membres, catégories, sous-catégories,
     * demandes, reçus, invitations, lots) sont supprimées en cascade par la base
     * (ON DELETE CASCADE). Seuls les fichiers reçus physiques sont supprimés manuellement.
     */
    public function delete()
    {
        global $mysqlClient;

        try {
            $mysqlClient->beginTransaction();

            // Récupérer tous les chemins de reçus (table repayment_receipts + colonne de secours)
            // AVANT la suppression, pour pouvoir effacer les fichiers physiques ensuite.
            $stmt = $mysqlClient->prepare("SELECT rec_path FROM repayment_receipts r JOIN repayment_requests q ON r.idx_repayment = q.id_repayment WHERE q.idx_organisation = :org_id");
            $stmt->execute([':org_id' => $this->id]);
            $receiptPaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $mysqlClient->prepare("SELECT rep_receipt_path FROM repayment_requests WHERE idx_organisation = :org_id AND rep_receipt_path IS NOT NULL");
            $stmt->execute([':org_id' => $this->id]);
            $receiptPaths = array_merge($receiptPaths, $stmt->fetchAll(PDO::FETCH_COLUMN));

            // La suppression de l'organisation déclenche les cascades sur toutes les tables liées.
            $stmt = $mysqlClient->prepare("DELETE FROM organisations WHERE id_organisation = :org_id");
            $stmt->execute([':org_id' => $this->id]);

            $mysqlClient->commit();

            // Supprimer les fichiers reçus après succès de la transaction
            foreach (array_unique($receiptPaths) as $path) {
                if ($path) {
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            if ($mysqlClient->inTransaction()) {
                $mysqlClient->rollBack();
            }
            throw $e;
        }
    }

}

class RepaymentRequest
{
    public $id;
    public $user_id;
    public $organisation_id;
    public $label;
    public $category;
    public $subcategory;
    public $amount;
    public $receipt_path;
    public $receipt_paths;
    public $status;
    public $transaction_date;
    public $requested_at;
    public $batch_id;

    public $editable;

    public function __construct($id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT * FROM repayment_requests WHERE id_repayment = :id");
        $stmt->execute([':id' => $id]);
        $requestData = $stmt->fetch();

        if ($requestData) {
            $this->id = $requestData['id_repayment'];
            $this->user_id = $requestData['idx_user'];
            $this->organisation_id = $requestData['idx_organisation'];
            $this->label = $requestData['rep_label'];
            if ($requestData['idx_category']) {
                $this->category = new Category($requestData['idx_category']);
            } else {
                $this->category = null;
            }
            if ($requestData['idx_subcategory']) {
                $this->subcategory = new Subcategory($requestData['idx_subcategory']);
            } else {
                $this->subcategory = null;
            }
            $this->amount = $requestData['rep_amount'];
            $this->status = $requestData['rep_status'];
            $this->transaction_date = new DateTime($requestData['rep_transaction_date']);
            $this->receipt_path = $requestData['rep_receipt_path'];
            $this->receipt_paths = $this->getReceiptPaths();
            $this->requested_at = new DateTime($requestData['rep_repayment_date']);
            $this->batch_id = $requestData['idx_batch'];
        } else {
            throw new Exception("Demande de remboursement non trouvée");
        }
        $this->editable = $this->isEditable();
    }

    static function create($user_id, $organisation_id, $label, $amount, $transaction_date, array $receipts, $category_id = null, $subcategory_id = null)
    {
        global $mysqlClient;

        if (empty($receipts)) {
            throw new Exception("Au moins une quittance est requise");
        }

        $stmt = $mysqlClient->prepare("INSERT INTO repayment_requests (idx_user, idx_organisation, rep_label, rep_amount, rep_transaction_date, rep_receipt_path, idx_category, idx_subcategory) VALUES (:user_id, :organisation_id, :label, :amount, :transaction_date, :receipt_path, :category_id, :subcategory_id)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':organisation_id' => $organisation_id,
            ':label' => $label,
            ':amount' => $amount,
            ':transaction_date' => $transaction_date,
            ':receipt_path' => $receipts[0]['path'],
            ':category_id' => $category_id,
            ':subcategory_id' => $subcategory_id
        ]);

        $repaymentId = $mysqlClient->lastInsertId();
        $receiptStmt = $mysqlClient->prepare("INSERT INTO repayment_receipts (idx_repayment, rec_path, rec_original_name, rec_position) VALUES (:repayment_id, :path, :original_name, :position)");
        foreach ($receipts as $position => $receipt) {
            $receiptStmt->execute([
                ':repayment_id' => $repaymentId,
                ':path' => $receipt['path'],
                ':original_name' => $receipt['original_name'],
                ':position' => $position
            ]);
        }

        return new RepaymentRequest($repaymentId);
    }

    public function getReceiptPaths()
    {
        return array_column($this->getReceipts(), 'path');
    }

    public function getReceipts()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT id_receipt, rec_path, rec_original_name FROM repayment_receipts WHERE idx_repayment = :repayment_id ORDER BY rec_position ASC");
        $stmt->execute([':repayment_id' => $this->id]);
        $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return !empty($receipts) ? array_map(fn($receipt) => [
            'id' => $receipt['id_receipt'],
            'path' => $receipt['rec_path'],
            'original_name' => $receipt['rec_original_name']
        ], $receipts) : ($this->receipt_path ? [[
            'id' => null,
            'path' => $this->receipt_path,
            'original_name' => basename($this->receipt_path)
        ]] : []);
    }

    public function replaceReceipts(array $receipts)
    {
        global $mysqlClient;

        if (empty($receipts)) {
            throw new Exception("Au moins une quittance est requise");
        }

        $previousPaths = $this->getReceiptPaths();
        $mysqlClient->beginTransaction();
        try {
            $stmt = $mysqlClient->prepare("DELETE FROM repayment_receipts WHERE idx_repayment = :repayment_id");
            $stmt->execute([':repayment_id' => $this->id]);
            $stmt = $mysqlClient->prepare("INSERT INTO repayment_receipts (idx_repayment, rec_path, rec_original_name, rec_position) VALUES (:repayment_id, :path, :original_name, :position)");
            foreach ($receipts as $position => $receipt) {
                $stmt->execute([':repayment_id' => $this->id, ':path' => $receipt['path'], ':original_name' => $receipt['original_name'], ':position' => $position]);
            }
            $this->receipt_path = $receipts[0]['path'];
            $this->receipt_paths = array_column($receipts, 'path');
            $this->save();
            $mysqlClient->commit();
        } catch (Exception $e) {
            $mysqlClient->rollBack();
            throw $e;
        }

        $newPaths = array_column($receipts, 'path');
        foreach ($previousPaths as $previousPath) {
            if (!in_array($previousPath, $newPaths, true) && file_exists($_SERVER['DOCUMENT_ROOT'] . $previousPath)) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $previousPath);
            }
        }
    }

    public function getStatus()
    {
        switch ($this->status) {
            case 0:
                return 'En attente';
            case 1:
                return 'Approuvée';
            case 2:
                return 'Refusée';
            default:
                return 'Inconnue';
        }
    }

    public function getBatch()
    {
        if ($this->batch_id) {
            return new RepaymentBatch($this->batch_id);
        }
        return null;
    }

    public function isEditable()
    {
        return $this->status == 0 && $this->user_id == $_SESSION['user_id'];
    }

    public function save()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("UPDATE repayment_requests SET rep_label = :label, rep_amount = :amount, rep_transaction_date = :transaction_date, idx_category = :category_id, idx_subcategory = :subcategory_id, rep_receipt_path = :receipt_path WHERE id_repayment = :id");
        $stmt->execute([
            ':label' => $this->label,
            ':amount' => $this->amount,
            ':transaction_date' => $this->transaction_date->format('Y-m-d H:i:s'),
            ':category_id' => $this->category ? $this->category->id : null,
            ':subcategory_id' => $this->subcategory ? $this->subcategory->id : null,
            ':receipt_path' => $this->receipt_path,
            ':id' => $this->id
        ]);
    }

    public function delete()
    {
        global $mysqlClient;

        foreach ($this->getReceiptPaths() as $receiptPath) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $receiptPath)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $receiptPath);
            }
        }

        $stmt = $mysqlClient->prepare("DELETE FROM repayment_requests WHERE id_repayment = :id");
        $stmt->execute([':id' => $this->id]);
    }

    public function getSafeData()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'organisation_id' => $this->organisation_id,
            'label' => htmlspecialchars($this->label, ENT_QUOTES, 'UTF-8'),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => htmlspecialchars($this->category->name, ENT_QUOTES, 'UTF-8')
            ] : null,
            'subcategory' => $this->subcategory ? [
                'id' => $this->subcategory->id,
                'name' => htmlspecialchars($this->subcategory->name, ENT_QUOTES, 'UTF-8')
            ] : null,
            'amount' => $this->amount,
            'status' => $this->status,
            'transaction_date' => $this->transaction_date,
            'requested_at' => $this->requested_at,
            'editable' => $this->editable,
            'receipt_type' => $this->receipt_path ? pathinfo($this->receipt_path, PATHINFO_EXTENSION) : null,
            'receipt_count' => count($this->receipt_paths),
            'receipt_types' => array_map(fn($path) => pathinfo($path, PATHINFO_EXTENSION), $this->receipt_paths),
        ];
    }
    public function updateStatus($newStatus, $batch_id = null)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("UPDATE repayment_requests SET rep_status = :status, idx_batch = :batch_id WHERE id_repayment = :id");
        $stmt->execute([
            ':status' => $newStatus,
            ':batch_id' => $batch_id,
            ':id' => $this->id
        ]);

        $this->status = $newStatus;
        $this->batch_id = $batch_id;
    }
}

class Category
{
    public $id;
    public $name;
    public $organisation_id;
    public $active;

    public function __construct($id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT * FROM categories WHERE id_category = :id");
        $stmt->execute([':id' => $id]);
        $categoryData = $stmt->fetch();

        if ($categoryData) {
            $this->id = $categoryData['id_category'];
            $this->name = $categoryData['cat_name'];
            $this->organisation_id = $categoryData['idx_organisation'];
            $this->active = $categoryData['cat_active'];
        } else {
            throw new Exception("Catégorie non trouvée");
        }
    }

    public function getSubcategories()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT id_subcategory FROM subcategories WHERE idx_category = :cat_id AND sub_active = 1");
        $stmt->execute([':cat_id' => $this->id]);

        $subcategories = [];
        foreach ($stmt->fetchAll() as $row) {
            $subcategories[] = new Subcategory($row['id_subcategory']);
        }
        return $subcategories;
    }

    public function delete()
    {
        global $mysqlClient;
        try {
            $mysqlClient->beginTransaction();


            // Désactiver les sous-catégories associées
            $stmt = $mysqlClient->prepare("UPDATE subcategories SET sub_active = 0 WHERE idx_category = :cat_id");
            $stmt->execute([':cat_id' => $this->id]);

            // Désactiver la catégorie
            $stmt = $mysqlClient->prepare("UPDATE categories SET cat_active = 0 WHERE id_category = :id");
            $stmt->execute([':id' => $this->id]);

            $mysqlClient->commit();
            return true;

        } catch (Exception $e) {
            $mysqlClient->rollBack();
            throw $e;
        }

    }

    public function updateName($newName)
    {
        try {
            global $mysqlClient;

            $stmt = $mysqlClient->prepare("UPDATE categories SET cat_name = :name WHERE id_category = :id");
            $stmt->execute([
                ':name' => $newName,
                ':id' => $this->id
            ]);

            $this->name = $newName;

            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la mise à jour du nom de la catégorie : " . $e->getMessage());
        }
    }

    public function addSubcategory($name)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("INSERT INTO subcategories (sub_name, idx_category) VALUES (:name, :cat_id)");
        $stmt->execute([
            ':name' => $name,
            ':cat_id' => $this->id
        ]);

        return new Subcategory($mysqlClient->lastInsertId());
    }
}

class Subcategory
{
    public $id;
    public $name;
    public $category_id;
    public $active;

    public function __construct($id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT * FROM subcategories WHERE id_subcategory = :id");
        $stmt->execute([':id' => $id]);
        $subcategoryData = $stmt->fetch();

        if ($subcategoryData) {
            $this->id = $subcategoryData['id_subcategory'];
            $this->name = $subcategoryData['sub_name'];
            $this->category_id = $subcategoryData['idx_category'];
            $this->active = $subcategoryData['sub_active'];
        } else {
            throw new Exception("Sous-catégorie non trouvée");
        }
    }

    public function updateName($newName)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("UPDATE subcategories SET sub_name = :name WHERE id_subcategory = :id");
        $stmt->execute([
            ':name' => $newName,
            ':id' => $this->id
        ]);

        $this->name = $newName;

        return true;
    }

    public function delete()
    {

        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT COUNT(*) as count FROM repayment_requests WHERE idx_subcategory = :subcat_id");
        $stmt->execute([':subcat_id' => $this->id]);
        $data = $stmt->fetch();

        if ($data && $data['count'] > 0) {
            // Il existe des demandes de remboursement associées à cette sous-catégorie, on la désactive
            $stmt = $mysqlClient->prepare("UPDATE subcategories SET sub_active = 0 WHERE id_subcategory = :id");
            $stmt->execute([':id' => $this->id]);
        } else {

            $stmt = $mysqlClient->prepare("DELETE FROM subcategories WHERE id_subcategory = :id");
            $stmt->execute([':id' => $this->id]);
        }

        return true;
    }
}

class RepaymentBatch
{
    public $id;
    public $organisation_id;
    public $cashier;
    public $date;

    public function __construct($id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT * FROM repayment_batches WHERE id_batch = :id");
        $stmt->execute([':id' => $id]);
        $batchData = $stmt->fetch();

        if ($batchData) {
            $this->id = $batchData['id_batch'];
            $this->organisation_id = $batchData['idx_organisation'];
            $this->cashier = new User($batchData['idx_cashier']);
            $this->date = new DateTime($batchData['batch_date']);
        } else {
            throw new Exception("Batch non trouvé");
        }
    }

    static function create($organisation_id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("INSERT INTO repayment_batches (idx_organisation, idx_cashier) VALUES (:org_id, :cashier_id)");
        $stmt->execute([
            ':org_id' => $organisation_id,
            ':cashier_id' => $_SESSION['user_id']
        ]);

        return new RepaymentBatch($mysqlClient->lastInsertId());
    }

    public function getRepaymentRequests()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT id_repayment FROM repayment_requests WHERE idx_batch = :batch_id");
        $stmt->execute([':batch_id' => $this->id]);

        $requests = [];
        foreach ($stmt->fetchAll() as $row) {
            $requests[] = new RepaymentRequest($row['id_repayment']);
        }
        return $requests;
    }

    public function getTotalAmount()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT SUM(rep_amount) as total FROM repayment_requests WHERE idx_batch = :batch_id AND rep_status = '1'");
        $stmt->execute([':batch_id' => $this->id]);
        $data = $stmt->fetch();

        return $data ? (float) $data['total'] : 0.0;
    }

    public function getBeneficiary()
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT DISTINCT idx_user FROM repayment_requests WHERE idx_batch = :batch_id");
        $stmt->execute([':batch_id' => $this->id]);
        $beneficiaries = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $beneficiary = $beneficiaries[0];
        foreach ($beneficiaries as $beneficiary_) {
            if ($beneficiary_ !== $beneficiary) {
                throw new Exception("Tous les remboursements d'un batch doivent appartenir au même utilisateur");
            }
        }

        return new User($beneficiary);
    }

}

class Invitation
{
    public $id;
    public $organisation_id;
    public $user_id;
    public $role;
    public $token;
    public $expires_at;
    public $used;
    public $last_mailed_at;

    public function __construct($id = null, $token = null)
    {
        global $mysqlClient;

        if (!$id && !$token) {
            throw new Exception("ID ou token requis pour créer une invitation");
        }

        if ($id) {
            $stmt = $mysqlClient->prepare("SELECT i.id_invitation, i.idx_organisation, i.idx_user, i.inv_role, t.tok_token, t.tok_expires_at, t.tok_used, MAX(e.ema_send_date) FROM invitations i JOIN tokens t ON i.idx_token = t.id_token JOIN emails e ON e.idx_token = t.id_token WHERE i.id_invitation = :id GROUP BY i.id_invitation;");
            $stmt->execute([':id' => $id]);
        } else {
            $stmt = $mysqlClient->prepare("SELECT i.id_invitation, i.idx_organisation, i.idx_user, i.inv_role, t.tok_token, t.tok_expires_at, t.tok_used, MAX(e.ema_send_date) FROM invitations i JOIN tokens t ON i.idx_token = t.id_token JOIN emails e ON e.idx_token = t.id_token WHERE t.tok_token = :token GROUP BY i.id_invitation;");
            $stmt->execute([':token' => $token]);
        }

        $invitationData = $stmt->fetch();

        if ($invitationData) {
            $this->id = $invitationData['id_invitation'];
            $this->organisation_id = $invitationData['idx_organisation'];
            $this->user_id = $invitationData['idx_user'];
            $this->role = $invitationData['inv_role'];
            $this->token = $invitationData['tok_token'];
            $this->expires_at = new DateTime($invitationData['tok_expires_at']);
            $this->used = $invitationData['tok_used'];
            $this->last_mailed_at = new DateTime($invitationData['MAX(e.ema_send_date)']);

        } else {
            throw new Exception("Invitation non trouvée");
        }
    }

    static function create($organisation_id, $user_id, $role)
    {
        global $mysqlClient;

        $token = bin2hex(random_bytes(16));
        $expires_at = (new DateTime())->modify('+30 days')->format('Y-m-d H:i:s');
        $member = new User($user_id);
        $organisation = new Organisation($organisation_id);

        $mysqlClient->beginTransaction();

        $stmt = $mysqlClient->prepare("
            INSERT INTO tokens (tok_token, tok_user, tok_expires_at) 
            VALUES (:token, :user_id, :expires_at)
        ");
        $stmt->execute([
            ':token' => $token,
            ':user_id' => $user_id,
            ':expires_at' => $expires_at
        ]);
        $stmt->closeCursor();

        $token_id = $mysqlClient->lastInsertId(); // ✅ Avant le 2e insert

        $stmt = $mysqlClient->prepare("
            INSERT INTO invitations (idx_organisation, idx_user, inv_role, idx_token) 
            VALUES (:org_id, :user_id, :role, :token_id)
        ");
        $stmt->execute([
            ':org_id' => $organisation_id,
            ':user_id' => $user_id,
            ':role' => $role,
            ':token_id' => $token_id  // ✅ Plus de sous-SELECT
        ]);
        $invitation_id = $mysqlClient->lastInsertId();
        $stmt->closeCursor();

        $mysqlClient->commit();

        // ✅ Mail envoyé après le commit (la donnée est persistée)
        $subject = "Invitation à rejoindre l'organisation " . $organisation->name;
        $message = "Vous avez été invité à rejoindre l'organisation " . $organisation->name .
            ".\n\nPour accepter l'invitation, veuillez cliquer sur le lien suivant :\n" .
            $_SERVER['SERVER_NAME'] . "/token.php?token=" . $token;

        sendMail($member->email, $subject, $message, token: $token);

        return new Invitation($invitation_id);


    }

    public function accept()
    {
        global $mysqlClient;

        if ($this->used) {
            throw new Exception("Invitation déjà utilisée");
        }
        if ($this->expires_at < new DateTime()) {
            throw new Exception("Invitation expirée");
        }

        //Ajouter l'utilisateur à l'organisation
        $stmt = $mysqlClient->prepare("INSERT INTO users_organisations (id_idx_user, id_idx_organisation, org_role) VALUES (:user_id, :org_id, :role)");
        $stmt->execute([
            ':user_id' => $this->user_id,
            ':org_id' => $this->organisation_id,
            ':role' => $this->role
        ]);

        //Marquer le token comme utilisé
        $stmt = $mysqlClient->prepare("UPDATE tokens SET tok_used = 1 WHERE tok_token = :token");
        $stmt->execute([':token' => $this->token]);

        return true;
    }

    public function decline()
    {
        global $mysqlClient;

        if ($this->used) {
            throw new Exception("Invitation déjà utilisée");
        }
        if ($this->expires_at < new DateTime()) {
            throw new Exception("Invitation expirée");
        }

        //Marquer le token comme utilisé
        $stmt = $mysqlClient->prepare("UPDATE tokens SET tok_used = 1 WHERE tok_token = :token");
        $stmt->execute([':token' => $this->token]);

        return true;
    }

    public function resendMail()
    {
        try {
            $member = new User($this->user_id);
            $organisation = new Organisation($this->organisation_id);

            $subject = "Rappel : Invitation à rejoindre l'organisation " . $organisation->name;

            $message = "Vous avez été invité à rejoindre l'organisation " . $organisation->name . "." . "\n\nPour accepter l'invitation, veuillez cliquer sur le lien suivant :\n" . $_SERVER['SERVER_NAME'] . "/token.php?token=" . $this->token;

            $result = sendMail(
                $member->email,
                $subject,
                $message,
                token: $this->token
            );

            return $result;
        } catch (Exception $e) {
            error_log("Erreur resendMail: " . $e->getMessage());
            return false;
        }
    }

    static function allreadyexist($organisation_id, $user_id)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT id_invitation FROM invitations JOIN tokens ON invitations.idx_token = tokens.id_token WHERE idx_organisation = :org_id AND idx_user = :user_id AND tok_used = 0 AND tok_expires_at > NOW()");
        $stmt->execute([':org_id' => $organisation_id, ':user_id' => $user_id]);
        return $stmt->fetch() !== false;
    }

    public function getRole()
    {
        switch ($this->role) {
            case 'admin':
                return 'Administrateur';
            case 'cashier':
                return 'Caissier';
            case 'member':
                return 'Membre';
            default:
                return 'Rôle inconnu';
        }
    }


}

class PasswordResetRequest
{
    public $user_id;
    public $token;
    public $expires_at;
    public $used;

    public function __construct($token)
    {
        global $mysqlClient;

        if (!$token) {
            throw new Exception("Token requis pour créer une demande de réinitialisation de mot de passe");
        }

        $tokenHash = hash('sha256', $token);
        $stmt = $mysqlClient->prepare("SELECT * FROM password_reset_requests JOIN tokens ON password_reset_requests.idx_token = tokens.id_token WHERE tokens.tok_token = :token_hash");
        $stmt->execute([':token_hash' => $tokenHash]);

        $resetData = $stmt->fetch();

        if ($resetData) {
            $this->user_id = $resetData['idx_user'];
            $this->token = $resetData['tok_token'];
            $this->expires_at = new DateTime($resetData['tok_expires_at']);
            $this->used = $resetData['tok_used'];
        } else {
            throw new Exception("Demande de réinitialisation non trouvée");
        }
    }

    static function create($email)
    {
        global $mysqlClient;

        $stmt = $mysqlClient->prepare("SELECT id_user FROM users WHERE use_email = :email AND use_active = 1");
        $stmt->execute([':email' => $email]);
        $userData = $stmt->fetch();

        if (!$userData) {
            throw new Exception("Aucun utilisateur trouvé avec cet email");
        }

        $user = new User($userData['id_user']);
        $token = bin2hex(random_bytes(16));
        $tokenHash = hash('sha256', $token);
        $expires_at = (new DateTime())->modify('+1 hour')->format('Y-m-d H:i:s');

        $mysqlClient->beginTransaction();

        $stmt = $mysqlClient->prepare("INSERT INTO tokens (tok_token, tok_user, tok_expires_at) VALUES (:token_hash, :user_id, :expires_at)");
        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':user_id' => $user->id,
            ':expires_at' => $expires_at
        ]);

        $token_id = $mysqlClient->lastInsertId();

        $stmt = $mysqlClient->prepare("INSERT INTO password_reset_requests (idx_user, idx_token) VALUES (:user_id, :token_id)");
        $stmt->execute([
            ':user_id' => $user->id,
            ':token_id' => $token_id
        ]);

        $mysqlClient->commit();

        // Envoyer l'email après le commit
        $subject = "Réinitialisation de mot de passe";
        $message = "Vous avez demandé à réinitialiser votre mot de passe. Pour ce faire, veuillez cliquer sur le lien suivant :\n" . $_SERVER['SERVER_NAME'] . "/token.php?token=" . $token;

        $loggedMessage = str_replace($token, '[REDACTED]', $message);
        sendMail($email, $subject, $message, token: $token, loggedMessage: $loggedMessage);

        return new PasswordResetRequest($token);
    }

    public function isValid()
    {
        return !$this->used && $this->expires_at > new DateTime();
    }

    public function use($password)
    {
        if (!$this->isValid()) {
            throw new Exception("Token invalide ou expiré");
        }

        $user = new User($this->user_id);
        $user->updatePassword($password);

        global $mysqlClient;
        $stmt = $mysqlClient->prepare("UPDATE tokens SET tok_used = 1 WHERE tok_token = :token_hash");
        $stmt->execute([':token_hash' => $this->token]);

        return true;
    }
}