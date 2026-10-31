const express = require('express');
const { str, idParam, homeFor } = require('../lib/helpers');
const { envoyer, nomOriginal, supprimerSiInutilise } = require('../lib/files');

module.exports = function communRoutes({ db, upload, verifierCsrf, flash }) {
  const router = express.Router();

  router.use((req, res, next) => {
    if (!res.locals.user && req.path !== '/deconnexion') return res.redirect('/');
    next();
  });

  router.get('/notifications', (req, res) => {
    const user = res.locals.user;
    db.prepare('UPDATE notifications SET lu = 1 WHERE utilisateur_id = ?').run(user.id);
    res.locals.notifCount = 0;
    const notifications = db.prepare(`
      SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY cree_le DESC, id DESC LIMIT 50
    `).all(user.id);
    res.render('notifications', { titre: 'Notifications', active: 'notifications', notifications });
  });

  router.get('/profil', (req, res) => {
    if (res.locals.user.role === 'admin') return res.redirect(homeFor('admin'));
    const profil = db.prepare(`
      SELECT id, email, role, nom, prenom, filiere, raison_sociale, competences, cv_nom
      FROM utilisateurs WHERE id = ?
    `).get(res.locals.user.id);
    res.render('profil', { titre: 'Mon profil', active: 'profil', profil });
  });

  router.post('/profil', upload.single('cv'), verifierCsrf, (req, res) => {
    const user = res.locals.user;
    if (user.role === 'admin') return res.redirect(homeFor('admin'));
    const nom = str(req.body.nom);
    const prenom = str(req.body.prenom);
    const filiere = str(req.body.filiere);
    const competences = str(req.body.competences);
    const raison = str(req.body.raison_sociale);
    if (!nom || nom.length > 80 || prenom.length > 80) {
      if (req.file) supprimerSiInutilise(db, req.file.filename);
      flash(req, 'ko', 'Le nom est obligatoire (80 caractères maximum).');
      return res.redirect('/profil');
    }
    if (user.role === 'etudiant') {
      if (!prenom || !filiere || filiere.length > 120 || competences.length > 500) {
        if (req.file) supprimerSiInutilise(db, req.file.filename);
        flash(req, 'ko', 'Indique ton prénom et ta filière.');
        return res.redirect('/profil');
      }
      const ancien = db.prepare('SELECT cv_stocke FROM utilisateurs WHERE id = ?').get(user.id).cv_stocke;
      db.prepare(`
        UPDATE utilisateurs SET nom = ?, prenom = ?, filiere = ?, competences = ?, cv_stocke = ?, cv_nom = ? WHERE id = ?
      `).run(
        nom,
        prenom,
        filiere,
        competences,
        req.file ? req.file.filename : ancien,
        req.file ? nomOriginal(req.file) : db.prepare('SELECT cv_nom FROM utilisateurs WHERE id = ?').get(user.id).cv_nom,
        user.id,
      );
      if (req.file && ancien && ancien !== req.file.filename) supprimerSiInutilise(db, ancien);
    } else if (user.role === 'entreprise') {
      if (!raison || raison.length > 140 || !prenom) {
        flash(req, 'ko', 'Indique la raison sociale et le prénom du contact.');
        return res.redirect('/profil');
      }
      db.prepare('UPDATE utilisateurs SET nom = ?, prenom = ?, raison_sociale = ? WHERE id = ?').run(nom, prenom, raison, user.id);
    } else {
      if (!prenom) {
        flash(req, 'ko', 'Indique le prénom.');
        return res.redirect('/profil');
      }
      db.prepare('UPDATE utilisateurs SET nom = ?, prenom = ? WHERE id = ?').run(nom, prenom, user.id);
    }
    flash(req, 'ok', 'Profil mis à jour.');
    res.redirect('/profil');
  });

  router.get('/fichiers/offre/:id', (req, res) => {
    const id = idParam(req.params.id);
    const offre = id && db.prepare('SELECT * FROM offres WHERE id = ?').get(id);
    if (!offre || !offre.document_stocke) return res.status(404).send('Fichier introuvable.');
    const user = res.locals.user;
    const concerne = user.role === 'admin'
      || (user.role === 'entreprise' && offre.entreprise_id === user.id)
      || user.role === 'etudiant'
      || user.role === 'encadrant';
    if (!concerne || (offre.retiree && user.role === 'etudiant' && !db.prepare(
      'SELECT id FROM candidatures WHERE offre_id = ? AND etudiant_id = ?'
    ).get(offre.id, user.id))) {
      return res.status(403).send('Accès refusé.');
    }
    if (!envoyer(res, offre.document_stocke, offre.document_nom)) return res.status(404).send('Fichier introuvable.');
  });

  router.get('/fichiers/candidature/:id/:type', (req, res) => {
    const id = idParam(req.params.id);
    const type = req.params.type === 'cv' ? 'cv' : req.params.type === 'convention' ? 'convention' : '';
    const c = id && type && db.prepare(`
      SELECT c.*, o.entreprise_id
      FROM candidatures c JOIN offres o ON o.id = c.offre_id
      WHERE c.id = ?
    `).get(id);
    if (!c) return res.status(404).send('Fichier introuvable.');
    const user = res.locals.user;
    const stage = db.prepare('SELECT encadrant_id FROM stages WHERE candidature_id = ?').get(c.id);
    const autorise = user.role === 'admin'
      || (user.role === 'etudiant' && c.etudiant_id === user.id)
      || (user.role === 'entreprise' && c.entreprise_id === user.id)
      || (user.role === 'encadrant' && stage && stage.encadrant_id === user.id);
    if (!autorise) return res.status(403).send('Accès refusé.');
    const stocke = type === 'cv' ? c.cv_stocke : c.convention_stocke;
    const nom = type === 'cv' ? c.cv_nom : c.convention_nom;
    if (!envoyer(res, stocke, nom)) return res.status(404).send('Fichier introuvable.');
  });

  router.get('/fichiers/document/:id', (req, res) => {
    const id = idParam(req.params.id);
    const doc = id && db.prepare(`
      SELECT d.*, s.etudiant_id, s.entreprise_id, s.encadrant_id
      FROM documents d JOIN stages s ON s.id = d.stage_id
      WHERE d.id = ?
    `).get(id);
    if (!doc) return res.status(404).send('Fichier introuvable.');
    const user = res.locals.user;
    const autorise = user.role === 'admin'
      || (user.role === 'etudiant' && doc.etudiant_id === user.id)
      || (user.role === 'entreprise' && doc.entreprise_id === user.id)
      || (user.role === 'encadrant' && doc.encadrant_id === user.id);
    if (!autorise) return res.status(403).send('Accès refusé.');
    if (!envoyer(res, doc.fichier_stocke, doc.fichier_nom)) return res.status(404).send('Fichier introuvable.');
  });

  router.get('/fichiers/cv/:id', (req, res) => {
    const id = idParam(req.params.id);
    const cible = id && db.prepare('SELECT id, role, cv_stocke, cv_nom FROM utilisateurs WHERE id = ?').get(id);
    if (!cible || cible.role !== 'etudiant' || !cible.cv_stocke) return res.status(404).send('Fichier introuvable.');
    const user = res.locals.user;
    const lie = user.role === 'admin'
      || (user.role === 'etudiant' && user.id === cible.id)
      || (user.role === 'entreprise' && db.prepare(`
        SELECT c.id FROM candidatures c JOIN offres o ON o.id = c.offre_id
        WHERE c.etudiant_id = ? AND o.entreprise_id = ?
      `).get(cible.id, user.id))
      || (user.role === 'encadrant' && db.prepare(`
        SELECT id FROM stages WHERE etudiant_id = ? AND encadrant_id = ?
      `).get(cible.id, user.id));
    if (!lie) return res.status(403).send('Accès refusé.');
    if (!envoyer(res, cible.cv_stocke, cible.cv_nom)) return res.status(404).send('Fichier introuvable.');
  });

  return router;
};
