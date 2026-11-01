<h2>Mot de passe oublié</h2>
<p class="sous">Indique ton e-mail. L'administrateur pourra réinitialiser ton mot de passe.</p>
<form action="/mot-de-passe-oublie" method="post">
  <?= csrf_field() ?>
  <label for="email">Adresse e-mail</label>
  <input id="email" name="email" type="email" required>
  <div class="actions"><button class="btn" type="submit">Transmettre la demande</button></div>
</form>
<p><a href="/">Retour à la connexion</a></p>
