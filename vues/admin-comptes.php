<h1>Gestion des comptes</h1>
<p class="sous">Réinitialise un mot de passe, désactive un compte, ou ouvre un accès encadrant.</p>
<?php if ($demandes): ?>
  <div class="carte">
    <h2>Demandes de mot de passe</h2>
    <ul class="liste">
      <?php foreach ($demandes as $d): ?>
        <li>
          <b><?= e(nom_affiche($d)) ?></b>
          <small><?= e($d['email']) ?> · <?= e(role_label($d['role'])) ?> · <?= e(date_heure_fr($d['demande_le'])) ?></small>
          <form method="post" action="/admin/comptes/reinitialiser">
            <?= csrf_field() ?>
            <input type="hidden" name="utilisateur_id" value="<?= (int) $d['utilisateur_id'] ?>">
            <button class="btn petit" type="submit">Générer un mot de passe</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<form class="carte" method="post" action="/admin/encadrants">
  <?= csrf_field() ?>
  <h2>Créer un encadrant</h2>
  <label for="nom">Nom</label>
  <input id="nom" name="nom" required maxlength="80">
  <label for="prenom">Prénom</label>
  <input id="prenom" name="prenom" required maxlength="80">
  <label for="email">E-mail</label>
  <input id="email" name="email" type="email" required>
  <label for="mot_de_passe">Mot de passe (laisser vide pour en générer un)</label>
  <input id="mot_de_passe" name="mot_de_passe" type="text" minlength="8">
  <div class="actions"><button class="btn" type="submit">Créer le compte</button></div>
</form>
<form class="filtres" method="get" action="/admin/comptes">
  <label>Recherche
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, e-mail, filière…">
  </label>
  <button class="btn" type="submit">Rechercher</button>
</form>
<div class="table-wrap"><table>
  <tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Compte</th><th></th></tr>
  <?php foreach ($comptes as $c): ?>
    <tr>
      <td><?= e(nom_affiche($c)) ?><?php if ($c['filiere']): ?><br><span class="aide"><?= e($c['filiere']) ?></span><?php endif; ?></td>
      <td><?= e($c['email']) ?></td>
      <td><?= e(role_label($c['role'])) ?></td>
      <td><?= badge('compte', $c['actif']) ?></td>
      <td>
        <form method="post" action="/admin/comptes/reinitialiser">
          <?= csrf_field() ?>
          <input type="hidden" name="utilisateur_id" value="<?= (int) $c['id'] ?>">
          <button class="btn clair petit" type="submit">Réinitialiser</button>
        </form>
        <?php if ((int) $c['id'] !== (int) $user['id']): ?>
          <form method="post" action="/admin/comptes/basculer">
            <?= csrf_field() ?>
            <input type="hidden" name="utilisateur_id" value="<?= (int) $c['id'] ?>">
            <button class="btn petit <?= $c['actif'] ? 'danger' : '' ?>" type="submit"><?= $c['actif'] ? 'Désactiver' : 'Réactiver' ?></button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>
