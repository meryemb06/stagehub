<h1>Mes documents</h1>
<p class="sous">Convention de stage et rapport final, pour chaque stage en cours.</p>
<?php if (!$stages): ?>
  <div class="carte"><p>Aucune convention à déposer : une entreprise doit d'abord accepter ta candidature.</p><p><a href="/candidatures">Voir mes candidatures</a></p></div>
<?php else: ?>
  <?php if (count($stages) > 1): ?>
    <form class="filtres" method="get" action="/documents">
      <label>Stage
        <select name="stage">
          <?php foreach ($stages as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= $stage && (int) $stage['id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['titre']) ?> — <?= e($s['raison_sociale']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn" type="submit">Afficher</button>
    </form>
  <?php endif; ?>
  <?php if ($stage): ?>
    <p class="sous"><?= e($stage['raison_sociale']) ?> · <?= e($stage['titre']) ?><?php if ($stage['enc_nom']): ?> · Encadrant : Pr. <?= e($stage['enc_nom']) ?><?php else: ?> · Encadrant pas encore affecté<?php endif; ?></p>
    <div class="carte">
      <h2>Convention de stage</h2>
      <p>État : <?php if (!empty($docs['convention'])): ?><?= badge('document', $docs['convention']['statut']) ?><?php else: ?><span class="badge attente">Non déposée</span><?php endif; ?></p>
      <?php if (!empty($docs['convention']['commentaire']) && $docs['convention']['statut'] === 'rejete'): ?>
        <div class="bulle"><small>Motif du rejet</small><?= nl2br_e($docs['convention']['commentaire']) ?></div>
      <?php endif; ?>
      <?php if (!empty($docs['convention']['fichier_stocke'])): ?>
        <p><a href="/fichiers/document/<?= (int) $docs['convention']['id'] ?>">Télécharger <?= e($docs['convention']['fichier_nom'] ?: 'la convention') ?></a></p>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data" action="/documents/convention">
        <?= csrf_field() ?>
        <input type="hidden" name="stage_id" value="<?= (int) $stage['id'] ?>">
        <label for="sujet">Sujet du stage</label>
        <input id="sujet" name="sujet" required maxlength="180" value="<?= e($stage['sujet'] ?: $stage['titre']) ?>">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($stage['description_convention']) ?></textarea>
        <label for="date_debut">Date de début</label>
        <input id="date_debut" name="date_debut" type="date" value="<?= e($stage['date_debut']) ?>">
        <label for="date_fin">Date de fin</label>
        <input id="date_fin" name="date_fin" type="date" value="<?= e($stage['date_fin']) ?>">
        <label for="contact">Contact dans l'entreprise</label>
        <input id="contact" name="contact" maxlength="180" value="<?= e($stage['contact_entreprise']) ?>">
        <label for="fichier-conv">Fichier de la convention</label>
        <input id="fichier-conv" name="fichier" type="file" accept=".pdf,.doc,.docx,application/pdf">
        <div class="actions"><button class="btn" type="submit">Enregistrer la convention</button></div>
      </form>
    </div>
    <div class="carte">
      <h2>Rapport final</h2>
      <p>État : <?php if (!empty($docs['rapport'])): ?><?= badge('document', $docs['rapport']['statut']) ?><?php else: ?><span class="badge attente">Non déposé</span><?php endif; ?></p>
      <?php if (!empty($docs['rapport']['commentaire']) && $docs['rapport']['statut'] === 'rejete'): ?>
        <div class="bulle"><small>Motif du rejet</small><?= nl2br_e($docs['rapport']['commentaire']) ?></div>
      <?php endif; ?>
      <?php if (!empty($docs['rapport']['fichier_stocke'])): ?>
        <p><a href="/fichiers/document/<?= (int) $docs['rapport']['id'] ?>">Télécharger <?= e($docs['rapport']['fichier_nom'] ?: 'le rapport') ?></a></p>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data" action="/documents/rapport">
        <?= csrf_field() ?>
        <input type="hidden" name="stage_id" value="<?= (int) $stage['id'] ?>">
        <label for="fichier-rap">Rapport (PDF, DOC, DOCX)</label>
        <input id="fichier-rap" name="fichier" type="file" accept=".pdf,.doc,.docx,application/pdf" required>
        <div class="actions"><button class="btn" type="submit">Déposer le rapport</button></div>
      </form>
    </div>
  <?php endif; ?>
<?php endif; ?>
