const express = require('express');
const bcrypt = require('bcryptjs');
const { str, idParam, notifier, motDePasseTemporaire, nomAffiche, homeFor } = require('../lib/helpers');
const { collectStats } = require('../lib/stats');
const { statsPdf } = require('../lib/pdf');
const { statsExcel } = require('../lib/excel');

module.exports = function adminRoutes({ db, flash }) {
  const router = express.Router();

  router.use('/admin', (req, res, next) => {
    if (!res.locals.user) return res.redirect('/');
    if (res.locals.user.role !== 'admin') return res.redirect(homeFor(res.locals.user.role));
    next();
  });

  function reinitialiser(userId) {
    const pwd = motDePasseTemporaire();
    db.prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?').run(bcrypt.hashSync(pwd, 10), userId);
    db.prepare('UPDATE reinitialisations SET traite = 1 WHERE utilisateur_id = ? AND traite = 0').run(userId);
    return pwd;
  }

  router.get('/admin/comptes', (req, res) => {
    const q = str(req.query.q).slice(0, 80);
    const like = `%${q.replace(/[%_]/g, ' ')}%`;
    const comptes = db.prepare(`
      SELECT id, email, role, nom, prenom, filiere, raison_sociale, competences, actif, cree_le FROM utilisateurs
      WHERE ? = '' OR email LIKE ? OR nom LIKE ? OR prenom LIKE ? OR raison_sociale LIKE ? OR filiere LIKE ?
      ORDER BY role, nom, prenom
    `).all(q, like, like, like, like, like);
    const demandes = db.prepare(`
      SELECT r.*, u.email, u.nom, u.prenom, u.raison_sociale, u.role
      FROM reinitialisations r
      JOIN utilisateurs u ON u.id = r.utilisateur_id
      WHERE r.traite = 0
      ORDER BY r.demande_le DESC
    `).all();
    res.render('admin/comptes', { titre: 'Gestion des comptes', active: 'comptes', comptes, demandes, q });
  });

  router.post('/admin/comptes/reinitialiser', (req, res) => {
    const id = idParam(req.body.utilisateur_id);
    const user = id && db.prepare('SELECT * FROM utilisateurs WHERE id = ?').get(id);
    if (!user) {
      flash(req, 'ko', 'Compte introuvable.');
      return res.redirect('/admin/comptes');
    }
    const pwd = reinitialiser(user.id);
    flash(req, 'ok', `Nouveau mot de passe pour ${user.email} : ${pwd}. Communique-le à la personne, il ne sera plus affiché.`);
    res.redirect('/admin/comptes');
  });

  router.post('/admin/comptes/basculer', (req, res) => {
    const id = idParam(req.body.utilisateur_id);
    const user = id && db.prepare('SELECT * FROM utilisateurs WHERE id = ?').get(id);
    if (!user) {
      flash(req, 'ko', 'Compte introuvable.');
      return res.redirect('/admin/comptes');
    }
    if (user.id === res.locals.user.id) {
      flash(req, 'ko', 'Tu ne peux pas désactiver ton propre compte.');
      return res.redirect('/admin/comptes');
    }
    const actif = user.actif ? 0 : 1;
    db.prepare('UPDATE utilisateurs SET actif = ? WHERE id = ?').run(actif, user.id);
    flash(req, 'ok', actif ? `${nomAffiche(user)} est réactivé.` : `${nomAffiche(user)} est désactivé.`);
    res.redirect('/admin/comptes');
  });

  router.post('/admin/encadrants', (req, res) => {
    const nom = str(req.body.nom);
    const prenom = str(req.body.prenom);
    const email = str(req.body.email).toLowerCase();
    let password = String(req.body.mot_de_passe || '');
    let genere = false;
    if (!nom || !prenom || nom.length > 80 || prenom.length > 80) {
      flash(req, 'ko', 'Indique le nom et le prénom de l\'encadrant.');
      return res.redirect('/admin/comptes');
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      flash(req, 'ko', 'Adresse e-mail invalide.');
      return res.redirect('/admin/comptes');
    }
    if (!password) {
      password = motDePasseTemporaire();
      genere = true;
    }
    if (password.length < 8 || password.length > 200) {
      flash(req, 'ko', 'Le mot de passe doit contenir au moins 8 caractères.');
      return res.redirect('/admin/comptes');
    }
    const existe = db.prepare('SELECT id FROM utilisateurs WHERE email = ?').get(email);
    if (existe) {
      flash(req, 'ko', 'Un compte existe déjà avec cet e-mail.');
      return res.redirect('/admin/comptes');
    }
    db.prepare(`
      INSERT INTO utilisateurs (email, mot_de_passe, role, nom, prenom, filiere, raison_sociale, competences, actif, cree_le)
      VALUES (?, ?, 'encadrant', ?, ?, '', '', '', 1, ?)
    `).run(email, bcrypt.hashSync(password, 10), nom, prenom, new Date().toISOString());
    flash(req, 'ok', genere
      ? `Encadrant créé. Mot de passe de ${email} : ${password}.`
      : `Encadrant ${prenom} ${nom} créé.`);
    res.redirect('/admin/comptes');
  });

  router.get('/admin/affectations', (req, res) => {
    const stages = db.prepare(`
      SELECT s.*, o.titre, etu.prenom, etu.nom, etu.email, ent.raison_sociale, enc.nom AS enc_nom
      FROM stages s
      JOIN offres o ON o.id = s.offre_id
      JOIN utilisateurs etu ON etu.id = s.etudiant_id
      JOIN utilisateurs ent ON ent.id = s.entreprise_id
      LEFT JOIN utilisateurs enc ON enc.id = s.encadrant_id
      WHERE s.statut = 'en_cours'
      ORDER BY CASE WHEN s.encadrant_id IS NULL THEN 0 ELSE 1 END, etu.nom
    `).all();
    const encadrants = db.prepare(`
      SELECT id, nom, prenom, email FROM utilisateurs WHERE role = 'encadrant' AND actif = 1 ORDER BY nom
    `).all();
    res.render('admin/affectations', { titre: 'Affecter un encadrant', active: 'affectations', stages, encadrants });
  });

  router.post('/admin/affectations', (req, res) => {
    const stageId = idParam(req.body.stage_id);
    const encadrantId = idParam(req.body.encadrant_id);
    const stage = stageId && db.prepare(`SELECT * FROM stages WHERE id = ? AND statut = 'en_cours'`).get(stageId);
    const encadrant = encadrantId && db.prepare(`SELECT * FROM utilisateurs WHERE id = ? AND role = 'encadrant' AND actif = 1`).get(encadrantId);
    if (!stage || !encadrant) {
      flash(req, 'ko', 'Choisis un stage en cours et un encadrant actif.');
      return res.redirect('/admin/affectations');
    }
    db.prepare('UPDATE stages SET encadrant_id = ? WHERE id = ?').run(encadrant.id, stage.id);
    const etu = db.prepare('SELECT prenom, nom FROM utilisateurs WHERE id = ?').get(stage.etudiant_id);
    const offre = db.prepare('SELECT titre FROM offres WHERE id = ?').get(stage.offre_id);
    notifier(db, stage.etudiant_id, 'Encadrant affecté', `Pr. ${encadrant.nom} suit désormais ton stage.`, '/suivi');
    notifier(db, encadrant.id, 'Nouveau stagiaire', `${etu.prenom} ${etu.nom} t'est affecté pour ${offre.titre}.`, `/encadrant/stagiaires/${stage.id}`);
    const enAttente = db.prepare(`SELECT COUNT(*) AS n FROM documents WHERE stage_id = ? AND statut = 'en_attente'`).get(stage.id).n;
    if (enAttente) {
      notifier(db, encadrant.id, 'Documents à valider', `${etu.prenom} ${etu.nom} a déjà déposé ${enAttente} document(s).`, '/encadrant/documents');
    }
    flash(req, 'ok', `${etu.prenom} ${etu.nom} est suivi par Pr. ${encadrant.nom}.`);
    res.redirect('/admin/affectations');
  });

  router.get('/admin/statistiques', (req, res) => {
    res.render('admin/statistiques', { titre: 'Statistiques', active: 'stats', stats: collectStats(db) });
  });

  router.get('/admin/statistiques/pdf', async (req, res, next) => {
    try {
      const buf = await statsPdf(collectStats(db));
      res.setHeader('Content-Type', 'application/pdf');
      res.setHeader('Content-Disposition', 'attachment; filename="statistiques-stagehub.pdf"');
      res.send(Buffer.from(buf));
    } catch (err) {
      next(err);
    }
  });

  router.get('/admin/statistiques/excel', async (req, res, next) => {
    try {
      const buf = await statsExcel(collectStats(db));
      res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      res.setHeader('Content-Disposition', 'attachment; filename="statistiques-stagehub.xlsx"');
      res.send(Buffer.from(buf));
    } catch (err) {
      next(err);
    }
  });

  return router;
};
