<?php
require_once 'includes/header.php';
if (!est_connecte()) {
    header('Location: login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT o.*, u.raison_sociale FROM offres o JOIN utilisateurs u ON u.id = o.entreprise_id WHERE o.id = ?');
$stmt->execute([$id]);
$o = $stmt->fetch();
if (!$o) {
    message('Offre introuvable.', 'erreur');
    header('Location: index.php');
    exit;
}

$deja = false;
if ($_SESSION['user']['role'] === 'ETUDIANT') {
    $req = $pdo->prepare('SELECT statut FROM candidatures WHERE offre_id = ? AND etudiant_id = ?');
    $req->execute([$id, $_SESSION['user']['id']]);
    $deja = $req->fetch();
}
?>
<h1 class="ecran">Détail de l'offre</h1>
<div class="fiche">
    <div><dt>Poste : </dt><dd><?= e($o['titre']) ?></dd></div>
    <div><dt>Entreprise : </dt><dd><?= e($o['raison_sociale']) ?></dd></div>
    <div><dt>Durée : </dt><dd><?= (int)$o['duree_mois'] ?> mois</dd></div>
    <div><dt>Lieu : </dt><dd><?= e($o['ville']) ?></dd></div>
    <div><dt>Description : </dt><dd><?= nl2br(e($o['description'])) ?></dd></div>
    <?php if ($o['missions']): ?><div><dt>Missions : </dt><dd><?= nl2br(e($o['missions'])) ?></dd></div><?php endif; ?>
    <?php if ($o['competences']): ?><div><dt>Compétences requises : </dt><dd><?= e($o['competences']) ?></dd></div><?php endif; ?>
</div>

<?php if ($_SESSION['user']['role'] === 'ETUDIANT'): ?>
    <h2 class="surtitre">Candidature</h2>
    <?php if ($deja): ?>
        <p class="meta">Vous avez déjà postulé à cette offre.</p>
        <span class="badge <?= e($deja['statut']) ?>"><?= e(str_replace('_', ' ', $deja['statut'])) ?></span>
        <a class="btn btn-contour bloc" href="mes_candidatures.php">Suivre mes candidatures</a>
    <?php elseif ($o['statut'] !== 'OUVERTE'): ?>
        <p class="meta">Cette offre est clôturée.</p>
    <?php else: ?>
        <form method="post" action="postuler.php" enctype="multipart/form-data">
            <input type="hidden" name="offre_id" value="<?= (int)$o['id'] ?>">
            <label class="btn btn-contour bloc fichier">Déposer CV (PDF)
                <input type="file" name="cv" accept="application/pdf,.pdf" required>
                <span class="nom-fichier"></span>
            </label>
            <label class="btn btn-contour bloc fichier">Déposer Lettre de motivation
                <input type="file" name="lettre" accept="application/pdf,.pdf">
                <span class="nom-fichier"></span>
            </label>
            <button class="btn bloc" type="submit">Confirmer la candidature</button>
        </form>
    <?php endif; ?>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
