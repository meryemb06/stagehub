<h1>Mes candidatures</h1>
<p class="sous">L'état de chaque demande : en attente, acceptée ou refusée.</p>
<?php if (!$candidatures): ?>
  <div class="carte"><p>Tu n'as pas encore postulé. <a href="/offres">Voir les offres</a></p></div>
<?php else: ?>
<div class="table-wrap"><table>
  <tr><th>Offre</th><th>Entreprise</th><th>Ville</th><th>Date</th><th>État</th></tr>
  <?php foreach ($candidatures as $c): ?>
    <tr>
      <td><?= e($c['titre']) ?></td>
      <td><?= e($c['raison_sociale']) ?></td>
      <td><?= e($c['ville']) ?></td>
      <td><?= e(date_fr($c['cree_le'])) ?></td>
      <td><?= badge('candidature', $c['statut']) ?></td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
