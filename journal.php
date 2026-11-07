<?php
require_once 'includes/header.php';
exiger_role('ETUDIANT');
$stage = stage_actif($pdo, $_SESSION['user']['id']);
if (!$stage) {
    message("Vous n'avez pas encore de stage en cours.", 'erreur');
    header('Location: mon_stage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date_entree'] ?? '';
    $taches = trim($_POST['taches_realisees'] ?? '');
    $difficultes = trim($_POST['difficultes'] ?? '');
    if ($date === '' || $taches === '') {
        message('La date et les tâches réalisées sont obligatoires.', 'erreur');
    } else {
        $ins = $pdo->prepare('INSERT INTO entrees_journal (stage_id, date_entree, taches_realisees, difficultes) VALUES (?, ?, ?, ?)');
        $ins->execute([$stage['id'], $date, $taches, $difficultes ?: null]);
        message('Entrée ajoutée au journal.');
    }
    header('Location: journal.php');
    exit;
}

$req = $pdo->prepare('SELECT * FROM entrees_journal WHERE stage_id = ? ORDER BY date_entree DESC, id DESC');
$req->execute([$stage['id']]);
$entrees = $req->fetchAll();
?>
<h1>Mon journal de bord</h1>
<form class="formulaire" method="post" style="margin-bottom:24px">
    <label>Date</label><input type="date" name="date_entree" value="<?= date('Y-m-d') ?>" required>
    <label>Tâches réalisées</label><textarea name="taches_realisees" rows="4" required></textarea>
    <label>Difficultés rencontrées</label><textarea name="difficultes" rows="3"></textarea>
    <button class="btn" type="submit">Ajouter</button>
</form>

<?php if (!$entrees): ?><p>Aucune entrée pour le moment.</p><?php endif; ?>
<?php foreach ($entrees as $en): ?>
    <div class="carte">
        <div class="meta"><?= e(date('d/m/Y', strtotime($en['date_entree']))) ?></div>
        <p><strong>Tâches :</strong> <?= nl2br(e($en['taches_realisees'])) ?></p>
        <?php if ($en['difficultes']): ?><p><strong>Difficultés :</strong> <?= nl2br(e($en['difficultes'])) ?></p><?php endif; ?>
        <?php if ($en['feedback_encadrant']): ?><p><strong>Feedback de l'encadrant :</strong> <?= nl2br(e($en['feedback_encadrant'])) ?></p><?php endif; ?>
    </div>
<?php endforeach; ?>
<?php require_once 'includes/footer.php'; ?>
