<?php
require_once 'includes/header.php';
exiger_role('ENCADRANT');

$stmt = $pdo->prepare("SELECT s.id, s.statut,
                              etu.nom, etu.prenom, etu.email, etu.filiere,
                              o.titre, ent.raison_sociale
                       FROM stages s
                       JOIN candidatures c ON c.id = s.candidature_id
                       JOIN utilisateurs etu ON etu.id = c.etudiant_id
                       JOIN offres o ON o.id = c.offre_id
                       JOIN utilisateurs ent ON ent.id = o.entreprise_id
                       WHERE s.encadrant_id = ?
                       ORDER BY etu.nom");
$stmt->execute([$_SESSION['user']['id']]);
$stagiaires = $stmt->fetchAll();
?>
<h1>Mes stagiaires</h1>

<?php if (!$stagiaires): ?>
    <p>Aucun stagiaire ne vous est affecté pour le moment.</p>
<?php else: ?>
<table>
    <tr><th>Stagiaire</th><th>Filière</th><th>E-mail</th><th>Stage</th><th>Statut</th><th>Suivi</th></tr>
    <?php foreach ($stagiaires as $s): ?>
    <tr>
        <td><?= e($s['prenom'] . ' ' . $s['nom']) ?></td>
        <td><?= e($s['filiere']) ?></td>
        <td><?= e($s['email']) ?></td>
        <td><?= e($s['titre']) ?> (<?= e($s['raison_sociale']) ?>)</td>
        <td><span class="badge <?= $s['statut'] === 'ACTIF' ? 'OUVERTE' : 'CLOTUREE' ?>"><?= e($s['statut']) ?></span></td>
        <td><a class="btn" style="margin:0" href="suivi_stagiaire.php?id=<?= (int)$s['id'] ?>">Ouvrir</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
