const express = require('express');
const { str, idParam, notifier, NIVEAUX, homeFor } = require('../lib/helpers');
const { nomOriginal, supprimerSiInutilise } = require('../lib/files');

module.exports = function entrepriseRoutes({ db, upload, verifierCsrf, flash }) {
  const router = express.Router();

  router.use('/entreprise', (req, res, next) => {
    if (!res.locals.user) return res.redirect('/');
    if (res.locals.user.role !== 'entreprise') return res.redirect(homeFor(res.locals.user.role));
    next();
  });

  function lireOffre(body) {
    const titre = str(body.titre);
    const description = str(body.description);
    const missions = str(body.missions);
    const competences = str(body.competences);
    const ville = str(body.ville);
    const duree = Number(body.duree_mois);
    if (!titre || titre.length > 140) return { erreur: 'Indique un titre (140 caractères maximum).' };
    if (!description || description.length > 5000) return { erreur: 'Indique une description (5000 caractères maximum).' };
    if (missions.length > 3000 || competences.length > 500) return { erreur: 'Le texte des missions ou des compétences est trop long.' };
    if (!ville || ville.length > 80) return { erreur: 'Indique la ville.' };
    if (!Number.isInteger(duree) || duree < 1 || duree > 12) return { erreur: 'La durée doit être comprise entre 1 et 12 mois.' };
    return { titre, description, missions, competences, ville, duree };
  }

  router.get('/entreprise/offres', (req, res) => {
    const offres = db.prepare(`
      SELECT o.*,
        (SELECT COUNT(*) FROM candidatures c WHERE c.offre_id = o.id) AS nb
      FROM offres o
      WHERE o.entreprise_id = ?
      ORDER BY o.retiree ASC, o.cree_le DESC
    `).all(res.locals.user.id);
    res.render('entreprise/offres', { titre: 'Mes offres', active: 'offres', offres });
  });

  router.get('/entreprise/offres/nouvelle', (req, res) => {
    res.render('entreprise/form-offre', { titre: 'Publier une offre', active: 'publier', offre: null });
  });

  router.post('/entreprise/offres', upload.single('document'), verifierCsrf, (req, res) => {
    const lu = lireOffre(req.body);
    if (lu.erreur) {
      if (req.file) supprimerSiInutilise(db, req.file.filename);
      flash(req, 'ko', lu.erreur);
      return res.redirect('/entreprise/offres/nouvelle');
    }
    db.prepare(`
      INSERT INTO offres (entreprise_id, titre, description, missions, competences, duree_mois, ville, document_stocke, document_nom, retiree, cree_le)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
    `).run(
      res.locals.user.id,
      lu.titre,
      lu.description,
      lu.missions,
      lu.competences,
      lu.duree,
      lu.ville,
      req.file ? req.file.filename : null,
      req.file ? nomOriginal(req.file) : null,
      new Date().toISOString(),
    );
    flash(req, 'ok', 'Offre publiée.');
    res.redirect('/entreprise/offres');
  });

  router.get('/entreprise/offres/:id/modifier', (req, res) => {
    const id = idParam(req.params.id);
    const offre = id && db.prepare('SELECT * FROM offres WHERE id = ? AND entreprise_id = ? AND retiree = 0').get(id, res.locals.user.id);
    if (!offre) return res.status(404).send('Offre introuvable.');
    res.render('entreprise/form-offre', { titre: 'Modifier une offre', active: 'offres', offre });
  });

  router.post('/entreprise/offres/:id', upload.single('document'), verifierCsrf, (req, res) => {
    const id = idParam(req.params.id);
    const offre = id && db.prepare('SELECT * FROM offres WHERE id = ? AND entreprise_id = ? AND retiree = 0').get(id, res.locals.user.id);
    if (!offre) {
      if (req.file) supprimerSiInutilise(db, req.file.filename);
      flash(req, 'ko', 'Offre introuvable.');
      return res.redirect('/entreprise/offres');
    }
    const lu = lireOffre(req.body);
    if (lu.erreur) {
      if (req.file) supprimerSiInutilise(db, req.file.filename);
      flash(req, 'ko', lu.erreur);
      return res.redirect(`/entreprise/offres/${offre.id}/modifier`);
    }
    const ancien = offre.document_stocke;
    db.prepare(`
      UPDATE offres
      SET titre = ?, description = ?, missions = ?, competences = ?, duree_mois = ?, ville = ?,
          document_stocke = ?, document_nom = ?
      WHERE id = ?
    `).run(
      lu.titre,
      lu.description,
      lu.missions,
      lu.competences,
      lu.duree,
      lu.ville,
      req.file ? req.file.filename : offre.document_stocke,
      req.file ? nomOriginal(req.file) : offre.document_nom,
      offre.id,
    );
    if (req.file && ancien && ancien !== req.file.filename) supprimerSiInutilise(db, ancien);
    flash(req, 'ok', 'Offre mise à jour.');
    res.redirect('/entreprise/offres');
  });

  router.post('/entreprise/offres/:id/supprimer', (req, res) => {
    const id = idParam(req.params.id);
    const offre = id && db.prepare('SELECT * FROM offres WHERE id = ? AND entreprise_id = ?').get(id, res.locals.user.id);
    if (!offre) {
      flash(req, 'ko', 'Offre introuvable.');
      return res.redirect('/entreprise/offres');
    }
    const acceptees = db.prepare(`SELECT COUNT(*) AS n FROM candidatures WHERE offre_id = ? AND statut = 'acceptee'`).get(offre.id).n;
    if (acceptees || offre.retiree) {
      db.prepare('UPDATE offres SET retiree = 1 WHERE id = ?').run(offre.id);
      flash(req, 'ok', 'Offre retirée des annonces. Les stages déjà acceptés sont conservés.');
      return res.redirect('/entreprise/offres');
    }
    const fichier = offre.document_stocke;
    db.prepare('DELETE FROM candidatures WHERE offre_id = ?').run(offre.id);
    db.prepare('DELETE FROM offres WHERE id = ?').run(offre.id);
    if (fichier) supprimerSiInutilise(db, fichier);
    flash(req, 'ok', 'Offre supprimée.');
    res.redirect('/entreprise/offres');
  });

  router.get('/entreprise/candidatures', (req, res) => {
    const offres = db.prepare('SELECT id, titre FROM offres WHERE entreprise_id = ? ORDER BY titre').all(res.locals.user.id);
    const filtre = idParam(req.query.offre);
    const rows = db.prepare(`
      SELECT c.*, o.titre, u.prenom, u.nom, u.filiere
      FROM candidatures c
      JOIN offres o ON o.id = c.offre_id
      JOIN utilisateurs u ON u.id = c.etudiant_id
      WHERE o.entreprise_id = ? AND (? IS NULL OR o.id = ?)
      ORDER BY c.cree_le DESC
    `).all(res.locals.user.id, filtre, filtre);
    res.render('entreprise/candidatures', {
      titre: 'Candidatures reçues',
      active: 'candidatures',
      candidatures: rows,
      offres,
      filtre: filtre || '',
    });
  });

  router.get('/entreprise/candidatures/:id', (req, res) => {
    const id = idParam(req.params.id);
    const dossier = id && db.prepare(`
      SELECT c.*, u.prenom, u.nom, u.filiere, u.competences, u.email, o.titre, o.ville, o.duree_mois
      FROM candidatures c
      JOIN utilisateurs u ON u.id = c.etudiant_id
      JOIN offres o ON o.id = c.offre_id
      WHERE c.id = ? AND o.entreprise_id = ?
    `).get(id, res.locals.user.id);
    if (!dossier) return res.status(404).send('Dossier introuvable.');
    res.render('entreprise/dossier', { titre: 'Dossier de candidature', active: 'candidatures', dossier });
  });

  const decider = db.transaction((candidatureId, entrepriseId, decision) => {
    const c = db.prepare(`
      SELECT c.*, o.entreprise_id, o.titre
      FROM candidatures c JOIN offres o ON o.id = c.offre_id
      WHERE c.id = ? AND o.entreprise_id = ?
    `).get(candidatureId, entrepriseId);
    if (!c || c.statut !== 'en_attente') return null;
    db.prepare('UPDATE candidatures SET statut = ? WHERE id = ?').run(decision, c.id);
    if (decision === 'acceptee') {
      const info = db.prepare(`
        INSERT INTO stages (candidature_id, etudiant_id, entreprise_id, encadrant_id, offre_id, statut, sujet, cree_le)
        VALUES (?, ?, ?, NULL, ?, 'en_cours', ?, ?)
      `).run(c.id, c.etudiant_id, c.entreprise_id, c.offre_id, c.titre, new Date().toISOString());
      const stageId = Number(info.lastInsertRowid);
      if (c.convention_stocke) {
        db.prepare(`
          INSERT INTO documents (stage_id, type, fichier_stocke, fichier_nom, statut, commentaire, depose_le)
          VALUES (?, 'convention', ?, ?, 'en_attente', '', ?)
        `).run(stageId, c.convention_stocke, c.convention_nom || 'convention.pdf', new Date().toISOString());
      }
      return { ...c, stageId };
    }
    return c;
  });

  router.post('/entreprise/candidatures/:id/decision', (req, res) => {
    const id = idParam(req.params.id);
    const decision = req.body.decision === 'acceptee' ? 'acceptee' : req.body.decision === 'refusee' ? 'refusee' : '';
    if (!id || !decision) {
      flash(req, 'ko', 'Décision invalide.');
      return res.redirect('/entreprise/candidatures');
    }
    const resultat = decider(id, res.locals.user.id, decision);
    if (!resultat) {
      flash(req, 'ko', 'Cette candidature est introuvable ou déjà traitée.');
      return res.redirect('/entreprise/candidatures');
    }
    const entreprise = res.locals.user.raison_sociale || res.locals.user.nom;
    if (decision === 'acceptee') {
      notifier(db, resultat.etudiant_id, 'Candidature acceptée', `${entreprise} a accepté ta candidature pour ${resultat.titre}.`, '/candidatures');
      flash(req, 'ok', 'Candidature acceptée. Un stage est ouvert, l\'administrateur peut affecter un encadrant.');
    } else {
      notifier(db, resultat.etudiant_id, 'Candidature refusée', `${entreprise} n\'a pas retenu ta candidature pour ${resultat.titre}.`, '/candidatures');
      flash(req, 'ok', 'Candidature refusée.');
    }
    res.redirect(`/entreprise/candidatures/${id}`);
  });

  router.get('/entreprise/evaluations', (req, res) => {
    const stages = db.prepare(`
      SELECT s.*, u.prenom, u.nom, u.filiere, o.titre
      FROM stages s
      JOIN utilisateurs u ON u.id = s.etudiant_id
      JOIN offres o ON o.id = s.offre_id
      WHERE s.entreprise_id = ?
      ORDER BY CASE s.statut WHEN 'en_cours' THEN 0 ELSE 1 END, s.cree_le DESC
    `).all(res.locals.user.id);
    res.render('entreprise/evaluations', { titre: 'Évaluer un stagiaire', active: 'evaluations', stages });
  });

  router.get('/entreprise/stages/:id/evaluation', (req, res) => {
    const id = idParam(req.params.id);
    const stage = id && db.prepare(`
      SELECT s.*, u.prenom, u.nom, u.filiere, o.titre
      FROM stages s
      JOIN utilisateurs u ON u.id = s.etudiant_id
      JOIN offres o ON o.id = s.offre_id
      WHERE s.id = ? AND s.entreprise_id = ?
    `).get(id, res.locals.user.id);
    if (!stage) return res.status(404).send('Stage introuvable.');
    res.render('entreprise/evaluer', { titre: 'Grille d\'évaluation', active: 'evaluations', stage, niveaux: NIVEAUX });
  });

  router.post('/entreprise/stages/:id/evaluation', (req, res) => {
    const id = idParam(req.params.id);
    const stage = id && db.prepare('SELECT * FROM stages WHERE id = ? AND entreprise_id = ?').get(id, res.locals.user.id);
    if (!stage) {
      flash(req, 'ko', 'Stage introuvable.');
      return res.redirect('/entreprise/evaluations');
    }
    const note = Number(req.body.note);
    const champs = ['assiduite', 'competences_tech', 'autonomie', 'communication', 'qualite'];
    const valeurs = {};
    for (const champ of champs) {
      const v = str(req.body[champ]);
      if (!NIVEAUX.includes(v)) {
        flash(req, 'ko', 'Complète chaque critère de la grille.');
        return res.redirect(`/entreprise/stages/${stage.id}/evaluation`);
      }
      valeurs[champ] = v;
    }
    if (!Number.isFinite(note) || note < 0 || note > 20) {
      flash(req, 'ko', 'La note doit être comprise entre 0 et 20.');
      return res.redirect(`/entreprise/stages/${stage.id}/evaluation`);
    }
    const commentaire = str(req.body.commentaire);
    if (commentaire.length > 4000) {
      flash(req, 'ko', 'Le commentaire est trop long.');
      return res.redirect(`/entreprise/stages/${stage.id}/evaluation`);
    }
    db.prepare(`
      UPDATE stages
      SET note_entreprise = ?, commentaire_entreprise = ?, assiduite = ?, competences_tech = ?, autonomie = ?, communication = ?, qualite = ?
      WHERE id = ?
    `).run(note, commentaire, valeurs.assiduite, valeurs.competences_tech, valeurs.autonomie, valeurs.communication, valeurs.qualite, stage.id);
    const etu = db.prepare('SELECT prenom, nom FROM utilisateurs WHERE id = ?').get(stage.etudiant_id);
    notifier(db, stage.etudiant_id, 'Évaluation de l\'entreprise', 'Ton entreprise d\'accueil a enregistré ton évaluation de fin de stage.', `/suivi?stage=${stage.id}`);
    notifier(db, stage.encadrant_id, 'Évaluation entreprise disponible', `${etu.prenom} ${etu.nom} a été évalué par l\'entreprise.`, `/encadrant/stagiaires/${stage.id}`);
    flash(req, 'ok', 'Évaluation enregistrée.');
    res.redirect(`/entreprise/stages/${stage.id}/evaluation`);
  });

  return router;
};
