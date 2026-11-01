<h1>Stagiaires assignés</h1>
<p class="sous">Les étudiants qui te sont affectés par l'administration.</p>
<?php if (!$stages): ?>
  <div class="carte"><p class="aide">Aucun stagiaire ne t'est affecté pour le moment.</p></div>
<?php else: ?>
<div class="table-wrap"><table>
  <tr><th>Stagiaire</th><th>Offre</th><th>Entreprise</th><th>Journal</th><th>État</th><th></th></tr>
  <?php foreach ($stages as $s): ?>
    <tr>
      <td><?= e($s['prenom'] . ' ' . $s['nom']) ?><br><span class="aide"><?= e($s['filiere'] ?: $s['email']) ?></span></td>
      <td><?= e($s['titre']) ?></td>
      <td><?= e($s['raison_sociale']) ?></td>
      <td><?= (int) $s['nb_journal'] ?></td>
      <td><?= badge('stage', $s['statut']) ?></td>
      <td><a href="/encadrant/stages/<?= (int) $s['id'] ?>">Ouvrir le suivi</a></td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
