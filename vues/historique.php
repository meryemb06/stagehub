<h1>Stages précédents</h1>
<p class="sous">L'historique des stages terminés, avec les notes et les appréciations.</p>
<?php if (!$stages): ?>
  <div class="carte"><p class="aide">Aucun stage précédent.</p></div>
<?php endif; ?>
<?php foreach ($stages as $s): ?>
  <article class="carte">
    <h2><?= e($s['titre']) ?></h2>
    <p class="sous"><?= e($s['raison_sociale']) ?><?php if ($s['enc_nom']): ?> · Pr. <?= e($s['enc_nom']) ?><?php endif; ?><?php if ($s['date_debut']): ?> · <?= e(date_fr($s['date_debut'])) ?> – <?= e(date_fr($s['date_fin'])) ?><?php endif; ?></p>
    <?php if ($s['sujet']): ?><p><b>Sujet :</b> <?= e($s['sujet']) ?></p><?php endif; ?>
    <div class="deux">
      <div>
        <p>Note de l'entreprise</p>
        <p><b><?= $s['entreprise_eval'] ? e($s['entreprise_eval']['note']) . '/20' : '—' ?></b></p>
        <?php if (!empty($s['entreprise_eval']['commentaire'])): ?><p><?= nl2br_e($s['entreprise_eval']['commentaire']) ?></p><?php endif; ?>
      </div>
      <div>
        <p>Note de l'encadrant</p>
        <p><b><?= $s['encadrant_eval'] ? e($s['encadrant_eval']['note']) . '/20' : '—' ?></b></p>
        <?php if (!empty($s['encadrant_eval']['commentaire'])): ?><p><?= nl2br_e($s['encadrant_eval']['commentaire']) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="actions">
      <?php foreach ($s['documents'] as $d): if ($d['fichier_stocke']): ?>
        <a class="btn clair petit" href="/fichiers/document/<?= (int) $d['id'] ?>"><?= $d['type'] === 'rapport' ? 'Rapport' : 'Convention' ?></a>
      <?php endif; endforeach; ?>
    </div>
  </article>
<?php endforeach; ?>
