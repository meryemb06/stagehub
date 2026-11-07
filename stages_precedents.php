<?php
require_once 'includes/header.php';
exiger_role('ETUDIANT');

$req = $pdo->prepare("SELECT s.id, o.titre, ent.raison_sociale,
                             (SELECT note FROM evaluations WHERE stage_id = s.id AND type = 'ENTREPRISE') AS note_entreprise,
                             (SELECT note FROM evaluations WHERE stage_id = s.id AND type = 'ENCADRANT') AS note_encadrant
                      FROM stages s
                      JOIN candidatures c ON c.id = s.candidature_id
                      JOIN offres o ON o.id = c.offre_id
                      JOIN utilisateurs ent ON ent.id = o.entreprise_id
                      WHERE c.etudiant_id = ? AND s.statut = 'TERMINE'
                      ORDER BY s.id DESC");
$req->execute([$_SESSION['user']['id']]);
$stages = $req->fetchAll();
?>
<h1>Mes stages précédents</h1>
<?php if (!$stages): ?>
    <p>Vous n'avez aucun stage terminé pour le moment.</p>
<?php else: ?>
<table>
    <tr><th>Stage</th><th>Entreprise</th><th>Note de l'entreprise</th><th>Note de l'encadrant</th></tr>
    <?php foreach ($stages as $s): ?>
    <tr>
        <td><?= e($s['titre']) ?></td>
        <td><?= e($s['raison_sociale']) ?></td>
        <td><?= $s['note_entreprise'] !== null ? e($s['note_entreprise']) . ' / 20' : '—' ?></td>
        <td><?= $s['note_encadrant'] !== null ? e($s['note_encadrant']) . ' / 20' : '—' ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
