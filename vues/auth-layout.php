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
<div class="porte">
  <section class="vitrine">
    <p class="logo">Stage<span>Hub</span></p>
    <h1>Tout le stage, du premier clic à la note.</h1>
    <p>Les entreprises publient, les étudiants postulent, les encadrants valident. L'administrateur relie les trois.</p>
    <div class="etapes">
      <div class="etape"><b>01 · Publier</b><br>Titre, description, durée, ville et document.</div>
      <div class="etape"><b>02 · Postuler</b><br>CV, lettre de motivation et convention.</div>
      <div class="etape"><b>03 · Suivre</b><br>Journal, commentaires, rapport et note.</div>
    </div>
  </section>
  <main class="panneau">
    <div class="boite">
      <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
      <?= $contenu ?>
    </div>
  </main>
</div>
</body>
</html>
