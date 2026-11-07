<?php
require_once 'includes/header.php';

if (!est_connecte()) {
    ?>
    <a class="splash" href="login.php">
        <div class="logo-carre"><?php include 'includes/mallette.php'; ?></div>
        <p class="marque">StageHub</p>
        <p class="baseline">Gestion des stages et PFE</p>
        <div class="chargement">
            Chargement...
            <div class="piste" aria-hidden="true"><span></span></div>
        </div>
    </a>
    <script>
    setTimeout(function () { window.location.href = 'login.php'; }, 1700);
    </script>
    <?php
    require_once 'includes/footer.php';
    exit;
}

$q = trim($_GET['q'] ?? '');
$ville = trim($_GET['ville'] ?? '');

$sql = "SELECT o.*, u.raison_sociale FROM offres o
        JOIN utilisateurs u ON u.id = o.entreprise_id
        WHERE o.statut = 'OUVERTE'
          AND (o.titre LIKE ? OR u.raison_sociale LIKE ? OR o.competences LIKE ?)
          AND o.ville LIKE ?
        ORDER BY o.date_publication DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$q%", "%$q%", "%$q%", "%$ville%"]);
$offres = $stmt->fetchAll();
?>
<form method="get">
    <input class="filtre-check" type="checkbox" id="filtre-ville" <?= $ville !== '' ? 'checked' : '' ?>>
    <div class="titre-ligne">
        <h1>Recherche d'offres</h1>
        <div class="outils">
            <span title="Liste"><?= icone('suivi') ?></span>
            <label for="filtre-ville" title="Filtrer par ville"><?= icone('filtre') ?></label>
        </div>
    </div>
    <div class="recherche">
        <div class="champ"><?= icone('recherche') ?><input type="search" name="q" placeholder="Rechercher..." value="<?= e($q) ?>"></div>
    </div>
    <div class="filtre-ville">
        <input type="search" name="ville" placeholder="Ville" value="<?= e($ville) ?>">
    </div>
</form>

<?php if (!$offres): ?>
    <p class="meta">Aucune offre trouvée.</p>
<?php endif; ?>

<div class="liste-lignes">
<?php foreach ($offres as $o):
    [$fond, $encre, $court] = tuile_entreprise($o['raison_sociale'] ?: $o['titre']);
?>
    <a class="offre" href="offre_detail.php?id=<?= (int)$o['id'] ?>">
        <span class="tuile" style="background:<?= e($fond) ?>;color:<?= e($encre) ?>"><?= e($court) ?></span>
        <span class="texte">
            <strong><?= e($o['titre']) ?></strong>
            <em><?= e($o['raison_sociale']) ?></em>
            <small>Lieu : <?= e($o['ville']) ?><br>Durée : <?= (int)$o['duree_mois'] ?> mois</small>
        </span>
        <span class="chevron"><?= icone('fleche') ?></span>
    </a>
<?php endforeach; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
