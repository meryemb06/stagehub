<h1>Affecter un encadrant</h1>
<p class="sous">Chaque stagiaire en cours doit avoir un encadrant. Sans cette affectation, l'encadrant ne voit personne.</p>
<?php if (!$encadrants): ?>
  <div class="carte"><p>Aucun encadrant actif. <a href="/admin/comptes">Crée un compte encadrant</a> d'abord.</p></div>
<?php elseif (!$stages): ?>
  <p class="aide">Aucun stage en cours.</p>
<?php else: ?>
<div class="table-wrap"><table>
  <tr><th>Stagiaire</th><th>Offre</th><th>Entreprise</th><th>Encadrant actuel</th><th>Affectation</th></tr>
  <?php foreach ($stages as $s): ?>
    <tr>
      <td><?= e($s['prenom'] . ' ' . $s['nom']) ?><br><span class="aide"><?= e($s['email']) ?></span></td>
      <td><?= e($s['titre']) ?></td>
      <td><?= e($s['raison_sociale']) ?></td>
      <td><?= $s['enc_nom'] ? 'Pr. ' . e($s['enc_nom']) : '— Non affecté' ?></td>
      <td>
        <form method="post" action="/admin/affectations">
          <?= csrf_field() ?>
          <input type="hidden" name="stage_id" value="<?= (int) $s['id'] ?>">
          <select name="encadrant_id" aria-label="Encadrant pour <?= e($s['prenom'] . ' ' . $s['nom']) ?>" required>
            <option value="">Choisir</option>
            <?php foreach ($encadrants as $enc): ?>
              <option value="<?= (int) $enc['id'] ?>" <?= (int) $s['encadrant_id'] === (int) $enc['id'] ? 'selected' : '' ?>>Pr. <?= e($enc['nom']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="actions"><button class="btn petit" type="submit">Affecter</button></div>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
