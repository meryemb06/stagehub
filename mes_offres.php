<?php
require_once 'includes/header.php';
exiger_role('ENTREPRISE');

// Suppression d'une offre (uniquement les siennes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $del = $pdo->prepare('DELETE FROM offres WHERE id = ? AND entreprise_id = ?');
    $del->execute([(int)$_POST['offre_id'], $_SESSION['user']['id']]);
    message('Offre supprimée.');
    header('Location: mes_offres.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM offres WHERE entreprise_id = ? ORDER BY date_publication DESC');
$stmt->execute([$_SESSION['user']['id']]);
$offres = $stmt->fetchAll();
?>
<h1>Mes offres</h1>
<?php if (!$offres): ?><p>Vous n'avez publié aucune offre.</p><?php endif; ?>
<?php foreach ($offres as $o): ?>
    <div class="carte">
        <h3><a href="offre_detail.php?id=<?= (int)$o['id'] ?>" style="text-decoration:none;color:inherit"><?= e($o['titre']) ?></a> <span class="badge <?= e($o['statut']) ?>"><?= e($o['statut']) ?></span></h3>
        <div class="meta"><?= e($o['ville']) ?> — <?= (int)$o['duree_mois'] ?> mois</div>
        <a class="btn" href="offre_modifier.php?id=<?= (int)$o['id'] ?>">Modifier</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette offre ?');">
            <input type="hidden" name="offre_id" value="<?= (int)$o['id'] ?>">
            <button class="btn rouge" type="submit">Supprimer</button>
        </form>
    </div>
<?php endforeach; ?>
<?php require_once 'includes/footer.php'; ?>
