<p><a href="/offres">← Retour aux offres</a></p>
<article class="affiche">
  <div>
    <p class="entreprise"><?= e($offre['raison_sociale']) ?></p>
    <h1><?= e($offre['titre']) ?></h1>
    <div class="meta">
      <span class="chip"><?= e($offre['ville']) ?></span>
      <span class="chip"><?= (int) $offre['duree_mois'] ?> mois</span>
    </div>
  </div>
  <?php if ($offre['deja']): ?>
    <?= badge('candidature', $offre['deja']) ?>
  <?php else: ?>
    <a class="btn" href="/offres/<?= (int) $offre['id'] ?>/postuler">Postuler</a>
  <?php endif; ?>
</article>
<div class="deux">
  <div class="carte">
    <h2>Description</h2>
    <p><?= nl2br_e($offre['description']) ?></p>
    <?php if (lignes($offre['missions'])): ?>
      <h2>Missions</h2>
      <ul><?php foreach (lignes($offre['missions']) as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
  <div>
    <?php if ($offre['competences']): ?>
      <div class="carte"><h2>Compétences</h2><p><?= e($offre['competences']) ?></p></div>
    <?php endif; ?>
    <div class="carte">
      <h2>Document de présentation</h2>
      <?php if ($offre['document_stocke']): ?>
        <a href="/fichiers/offre/<?= (int) $offre['id'] ?>">Télécharger <?= e($offre['document_nom'] ?: 'le PDF') ?></a>
      <?php else: ?>
        <p class="aide">Aucun document joint.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
