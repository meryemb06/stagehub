const ExcelJS = require('exceljs');
const { dateHeureFr } = require('./helpers');

async function statsExcel(stats) {
  const wb = new ExcelJS.Workbook();
  wb.creator = 'StageHub';
  wb.created = new Date();

  const synthese = wb.addWorksheet('Synthèse');
  synthese.addRow(['Indicateur', 'Valeur']);
  synthese.addRow(['Export', dateHeureFr(new Date().toISOString())]);
  synthese.addRow(['Étudiants inscrits', stats.etudiants]);
  synthese.addRow(['Entreprises', stats.entreprises]);
  synthese.addRow(['Encadrants', stats.encadrants]);
  synthese.addRow(['Offres publiées', stats.offres]);
  synthese.addRow(['Candidatures', stats.candidatures]);
  synthese.addRow(['Candidatures acceptées', stats.acceptees.n]);
  synthese.addRow(['Candidatures en attente', stats.enAttente.n]);
  synthese.addRow(['Candidatures refusées', stats.refusees.n]);
  synthese.addRow(['Stages en cours', stats.stagesEnCours]);
  synthese.addRow(['Stages terminés', stats.stagesTermines]);
  synthese.addRow(['Note moyenne entreprise', stats.moyenneEntreprise ?? '']);
  synthese.addRow(['Note moyenne encadrant', stats.moyenneEncadrant ?? '']);
  synthese.getRow(1).font = { bold: true };
  synthese.columns = [{ width: 36 }, { width: 28 }];

  const villes = wb.addWorksheet('Villes');
  villes.addRow(['Ville', 'Offres publiées']);
  for (const v of stats.villes) villes.addRow([v.ville, v.n]);
  villes.getRow(1).font = { bold: true };

  const stages = wb.addWorksheet('Stages');
  stages.addRow(['Étudiant', 'E-mail', 'Entreprise', 'Offre', 'Encadrant', 'Statut', 'Note encadrant', 'Note entreprise', 'Début', 'Fin']);
  for (const s of stats.stages) {
    stages.addRow([
      `${s.prenom} ${s.nom}`.trim(),
      s.email,
      s.raison_sociale,
      s.titre,
      s.enc_nom ? `Pr. ${s.enc_nom}` : '',
      s.statut === 'termine' ? 'Terminé' : 'En cours',
      s.note_encadrant ?? '',
      s.note_entreprise ?? '',
      s.date_debut || '',
      s.date_fin || '',
    ]);
  }
  stages.getRow(1).font = { bold: true };
  stages.columns.forEach((col) => { col.width = 24; });

  const candidatures = wb.addWorksheet('Candidatures');
  candidatures.addRow(['Étudiant', 'E-mail', 'Offre', 'Entreprise', 'Statut', 'Date']);
  for (const c of stats.candidaturesDetail) {
    const statut = { en_attente: 'En attente', acceptee: 'Acceptée', refusee: 'Refusée' }[c.statut] || c.statut;
    candidatures.addRow([
      `${c.prenom} ${c.nom}`.trim(),
      c.email,
      c.titre,
      c.raison_sociale,
      statut,
      c.cree_le,
    ]);
  }
  candidatures.getRow(1).font = { bold: true };

  return wb.xlsx.writeBuffer();
}

module.exports = { statsExcel };
