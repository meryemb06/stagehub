function dossierEtat(stage, documents, nbJournal) {
  const conv = documents.find((d) => d.type === 'convention') || null;
  const rap = documents.find((d) => d.type === 'rapport') || null;
  const items = [
    { label: 'Convention déposée', ok: Boolean(conv) },
    { label: 'Convention validée', ok: conv?.statut === 'valide' },
    { label: 'Journal de bord commencé', ok: nbJournal > 0 },
    { label: 'Rapport final déposé', ok: Boolean(rap) },
    { label: 'Rapport validé', ok: rap?.statut === 'valide' },
    { label: 'Évaluation de l\'entreprise', ok: stage.note_entreprise != null },
    { label: 'Note de l\'encadrant', ok: stage.note_encadrant != null },
  ];
  const faits = items.filter((item) => item.ok).length;
  const pourcent = stage.statut === 'termine' ? 100 : Math.round((faits / items.length) * 100);
  return { items, pourcent, conv, rap };
}

function enrichir(db, stage) {
  const documents = db.prepare('SELECT * FROM documents WHERE stage_id = ? ORDER BY id').all(stage.id);
  const nbJournal = db.prepare('SELECT COUNT(*) AS n FROM journal WHERE stage_id = ?').get(stage.id).n;
  return { ...stage, documents, nbJournal, etat: dossierEtat(stage, documents, nbJournal) };
}

module.exports = { dossierEtat, enrichir };
