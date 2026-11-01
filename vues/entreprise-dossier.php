<p><a href="/entreprise/candidatures">← Candidatures</a></p>
<h1><?= e($dossier['prenom'] . ' ' . $dossier['nom']) ?></h1>
<p class="sous"><?= e($dossier['titre']) ?> · <?= e($dossier['ville']) ?> · <?= (int) $dossier['duree_mois'] ?> mois</p>
<div class="carte">
  <p>Filière : <?= e($dossier['filiere'] ?: '—') ?> · <?= e($dossier['email']) ?></p>
  <p>État : <?= badge('candidature', $dossier['statut']) ?></p>
  <h2>Lettre de motivation</h2>
  <p><?= nl2br_e($dossier['lettre']) ?></p>
  <div class="actions">
    <?php if ($dossier['cv_stocke']): ?><a class="btn clair" href="/fichiers/candidature/<?= (int) $dossier['id'] ?>/cv">CV</a><?php endif; ?>
    <?php if ($dossier['convention_stocke']): ?><a class="btn clair" href="/fichiers/candidature/<?= (int) $dossier['id'] ?>/convention">Convention</a><?php endif; ?>
  </div>
  <?php if ($dossier['statut'] === 'en_attente'): ?>
    <form method="post" action="/entreprise/candidatures/<?= (int) $dossier['id'] ?>/decision">
      <?= csrf_field() ?>
      <div class="actions">
        <button class="btn ok" name="decision" value="acceptee" type="submit">Accepter</button>
        <button class="btn danger" name="decision" value="refusee" type="submit">Refuser</button>
      </div>
    </form>
  <?php endif; ?>
</div>
