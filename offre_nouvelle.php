<?php
require_once 'includes/header.php';
exiger_role('ENTREPRISE');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duree = (int)($_POST['duree_mois'] ?? 0);
    $ville = trim($_POST['ville'] ?? '');
    $missions = trim($_POST['missions'] ?? '');
    $competences = trim($_POST['competences'] ?? '');

    if ($titre === '' || $description === '' || $duree < 1 || $ville === '') {
        message('Remplissez tous les champs.', 'erreur');
        header('Location: offre_nouvelle.php');
        exit;
    }
    $ins = $pdo->prepare('INSERT INTO offres (entreprise_id, titre, description, duree_mois, ville, missions, competences) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([$_SESSION['user']['id'], $titre, $description, $duree, $ville, $missions ?: null, $competences ?: null]);
    message('Offre publiée.');
    header('Location: mes_offres.php');
    exit;
}
?>
<h1>Publier une offre</h1>
<form class="formulaire" method="post">
    <label>Titre</label><input type="text" name="titre" required>
    <label>Description</label><textarea name="description" rows="5" required></textarea>
    <label>Missions</label><textarea name="missions" rows="4"></textarea>
    <label>Compétences requises</label><input type="text" name="competences" placeholder="Ex. : PHP, MySQL, HTML/CSS">
    <div class="deux">
        <div><label>Durée (en mois)</label><input type="number" name="duree_mois" min="1" max="12" required></div>
        <div><label>Ville</label><input type="text" name="ville" required></div>
    </div>
    <button class="btn" type="submit">Publier</button>
</form>
<?php require_once 'includes/footer.php'; ?>
