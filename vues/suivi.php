<h1>Journal et suivi</h1>
<?php if (!$stage): ?>
  <p class="sous">Tu n'as pas de stage en cours.</p>
  <div class="carte">
    <p>Quand une entreprise accepte ta candidature, le journal de bord et les commentaires de l'encadrant s'affichent ici.</p>
    <?php if ($passes): ?><p><a href="/historique">Voir mes stages précédents</a></p><?php endif; ?>
  </div>
<?php else: ?>
  <?php if (count($stages) > 1): ?>
    <form class="filtres" method="get" action="/suivi">
      <label>Stage
        <select name="stage">
          <?php foreach ($stages as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= (int) $stage['id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['titre']) ?> — <?= e($s['raison_sociale']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn" type="submit">Afficher</button>
    </form>
  <?php endif; ?>
  <p class="sous"><?= e($stage['raison_sociale']) ?> · <?= e($stage['titre']) ?><?php if ($stage['enc_nom']): ?> · Encadrant : Pr. <?= e($stage['enc_nom']) ?><?php else: ?> · Encadrant pas encore affecté<?php endif; ?></p>
  <?php
    $docsSuivi = [];
    foreach (q('SELECT type, statut FROM documents WHERE stage_id = ?', [$stage['id']]) as $doc) {
        $docsSuivi[$doc['type']] = $doc['statut'];
    }
  ?>
  <div class="carte">
    <h2>Où en est le dossier</h2>
    <ul class="checklist">
      <li><span>Convention déposée</span><?php if (empty($docsSuivi['convention'])): ?><span class="badge attente">À faire</span><?php else: ?><?= badge('document', $docsSuivi['convention']) ?><?php endif; ?></li>
      <li><span>Rapport déposé</span><?= !empty($docsSuivi['rapport']) ? badge('document', $docsSuivi['rapport']) : '<span class="badge attente">À faire</span>' ?></li>
      <li><span>Journal de bord</span><?= $journal ? '<span class="badge ok">'.count($journal).' entrée(s)</span>' : '<span class="badge attente">À faire</span>' ?></li>
      <li><span>Encadrant</span><?= $stage['enc_nom'] ? '<span class="badge ok">Pr. '.e($stage['enc_nom']).'</span>' : '<span class="badge attente">En attente</span>' ?></li>
    </ul>
  </div>
  <div class="carte">
    <h2>Journal de bord</h2>
    <?php if (!$journal): ?><p class="aide">Aucune entrée pour le moment.</p><?php endif; ?>
    <ul class="liste">
      <?php foreach ($journal as $entree): ?>
        <li>
          <b><?= e(date_fr($entree['date_entree'])) ?></b>
          <p>Tâches : <?= e($entree['taches']) ?></p>
          <?php if ($entree['difficultes']): ?><p>Difficultés : <?= e($entree['difficultes']) ?></p><?php endif; ?>
          <?php if ($entree['feedback']): ?><div class="bulle"><small>Encadrant<?php if ($entree['feedback_le']): ?> · <?= e(date_fr($entree['feedback_le'])) ?><?php endif; ?></small><?= nl2br_e($entree['feedback']) ?></div><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <form method="post" action="/suivi/journal">
      <?= csrf_field() ?>
      <input type="hidden" name="stage_id" value="<?= (int) $stage['id'] ?>">
      <label for="date_entree">Date</label>
      <input id="date_entree" name="date_entree" type="date" required value="<?= e($aujourdhui) ?>" max="<?= e($aujourdhui) ?>">
      <label for="taches">Tâches réalisées</label>
      <textarea id="taches" name="taches" required></textarea>
      <label for="difficultes">Difficultés rencontrées</label>
      <textarea id="difficultes" name="difficultes"></textarea>
      <div class="actions"><button class="btn" type="submit">Ajouter l'entrée</button></div>
    </form>
  </div>
  <div class="carte">
    <h2>Commentaires de l'encadrant</h2>
    <?php if (!$commentaires): ?><p class="aide">Pas encore de commentaire.</p><?php endif; ?>
    <?php foreach ($commentaires as $c): ?>
      <div class="bulle <?= (int) $c['auteur_id'] === (int) $user['id'] ? 'moi' : '' ?>">
        <small><?= (int) $c['auteur_id'] === (int) $user['id'] ? 'Moi' : e(nom_affiche($c)) ?> · <?= e(date_heure_fr($c['cree_le'])) ?></small>
        <?= nl2br_e($c['contenu']) ?>
      </div>
    <?php endforeach; ?>
    <?php if ($stage['encadrant_id']): ?>
      <form method="post" action="/suivi/commentaire">
        <?= csrf_field() ?>
        <input type="hidden" name="stage_id" value="<?= (int) $stage['id'] ?>">
        <label for="reponse">Répondre</label>
        <textarea id="reponse" name="contenu" required></textarea>
        <div class="actions"><button class="btn clair" type="submit">Envoyer la réponse</button></div>
      </form>
    <?php else: ?>
      <p class="aide">Les échanges commenceront quand l'administrateur t'aura affecté un encadrant.</p>
    <?php endif; ?>
  </div>
  <?php if ($evals['entreprise'] || $evals['encadrant']): ?>
    <div class="deux">
      <div class="carte">
        <h2>Note de l'entreprise</h2>
        <p><b><?= $evals['entreprise'] ? e($evals['entreprise']['note']) . '/20' : '—' ?></b></p>
        <?php if ($evals['entreprise'] && $evals['entreprise']['commentaire']): ?><p><?= nl2br_e($evals['entreprise']['commentaire']) ?></p><?php endif; ?>
      </div>
      <div class="carte">
        <h2>Note de l'encadrant</h2>
        <p><b><?= $evals['encadrant'] ? e($evals['encadrant']['note']) . '/20' : '—' ?></b></p>
        <?php if ($evals['encadrant'] && $evals['encadrant']['commentaire']): ?><p><?= nl2br_e($evals['encadrant']['commentaire']) ?></p><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
