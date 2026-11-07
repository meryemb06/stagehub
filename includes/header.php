<?php
require_once __DIR__ . '/../config.php';
$page = basename($_SERVER['SCRIPT_NAME']);
$role = est_connecte() ? $_SESSION['user']['role'] : null;
$telephone = !$role || $role === 'ETUDIANT';
$non_lues = 0;
if ($role) {
    $non_lues = notifications_non_lues($pdo, $_SESSION['user']['id']);
}

function lien_nav($fichier, $texte, $icone, $liees = []) {
    global $page;
    $actif = ($page === $fichier || in_array($page, $liees, true)) ? ' actif' : '';
    echo '<a class="nav-lien' . $actif . '" href="' . $fichier . '">' . icone($icone) . '<span>' . e($texte) . '</span></a>';
}

function onglet($fichier, $texte, $icone, $liees, $href) {
    global $page;
    $actif = ($page === $fichier || in_array($page, $liees, true)) ? ' actif' : '';
    echo '<a class="onglet' . $actif . '" href="' . $href . '">' . icone($icone) . '<span>' . e($texte) . '</span></a>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StageHub</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
</head>
<body class="<?= $telephone ? 'telephone' : 'bureau' ?>">
<div class="app">
<?php if (!$telephone && $role): ?>
<header class="barre">
    <a class="logo-mini" href="<?= $role === 'ADMIN' ? 'admin_comptes.php' : ($role === 'ENCADRANT' ? 'encadrant_stagiaires.php' : 'mes_offres.php') ?>">
        <span class="pastille"><?php include __DIR__ . '/mallette.php'; ?></span>
        <span class="mot">StageHub</span>
    </a>
    <nav class="principal">
        <?php if ($role === 'ENTREPRISE'): ?>
            <?php lien_nav('index.php', 'Offres', 'offres', ['offre_detail.php']); ?>
            <?php lien_nav('offre_nouvelle.php', 'Publier', 'plus'); ?>
            <?php lien_nav('mes_offres.php', 'Mes offres', 'document', ['offre_modifier.php']); ?>
            <?php lien_nav('candidatures_recues.php', 'Candidatures', 'equipe', ['evaluer.php']); ?>
        <?php elseif ($role === 'ENCADRANT'): ?>
            <?php lien_nav('encadrant_stagiaires.php', 'Mes stagiaires', 'equipe', ['suivi_stagiaire.php']); ?>
        <?php elseif ($role === 'ADMIN'): ?>
            <?php lien_nav('admin_comptes.php', 'Comptes', 'profil'); ?>
            <?php lien_nav('admin_affectation.php', 'Affectation', 'valide'); ?>
            <?php lien_nav('admin_statistiques.php', 'Statistiques', 'stats'); ?>
        <?php endif; ?>
    </nav>
    <div class="droite">
        <a class="cloche" href="notifications.php" title="Notifications"><?= icone('cloche') ?><?php if ($non_lues): ?><em><?= $non_lues ?></em><?php endif; ?></a>
        <span class="chip"><span class="avatar"><?= initiale($_SESSION['user']['prenom']) ?></span><?= e($_SESSION['user']['prenom']) ?></span>
        <a class="deco" href="logout.php" title="Se déconnecter"><?= icone('sortie') ?></a>
    </div>
</header>
<?php endif; ?>
<main>
<?php if (isset($_SESSION['message'])): ?>
    <div class="alerte <?= e($_SESSION['message']['type']) ?>"><?= e($_SESSION['message']['texte']) ?></div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>
