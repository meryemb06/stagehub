<?php
require_once 'config.php';
exiger_role('ADMIN');

// Fonction qui compte les lignes d'une table avec une condition simple
function compter($pdo, $sql) {
    return (int)$pdo->query($sql)->fetchColumn();
}

$stats = [
    'Étudiants inscrits'          => compter($pdo, "SELECT COUNT(*) FROM utilisateurs WHERE role = 'ETUDIANT'"),
    'Entreprises inscrites'       => compter($pdo, "SELECT COUNT(*) FROM utilisateurs WHERE role = 'ENTREPRISE'"),
    'Encadrants'                  => compter($pdo, "SELECT COUNT(*) FROM utilisateurs WHERE role = 'ENCADRANT'"),
    'Offres publiées'             => compter($pdo, 'SELECT COUNT(*) FROM offres'),
    'Offres ouvertes'             => compter($pdo, "SELECT COUNT(*) FROM offres WHERE statut = 'OUVERTE'"),
    'Candidatures envoyées'       => compter($pdo, 'SELECT COUNT(*) FROM candidatures'),
    'Candidatures acceptées'      => compter($pdo, "SELECT COUNT(*) FROM candidatures WHERE statut = 'ACCEPTEE'"),
    'Stages en cours'             => compter($pdo, "SELECT COUNT(*) FROM stages WHERE statut = 'ACTIF'"),
    'Stages terminés'             => compter($pdo, "SELECT COUNT(*) FROM stages WHERE statut = 'TERMINE'"),
    'Rapports validés'            => compter($pdo, "SELECT COUNT(*) FROM rapports WHERE statut = 'VALIDE'"),
];

// Export au format CSV (s'ouvre directement dans Excel)
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="statistiques_stagehub.csv"');
    echo "\xEF\xBB\xBF";  // permet à Excel de lire correctement les accents
    $sortie = fopen('php://output', 'w');
    fputcsv($sortie, ['Indicateur', 'Valeur'], ';');
    foreach ($stats as $nom => $valeur) {
        fputcsv($sortie, [$nom, $valeur], ';');
    }
    fclose($sortie);
    exit;
}
require_once 'includes/header.php';
?>
<h1>Statistiques</h1>
<table>
    <tr><th>Indicateur</th><th>Valeur</th></tr>
    <?php foreach ($stats as $nom => $valeur): ?>
        <tr><td><?= e($nom) ?></td><td><?= $valeur ?></td></tr>
    <?php endforeach; ?>
</table>
<a class="btn" href="admin_statistiques.php?export=1">Exporter (Excel / CSV)</a>
<?php require_once 'includes/footer.php'; ?>
