<?php
require_once 'includes/header.php';
exiger_role('ETUDIANT');

$stmt = $pdo->prepare('SELECT c.*, o.titre, o.ville, u.raison_sociale
                       FROM candidatures c
                       JOIN offres o ON o.id = c.offre_id
                       JOIN utilisateurs u ON u.id = o.entreprise_id
                       WHERE c.etudiant_id = ? ORDER BY c.date_candidature DESC');
$stmt->execute([$_SESSION['user']['id']]);
$candidatures = $stmt->fetchAll();
?>
<h1>Mes candidatures</h1>
<?php if (!$candidatures): ?><p>Vous n'avez pas encore postulé.</p><?php endif; ?>
<?php if ($candidatures): ?>
<table>
    <tr><th>Offre</th><th>Entreprise</th><th>Ville</th><th>Date</th><th>Statut</th></tr>
    <?php foreach ($candidatures as $c): ?>
    <tr>
        <td><?= e($c['titre']) ?></td>
        <td><?= e($c['raison_sociale']) ?></td>
        <td><?= e($c['ville']) ?></td>
        <td><?= e(date('d/m/Y', strtotime($c['date_candidature']))) ?></td>
        <td><span class="badge <?= e($c['statut']) ?>"><?= e($c['statut']) ?></span></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
