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
    $contenu = trim($_POST['contenu'] ?? '');
    if ($contenu === '') {
        message('Écrivez votre réponse.', 'erreur');
    } else {
        // La réponse est rattachée au dernier commentaire de l'encadrant (s'il existe)
        $dernier = $pdo->prepare("SELECT c.id FROM commentaires c JOIN utilisateurs u ON u.id = c.auteur_id
                                  WHERE c.stage_id = ? AND u.role = 'ENCADRANT' ORDER BY c.id DESC LIMIT 1");
        $dernier->execute([$stage['id']]);
        $parent = $dernier->fetchColumn() ?: null;

        $ins = $pdo->prepare('INSERT INTO commentaires (stage_id, auteur_id, parent_id, contenu) VALUES (?, ?, ?, ?)');
        $ins->execute([$stage['id'], $_SESSION['user']['id'], $parent, $contenu]);
        if (!empty($stage['encadrant_id'])) {
            notifier($pdo, $stage['encadrant_id'], 'Nouvelle réponse dans les commentaires du stage.');
        }
        message('Réponse envoyée.');
    }
    header('Location: commentaires.php');
    exit;
}
?>
<h1>Commentaires de l'encadrant</h1>
<?php afficher_commentaires($pdo, $stage['id']); ?>

<form class="formulaire" method="post">
    <label>Votre réponse</label><textarea name="contenu" rows="3" required></textarea>
    <button class="btn" type="submit">Répondre</button>
</form>
<?php require_once 'includes/footer.php'; ?>
