<?php
require_once 'includes/header.php';
exiger_role('ETUDIANT');
$stage = stage_actif($pdo, $_SESSION['user']['id']);
if (!$stage) {
    message("Vous n'avez pas encore de stage en cours.", 'erreur');
    header('Location: mon_stage.php');
    exit;
}

$enc = $pdo->prepare('SELECT nom, prenom FROM utilisateurs WHERE id = ?');
$enc->execute([$stage['encadrant_id'] ?: 0]);
$encadrant = $enc->fetch();
$nomEnc = $encadrant ? $encadrant['prenom'] . ' ' . $encadrant['nom'] : 'Pas encore affecté';
?>
<h1 class="ecran">Suivi pédagogique</h1>
<div class="liste-lignes">
    <div class="ligne">
        <span class="pastille-ico"><?= icone('profil') ?></span>
        <span class="texte"><strong>Encadrant académique : <?= e($nomEnc) ?></strong></span>
    </div>
    <div class="ligne" style="flex-wrap:wrap">
        <span class="pastille-ico"><?= icone('document') ?></span>
        <span class="texte"><strong>Rapports (déposer le rapport 1 en PDF)</strong></span>
        <a class="btn btn-contour bloc" href="rapport.php">Déposer rapport</a>
    </div>
    <div class="ligne" style="flex-wrap:wrap">
        <span class="pastille-ico"><?= icone('journal') ?></span>
        <span class="texte"><strong>Journal de Bord</strong></span>
        <a class="btn btn-contour bloc" href="journal.php">ajouter un feedback</a>
    </div>
    <a class="ligne" href="commentaires.php">
        <span class="pastille-ico"><?= icone('document') ?></span>
        <span class="texte"><strong>Commentaires</strong></span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
</div>
<?php require_once 'includes/footer.php'; ?>
