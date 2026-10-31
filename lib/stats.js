function part(map, statut, total) {
  const n = map[statut] || 0;
  return { n, pct: total ? Math.round((n / total) * 100) : 0 };
}

function collectStats(db) {
  const count = (sql) => db.prepare(sql).get().n;
  const rows = db.prepare('SELECT statut, COUNT(*) AS n FROM candidatures GROUP BY statut').all();
  const map = Object.fromEntries(rows.map((r) => [r.statut, r.n]));
  const candidatures = rows.reduce((sum, r) => sum + r.n, 0);
  const moyenne = (col) => db.prepare(
    `SELECT ROUND(AVG(${col}), 2) AS n FROM stages WHERE ${col} IS NOT NULL`
  ).get().n;

  const stages = db.prepare(`
    SELECT s.*, etu.prenom, etu.nom, etu.email, ent.raison_sociale, o.titre,
           enc.nom AS enc_nom
    FROM stages s
    JOIN utilisateurs etu ON etu.id = s.etudiant_id
    JOIN utilisateurs ent ON ent.id = s.entreprise_id
    JOIN offres o ON o.id = s.offre_id
    LEFT JOIN utilisateurs enc ON enc.id = s.encadrant_id
    ORDER BY s.cree_le DESC, s.id DESC
  `).all();

  const candidaturesDetail = db.prepare(`
    SELECT c.*, etu.prenom, etu.nom, etu.email, o.titre, ent.raison_sociale
    FROM candidatures c
    JOIN utilisateurs etu ON etu.id = c.etudiant_id
    JOIN offres o ON o.id = c.offre_id
    JOIN utilisateurs ent ON ent.id = o.entreprise_id
    ORDER BY c.cree_le DESC
  `).all();

  return {
    etudiants: count(`SELECT COUNT(*) AS n FROM utilisateurs WHERE role = 'etudiant'`),
    entreprises: count(`SELECT COUNT(*) AS n FROM utilisateurs WHERE role = 'entreprise'`),
    encadrants: count(`SELECT COUNT(*) AS n FROM utilisateurs WHERE role = 'encadrant'`),
    offres: count('SELECT COUNT(*) AS n FROM offres WHERE retiree = 0'),
    candidatures,
    acceptees: part(map, 'acceptee', candidatures),
    enAttente: part(map, 'en_attente', candidatures),
    refusees: part(map, 'refusee', candidatures),
    stagesEnCours: count(`SELECT COUNT(*) AS n FROM stages WHERE statut = 'en_cours'`),
    stagesTermines: count(`SELECT COUNT(*) AS n FROM stages WHERE statut = 'termine'`),
    moyenneEntreprise: moyenne('note_entreprise'),
    moyenneEncadrant: moyenne('note_encadrant'),
    villes: db.prepare(`
      SELECT ville, COUNT(*) AS n FROM offres WHERE retiree = 0 GROUP BY ville ORDER BY n DESC, ville
    `).all(),
    stages,
    candidaturesDetail,
  };
}

module.exports = { collectStats };
