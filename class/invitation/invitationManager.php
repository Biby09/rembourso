<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/token/tokenManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/database/transactional.php';

/**
 * Invitations à rejoindre une organisation : envoi (administrateurs), réponse (compte invité).
 */
class InvitationManager
{
    use Transactional;

    private const VALIDITY = '+30 days';

    private const SELECT = "SELECT i.*, t.tok_token, t.tok_expires_at, t.tok_used, o.org_name,
            CONCAT(u.use_first_name, ' ', u.use_last_name) AS user_name,
            (SELECT MAX(e.ema_send_date) FROM emails e WHERE e.idx_token = t.id_token) AS last_mailed_at
        FROM invitations i
        JOIN tokens t ON t.id_token = i.idx_token
        JOIN organisations o ON o.id_organisation = i.idx_organisation
        JOIN users u ON u.id_user = i.idx_user";

    // Invitation en cours : jeton ni utilisé, ni expiré
    private const PENDING = 't.tok_used = 0 AND t.tok_expires_at > NOW()';

    private PDO $pdo;
    private User $user;
    private TokenManager $tokenManager;

    public function __construct(PDO $pdo, User $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->tokenManager = new TokenManager($pdo);
    }

    public function getInvitationById(int $id): Invitation
    {
        return $this->fetchOne(self::SELECT . ' WHERE i.id_invitation = :id', ['id' => $id]);
    }

    public function getInvitationByToken(string $token): Invitation
    {
        return $this->fetchOne(self::SELECT . ' WHERE t.tok_token = :token', ['token' => $token]);
    }

    /**
     * Invitations en cours reçues par l'utilisateur connecté (tableau de bord).
     */
    public function getPendingInvitationsForUser(): array
    {
        return $this->fetchAll(self::SELECT . ' WHERE i.idx_user = :user AND ' . self::PENDING . ' ORDER BY o.org_name', ['user' => $this->user->getId()]);
    }

    /**
     * Invitations en cours envoyées par l'organisation (page Membres).
     */
    public function getPendingInvitationsOfOrganisation(Organisation $organisation): array
    {
        return $this->fetchAll(self::SELECT . ' WHERE i.idx_organisation = :organisation AND ' . self::PENDING . ' ORDER BY user_name',
            ['organisation' => $organisation->getId()]);
    }

    public function hasPendingInvitation(Organisation $organisation, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM invitations i JOIN tokens t ON t.id_token = i.idx_token
            WHERE i.idx_organisation = :organisation AND i.idx_user = :user AND ' . self::PENDING);
        $stmt->execute(['organisation' => $organisation->getId(), 'user' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Invite un compte dans l'organisation et lui envoie le lien par e-mail.
     */
    public function createInvitation(Organisation $organisation, User $member, string $role): Invitation
    {
        $invitationId = $this->transaction(function () use ($organisation, $member, $role) {
            [$tokenId] = $this->tokenManager->createToken($member->getId(), self::VALIDITY, false);
            $stmt = $this->pdo->prepare('INSERT INTO invitations (idx_organisation, idx_user, inv_role, idx_token) VALUES (:organisation, :user, :role, :token)');
            $stmt->execute(['organisation' => $organisation->getId(), 'user' => $member->getId(), 'role' => $role, 'token' => $tokenId]);
            return (int) $this->pdo->lastInsertId();
        });

        // E-mail après la validation : l'invitation existe bien quand la personne clique sur le lien
        $invitation = $this->getInvitationById($invitationId);
        $this->sendInvitationMail($invitation, $member, false);
        return $invitation;
    }

    /**
     * Renvoie l'e-mail d'invitation (bouton « Renvoyer le mail »).
     */
    public function resendInvitation(Invitation $invitation, User $member): bool
    {
        return $this->sendInvitationMail($invitation, $member, true);
    }

    /**
     * L'utilisateur connecté accepte l'invitation : il devient membre avec le rôle prévu.
     */
    public function acceptInvitation(Invitation $invitation): void
    {
        $this->assertCanRespond($invitation);

        $this->transaction(function () use ($invitation) {
            $stmt = $this->pdo->prepare('INSERT INTO users_organisations (id_idx_user, id_idx_organisation, org_role) VALUES (:user, :organisation, :role)');
            $stmt->execute(['user' => $invitation->getUserId(), 'organisation' => $invitation->getOrganisationId(), 'role' => $invitation->getRole()]);
            $this->tokenManager->markUsed($invitation->getTokenId());
        });
    }

    /**
     * L'utilisateur connecté refuse l'invitation.
     */
    public function declineInvitation(Invitation $invitation): void
    {
        $this->assertCanRespond($invitation);
        $this->tokenManager->markUsed($invitation->getTokenId());
    }

    /**
     * Un administrateur annule l'invitation (bouton « Supprimer »).
     */
    public function cancelInvitation(Invitation $invitation): void
    {
        $this->tokenManager->markUsed($invitation->getTokenId());
    }

    private function assertCanRespond(Invitation $invitation): void
    {
        if ($invitation->getUserId() !== $this->user->getId()) {
            throw new Exception("Cette invitation ne vous est pas destinée.");
        }
        if (!$invitation->isValid()) {
            throw new Exception('Cette invitation a déjà été utilisée ou a expiré.');
        }
    }

    private function sendInvitationMail(Invitation $invitation, User $member, bool $reminder): bool
    {
        $subject = ($reminder ? 'Rappel : ' : '') . "Invitation à rejoindre l'organisation " . $invitation->getOrganisationName();
        $message = "Vous avez été invité à rejoindre l'organisation " . $invitation->getOrganisationName() . ".\n\n"
            . "Pour accepter l'invitation, veuillez cliquer sur le lien suivant :\n" . token_link($invitation->getToken());

        return (bool) sendMail($member->getEmail(), $subject, $message, token: $invitation->getToken());
    }

    private function fetchOne(string $sql, array $params): Invitation
    {
        $invitations = $this->fetchAll($sql, $params);
        if (!$invitations) {
            throw new Exception('Invitation not found');
        }
        return $invitations[0];
    }

    private function fetchAll(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $invitations = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $invitation = new Invitation();
            $invitation->hydrate($ligne);
            $invitations[] = $invitation;
        }
        return $invitations;
    }
}
