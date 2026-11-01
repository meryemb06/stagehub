<div class="entete">
  <div>
    <h1>Mes offres</h1>
    <p class="sous">Annonces publiées par <?= e($user['raison_sociale'] ?: 'ton entreprise') ?>.</p>
  </div>
  <a class="btn" href="/entreprise/offres/nouvelle">Publier une offre</a>
</div>
<?php if (!$offres): ?>
  <p class="aide">Tu n'as pas encore publié d'offre.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <tr><th>Titre</th><th>Ville</th><th>Durée</th><th>Candidatures</th><th></th></tr>
  <?php foreach ($offres as $o): ?>
    <tr>
      <td><?= e($o['titre']) ?> <?php if ($o['retiree']): ?><span class="badge ko">Retirée</span><?php endif; ?></td>
      <td><?= e($o['ville']) ?></td>
      <td><?= (int) $o['duree_mois'] ?> mois</td>
      <td><a href="/entreprise/candidatures?offre=<?= (int) $o['id'] ?>"><?= (int) $o['nb'] ?></a></td>
      <td>
        <?php if (!$o['retiree']): ?>
          <a href="/entreprise/offres/<?= (int) $o['id'] ?>/modifier">Modifier</a>
          <form method="post" action="/entreprise/offres/<?= (int) $o['id'] ?>/supprimer" onsubmit="return confirm('Supprimer ou retirer cette offre ?');">
            <?= csrf_field() ?>
            <button class="btn danger petit" type="submit">Supprimer</button>
          </form>
        <?php else: ?>
          <span class="aide">Conservée pour les stages acceptés</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
