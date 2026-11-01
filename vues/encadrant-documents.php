<h1>Valider les documents</h1>
<p class="sous">Conventions et rapports déposés par tes stagiaires.</p>
<?php if (!$documents): ?>
  <p class="aide">Aucun document déposé.</p>
<?php endif; ?>
<?php foreach ($documents as $d): ?>
  <article class="carte">
    <h2><?= $d['type'] === 'rapport' ? 'Rapport final' : 'Convention de stage' ?></h2>
    <p><?= e($d['prenom'] . ' ' . $d['nom']) ?> · <?= e($d['raison_sociale']) ?> · <?= e($d['titre']) ?></p>
    <p>Déposé le <?= e(date_fr($d['depose_le'])) ?> · <?= badge('document', $d['statut']) ?></p>
    <?php if ($d['fichier_stocke']): ?>
      <p><a href="/fichiers/document/<?= (int) $d['id'] ?>">Télécharger <?= e($d['fichier_nom'] ?: 'le fichier') ?></a></p>
    <?php else: ?>
      <p class="aide">Aucun fichier joint.</p>
    <?php endif; ?>
    <?php if ($d['statut'] === 'rejete' && $d['commentaire']): ?>
      <div class="bulle"><small>Motif envoyé</small><?= nl2br_e($d['commentaire']) ?></div>
    <?php endif; ?>
    <?php if ($d['statut'] !== 'valide'): ?>
      <form method="post" action="/encadrant/documents/<?= (int) $d['id'] ?>/valider">
        <?= csrf_field() ?>
        <div class="actions"><button class="btn ok" type="submit">Valider</button></div>
      </form>
      <form method="post" action="/encadrant/documents/<?= (int) $d['id'] ?>/rejeter">
        <?= csrf_field() ?>
        <label for="motif-<?= (int) $d['id'] ?>">Motif du rejet</label>
        <textarea id="motif-<?= (int) $d['id'] ?>" name="commentaire" required></textarea>
        <div class="actions"><button class="btn danger" type="submit">Rejeter avec commentaire</button></div>
      </form>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
