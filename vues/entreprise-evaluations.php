<h1>Évaluer un stagiaire</h1>
<p class="sous">Grille d'évaluation et commentaire de fin de stage.</p>
<?php if (!$stages): ?>
  <p class="aide">Aucun stagiaire pour le moment. Accepte d'abord une candidature.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <tr><th>Stagiaire</th><th>Offre</th><th>État</th><th>Note</th><th></th></tr>
  <?php foreach ($stages as $s): ?>
    <tr>
      <td><?= e($s['prenom'] . ' ' . $s['nom']) ?><?php if ($s['filiere']): ?><br><span class="aide"><?= e($s['filiere']) ?></span><?php endif; ?></td>
      <td><?= e($s['titre']) ?></td>
      <td><?= badge('stage', $s['statut']) ?></td>
      <td><?= $s['note_entreprise'] === null ? '—' : e($s['note_entreprise']) . '/20' ?></td>
      <td><a href="/entreprise/stages/<?= (int) $s['id'] ?>/evaluation">Remplir la grille</a></td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
