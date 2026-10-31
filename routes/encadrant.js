const express = require('express');
const { str, idParam, notifier, homeFor } = require('../lib/helpers');
const { enrichir } = require('../lib/stages');

module.exports = function encadrantRoutes({ db, flash }) {
  const router = express.Router();

  router.use('/encadrant', (req, res, next) => {
    if (!res.locals.user) return res.redirect('/');
    if (res.locals.user.role !== 'encadrant') return res.redirect(homeFor(res.locals.user.role));
    next();
  });

  function mesStages(userId, statut) {
    return db.prepare(`
      SELECT s.*, o.titre, etu.prenom, etu.nom, etu.filiere, ent.raison_sociale
      FROM stages s
      JOIN offres o ON o.id = s.offre_id
      JOIN utilisateurs etu ON etu.id = s.etudiant_id
      JOIN utilisateurs ent ON ent.id = s.entreprise_id
      WHERE s.encadrant_id = ? AND s.statut = ?
      ORDER BY s.cree_le DESC
    `).all(userId, statut).map((s) => enrichir(db, s));
  }

  router.get('/encadrant/stagiaires', (req, res) => {
    const user = res.locals.user;
    res.render('encadrant/stagiaires', {
      titre: 'Mes stagiaires',
      active: 'stagiaires',
      enCours: mesStages(user.id, 'en_cours'),
      termines: mesStages(user.id, 'termine'),
    });
  });

  router.get('/encadrant/stagiaires/:id', (req, res) => {
    const id = idParam(req.params.id);
    const row = id && db.prepare(`
      SELECT s.*, o.titre, o.ville, etu.prenom, etu.nom, etu.filiere, etu.email, ent.raison_sociale
      FROM stages s
      JOIN offres o ON o.id = s.offre_id
      JOIN utilisateurs etu ON etu.id = s.etudiant_id
      JOIN utilisateurs ent ON ent.id = s.entreprise_id
      WHERE s.id = ? AND s.encadrant_id = ?
    `).get(id, res.locals.user.id);
    if (!row) return res.status(404).send('Stagiaire introuvable.');
    const stage = enrichir(db, row);
    const journal = db.prepare('SELECT * FROM journal WHERE stage_id = ? ORDER BY date_entree DESC, id DESC').all(stage.id);
    const commentaires = db.prepare(`
      SELECT c.*, u.role, u.nom, u.prenom, u.raison_sociale
      FROM commentaires c JOIN utilisateurs u ON u.id = c.auteur_id
      WHERE c.stage_id = ? ORDER BY c.cree_le ASC, c.id ASC
    `).all(stage.id);
    res.render('encadrant/suivi', { titre: 'Suivi du stagiaire', active: 'stagiaires', stage, journal, commentaires });
  });

  router.post('/encadrant/journal/:id/feedback', (req, res) => {
    const id = idParam(req.params.id);
    const entree = id && db.prepare(`
      SELECT j.*, s.encadrant_id, s.etudiant_id, s.id AS stage_id
      FROM journal j JOIN stages s ON s.id = j.stage_id
      WHERE j.id = ? AND s.encadrant_id = ?
    `).get(id, res.locals.user.id);
    const feedback = str(req.body.feedback);
    if (!entree) {
      flash(req, 'ko', 'Entrée introuvable.');
      return res.redirect('/encadrant/stagiaires');
    }
    if (!feedback || feedback.length > 2000) {
      flash(req, 'ko', 'Écris un feedback (2000 caractères maximum).');
      return res.redirect(`/encadrant/stagiaires/${entree.stage_id}`);
    }
    db.prepare('UPDATE journal SET feedback = ?, feedback_le = ? WHERE id = ?').run(feedback, new Date().toISOString(), entree.id);
    notifier(db, entree.etudiant_id, 'Feedback sur le journal', 'Ton encadrant a commenté une entrée de ton journal.', `/suivi?stage=${entree.stage_id}`);
    flash(req, 'ok', 'Feedback enregistré.');
    res.redirect(`/encadrant/stagiaires/${entree.stage_id}`);
  });

  router.post('/encadrant/stages/:id/commentaire', (req, res) => {
    const id = idParam(req.params.id);
    const stage = id && db.prepare('SELECT * FROM stages WHERE id = ? AND encadrant_id = ?').get(id, res.locals.user.id);
    const contenu = str(req.body.contenu);
    if (!stage) {
      flash(req, 'ko', 'Stage introuvable.');
      return res.redirect('/encadrant/stagiaires');
    }
    if (!contenu || contenu.length > 2000) {
      flash(req, 'ko', 'Écris un commentaire (2000 caractères maximum).');
      return res.redirect(`/encadrant/stagiaires/${stage.id}`);
    }
    db.prepare('INSERT INTO commentaires (stage_id, auteur_id, contenu, cree_le) VALUES (?, ?, ?, ?)').run(
      stage.id, res.locals.user.id, contenu, new Date().toISOString(),
    );
    notifier(db, stage.etudiant_id, 'Nouveau commentaire', 'Ton encadrant a laissé un commentaire sur ton suivi.', `/suivi?stage=${stage.id}`);
    flash(req, 'ok', 'Commentaire envoyé à l\'étudiant.');
    res.redirect(`/encadrant/stagiaires/${stage.id}`);
  });

  router.post('/encadrant/stages/:id/note', (req, res) => {
    const id = idParam(req.params.id);
    const stage = id && db.prepare('SELECT * FROM stages WHERE id = ? AND encadrant_id = ?').get(id, res.locals.user.id);
    if (!stage) {
      flash(req, 'ko', 'Stage introuvable.');
      return res.redirect('/encadrant/stagiaires');
    }
    const note = Number(req.body.note);
    const appreciation = str(req.body.appreciation);
    if (!Number.isFinite(note) || note < 0 || note > 20) {
      flash(req, 'ko', 'La note doit être comprise entre 0 et 20.');
      return res.redirect(`/encadrant/stagiaires/${stage.id}`);
    }
    if (!appreciation || appreciation.length > 2000) {
      flash(req, 'ko', 'Ajoute une appréciation.');
      return res.redirect(`/encadrant/stagiaires/${stage.id}`);
    }
    db.prepare('UPDATE stages SET note_encadrant = ?, appreciation_encadrant = ? WHERE id = ?').run(note, appreciation, stage.id);
    notifier(db, stage.etudiant_id, 'Note de l\'encadrant', 'Ton encadrant a enregistré ta note et ton appréciation.', `/suivi?stage=${stage.id}`);
    flash(req, 'ok', 'Note enregistrée.');
    res.redirect(`/encadrant/stagiaires/${stage.id}`);
  });

  router.post('/encadrant/stages/:id/cloturer', (req, res) => {
    const id = idParam(req.params.id);
    const stage = id && db.prepare('SELECT * FROM stages WHERE id = ? AND encadrant_id = ?').get(id, res.locals.user.id);
    if (!stage) {
      flash(req, 'ko', 'Stage introuvable.');
      return res.redirect('/encadrant/stagiaires');
    }
    if (stage.note_encadrant == null) {
      flash(req, 'ko', 'Attribue une note avant de clôturer le stage.');
      return res.redirect(`/encadrant/stagiaires/${stage.id}`);
    }
    db.prepare(`UPDATE stages SET statut = 'termine' WHERE id = ?`).run(stage.id);
    notifier(db, stage.etudiant_id, 'Stage clôturé', 'Ton encadrant a clôturé le stage. Il apparaît dans tes stages précédents.', '/historique');
    flash(req, 'ok', 'Stage clôturé.');
    res.redirect('/encadrant/stagiaires');
  });

  router.get('/encadrant/documents', (req, res) => {
    const docs = db.prepare(`
      SELECT d.*, s.id AS stage_id, s.sujet, o.titre, etu.prenom, etu.nom, ent.raison_sociale
      FROM documents d
      JOIN stages s ON s.id = d.stage_id
      JOIN offres o ON o.id = s.offre_id
      JOIN utilisateurs etu ON etu.id = s.etudiant_id
      JOIN utilisateurs ent ON ent.id = s.entreprise_id
      WHERE s.encadrant_id = ?
      ORDER BY CASE d.statut WHEN 'en_attente' THEN 0 WHEN 'rejete' THEN 1 ELSE 2 END, d.depose_le DESC
    `).all(res.locals.user.id);
    res.render('encadrant/documents', { titre: 'Validation des documents', active: 'documents', docs });
  });

  function documentAssigne(docId, encadrantId) {
    return db.prepare(`
      SELECT d.*, s.etudiant_id, s.encadrant_id, etu.prenom, etu.nom
      FROM documents d
      JOIN stages s ON s.id = d.stage_id
      JOIN utilisateurs etu ON etu.id = s.etudiant_id
      WHERE d.id = ? AND s.encadrant_id = ?
    `).get(docId, encadrantId);
  }

  router.post('/encadrant/documents/:id/valider', (req, res) => {
    const doc = documentAssigne(idParam(req.params.id), res.locals.user.id);
    if (!doc) {
      flash(req, 'ko', 'Document introuvable.');
      return res.redirect('/encadrant/documents');
    }
    db.prepare(`UPDATE documents SET statut = 'valide', commentaire = '', traite_le = ? WHERE id = ?`).run(new Date().toISOString(), doc.id);
    const label = doc.type === 'rapport' ? 'rapport' : 'convention';
    notifier(db, doc.etudiant_id, 'Document validé', `Ton ${label} a été validé.`, '/documents');
    flash(req, 'ok', 'Document validé.');
    res.redirect('/encadrant/documents');
  });

  router.post('/encadrant/documents/:id/rejeter', (req, res) => {
    const doc = documentAssigne(idParam(req.params.id), res.locals.user.id);
    const commentaire = str(req.body.commentaire);
    if (!doc) {
      flash(req, 'ko', 'Document introuvable.');
      return res.redirect('/encadrant/documents');
    }
    if (!commentaire || commentaire.length > 2000) {
      flash(req, 'ko', 'Indique le motif du rejet.');
      return res.redirect('/encadrant/documents');
    }
    db.prepare(`UPDATE documents SET statut = 'rejete', commentaire = ?, traite_le = ? WHERE id = ?`).run(commentaire, new Date().toISOString(), doc.id);
    const label = doc.type === 'rapport' ? 'rapport' : 'convention';
    notifier(db, doc.etudiant_id, 'Document rejeté', `Ton ${label} a été rejeté : ${commentaire}`, '/documents');
    flash(req, 'ok', 'Document rejeté, l\'étudiant est prévenu.');
    res.redirect('/encadrant/documents');
  });

  return router;
};
