<?php
/*
    Gabarit HTML des e-mails (rendu par render_mail_html() de config/mail.php).
    Mise en page en tableaux et styles « inline » : seule façon d'avoir un rendu fiable dans les messageries
    (Outlook, Gmail… ignorent la plupart des feuilles de style et du CSS moderne).

    Variables : $mailTitle (titre affiché), $mailParagraphs (paragraphes HTML déjà échappés),
                $mailDetails ([libellé => valeur] ou []), $mailAction (['label' => …, 'url' => …] ou null).
*/
$green = '#367447';
$text = '#1f2937';
$muted = '#6b7280';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?= e($mailTitle) ?></title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6;">
    <!-- Texte d'aperçu (affiché après le sujet dans la liste des e-mails), masqué dans le message -->
    <div style="display: none; max-height: 0; overflow: hidden;"><?= e($mailTitle) ?></div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px;">
                    <!-- En-tête -->
                    <tr>
                        <td style="background-color: <?= $green ?>; border-radius: 12px 12px 0 0; padding: 22px 32px;">
                            <span style="font-family: Arial, Helvetica, sans-serif; font-size: 24px; font-weight: bold; color: #ffffff; letter-spacing: -0.5px;">Rembourso</span>
                        </td>
                    </tr>

                    <!-- Contenu -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px; border-radius: 0 0 12px 12px; font-family: Arial, Helvetica, sans-serif; color: <?= $text ?>; font-size: 15px; line-height: 1.6;">
                            <h1 style="margin: 0 0 20px 0; font-size: 21px; line-height: 1.3; color: <?= $text ?>;"><?= e($mailTitle) ?></h1>
                            <p style="margin: 0 0 16px 0;">Bonjour,</p>

                            <?php foreach ($mailParagraphs as $paragraph): ?>
                                <p style="margin: 0 0 16px 0;"><?= $paragraph ?></p>
                            <?php endforeach; ?>

                            <?php if ($mailDetails): ?>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                    style="margin: 8px 0 24px 0; border: 1px solid #e5e7eb; border-radius: 8px; border-collapse: separate;">
                                    <?php $first = true; foreach ($mailDetails as $label => $value): ?>
                                        <tr>
                                            <td style="padding: 10px 14px; width: 40%; color: <?= $muted ?>; font-size: 14px;<?= $first ? '' : ' border-top: 1px solid #e5e7eb;' ?>"><?= e($label) ?></td>
                                            <td style="padding: 10px 14px; font-weight: bold; font-size: 14px;<?= $first ? '' : ' border-top: 1px solid #e5e7eb;' ?>"><?= e($value) ?></td>
                                        </tr>
                                    <?php $first = false; endforeach; ?>
                                </table>
                            <?php endif; ?>

                            <?php if ($mailAction): ?>
                                <!-- Bouton (table : seul moyen d'avoir un bouton cliquable partout, y compris dans Outlook) -->
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 8px 0 24px 0;">
                                    <tr>
                                        <td style="background-color: <?= $green ?>; border-radius: 8px;">
                                            <a href="<?= e($mailAction['url']) ?>" target="_blank"
                                                style="display: inline-block; padding: 13px 26px; font-family: Arial, Helvetica, sans-serif; font-size: 15px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 8px;"><?= e($mailAction['label']) ?></a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin: 0 0 16px 0; font-size: 13px; color: <?= $muted ?>;">
                                    Le bouton ne fonctionne pas ? Copiez ce lien dans votre navigateur :<br>
                                    <a href="<?= e($mailAction['url']) ?>" style="color: <?= $green ?>; word-break: break-all;"><?= e($mailAction['url']) ?></a>
                                </p>
                            <?php endif; ?>

                            <p style="margin: 24px 0 0 0;">Cordialement,<br>L'équipe Rembourso</p>
                        </td>
                    </tr>

                    <!-- Pied de page -->
                    <tr>
                        <td align="center" style="padding: 20px 32px; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.5; color: <?= $muted ?>;">
                            Cet e-mail a été envoyé automatiquement par
                            <a href="<?= e(app_url('/')) ?>" style="color: <?= $muted ?>;">Rembourso</a>, la gestion des remboursements associatifs.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
