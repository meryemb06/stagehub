<?php
require_once 'includes/header.php';
exiger_role('ENTREPRISE');

// Accepter ou refuser (uniquement pour ses propres offres)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'] === 'ACCEPTEE' ? 'ACCEPTEE' : 'REFUSEE';
    $upd = $pdo->prepare('UPDATE candidatures c JOIN offres o ON o.id = c.offre_id
                          SET c.statut = ? WHERE c.id = ? AND o.entreprise_id = ?');
    $info = $pdo->prepare('SELECT c.etudiant_id, o.titre FROM candidatures c
                           JOIN offres o ON o.id = c.offre_id
                           WHERE c.id = ? AND o.entreprise_id = ?');
    $info->execute([(int)$_POST['candidature_id'], $_SESSION['user']['id']]);
    $info = $info->fetch();

    $upd->execute([$decision, (int)$_POST['candidature_id'], $_SESSION['user']['id']]);

    if ($info) {
        $texte = $decision === 'ACCEPTEE'
            ? 'Votre candidature pour « ' . $info['titre'] . ' » a été acceptée.'
            : 'Votre candidature pour « ' . $info['titre'] . ' » a été refusée.';
        notifier($pdo, $info['etudiant_id'], $texte);
    }

    // Si la candidature est acceptée, on crée le stage correspondant
    if ($decision === 'ACCEPTEE') {
        $stage = $pdo->prepare("INSERT IGNORE INTO stages (candidature_id)
                                SELECT c.id FROM candidatures c
                                JOIN offres o ON o.id = c.offre_id
                                WHERE c.id = ? AND o.entreprise_id = ? AND c.statut = 'ACCEPTEE'");
        $stage->execute([(int)$_POST['candidature_id'], $_SESSION['user']['id']]);
    }
    message('Décision enregistrée.');
    header('Location: candidatures_recues.php');
    exit;
}

$stmt = $pdo->prepare('SELECT c.*, o.titre, u.nom, u.prenom, u.filiere, s.id AS stage_id, s.statut AS stage_statut
                       FROM candidatures c
                       LEFT JOIN stages s ON s.candidature_id = c.id
                       JOIN offres o ON o.id = c.offre_id
                       JOIN utilisateurs u ON u.id = c.etudiant_id
                       WHERE o.entreprise_id = ? ORDER BY c.date_candidature DESC');
$stmt->execute([$_SESSION['user']['id']]);
$candidatures = $stmt->fetchAll();
?>
<h1>Candidatures reçues</h1>
<?php if (!$candidatures): ?><p>Aucune candidature pour le moment.</p><?php endif; ?>
<?php foreach ($candidatures as $c): ?>
    <div class="carte">
        <h3><?= e($c['prenom'] . ' ' . $c['nom']) ?> <span class="badge <?= e($c['statut']) ?>"><?= e($c['statut']) ?></span></h3>
        <div class="meta">Offre : <?= e($c['titre']) ?> — Filière : <?= e($c['filiere']) ?></div>
        <?php if ($c['lettre_motivation'] && preg_match('/\.pdf$/i', $c['lettre_motivation'])): ?>
            <a class="btn btn-contour" href="uploads/lettres/<?= e($c['lettre_motivation']) ?>" target="_blank">Voir la lettre</a>
        <?php elseif ($c['lettre_motivation']): ?><p><?= nl2br(e($c['lettre_motivation'])) ?></p><?php endif; ?>
        <a class="btn" href="uploads/cv/<?= e($c['cv']) ?>" target="_blank">Voir le CV</a>
        <?php if ($c['statut'] === 'EN_ATTENTE'): ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="candidature_id" value="<?= (int)$c['id'] ?>">
                <button class="btn vert" name="decision" value="ACCEPTEE">Accepter</button>
                <button class="btn rouge" name="decision" value="REFUSEE">Refuser</button>
            </form>
        <?php endif; ?>
        <?php if ($c['stage_id']): ?>
            <a class="btn vert" href="evaluer.php?stage_id=<?= (int)$c['stage_id'] ?>">
                <?= $c['stage_statut'] === 'TERMINE' ? "Modifier l'évaluation" : 'Évaluer le stagiaire' ?>
            </a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php require_once 'includes/footer.php'; ?>
