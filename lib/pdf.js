const PDFDocument = require('pdfkit');
const { dateHeureFr } = require('./helpers');

function pdfBuffer(titre, lignes) {
  return new Promise((resolve, reject) => {
    const doc = new PDFDocument({ margin: 50, size: 'A4' });
    const chunks = [];
    doc.on('data', (c) => chunks.push(c));
    doc.on('end', () => resolve(Buffer.concat(chunks)));
    doc.on('error', reject);
    doc.fontSize(20).text(titre);
    doc.moveDown();
    doc.fontSize(12);
    for (const line of lignes) {
      doc.text(line, { width: 480 });
      doc.moveDown(0.35);
    }
    doc.end();
  });
}

function statsPdf(stats) {
  return new Promise((resolve, reject) => {
    const doc = new PDFDocument({ margin: 48, size: 'A4' });
    const chunks = [];
    doc.on('data', (c) => chunks.push(c));
    doc.on('end', () => resolve(Buffer.concat(chunks)));
    doc.on('error', reject);
    doc.fontSize(18).text('StageHub — Rapport statistique');
    doc.moveDown(0.3);
    doc.fontSize(11).fillColor('#444').text(`Export du ${dateHeureFr(new Date().toISOString())} (heure de Casablanca)`);
    doc.fillColor('#000').moveDown();
    const lignes = [
      `Étudiants inscrits : ${stats.etudiants}`,
      `Entreprises : ${stats.entreprises}`,
      `Encadrants : ${stats.encadrants}`,
      `Offres publiées : ${stats.offres}`,
      `Candidatures : ${stats.candidatures}`,
      `Stages en cours : ${stats.stagesEnCours}`,
      `Stages terminés : ${stats.stagesTermines}`,
      `Note moyenne entreprise : ${stats.moyenneEntreprise ?? '—'}`,
      `Note moyenne encadrant : ${stats.moyenneEncadrant ?? '—'}`,
      `Candidatures acceptées : ${stats.acceptees.n} (${stats.acceptees.pct} %)`,
      `Candidatures en attente : ${stats.enAttente.n} (${stats.enAttente.pct} %)`,
      `Candidatures refusées : ${stats.refusees.n} (${stats.refusees.pct} %)`,
    ];
    doc.fontSize(12);
    for (const line of lignes) doc.text(line);
    doc.moveDown();
    doc.fontSize(14).text('Stages');
    doc.moveDown(0.4);
    doc.fontSize(10);
    if (!stats.stages.length) doc.text('Aucun stage enregistré.');
    for (const s of stats.stages) {
      const noteE = s.note_encadrant == null ? '—' : `${s.note_encadrant}/20`;
      const noteP = s.note_entreprise == null ? '—' : `${s.note_entreprise}/20`;
      doc.text(`${s.prenom} ${s.nom} — ${s.titre} — ${s.raison_sociale} — ${s.statut === 'termine' ? 'Terminé' : 'En cours'} — encadrant ${noteE} — entreprise ${noteP}`);
      doc.moveDown(0.2);
    }
    doc.end();
  });
}

module.exports = { pdfBuffer, statsPdf };
