<?php
require_once 'includes/header.php';
exiger_role('ETUDIANT');
$stage = stage_actif($pdo, $_SESSION['user']['id']);
if (!$stage) {
    message("Vous n'avez pas encore de stage en cours.", 'erreur');
    header('Location: mon_stage.php');
    exit;
}

$req = $pdo->prepare('SELECT * FROM rapports WHERE stage_id = ? ORDER BY date_depot DESC');
$req->execute([$stage['id']]);
$rapports = $req->fetchAll();

// On ne peut redéposer que si aucun rapport n'est en attente ou validé
$peut_deposer = true;
foreach ($rapports as $r) {
    if ($r['statut'] !== 'REJETE') { $peut_deposer = false; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $peut_deposer) {
    $titre = trim($_POST['titre'] ?? '');
    if ($titre === '') {
        message('Donnez un titre à votre rapport.', 'erreur');
    } else {
        list($nom_fichier, $erreur) = enregistrer_pdf('fichier', 'rapports');
        if ($erreur) {
            message($erreur, 'erreur');
        } else {
            $ins = $pdo->prepare('INSERT INTO rapports (stage_id, titre, fichier) VALUES (?, ?, ?)');
            $ins->execute([$stage['id'], $titre, $nom_fichier]);
            message('Rapport déposé.');
        }
    }
    header('Location: rapport.php');
    exit;
}
?>
<h1>Mon rapport final</h1>
<?php foreach ($rapports as $r): ?>
    <div class="carte">
        <h3><?= e($r['titre']) ?> <span class="badge <?= e($r['statut']) ?>"><?= e($r['statut']) ?></span></h3>
        <div class="meta">Déposé le <?= e(date('d/m/Y', strtotime($r['date_depot']))) ?></div>
        <?php if ($r['commentaire']): ?><p><strong>Commentaire de l'encadrant :</strong> <?= nl2br(e($r['commentaire'])) ?></p><?php endif; ?>
        <a class="btn" href="uploads/rapports/<?= e($r['fichier']) ?>" target="_blank">Voir le PDF</a>
    </div>
<?php endforeach; ?>

<?php if ($peut_deposer): ?>
<form class="formulaire" method="post" enctype="multipart/form-data">
    <label>Titre du rapport</label><input type="text" name="titre" required>
    <label>Fichier (PDF, 2 Mo maximum)</label><input type="file" name="fichier" accept=".pdf" required>
    <button class="btn" type="submit">Déposer</button>
</form>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
