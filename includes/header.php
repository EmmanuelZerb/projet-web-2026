<?php
/**
 * ECE In - En-tête HTML commun
 */
require_once __DIR__ . '/../config/config.php';

$jourSpecial = getJourSpecial();
$themeClass  = $jourSpecial['theme'] ? 'theme-' . $jourSpecial['theme'] : '';

if (estConnecte()) {
    $nbNotifs   = getNbNotificationsNonLues();
    $nbMessages = getNbMessagesNonLus();
    $userCourant = getUtilisateurConnecte();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="description" content="<?= SITE_SLOGAN ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0a66c2">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' - ' : '' ?><?= SITE_NOM ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- CSS mobile app -->
    <link rel="stylesheet" href="assets/css/mobile-app.css">

    <?php if ($themeClass): ?>
    <link rel="stylesheet" href="assets/css/themes/<?= $jourSpecial['theme'] ?>.css">
    <?php endif; ?>
</head>
<body class="<?= $themeClass ?>">

<?php if ($jourSpecial['message']): ?>
<div class="alerte-jour-special alert alert-dismissible fade show mb-0" role="alert">
    <div class="text-center fw-bold"><?= $jourSpecial['message'] ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
