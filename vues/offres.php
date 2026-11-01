<p class="entreprise">Catalogue</p>
<h1>Offres de stage</h1>
<p class="sous">Recherche par mot-clé, ville ou durée. Les compétences enregistrées sont aussi prises en compte.</p>
<form class="filtres" method="get" action="/offres">
  <label>Recherche
    <input name="recherche" type="search" value="<?= e($recherche) ?>" placeholder="PHP, Tanger, entreprise…">
  </label>
  <label>Ville
    <select name="ville">
      <option value="">Toutes les villes</option>
      <?php foreach ($villes as $v): ?>
        <option value="<?= e($v) ?>" <?= $ville === $v ? 'selected' : '' ?>><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Durée
    <select name="duree">
      <option value="">Toutes</option>
      <?php foreach ($durees as $d): ?>
        <option value="<?= e($d) ?>" <?= (string) $duree === (string) $d ? 'selected' : '' ?>><?= e($d) ?> mois</option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn" type="submit">Filtrer</button>
</form>
<?php if (!$offres): ?>
  <p class="aide">Aucune offre ne correspond à ta recherche.</p>
<?php endif; ?>
<div class="cartes">
<?php foreach ($offres as $o): ?>
  <article class="carte">
    <p class="duree"><?= (int) $o['duree_mois'] ?><small>mois</small></p>
    <p class="entreprise"><?= e($o['raison_sociale']) ?></p>
    <h2><?= e($o['titre']) ?></h2>
    <div class="meta"><span class="chip"><?= e($o['ville']) ?></span></div>
    <?php if ($o['competences']): ?><p class="aide"><?= e($o['competences']) ?></p><?php endif; ?>
    <p class="resume"><?= e($o['description']) ?></p>
    <div class="actions">
      <a class="btn clair petit" href="/offres/<?= (int) $o['id'] ?>">Consulter</a>
      <?php if ($o['deja']): ?>
        <?= badge('candidature', $o['deja']) ?>
      <?php else: ?>
        <a class="btn petit" href="/offres/<?= (int) $o['id'] ?>/postuler">Postuler</a>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
</div>
