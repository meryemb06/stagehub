<p><a href="/encadrant/stagiaires">← Stagiaires</a></p>
<h1><?= e($stage['prenom'] . ' ' . $stage['nom']) ?></h1>
<p class="sous"><?= e($stage['raison_sociale']) ?> · <?= e($stage['titre']) ?> · <?= e($stage['filiere'] ?: 'Filière non renseignée') ?></p>
<div class="carte">
  <h2>Journal de bord</h2>
  <?php if (!$journal): ?><p class="aide">L'étudiant n'a pas encore écrit dans son journal.</p><?php endif; ?>
  <?php foreach ($journal as $entree): ?>
    <div class="carte">
      <b><?= e(date_fr($entree['date_entree'])) ?></b>
      <p><?= e($entree['taches']) ?></p>
      <?php if ($entree['difficultes']): ?><p class="aide">Difficultés : <?= e($entree['difficultes']) ?></p><?php endif; ?>
      <?php if ($entree['feedback']): ?><div class="bulle"><small>Ton feedback</small><?= nl2br_e($entree['feedback']) ?></div><?php endif; ?>
      <form method="post" action="/encadrant/journal/<?= (int) $entree['id'] ?>/feedback">
        <?= csrf_field() ?>
        <label for="fb-<?= (int) $entree['id'] ?>">Feedback</label>
        <textarea id="fb-<?= (int) $entree['id'] ?>" name="feedback"><?= e($entree['feedback']) ?></textarea>
        <div class="actions"><button class="btn clair petit" type="submit">Enregistrer le feedback</button></div>
      </form>
    </div>
  <?php endforeach; ?>
</div>
<div class="carte">
  <h2>Commentaires de suivi</h2>
  <p class="aide">Visibles par l'étudiant, qui peut y répondre.</p>
  <?php foreach ($commentaires as $c): ?>
    <div class="bulle <?= (int) $c['auteur_id'] === (int) $user['id'] ? 'moi' : '' ?>">
      <small><?= (int) $c['auteur_id'] === (int) $user['id'] ? 'Moi' : e($c['prenom'] . ' ' . $c['nom']) ?> · <?= e(date_heure_fr($c['cree_le'])) ?></small>
      <?= nl2br_e($c['contenu']) ?>
    </div>
  <?php endforeach; ?>
  <form method="post" action="/encadrant/stages/<?= (int) $stage['id'] ?>/commentaire">
    <?= csrf_field() ?>
    <label for="contenu">Commentaire</label>
    <textarea id="contenu" name="contenu" required></textarea>
    <div class="actions"><button class="btn" type="submit">Envoyer</button></div>
  </form>
</div>
<div class="carte">
  <h2>Documents</h2>
  <?php if (!$documents): ?><p class="aide">Aucun document déposé.</p><?php endif; ?>
  <ul class="liste">
    <?php foreach ($documents as $d): ?>
      <li><?= $d['type'] === 'rapport' ? 'Rapport' : 'Convention' ?> · <?= badge('document', $d['statut']) ?>
        <?php if ($d['fichier_stocke']): ?> · <a href="/fichiers/document/<?= (int) $d['id'] ?>">Télécharger</a><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <p><a href="/encadrant/documents">Valider ou rejeter</a></p>
</div>
<form class="carte" method="post" action="/encadrant/stages/<?= (int) $stage['id'] ?>/note">
  <?= csrf_field() ?>
  <h2>Note et appréciation</h2>
  <label for="note">Note /20</label>
  <input id="note" name="note" type="number" min="0" max="20" step="0.5" required value="<?= e($note['note'] ?? '') ?>">
  <label for="appreciation">Appréciation</label>
  <textarea id="appreciation" name="appreciation" required><?= e($note['commentaire'] ?? '') ?></textarea>
  <div class="actions"><button class="btn" type="submit">Enregistrer la note</button></div>
</form>
<?php if ($entreprise): ?>
  <div class="carte">
    <h2>Évaluation de l'entreprise</h2>
    <p><b><?= e($entreprise['note']) ?>/20</b></p>
    <p class="aide">Assiduité <?= e($entreprise['assiduite']) ?>, compétences <?= e($entreprise['competences_tech']) ?>, autonomie <?= e($entreprise['autonomie']) ?>, communication <?= e($entreprise['communication']) ?>, qualité <?= e($entreprise['qualite']) ?>.</p>
    <?php if ($entreprise['commentaire']): ?><p><?= nl2br_e($entreprise['commentaire']) ?></p><?php endif; ?>
  </div>
<?php endif; ?>
<?php if ($stage['statut'] === 'en_cours'): ?>
  <form method="post" action="/encadrant/stages/<?= (int) $stage['id'] ?>/cloturer">
    <?= csrf_field() ?>
    <button class="btn" type="submit">Clôturer le stage</button>
  </form>
<?php else: ?>
  <p><span class="badge ok">Stage terminé</span></p>
<?php endif; ?>
