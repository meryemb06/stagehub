<h1>Candidatures reçues</h1>
<p class="sous">Ouvre le dossier avant d'accepter ou de refuser.</p>
<form class="filtres" method="get" action="/entreprise/candidatures">
  <label>Offre
    <select name="offre">
      <option value="">Toutes les offres</option>
      <?php foreach ($offres as $o): ?>
        <option value="<?= (int) $o['id'] ?>" <?= (string) $filtre === (string) $o['id'] ? 'selected' : '' ?>><?= e($o['titre']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn" type="submit">Filtrer</button>
</form>
<?php if (!$candidatures): ?>
  <p class="aide">Aucune candidature pour le moment.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <tr><th>Étudiant</th><th>Filière</th><th>Offre</th><th>Date</th><th>État</th><th></th></tr>
  <?php foreach ($candidatures as $c): ?>
    <tr>
      <td><?= e($c['prenom'] . ' ' . $c['nom']) ?></td>
      <td><?= e($c['filiere'] ?: '—') ?></td>
      <td><?= e($c['titre']) ?></td>
      <td><?= e(date_fr($c['cree_le'])) ?></td>
      <td><?= badge('candidature', $c['statut']) ?></td>
      <td><a href="/entreprise/candidatures/<?= (int) $c['id'] ?>">Consulter le dossier</a></td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
