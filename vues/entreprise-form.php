<h1><?= $offre ? 'Modifier l\'offre' : 'Publier une offre' ?></h1>
<p class="sous">Titre, description, durée, ville, et un document de présentation si tu en as un.</p>
<form class="carte" method="post" enctype="multipart/form-data" action="<?= $offre ? '/entreprise/offres/' . (int) $offre['id'] : '/entreprise/offres' ?>">
  <?= csrf_field() ?>
  <label for="titre">Titre</label>
  <input id="titre" name="titre" required maxlength="140" value="<?= e($offre['titre'] ?? '') ?>">
  <label for="description">Description</label>
  <textarea id="description" name="description" required><?= e($offre['description'] ?? '') ?></textarea>
  <label for="missions">Missions</label>
  <textarea id="missions" name="missions"><?= e($offre['missions'] ?? '') ?></textarea>
  <label for="competences">Compétences demandées</label>
  <input id="competences" name="competences" value="<?= e($offre['competences'] ?? '') ?>" placeholder="PHP, SQL, rédaction">
  <label for="duree_mois">Durée (mois)</label>
  <input id="duree_mois" name="duree_mois" type="number" min="1" max="24" required value="<?= e($offre['duree_mois'] ?? 3) ?>">
  <label for="ville">Ville</label>
  <input id="ville" name="ville" required maxlength="80" value="<?= e($offre['ville'] ?? '') ?>">
  <label for="document">Document de présentation</label>
  <input id="document" name="document" type="file" accept=".pdf,.doc,.docx,application/pdf">
  <?php if (!empty($offre['document_nom'])): ?>
    <p class="aide">Document actuel : <a href="/fichiers/offre/<?= (int) $offre['id'] ?>"><?= e($offre['document_nom']) ?></a></p>
  <?php endif; ?>
  <div class="actions">
    <button class="btn" type="submit"><?= $offre ? 'Enregistrer' : 'Publier' ?></button>
    <a class="btn clair" href="/entreprise/offres">Annuler</a>
  </div>
</form>
