<?php
require_once 'includes/header.php';
exiger_role('ETUDIANT');
$id = $_SESSION['user']['id'];
$vue = $_GET['vue'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $filiere = trim($_POST['filiere'] ?? '');
    if ($nom === '' || $prenom === '') {
        message('Le nom et le prénom sont obligatoires.', 'erreur');
    } else {
        $upd = $pdo->prepare('UPDATE utilisateurs SET nom = ?, prenom = ?, filiere = ? WHERE id = ?');
        $upd->execute([$nom, $prenom, $filiere ?: null, $id]);
        $_SESSION['user']['nom'] = $nom;
        $_SESSION['user']['prenom'] = $prenom;
        message('Profil mis à jour.');
    }
    header('Location: profil.php?vue=infos');
    exit;
}

$req = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$req->execute([$id]);
$u = $req->fetch();
$non_lues = notifications_non_lues($pdo, $id);
?>
<h1 class="ecran">Profil</h1>

<?php if ($vue === 'infos'): ?>
<form class="formulaire" method="post">
    <p class="meta">Informations personnelles</p>
    <div class="deux">
        <div><label>Nom</label><input type="text" name="nom" value="<?= e($u['nom']) ?>" required></div>
        <div><label>Prénom</label><input type="text" name="prenom" value="<?= e($u['prenom']) ?>" required></div>
    </div>
    <label>Filière</label><input type="text" name="filiere" value="<?= e($u['filiere']) ?>">
    <button class="btn bloc" type="submit">Enregistrer</button>
</form>
<a class="btn btn-contour bloc" href="profil.php">Retour au profil</a>

<?php elseif ($vue === 'documents'): ?>
<?php
    $docs = $pdo->prepare('SELECT cv, lettre_motivation, date_candidature FROM candidatures WHERE etudiant_id = ? ORDER BY date_candidature DESC');
    $docs->execute([$id]);
    $docs = $docs->fetchAll();
?>
<p class="meta">CV &amp; Documents</p>
<?php if (!$docs): ?><p>Aucun document déposé pour le moment.</p><?php endif; ?>
<div class="liste-lignes">
<?php foreach ($docs as $d): ?>
    <a class="ligne" href="uploads/cv/<?= e($d['cv']) ?>" target="_blank">
        <span class="pastille-ico"><?= icone('document') ?></span>
        <span class="texte"><strong>CV</strong><span><?= e(date('d/m/Y', strtotime($d['date_candidature']))) ?></span></span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
    <?php if ($d['lettre_motivation'] && preg_match('/\.pdf$/i', $d['lettre_motivation'])): ?>
    <a class="ligne" href="uploads/lettres/<?= e($d['lettre_motivation']) ?>" target="_blank">
        <span class="pastille-ico"><?= icone('document') ?></span>
        <span class="texte"><strong>Lettre de motivation</strong></span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
    <?php endif; ?>
<?php endforeach; ?>
</div>
<a class="btn btn-contour bloc" href="profil.php">Retour au profil</a>

<?php else: ?>
<a class="entete-profil" href="profil.php?vue=infos" style="text-decoration:none;color:inherit">
    <div class="avatar"><?= initiale($u['prenom']) ?></div>
    <div>
        <strong>Informations personnelles</strong>
        <div class="meta">info : <?= e($u['nom']) ?>, <?= e($u['prenom']) ?><?= $u['filiere'] ? ', Filière ' . e($u['filiere']) : '' ?></div>
    </div>
</a>
<div class="liste-lignes">
    <a class="ligne" href="profil.php?vue=documents">
        <span class="pastille-ico"><?= icone('document') ?></span>
        <span class="texte"><strong>CV &amp; Documents</strong></span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
    <a class="ligne" href="stages_precedents.php">
        <span class="pastille-ico"><?= icone('journal') ?></span>
        <span class="texte"><strong>Stages précédents</strong></span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
    <a class="ligne" href="notifications.php">
        <span class="pastille-ico"><?= icone('cloche') ?></span>
        <span class="texte"><strong>Notifications</strong></span>
        <?php if ($non_lues): ?><span class="badge-rouge"><?= $non_lues ?></span><?php endif; ?>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
    <a class="ligne" href="logout.php">
        <span class="pastille-ico"><?= icone('sortie') ?></span>
        <span class="texte"><strong>Déconnexion</strong></span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
