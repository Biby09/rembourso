<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8')?>">
    <title><?= $page_title ?? 'Remourso' ?></title>
    <link rel="stylesheet" href="/stylesheets/global.min.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/media/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/media/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/media/favicon/favicon-16x16.png">
    <link rel="manifest" href="/media/favicon/site.webmanifest">
</head>