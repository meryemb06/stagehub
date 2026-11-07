<?php
require_once 'includes/header.php';
exiger_role('ETUDIANT');
$uid = $_SESSION['user']['id'];

$req = $pdo->prepare('SELECT statut, COUNT(*) AS n FROM candidatures WHERE etudiant_id = ? GROUP BY statut');
$req->execute([$uid]);
$nb = ['EN_ATTENTE' => 0, 'ACCEPTEE' => 0, 'REFUSEE' => 0];
foreach ($req->fetchAll() as $l) { $nb[$l['statut']] = (int)$l['n']; }

$cv = $pdo->prepare("SELECT v.statut, COUNT(*) AS n FROM conventions v
                     JOIN candidatures c ON c.id = v.candidature_id
                     WHERE c.etudiant_id = ? GROUP BY v.statut");
$cv->execute([$uid]);
$convNb = ['EN_COURS' => 0, 'VALIDEE' => 0, 'REFUSEE' => 0];
foreach ($cv->fetchAll() as $l) { $convNb[$l['statut']] = (int)$l['n']; }

$stage = stage_actif($pdo, $uid);
$convention = null;
if ($stage) {
    $c = $pdo->prepare('SELECT * FROM conventions WHERE candidature_id = ?');
    $c->execute([$stage['candidature_id']]);
    $convention = $c->fetch();
}
$rapport = null;
if ($stage) {
    $r = $pdo->prepare('SELECT * FROM rapports WHERE stage_id = ? ORDER BY id DESC LIMIT 1');
    $r->execute([$stage['id']]);
    $rapport = $r->fetch();
}

$etapes = [
    (bool)$stage,
    (bool)$convention,
    $convention && $convention['statut'] === 'VALIDEE',
    (bool)$rapport,
    $rapport && $rapport['statut'] === 'VALIDE',
];
$pourcent = 20 * count(array_filter($etapes));

$statutConvention = 'À déposer';
if ($convention) {
    $statutConvention = ['EN_COURS' => 'Validation', 'VALIDEE' => 'Validée', 'REFUSEE' => 'Refusée'][$convention['statut']];
}
$suivi = $stage ? 'Actif' : 'Pas encore de stage';
?>
<h1 class="ecran">Mes démarches</h1>
<div class="progression">
    <div class="lib">Progression</div>
    <div class="sous">Progression globale (<?= $pourcent ?>%)</div>
    <div class="barre-prog"><div style="width:<?= $pourcent ?>%"></div></div>
</div>
<p class="section-legende">Conventions (Validées : <?= $convNb['VALIDEE'] ?> | Refusées : <?= $convNb['REFUSEE'] ?>)</p>

<div class="liste-lignes">
    <a class="ligne" href="mes_candidatures.php">
        <span class="pastille-ico"><?= icone('equipe') ?></span>
        <span class="texte"><strong>Candidatures</strong>
            <span><?= $nb['ACCEPTEE'] ?> validée<?= $nb['ACCEPTEE'] > 1 ? 's' : '' ?>, <?= $nb['REFUSEE'] ?> refusée<?= $nb['REFUSEE'] > 1 ? 's' : '' ?><?= $nb['EN_ATTENTE'] ? ', ' . $nb['EN_ATTENTE'] . ' en attente' : '' ?></span>
        </span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
    <a class="ligne" href="convention.php">
        <span class="pastille-ico"><?= icone('check') ?></span>
        <span class="texte"><strong>Demande de convention :</strong>
            <span><?= $convNb['EN_COURS'] ?> en cours<br>Statut : <?= e($statutConvention) ?></span>
        </span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
    <a class="ligne" href="<?= $stage ? 'suivi.php' : 'index.php' ?>">
        <span class="pastille-ico"><?= icone('suivi') ?></span>
        <span class="texte"><strong>Suivi du stage : <?= e($suivi) ?></strong>
            <?php if ($stage): ?><span><?= e($stage['titre']) ?></span><?php endif; ?>
        </span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
</div>
<?php require_once 'includes/footer.php'; ?>
