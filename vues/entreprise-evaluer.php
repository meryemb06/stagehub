<?php $ev = $evaluation; ?>
<p><a href="/entreprise/evaluations">← Évaluations</a></p>
<h1>Grille d'évaluation</h1>
<p class="sous"><?= e($stage['prenom'] . ' ' . $stage['nom']) ?> · <?= e($stage['titre']) ?></p>
<form class="carte" method="post" action="/entreprise/stages/<?= (int) $stage['id'] ?>/evaluation">
  <?= csrf_field() ?>
  <?php
    $criteres = [
      'assiduite' => 'Assiduité',
      'competences_tech' => 'Compétences techniques',
      'autonomie' => 'Autonomie',
      'communication' => 'Communication',
      'qualite' => 'Qualité du travail',
    ];
    $niveaux = ['Insuffisant', 'Moyen', 'Bien', 'Très bien'];
  ?>
  <?php foreach ($criteres as $cle => $label): ?>
    <label for="<?= e($cle) ?>"><?= e($label) ?></label>
    <select id="<?= e($cle) ?>" name="<?= e($cle) ?>" required>
      <option value="">Choisir</option>
      <?php foreach ($niveaux as $niveau): ?>
        <option value="<?= e($niveau) ?>" <?= ($ev[$cle] ?? '') === $niveau ? 'selected' : '' ?>><?= e($niveau) ?></option>
      <?php endforeach; ?>
    </select>
  <?php endforeach; ?>
  <label for="note">Note /20</label>
  <input id="note" name="note" type="number" min="0" max="20" step="0.5" required value="<?= e($ev['note'] ?? '') ?>">
  <label for="commentaire">Commentaire</label>
  <textarea id="commentaire" name="commentaire"><?= e($ev['commentaire'] ?? '') ?></textarea>
  <div class="actions"><button class="btn" type="submit">Enregistrer l'évaluation</button></div>
</form>
