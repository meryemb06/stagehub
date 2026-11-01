<p><a href="/offres/<?= (int) $offre['id'] ?>">← <?= e($offre['titre']) ?></a></p>
<h1>Postuler</h1>
<p class="sous"><?= e($offre['raison_sociale']) ?> · <?= e($offre['ville']) ?> · <?= (int) $offre['duree_mois'] ?> mois</p>
<form class="carte" method="post" enctype="multipart/form-data" action="/offres/<?= (int) $offre['id'] ?>/postuler">
  <?= csrf_field() ?>
  <label for="lettre">Lettre de motivation</label>
  <textarea id="lettre" name="lettre" required minlength="20"></textarea>
  <label for="cv">CV (PDF, DOC, DOCX)</label>
  <input id="cv" name="cv" type="file" accept=".pdf,.doc,.docx,application/pdf">
  <p class="aide nom-fichier"><?php if ($user['cv_nom']): ?>CV du profil utilisé si tu n'en déposes pas un nouveau : <?= e($user['cv_nom']) ?><?php endif; ?></p>
  <label for="convention">Convention de stage, si tu l'as déjà</label>
  <input id="convention" name="convention" type="file" accept=".pdf,.doc,.docx,application/pdf">
  <p class="aide">Tu pourras aussi la déposer plus tard, une fois le stage accepté.</p>
  <div class="actions"><button class="btn" type="submit">Envoyer la candidature</button></div>
</form>
