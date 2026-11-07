<?php
require_once 'includes/header.php';
exiger_role('ENCADRANT');

$stage_id = (int)($_GET['id'] ?? $_POST['stage_id'] ?? 0);

// Le stage doit appartenir à cet encadrant
$req = $pdo->prepare("SELECT s.id, s.candidature_id, etu.id AS etudiant_id, etu.nom, etu.prenom, o.titre, ent.raison_sociale
                      FROM stages s
                      JOIN candidatures c ON c.id = s.candidature_id
                      JOIN utilisateurs etu ON etu.id = c.etudiant_id
                      JOIN offres o ON o.id = c.offre_id
                      JOIN utilisateurs ent ON ent.id = o.entreprise_id
                      WHERE s.id = ? AND s.encadrant_id = ?");
$req->execute([$stage_id, $_SESSION['user']['id']]);
$stage = $req->fetch();
if (!$stage) {
    message('Stage introuvable.', 'erreur');
    header('Location: encadrant_stagiaires.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $commentaire = trim($_POST['commentaire'] ?? '');

    $decision = $_POST['decision'] ?? '';

    if (in_array($decision, ['REFUSEE', 'REJETE']) && $commentaire === '') {
        message('Un commentaire est obligatoire pour refuser ou rejeter un document.', 'erreur');

    } elseif ($action === 'convention') {
        $statut = $decision === 'VALIDEE' ? 'VALIDEE' : 'REFUSEE';
        $upd = $pdo->prepare('UPDATE conventions SET statut = ?, commentaire = ? WHERE candidature_id = ?');
        $upd->execute([$statut, $commentaire ?: null, $stage['candidature_id']]);
        notifier($pdo, $stage['etudiant_id'], $statut === 'VALIDEE'
            ? 'Votre convention a été validée.'
            : 'Votre convention a été refusée.');
        message('Décision enregistrée pour la convention.');

    } elseif ($action === 'rapport') {
        $statut = $decision === 'VALIDE' ? 'VALIDE' : 'REJETE';
        $upd = $pdo->prepare('UPDATE rapports SET statut = ?, commentaire = ? WHERE id = ? AND stage_id = ?');
        $upd->execute([$statut, $commentaire ?: null, (int)$_POST['rapport_id'], $stage_id]);
        notifier($pdo, $stage['etudiant_id'], $statut === 'VALIDE'
            ? 'Votre rapport a été validé.'
            : 'Votre rapport a été rejeté.');
        message('Décision enregistrée pour le rapport.');

    } elseif ($action === 'feedback') {
        $upd = $pdo->prepare('UPDATE entrees_journal SET feedback_encadrant = ? WHERE id = ? AND stage_id = ?');
        $upd->execute([$commentaire ?: null, (int)$_POST['entree_id'], $stage_id]);
        notifier($pdo, $stage['etudiant_id'], 'Votre encadrant a ajouté un feedback au journal.');
        message('Feedback enregistré.');

    } elseif ($action === 'commentaire') {
        if ($commentaire !== '') {
            $ins = $pdo->prepare('INSERT INTO commentaires (stage_id, auteur_id, contenu) VALUES (?, ?, ?)');
            $ins->execute([$stage_id, $_SESSION['user']['id'], $commentaire]);
            notifier($pdo, $stage['etudiant_id'], 'Nouveau commentaire de votre encadrant.');
            message('Commentaire publié.');
        }

    } elseif ($action === 'note') {
        $note = str_replace(',', '.', $_POST['note'] ?? '');
        if (!is_numeric($note) || $note < 0 || $note > 20) {
            message('La note doit être comprise entre 0 et 20.', 'erreur');
        } else {
            $sql = "INSERT INTO evaluations (stage_id, auteur_id, type, note, commentaire) VALUES (?, ?, 'ENCADRANT', ?, ?)
                    ON DUPLICATE KEY UPDATE note = VALUES(note), commentaire = VALUES(commentaire), auteur_id = VALUES(auteur_id)";
            $pdo->prepare($sql)->execute([$stage_id, $_SESSION['user']['id'], $note, $commentaire ?: null]);
            notifier($pdo, $stage['etudiant_id'], 'Votre encadrant a enregistré une note.');
            message('Note enregistrée.');
        }
    }
    header('Location: suivi_stagiaire.php?id=' . $stage_id);
    exit;
}

$conv = $pdo->prepare('SELECT * FROM conventions WHERE candidature_id = ?');
$conv->execute([$stage['candidature_id']]);
$convention = $conv->fetch();

$rap = $pdo->prepare('SELECT * FROM rapports WHERE stage_id = ? ORDER BY date_depot DESC');
$rap->execute([$stage_id]);
$rapports = $rap->fetchAll();

$jou = $pdo->prepare('SELECT * FROM entrees_journal WHERE stage_id = ? ORDER BY date_entree DESC, id DESC');
$jou->execute([$stage_id]);
$entrees = $jou->fetchAll();

$ev = $pdo->prepare("SELECT * FROM evaluations WHERE stage_id = ? AND type = 'ENCADRANT'");
$ev->execute([$stage_id]);
$evaluation = $ev->fetch();
?>
<h1>Suivi de <?= e($stage['prenom'] . ' ' . $stage['nom']) ?></h1>
<p class="meta"><?= e($stage['titre']) ?> — <?= e($stage['raison_sociale']) ?></p>

<h2>Convention</h2>
<?php if (!$convention): ?>
    <p>Aucune convention déposée.</p>
<?php else: ?>
    <div class="carte">
        <h3><?= e($convention['sujet']) ?> <span class="badge <?= e($convention['statut']) ?>"><?= e($convention['statut']) ?></span></h3>
        <div class="meta">Du <?= e(date('d/m/Y', strtotime($convention['date_debut']))) ?>
            au <?= e(date('d/m/Y', strtotime($convention['date_fin']))) ?> — Contact : <?= e($convention['contact_entreprise']) ?></div>
        <a class="btn" href="uploads/conventions/<?= e($convention['fichier']) ?>" target="_blank">Voir le PDF</a>
        <?php if ($convention['statut'] === 'EN_COURS'): ?>
        <form method="post">
            <input type="hidden" name="stage_id" value="<?= $stage_id ?>">
            <input type="hidden" name="action" value="convention">
            <input type="text" name="commentaire" placeholder="Commentaire (obligatoire en cas de refus)">
            <button class="btn vert" name="decision" value="VALIDEE">Valider</button>
            <button class="btn rouge" name="decision" value="REFUSEE">Refuser</button>
        </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<h2>Rapports</h2>
<?php if (!$rapports): ?><p>Aucun rapport déposé.</p><?php endif; ?>
<?php foreach ($rapports as $r): ?>
    <div class="carte">
        <h3><?= e($r['titre']) ?> <span class="badge <?= e($r['statut']) ?>"><?= e($r['statut']) ?></span></h3>
        <div class="meta">Déposé le <?= e(date('d/m/Y', strtotime($r['date_depot']))) ?></div>
        <a class="btn" href="uploads/rapports/<?= e($r['fichier']) ?>" target="_blank">Voir le PDF</a>
        <?php if ($r['statut'] === 'DEPOSE'): ?>
        <form method="post">
            <input type="hidden" name="stage_id" value="<?= $stage_id ?>">
            <input type="hidden" name="action" value="rapport">
            <input type="hidden" name="rapport_id" value="<?= (int)$r['id'] ?>">
            <input type="text" name="commentaire" placeholder="Commentaire (obligatoire en cas de rejet)">
            <button class="btn vert" name="decision" value="VALIDE">Valider</button>
            <button class="btn rouge" name="decision" value="REJETE">Rejeter</button>
        </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<h2>Journal de bord</h2>
<?php if (!$entrees): ?><p>Aucune entrée dans le journal.</p><?php endif; ?>
<?php foreach ($entrees as $en): ?>
    <div class="carte">
        <div class="meta"><?= e(date('d/m/Y', strtotime($en['date_entree']))) ?></div>
        <p><strong>Tâches :</strong> <?= nl2br(e($en['taches_realisees'])) ?></p>
        <?php if ($en['difficultes']): ?><p><strong>Difficultés :</strong> <?= nl2br(e($en['difficultes'])) ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="stage_id" value="<?= $stage_id ?>">
            <input type="hidden" name="action" value="feedback">
            <input type="hidden" name="entree_id" value="<?= (int)$en['id'] ?>">
            <input type="text" name="commentaire" placeholder="Votre feedback" value="<?= e($en['feedback_encadrant']) ?>">
            <button class="btn" type="submit">Enregistrer le feedback</button>
        </form>
    </div>
<?php endforeach; ?>

<h2>Commentaires du suivi</h2>
<?php afficher_commentaires($pdo, $stage_id); ?>
<form class="formulaire" method="post">
    <input type="hidden" name="stage_id" value="<?= $stage_id ?>">
    <input type="hidden" name="action" value="commentaire">
    <label>Nouveau commentaire (visible par l'étudiant)</label>
    <textarea name="commentaire" rows="3" required></textarea>
    <button class="btn" type="submit">Publier</button>
</form>

<h2>Note du stagiaire</h2>
<form class="formulaire" method="post">
    <input type="hidden" name="stage_id" value="<?= $stage_id ?>">
    <input type="hidden" name="action" value="note">
    <label>Note (sur 20)</label>
    <input type="number" name="note" min="0" max="20" step="0.25" value="<?= $evaluation ? e($evaluation['note']) : '' ?>" required>
    <label>Appréciation</label>
    <textarea name="commentaire" rows="3"><?= $evaluation ? e($evaluation['commentaire']) : '' ?></textarea>
    <button class="btn" type="submit">Enregistrer la note</button>
</form>
<p><a href="encadrant_stagiaires.php">&larr; Retour à mes stagiaires</a></p>
<?php require_once 'includes/footer.php'; ?>
