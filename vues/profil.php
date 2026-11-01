<h1>Mon profil</h1>
<form class="carte" method="post" enctype="multipart/form-data" action="/profil">
  <?= csrf_field() ?>
  <label for="nom">Nom</label>
  <input id="nom" name="nom" required maxlength="80" value="<?= e($profil['nom']) ?>">
  <label for="prenom">Prénom</label>
  <input id="prenom" name="prenom" required maxlength="80" value="<?= e($profil['prenom']) ?>">
  <?php if ($profil['role'] === 'etudiant'): ?>
    <label for="filiere">Filière</label>
    <input id="filiere" name="filiere" required maxlength="120" value="<?= e($profil['filiere']) ?>">
    <label for="competences">Compétences</label>
    <input id="competences" name="competences" maxlength="300" value="<?= e($profil['competences']) ?>" placeholder="PHP, SQL, rédaction">
    <p class="aide">Sépare les compétences par des virgules. Elles sont enregistrées dans la base.</p>
    <label for="cv">CV</label>
    <input id="cv" name="cv" type="file" accept=".pdf,.doc,.docx,application/pdf">
    <?php if ($profil['cv_nom']): ?><p class="aide">CV actuel : <a href="/fichiers/cv/<?= (int) $profil['id'] ?>"><?= e($profil['cv_nom']) ?></a></p><?php endif; ?>
  <?php elseif ($profil['role'] === 'entreprise'): ?>
    <label for="raison_sociale">Raison sociale</label>
    <input id="raison_sociale" name="raison_sociale" required maxlength="140" value="<?= e($profil['raison_sociale']) ?>">
  <?php endif; ?>
  <div class="actions"><button class="btn" type="submit">Enregistrer</button></div>
</form>
