const express = require('express');
const bcrypt = require('bcryptjs');
const { str, homeFor, DEMO, DEMO_PASSWORD } = require('../lib/helpers');

module.exports = function authRoutes({ db, flash }) {
  const router = express.Router();

  function demosVisibles() {
    return DEMO.filter((d) => db.prepare('SELECT id FROM utilisateurs WHERE email = ?').get(d.email));
  }

  router.get('/', (req, res) => {
    if (res.locals.user) return res.redirect(homeFor(res.locals.user.role));
    res.render('auth/connexion', {
      titre: 'Connexion',
      demos: demosVisibles(),
      demoPassword: DEMO_PASSWORD,
    });
  });

  router.get('/inscription', (req, res) => {
    if (res.locals.user) return res.redirect(homeFor(res.locals.user.role));
    res.render('auth/inscription', { titre: 'Inscription' });
  });

  router.get('/mot-de-passe-oublie', (req, res) => {
    if (res.locals.user) return res.redirect(homeFor(res.locals.user.role));
    res.render('auth/oubli', { titre: 'Mot de passe oublié' });
  });

  router.post('/connexion', (req, res) => {
    const email = str(req.body.email).toLowerCase();
    const password = String(req.body.mot_de_passe || '');
    const user = db.prepare('SELECT * FROM utilisateurs WHERE email = ?').get(email);
    if (!user || !bcrypt.compareSync(password, user.mot_de_passe)) {
      flash(req, 'ko', 'E-mail ou mot de passe incorrect.');
      return res.redirect('/');
    }
    if (!user.actif) {
      flash(req, 'ko', 'Ce compte est désactivé. Contacte l\'administrateur.');
      return res.redirect('/');
    }
    req.session.regenerate((err) => {
      if (err) return res.status(500).send('Erreur de session');
      req.session.userId = user.id;
      req.session.csrf = require('crypto').randomBytes(24).toString('hex');
      res.redirect(homeFor(user.role));
    });
  });

  router.post('/inscription', (req, res) => {
    const role = req.body.role === 'entreprise' ? 'entreprise' : req.body.role === 'etudiant' ? 'etudiant' : '';
    const nom = str(req.body.nom);
    const prenom = str(req.body.prenom);
    const email = str(req.body.email).toLowerCase();
    const password = String(req.body.mot_de_passe || '');
    const filiere = str(req.body.filiere);
    const raison = str(req.body.raison_sociale);
    if (!role) {
      flash(req, 'ko', 'Choisis un type de compte.');
      return res.redirect('/inscription');
    }
    if (!nom || !prenom || nom.length > 80 || prenom.length > 80) {
      flash(req, 'ko', 'Indique le nom et le prénom du contact (80 caractères maximum).');
      return res.redirect('/inscription');
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || email.length > 160) {
      flash(req, 'ko', 'Adresse e-mail invalide.');
      return res.redirect('/inscription');
    }
    if (password.length < 8 || password.length > 200) {
      flash(req, 'ko', 'Le mot de passe doit contenir au moins 8 caractères.');
      return res.redirect('/inscription');
    }
    if (role === 'etudiant' && (!filiere || filiere.length > 120)) {
      flash(req, 'ko', 'Indique ta filière.');
      return res.redirect('/inscription');
    }
    if (role === 'entreprise' && (!raison || raison.length > 140)) {
      flash(req, 'ko', 'Indique la raison sociale de l\'entreprise.');
      return res.redirect('/inscription');
    }
    const existe = db.prepare('SELECT id FROM utilisateurs WHERE email = ?').get(email);
    if (existe) {
      flash(req, 'ko', 'Un compte existe déjà avec cet e-mail.');
      return res.redirect('/inscription');
    }
    const info = db.prepare(`
      INSERT INTO utilisateurs (email, mot_de_passe, role, nom, prenom, filiere, raison_sociale, competences, actif, cree_le)
      VALUES (?, ?, ?, ?, ?, ?, ?, '', 1, ?)
    `).run(
      email,
      bcrypt.hashSync(password, 10),
      role,
      nom,
      prenom,
      role === 'etudiant' ? filiere : '',
      role === 'entreprise' ? raison : '',
      new Date().toISOString(),
    );
    const userId = Number(info.lastInsertRowid);
    req.session.regenerate((err) => {
      if (err) return res.status(500).send('Erreur de session');
      req.session.userId = userId;
      req.session.csrf = require('crypto').randomBytes(24).toString('hex');
      req.session.flash = { type: 'ok', message: 'Compte créé. Bienvenue sur StageHub.' };
      res.redirect(homeFor(role));
    });
  });

  router.post('/mot-de-passe-oublie', (req, res) => {
    const email = str(req.body.email).toLowerCase();
    const user = db.prepare('SELECT id, actif FROM utilisateurs WHERE email = ?').get(email);
    if (user && user.actif) {
      db.prepare(`
        INSERT INTO reinitialisations (utilisateur_id, demande_le, traite) VALUES (?, ?, 0)
      `).run(user.id, new Date().toISOString());
    }
    flash(req, 'ok', 'Si un compte actif correspond à cet e-mail, l\'administrateur peut maintenant réinitialiser le mot de passe.');
    res.redirect('/mot-de-passe-oublie');
  });

  router.post('/deconnexion', (req, res) => {
    req.session.destroy(() => res.redirect('/'));
  });

  return router;
};
