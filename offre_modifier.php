<?php
require_once 'includes/header.php';
exiger_role('ENTREPRISE');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$req = $pdo->prepare('SELECT * FROM offres WHERE id = ? AND entreprise_id = ?');
$req->execute([$id, $_SESSION['user']['id']]);
$offre = $req->fetch();
if (!$offre) {
    message('Offre introuvable.', 'erreur');
    header('Location: mes_offres.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duree = (int)($_POST['duree_mois'] ?? 0);
    $ville = trim($_POST['ville'] ?? '');
    $missions = trim($_POST['missions'] ?? '');
    $competences = trim($_POST['competences'] ?? '');
    $statut = $_POST['statut'] === 'CLOTUREE' ? 'CLOTUREE' : 'OUVERTE';

    if ($titre === '' || $description === '' || $duree < 1 || $ville === '') {
        message('Remplissez tous les champs.', 'erreur');
        header('Location: offre_modifier.php?id=' . $id);
        exit;
    }
    $upd = $pdo->prepare('UPDATE offres SET titre = ?, description = ?, duree_mois = ?, ville = ?, missions = ?, competences = ?, statut = ?
                          WHERE id = ? AND entreprise_id = ?');
    $upd->execute([$titre, $description, $duree, $ville, $missions ?: null, $competences ?: null, $statut, $id, $_SESSION['user']['id']]);
    message('Offre modifiée.');
    header('Location: mes_offres.php');
    exit;
}
?>
<h1>Modifier l'offre</h1>
<form class="formulaire" method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <label>Titre</label><input type="text" name="titre" value="<?= e($offre['titre']) ?>" required>
    <label>Description</label><textarea name="description" rows="5" required><?= e($offre['description']) ?></textarea>
    <label>Missions</label><textarea name="missions" rows="4"><?= e($offre['missions']) ?></textarea>
    <label>Compétences requises</label><input type="text" name="competences" value="<?= e($offre['competences']) ?>">
    <div class="deux">
        <div><label>Durée (en mois)</label><input type="number" name="duree_mois" min="1" max="12" value="<?= (int)$offre['duree_mois'] ?>" required></div>
        <div><label>Ville</label><input type="text" name="ville" value="<?= e($offre['ville']) ?>" required></div>
    </div>
    <label>Statut</label>
    <select name="statut">
        <option value="OUVERTE" <?= $offre['statut'] === 'OUVERTE' ? 'selected' : '' ?>>Ouverte</option>
        <option value="CLOTUREE" <?= $offre['statut'] === 'CLOTUREE' ? 'selected' : '' ?>>Clôturée</option>
    </select>
    <button class="btn" type="submit">Enregistrer</button>
</form>
<?php require_once 'includes/footer.php'; ?>
