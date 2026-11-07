<?php
require_once 'includes/header.php';
exiger_role('ENTREPRISE');

$stage_id = (int)($_GET['stage_id'] ?? $_POST['stage_id'] ?? 0);

// Le stage doit venir d'une offre de cette entreprise
$req = $pdo->prepare("SELECT s.id, s.statut, etu.id AS etudiant_id, etu.nom, etu.prenom, o.titre
                      FROM stages s
                      JOIN candidatures c ON c.id = s.candidature_id
                      JOIN offres o ON o.id = c.offre_id
                      JOIN utilisateurs etu ON etu.id = c.etudiant_id
                      WHERE s.id = ? AND o.entreprise_id = ?");
$req->execute([$stage_id, $_SESSION['user']['id']]);
$stage = $req->fetch();
if (!$stage) {
    message('Stage introuvable.', 'erreur');
    header('Location: candidatures_recues.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = str_replace(',', '.', $_POST['note'] ?? '');
    if (!is_numeric($note) || $note < 0 || $note > 20) {
        message('La note doit être comprise entre 0 et 20.', 'erreur');
        header('Location: evaluer.php?stage_id=' . $stage_id);
        exit;
    }
    $sql = "INSERT INTO evaluations (stage_id, auteur_id, type, note, commentaire) VALUES (?, ?, 'ENTREPRISE', ?, ?)
            ON DUPLICATE KEY UPDATE note = VALUES(note), commentaire = VALUES(commentaire), auteur_id = VALUES(auteur_id)";
    $pdo->prepare($sql)->execute([$stage_id, $_SESSION['user']['id'], $note, trim($_POST['commentaire'] ?? '') ?: null]);

    // L'évaluation de fin de stage clôture le stage
    $pdo->prepare("UPDATE stages SET statut = 'TERMINE' WHERE id = ?")->execute([$stage_id]);
    notifier($pdo, $stage['etudiant_id'], 'L\'entreprise a évalué votre stage. Il est maintenant terminé.');
    message('Évaluation enregistrée. Le stage est terminé.');
    header('Location: candidatures_recues.php');
    exit;
}

$ev = $pdo->prepare("SELECT * FROM evaluations WHERE stage_id = ? AND type = 'ENTREPRISE'");
$ev->execute([$stage_id]);
$evaluation = $ev->fetch();
?>
<h1>Évaluer <?= e($stage['prenom'] . ' ' . $stage['nom']) ?></h1>
<p class="meta">Stage : <?= e($stage['titre']) ?></p>
<form class="formulaire" method="post">
    <input type="hidden" name="stage_id" value="<?= $stage_id ?>">
    <label>Note (sur 20)</label>
    <input type="number" name="note" min="0" max="20" step="0.25" value="<?= $evaluation ? e($evaluation['note']) : '' ?>" required>
    <label>Commentaire</label>
    <textarea name="commentaire" rows="5"><?= $evaluation ? e($evaluation['commentaire']) : '' ?></textarea>
    <button class="btn" type="submit">Enregistrer et terminer le stage</button>
</form>
<?php require_once 'includes/footer.php'; ?>
