<div class="entete">
  <div>
    <h1>Statistiques</h1>
    <p class="sous">Chiffres calculés dans la base, exportables en PDF ou en Excel.</p>
  </div>
  <div class="actions">
    <a class="btn clair" href="/admin/statistiques/pdf">Exporter en PDF</a>
    <a class="btn clair" href="/admin/statistiques/excel">Exporter en Excel</a>
  </div>
</div>
<div class="chiffres">
  <div class="chiffre"><b><?= (int) $stats['etudiants'] ?></b><span>Étudiants inscrits</span></div>
  <div class="chiffre"><b><?= (int) $stats['entreprises'] ?></b><span>Entreprises</span></div>
  <div class="chiffre"><b><?= (int) $stats['encadrants'] ?></b><span>Encadrants</span></div>
  <div class="chiffre"><b><?= (int) $stats['offres'] ?></b><span>Offres publiées</span></div>
  <div class="chiffre"><b><?= (int) $stats['stagesEnCours'] ?></b><span>Stages en cours</span></div>
  <div class="chiffre"><b><?= (int) $stats['stagesTermines'] ?></b><span>Stages terminés</span></div>
</div>
<div class="deux">
  <div class="carte">
    <h2>Candidatures</h2>
    <p>Acceptées (<?= (int) $stats['acceptees']['n'] ?>)</p>
    <div class="barre"><i style="width:<?= (int) $stats['acceptees']['pct'] ?>%"></i></div>
    <p>En attente (<?= (int) $stats['enAttente']['n'] ?>)</p>
    <div class="barre"><i style="width:<?= (int) $stats['enAttente']['pct'] ?>%"></i></div>
    <p>Refusées (<?= (int) $stats['refusees']['n'] ?>)</p>
    <div class="barre"><i style="width:<?= (int) $stats['refusees']['pct'] ?>%"></i></div>
  </div>
  <div class="carte">
    <h2>Notes moyennes</h2>
    <p>Entreprise : <b><?= $stats['moyenneEntreprise'] === null ? '—' : e($stats['moyenneEntreprise']) . '/20' ?></b></p>
    <p>Encadrant : <b><?= $stats['moyenneEncadrant'] === null ? '—' : e($stats['moyenneEncadrant']) . '/20' ?></b></p>
    <h2>Offres par ville</h2>
    <?php if (!$stats['villes']): ?><p class="aide">Aucune offre publiée.</p><?php endif; ?>
    <ul class="liste">
      <?php foreach ($stats['villes'] as $v): ?>
        <li><b><?= e($v['ville']) ?></b><small><?= (int) $v['n'] ?> offre<?= $v['n'] > 1 ? 's' : '' ?></small></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
