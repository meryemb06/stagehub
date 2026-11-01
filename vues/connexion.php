<h2>Se connecter</h2>
<p class="sous">E-mail et mot de passe. Les comptes de démonstration ouvrent chaque rôle.</p>
<form action="/connexion" method="post">
  <?= csrf_field() ?>
  <label for="email">Adresse e-mail</label>
  <input id="email" name="email" type="email" autocomplete="username" required>
  <label for="mot_de_passe">Mot de passe</label>
  <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="current-password" required>
  <div class="actions"><button class="btn" type="submit">Entrer</button></div>
</form>
<p><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a></p>
<p>Pas de compte ? <a href="/inscription">S'inscrire</a></p>
<?php if (!empty($demos)): ?>
<div>
  <h2>Comptes de démonstration</h2>
  <p class="aide">Mot de passe commun : <?= e(DEMO_PASSWORD) ?></p>
  <div class="demos">
    <?php foreach ($demos as $demo): ?>
      <form method="post" action="/connexion">
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= e($demo['email']) ?>">
        <input type="hidden" name="mot_de_passe" value="<?= e(DEMO_PASSWORD) ?>">
        <span><strong><?= e(nom_affiche($demo)) ?></strong><br><?= e(role_label($demo['role'])) ?> · <?= e($demo['email']) ?></span>
        <button class="btn petit clair" type="submit">Entrer</button>
      </form>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
