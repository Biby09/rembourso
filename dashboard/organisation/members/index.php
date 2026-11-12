<?php
$requiredRole = 'admin';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/organisation-page.php';

/*
    « Membres » (/dashboard/organisation/members/?id=…), administrateurs :
    membres et leur rôle, retrait, invitations envoyées (scripts/pages/organisation/members.js).
    Les personnes sans compte apparaissent avec un badge ; leur rôle reste « Membre ».
*/

$members = $organisationManager->getMembers($organisation);
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Membres | ' . $organisation->getName();
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

    <main class="container py-4" id="organisation-page" data-organisation-id="<?= $organisation->getId() ?>" data-organisation-name="<?= e($organisation->getName()) ?>">
        <?php $heading = 'Membres'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/page-heading.php'; ?>

        <div class="card mb-5">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0" id="members-table">
                        <thead>
                            <tr>
                                <th style="width: 40%">Nom</th>
                                <th style="width: 35%">Rôle</th>
                                <th style="width: 25%" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as ['user' => $member, 'role' => $memberRole]): ?>
                                <tr data-member-id="<?= $member->getId() ?>" data-member-name="<?= e($member->getFullName()) ?>"
                                    data-role="<?= e($memberRole) ?>" data-registered="<?= $member->isRegistered() ? '1' : '0' ?>">
                                    <td><?= e($member->getFullName()) ?></td>
                                    <td>
                                        <span data-role-label><?= e(Organisation::roleLabel($memberRole)) ?></span>
                                        <?php if (!$member->isRegistered()): ?><span class="badge bg-secondary ms-1">Sans compte</span><?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($member->getId() !== $user->getId()): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary change-role-btn">Modifier</button>
                                        <?php else: ?>
                                            <span class="text-muted">C'est vous</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <section>
            <h2 class="fs-4 mb-3">Invitations en cours</h2>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/invitation-setup.php'; ?>
        </section>
    </main>

    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/member-modals.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php';
    ?>
    <script type="module" src="/scripts/pages/organisation/members.js"></script>
</body>

</html>
