const express = require('express');
const { str, idParam, dateIsoValide, todayISO, notifier, homeFor } = require('../lib/helpers');
const { nomOriginal, premierFichier, supprimer } = require('../lib/files');
const { enrichir } = require('../lib/stages');

module.exports = function etudiantRoutes({ db, upload, verifierCsrf, flash }) {
  const router = express.Router();

  const ESPACES = ['/offres', '/candidatures', '/documents', '/suivi', '/historique'];
  router.use((req, res, next) => {
    const concerne = ESPACES.some((p) => req.path === p || req.path.startsWith(`${p}/`));
    if (!concerne) return next();
    if (!res.locals.user) return res.redirect('/');
    if (res.locals.user.role !== 'etudiant') return res.redirect(homeFor(res.locals.user.role));
    next();
  });

  router.get('/offres', (req, res) => {
    const user = res.locals.user;
    const recherche = str(req.query.recherche).slice(0, 80);
    const ville = str(req.query.ville).slice(0, 80);
    const duree = str(req.query.duree);
    const like = `%${recherche.replace(/[%_]/g, ' ')}%`;
    const dureeNum = /^\d+$/.test(duree) ? Number(duree) : null;
    const offres = db.prepare(`
      SELECT o.*, u.raison_sociale,
        (SELECT statut FROM candidatures c WHERE c.offre_id = o.id AND c.etudiant_id = ?) AS deja
      FROM offres o
      JOIN utilisateurs u ON u.id = o.entreprise_id
      WHERE o.retiree = 0
        AND (? = '' OR o.titre LIKE ? OR o.description LIKE ? OR o.ville LIKE ? OR u.raison_sociale LIKE ? OR o.competences LIKE ?)
        AND (? = '' OR o.ville = ?)
        AND (? IS NULL OR o.duree_mois = ?)
      ORDER BY o.cree_le DESC, o.id DESC
    `).all(
      user.id,
      recherche, like, like, like, like, like,
      ville, ville,
      dureeNum, dureeNum,
    );
    const villes = db.prepare(`SELECT DISTINCT ville FROM offres WHERE retiree = 0 ORDER BY ville`).all().map((r) => r.ville);
    const durees = db.prepare(`SELECT DISTINCT duree_mois FROM offres WHERE retiree = 0 ORDER BY duree_mois`).all().map((r) => r.duree_mois);
    res.render('etudiant/offres', { titre: 'Offres de stage', active: 'offres', offres, villes, durees, recherche, ville, duree });
  });

  router.get('/offres/:id', (req, res, next) => {
    const id = idParam(req.params.id);
    if (!id) return next();
    const offre = db.prepare(`
      SELECT o.*, u.raison_sociale,
        (SELECT statut FROM candidatures c WHERE c.offre_id = o.id AND c.etudiant_id = ?) AS deja
      FROM offres o
      JOIN utilisateurs u ON u.id = o.entreprise_id
      WHERE o.id = ? AND o.retiree = 0
    `).get(res.locals.user.id, id);
    if (!offre) return res.status(404).send('Offre introuvable. <a href="/offres">Retour</a>');
    res.render('etudiant/detail', { titre: offre.titre, active: 'offres', offre });
  });

  router.get('/offres/:id/postuler', (req, res, next) => {
    const id = idParam(req.params.id);
    if (!id) return next();
    const offre = db.prepare(`
      SELECT o.*, u.raison_sociale,
        (SELECT id FROM candidatures c WHERE c.offre_id = o.id AND c.etudiant_id = ?) AS deja
      FROM offres o JOIN utilisateurs u ON u.id = o.entreprise_id
      WHERE o.id = ? AND o.retiree = 0
    `).get(res.locals.user.id, id);
    if (!offre) return res.status(404).send('Offre introuvable.');
    res.render('etudiant/postuler', { titre: 'Postuler', active: 'offres', offre });
  });

  router.post('/offres/:id/postuler', upload.fields([
    { name: 'cv', maxCount: 1 },
    { name: 'convention', maxCount: 1 },
  ]), verifierCsrf, (req, res) => {
    const user = res.locals.user;
    const id = idParam(req.params.id);
    const offre = id && db.prepare('SELECT * FROM offres WHERE id = ? AND retiree = 0').get(id);
    const cv = premierFichier(req.files, 'cv');
    const convention = premierFichier(req.files, 'convention');
    const lettre = str(req.body.lettre);
    const cleanup = () => {
      if (cv) supprimer(cv.filename);
      if (convention) supprimer(convention.filename);
    };
    if (!offre) {
      cleanup();
      flash(req, 'ko', 'Cette offre n\'est plus disponible.');
      return res.redirect('/offres');
    }
    if (lettre.length < 20 || lettre.length > 4000) {
      cleanup();
      flash(req, 'ko', 'La lettre de motivation doit faire entre 20 et 4000 caractères.');
      return res.redirect(`/offres/${offre.id}/postuler`);
    }
    const existe = db.prepare('SELECT id FROM candidatures WHERE offre_id = ? AND etudiant_id = ?').get(offre.id, user.id);
    if (existe) {
      cleanup();
      flash(req, 'ko', 'Tu as déjà postulé à cette offre.');
      return res.redirect('/candidatures');
    }
    const profil = db.prepare('SELECT cv_stocke, cv_nom FROM utilisateurs WHERE id = ?').get(user.id);
    let cvStocke = profil.cv_stocke;
    let cvNom = profil.cv_nom;
    if (cv) {
      cvStocke = cv.filename;
      cvNom = nomOriginal(cv);
    }
    if (!cvStocke) {
      cleanup();
      flash(req, 'ko', 'Dépose ton CV au format PDF, DOC ou DOCX.');
      return res.redirect(`/offres/${offre.id}/postuler`);
    }
    db.prepare(`
      INSERT INTO candidatures (offre_id, etudiant_id, cv_stocke, cv_nom, lettre, convention_stocke, convention_nom, statut, cree_le)
      VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente', ?)
    `).run(
      offre.id,
      user.id,
      cvStocke,
      cvNom,
      lettre,
      convention ? convention.filename : null,
      convention ? nomOriginal(convention) : null,
      new Date().toISOString(),
    );
    const entreprise = db.prepare('SELECT entreprise_id, titre FROM offres WHERE id = ?').get(offre.id);
    if (cv) db.prepare('UPDATE utilisateurs SET cv_stocke = ?, cv_nom = ? WHERE id = ?').run(cvStocke, cvNom, user.id);
    notifier(db, entreprise.entreprise_id, 'Nouvelle candidature', `${user.prenom} ${user.nom} a postulé à ${offre.titre}.`, '/entreprise/candidatures');
    flash(req, 'ok', 'Candidature envoyée.');
    res.redirect('/candidatures');
  });

  router.get('/candidatures', (req, res) => {
    const rows = db.prepare(`
      SELECT c.*, o.titre, o.ville, o.duree_mois, u.raison_sociale
      FROM candidatures c
      JOIN offres o ON o.id = c.offre_id
      JOIN utilisateurs u ON u.id = o.entreprise_id
      WHERE c.etudiant_id = ?
      ORDER BY c.cree_le DESC, c.id DESC
    `).all(res.locals.user.id);
    res.render('etudiant/candidatures', { titre: 'Mes candidatures', active: 'candidatures', candidatures: rows });
  });

  function stagesDe(userId, statut) {
    return db.prepare(`
      SELECT s.*, o.titre, o.ville, ent.raison_sociale, enc.nom AS enc_nom
      FROM stages s
      JOIN offres o ON o.id = s.offre_id
      JOIN utilisateurs ent ON ent.id = s.entreprise_id
      LEFT JOIN utilisateurs enc ON enc.id = s.encadrant_id
      WHERE s.etudiant_id = ? AND s.statut = ?
      ORDER BY s.cree_le DESC
    `).all(userId, statut).map((s) => enrichir(db, s));
  }

  function stageChoisi(rows, queryId) {
    const id = idParam(queryId);
    if (id) {
      const found = rows.find((s) => s.id === id);
      if (found) return found;
    }
    return rows[0] || null;
  }

  router.get('/documents', (req, res) => {
    const stages = stagesDe(res.locals.user.id, 'en_cours');
    const stage = stageChoisi(stages, req.query.stage);
    res.render('etudiant/documents', { titre: 'Mes documents', active: 'documents', stages, stage });
  });

  router.post('/documents/convention', upload.single('fichier'), verifierCsrf, (req, res) => {
    const user = res.locals.user;
    const stageId = idParam(req.body.stage_id);
    const stage = stageId && db.prepare(`SELECT * FROM stages WHERE id = ? AND etudiant_id = ? AND statut = 'en_cours'`).get(stageId, user.id);
    const sujet = str(req.body.sujet);
    const description = str(req.body.description);
    const debut = str(req.body.date_debut);
    const fin = str(req.body.date_fin);
    const contact = str(req.body.contact_entreprise);
    if (!stage) {
      if (req.file) supprimer(req.file.filename);
      flash(req, 'ko', 'Stage introuvable.');
      return res.redirect('/documents');
    }
    if (!sujet || sujet.length > 180) {
      if (req.file) supprimer(req.file.filename);
      flash(req, 'ko', 'Indique le sujet de la convention.');
      return res.redirect(`/documents?stage=${stage.id}`);
    }
    if (description.length > 4000 || contact.length > 180) {
      if (req.file) supprimer(req.file.filename);
      flash(req, 'ko', 'Le texte de la convention est trop long.');
      return res.redirect(`/documents?stage=${stage.id}`);
    }
    if ((debut && !dateIsoValide(debut, { futurAutorise: true })) || (fin && !dateIsoValide(fin, { futurAutorise: true }))) {
      if (req.file) supprimer(req.file.filename);
      flash(req, 'ko', 'Les dates de la convention sont invalides.');
      return res.redirect(`/documents?stage=${stage.id}`);
    }
    if (debut && fin && fin < debut) {
      if (req.file) supprimer(req.file.filename);
      flash(req, 'ko', 'La date de fin doit suivre la date de début.');
      return res.redirect(`/documents?stage=${stage.id}`);
    }
    const actuel = db.prepare(`SELECT * FROM documents WHERE stage_id = ? AND type = 'convention'`).get(stage.id);
    if (!actuel && !req.file) {
      flash(req, 'ko', 'Dépose la convention en PDF.');
      return res.redirect(`/documents?stage=${stage.id}`);
    }
    db.prepare(`
      UPDATE stages SET sujet = ?, description_convention = ?, date_debut = ?, date_fin = ?, contact_entreprise = ?
      WHERE id = ?
    `).run(sujet, description, debut || null, fin || null, contact, stage.id);
    const maintenant = new Date().toISOString();
    if (actuel) {
      db.prepare(`
        UPDATE documents
        SET fichier_stocke = ?, fichier_nom = ?, statut = 'en_attente', commentaire = '', depose_le = ?, traite_le = NULL
        WHERE id = ?
      `).run(
        req.file ? req.file.filename : actuel.fichier_stocke,
        req.file ? nomOriginal(req.file) : actuel.fichier_nom,
        maintenant,
        actuel.id,
      );
    } else {
      db.prepare(`
        INSERT INTO documents (stage_id, type, fichier_stocke, fichier_nom, statut, commentaire, depose_le)
        VALUES (?, 'convention', ?, ?, 'en_attente', '', ?)
      `).run(stage.id, req.file.filename, nomOriginal(req.file), maintenant);
    }
    notifier(db, stage.encadrant_id, 'Convention à valider', `${user.prenom} ${user.nom} a déposé une convention.`, '/encadrant/documents');
    flash(req, 'ok', 'Convention enregistrée. Elle est en attente de validation.');
    res.redirect(`/documents?stage=${stage.id}`);
  });

  router.post('/documents/rapport', upload.single('fichier'), verifierCsrf, (req, res) => {
    const user = res.locals.user;
    const stageId = idParam(req.body.stage_id);
    const stage = stageId && db.prepare(`SELECT * FROM stages WHERE id = ? AND etudiant_id = ? AND statut = 'en_cours'`).get(stageId, user.id);
    if (!stage || !req.file) {
      if (req.file) supprimer(req.file.filename);
      flash(req, 'ko', 'Choisis un stage en cours et dépose le rapport en PDF.');
      return res.redirect('/documents');
    }
    const actuel = db.prepare(`SELECT * FROM documents WHERE stage_id = ? AND type = 'rapport'`).get(stage.id);
    const maintenant = new Date().toISOString();
    if (actuel) {
      db.prepare(`
        UPDATE documents
        SET fichier_stocke = ?, fichier_nom = ?, statut = 'en_attente', commentaire = '', depose_le = ?, traite_le = NULL
        WHERE id = ?
      `).run(req.file.filename, nomOriginal(req.file), maintenant, actuel.id);
    } else {
      db.prepare(`
        INSERT INTO documents (stage_id, type, fichier_stocke, fichier_nom, statut, commentaire, depose_le)
        VALUES (?, 'rapport', ?, ?, 'en_attente', '', ?)
      `).run(stage.id, req.file.filename, nomOriginal(req.file), maintenant);
    }
    notifier(db, stage.encadrant_id, 'Rapport à valider', `${user.prenom} ${user.nom} a déposé un rapport de stage.`, '/encadrant/documents');
    flash(req, 'ok', 'Rapport déposé. Il est en attente de validation.');
    res.redirect(`/documents?stage=${stage.id}`);
  });

  router.get('/suivi', (req, res) => {
    const stages = stagesDe(res.locals.user.id, 'en_cours');
    const stage = stageChoisi(stages, req.query.stage);
    let journal = [];
    let commentaires = [];
    if (stage) {
      journal = db.prepare('SELECT * FROM journal WHERE stage_id = ? ORDER BY date_entree DESC, id DESC').all(stage.id);
      commentaires = db.prepare(`
        SELECT c.*, u.role, u.nom, u.prenom, u.raison_sociale
        FROM commentaires c JOIN utilisateurs u ON u.id = c.auteur_id
        WHERE c.stage_id = ? ORDER BY c.cree_le ASC, c.id ASC
      `).all(stage.id);
    }
    const nbTermines = db.prepare(`SELECT COUNT(*) AS n FROM stages WHERE etudiant_id = ? AND statut = 'termine'`).get(res.locals.user.id).n;
    res.render('etudiant/suivi', {
      titre: 'Suivi du stage',
      active: 'suivi',
      stages,
      stage,
      journal,
      commentaires,
      nbTermines,
      aujourdhui: todayISO(),
    });
  });

  router.post('/suivi/journal', (req, res) => {
    const user = res.locals.user;
    const stageId = idParam(req.body.stage_id);
    const stage = stageId && db.prepare(`SELECT * FROM stages WHERE id = ? AND etudiant_id = ? AND statut = 'en_cours'`).get(stageId, user.id);
    const dateEntree = str(req.body.date_entree);
    const taches = str(req.body.taches);
    const difficultes = str(req.body.difficultes);
    if (!stage) {
      flash(req, 'ko', 'Stage introuvable.');
      return res.redirect('/suivi');
    }
    if (!dateIsoValide(dateEntree)) {
      flash(req, 'ko', 'Choisis une date passée ou aujourd\'hui.');
      return res.redirect(`/suivi?stage=${stage.id}`);
    }
    if (!taches || taches.length > 4000 || difficultes.length > 4000) {
      flash(req, 'ko', 'Décris les tâches réalisées (4000 caractères maximum).');
      return res.redirect(`/suivi?stage=${stage.id}`);
    }
    db.prepare(`
      INSERT INTO journal (stage_id, date_entree, taches, difficultes, feedback, cree_le)
      VALUES (?, ?, ?, ?, '', ?)
    `).run(stage.id, dateEntree, taches, difficultes, new Date().toISOString());
    notifier(db, stage.encadrant_id, 'Journal mis à jour', `${user.prenom} ${user.nom} a ajouté une entrée de journal.`, `/encadrant/stagiaires/${stage.id}`);
    flash(req, 'ok', 'Entrée ajoutée au journal de bord.');
    res.redirect(`/suivi?stage=${stage.id}`);
  });

  router.post('/suivi/commentaire', (req, res) => {
    const user = res.locals.user;
    const stageId = idParam(req.body.stage_id);
    const stage = stageId && db.prepare(`SELECT * FROM stages WHERE id = ? AND etudiant_id = ?`).get(stageId, user.id);
    const contenu = str(req.body.contenu);
    if (!stage || !stage.encadrant_id) {
      flash(req, 'ko', 'Tu pourras répondre dès qu\'un encadrant sera affecté.');
      return res.redirect('/suivi');
    }
    if (!contenu || contenu.length > 2000) {
      flash(req, 'ko', 'Écris un commentaire (2000 caractères maximum).');
      return res.redirect(`/suivi?stage=${stage.id}`);
    }
    db.prepare(`
      INSERT INTO commentaires (stage_id, auteur_id, contenu, cree_le) VALUES (?, ?, ?, ?)
    `).run(stage.id, user.id, contenu, new Date().toISOString());
    notifier(db, stage.encadrant_id, 'Réponse de l\'étudiant', `${user.prenom} ${user.nom} a répondu sur le suivi.`, `/encadrant/stagiaires/${stage.id}`);
    flash(req, 'ok', 'Réponse envoyée.');
    res.redirect(`/suivi?stage=${stage.id}`);
  });

  router.get('/historique', (req, res) => {
    const stages = stagesDe(res.locals.user.id, 'termine');
    res.render('etudiant/historique', { titre: 'Stages précédents', active: 'historique', stages });
  });

  return router;
};
