<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titre) ?> · StageHub</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,640&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/app.css">
</head>
<body>
<button class="burger" type="button" aria-expanded="false" aria-label="Ouvrir le menu"><span></span><span></span><span></span></button>
<div class="voile" hidden></div>
<div class="app">
<aside class="rail">
  <a class="logo" href="<?= e(home_for($user['role'])) ?>">Stage<span>Hub</span></a>
  <p class="role-pill"><?= e(role_label($user['role'])) ?></p>
  <nav>
    <?php if ($user['role'] === 'etudiant'): ?>
      <a class="lien <?= $active === 'offres' ? 'actif' : '' ?>" href="/offres">Offres</a>
      <a class="lien <?= $active === 'candidatures' ? 'actif' : '' ?>" href="/candidatures">Candidatures</a>
      <a class="lien <?= $active === 'documents' ? 'actif' : '' ?>" href="/documents">Documents</a>
      <a class="lien <?= $active === 'suivi' ? 'actif' : '' ?>" href="/suivi">Journal et suivi</a>
      <a class="lien <?= $active === 'historique' ? 'actif' : '' ?>" href="/historique">Stages précédents</a>
    <?php elseif ($user['role'] === 'entreprise'): ?>
      <a class="lien <?= $active === 'offres' ? 'actif' : '' ?>" href="/entreprise/offres">Mes offres</a>
      <a class="lien <?= $active === 'publier' ? 'actif' : '' ?>" href="/entreprise/offres/nouvelle">Publier une offre</a>
      <a class="lien <?= $active === 'candidatures' ? 'actif' : '' ?>" href="/entreprise/candidatures">Candidatures reçues</a>
      <a class="lien <?= $active === 'evaluations' ? 'actif' : '' ?>" href="/entreprise/evaluations">Évaluer un stagiaire</a>
    <?php elseif ($user['role'] === 'encadrant'): ?>
      <a class="lien <?= $active === 'stagiaires' ? 'actif' : '' ?>" href="/encadrant/stagiaires">Stagiaires assignés</a>
      <a class="lien <?= $active === 'documents' ? 'actif' : '' ?>" href="/encadrant/documents">Valider les documents</a>
    <?php else: ?>
      <a class="lien <?= $active === 'comptes' ? 'actif' : '' ?>" href="/admin/comptes">Comptes</a>
      <a class="lien <?= $active === 'affectations' ? 'actif' : '' ?>" href="/admin/affectations">Affecter un encadrant</a>
      <a class="lien <?= $active === 'stats' ? 'actif' : '' ?>" href="/admin/statistiques">Statistiques</a>
    <?php endif; ?>
    <?php if ($user['role'] !== 'admin'): ?>
      <a class="lien <?= $active === 'notifications' ? 'actif' : '' ?>" href="/notifications">Notifications<?php if ($notifCount): ?> (<?= (int) $notifCount ?>)<?php endif; ?></a>
      <a class="lien <?= $active === 'profil' ? 'actif' : '' ?>" href="/profil">Mon profil</a>
    <?php endif; ?>
  </nav>
  <div class="bas">
    <div class="qui">
      <strong><?= e(nom_affiche($user)) ?></strong>
      <small><?= e($user['email']) ?></small>
    </div>
    <form class="deco" method="post" action="/deconnexion">
      <?= csrf_field() ?>
      <button type="submit">Se déconnecter</button>
    </form>
  </div>
</aside>
<main class="contenu">
<?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<?= $contenu ?>
</main>
</div>
<script src="/js/app.js"></script>
</body>
</html>
