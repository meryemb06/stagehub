<h2>Créer un compte</h2>
<p class="sous">Étudiant ou entreprise. Les encadrants sont créés par l'administration.</p>
<form action="/inscription" method="post">
  <?= csrf_field() ?>
  <label for="role">Je suis</label>
  <select id="role" name="role">
    <option value="etudiant">Étudiant</option>
    <option value="entreprise">Entreprise</option>
  </select>
  <label for="nom">Nom</label>
  <input id="nom" name="nom" required maxlength="80">
  <label for="prenom">Prénom</label>
  <input id="prenom" name="prenom" required maxlength="80">
  <label for="filiere">Filière (étudiants)</label>
  <input id="filiere" name="filiere" maxlength="120">
  <label for="raison_sociale">Raison sociale (entreprises)</label>
  <input id="raison_sociale" name="raison_sociale" maxlength="140">
  <label for="email">Adresse e-mail</label>
  <input id="email" name="email" type="email" required autocomplete="username">
  <label for="mot_de_passe">Mot de passe</label>
  <input id="mot_de_passe" name="mot_de_passe" type="password" minlength="8" required autocomplete="new-password">
  <p class="aide">8 caractères minimum.</p>
  <div class="actions"><button class="btn" type="submit">Créer mon compte</button></div>
</form>
<p>Déjà inscrit ? <a href="/">Se connecter</a></p>
