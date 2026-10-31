const path = require('path');
const crypto = require('crypto');
const express = require('express');
const session = require('express-session');
const { openDatabase } = require('./lib/db');
const { upload } = require('./lib/files');
const {
  homeFor, publicUser, nomAffiche, roleLabel, dateFr, dateHeureFr, nl2br, lignes, badge, flash, retourSur,
} = require('./lib/helpers');

function guardCsrf(req, res, next) {
  const type = req.headers['content-type'] || '';
  if (req.method === 'POST' && !type.includes('multipart/form-data')) {
    if (!req.session || req.body._csrf !== req.session.csrf) {
      return res.status(403).send('Session expirée. Recharge la page puis réessaie.');
    }
  }
  next();
}

function verifierCsrf(req, res, next) {
  if (!req.session || req.body._csrf !== req.session.csrf) {
    return res.status(403).send('Session expirée. Recharge la page puis réessaie.');
  }
  next();
}

async function createApp() {
  const db = await openDatabase();
  const app = express();
  app.disable('x-powered-by');
  app.set('view engine', 'ejs');
  app.set('views', path.join(__dirname, 'views'));
  app.locals.nomAffiche = nomAffiche;
  app.locals.roleLabel = roleLabel;
  app.locals.dateFr = dateFr;
  app.locals.dateHeureFr = dateHeureFr;
  app.locals.nl2br = nl2br;
  app.locals.lignes = lignes;
  app.locals.badge = badge;

  app.use((req, res, next) => {
    res.setHeader('X-Content-Type-Options', 'nosniff');
    res.setHeader('Referrer-Policy', 'same-origin');
    res.setHeader('X-Frame-Options', 'DENY');
    next();
  });
  app.use('/css', express.static(path.join(__dirname, 'public', 'css')));
  app.use(express.urlencoded({ extended: false }));
  app.use(session({
    secret: process.env.SESSION_SECRET || crypto.randomBytes(32).toString('hex'),
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      sameSite: 'lax',
      secure: process.env.COOKIE_SECURE === '1',
      maxAge: 1000 * 60 * 60 * 8,
    },
  }));

  app.use((req, res, next) => {
    if (!req.session.csrf) req.session.csrf = crypto.randomBytes(24).toString('hex');
    res.locals.csrf = req.session.csrf;
    res.locals.flash = req.session.flash || null;
    delete req.session.flash;
    res.locals.compteInactif = req.query.compte === 'inactif';

    if (!req.session.userId) {
      res.locals.user = null;
      res.locals.notifCount = 0;
      return next();
    }
    const row = db.prepare('SELECT * FROM utilisateurs WHERE id = ?').get(req.session.userId);
    if (!row || !row.actif) {
      return req.session.destroy(() => res.redirect('/?compte=inactif'));
    }
    res.locals.user = publicUser(row);
    res.locals.notifCount = db.prepare(
      'SELECT COUNT(*) AS n FROM notifications WHERE utilisateur_id = ? AND lu = 0'
    ).get(row.id).n;
    next();
  });
  app.use(guardCsrf);

  const ctx = { db, upload, verifierCsrf, flash, homeFor, retourSur };
  app.use(require('./routes/auth')(ctx));
  app.use(require('./routes/etudiant')(ctx));
  app.use(require('./routes/entreprise')(ctx));
  app.use(require('./routes/encadrant')(ctx));
  app.use(require('./routes/admin')(ctx));
  app.use(require('./routes/commun')(ctx));

  app.use((req, res) => {
    res.status(404).type('html').send('<!DOCTYPE html><meta charset="utf-8"><title>Introuvable</title><p>Page introuvable. <a href="/">Retour à StageHub</a></p>');
  });

  app.use((err, req, res, next) => {
    if (res.headersSent) return next(err);
    const fallback = res.locals.user ? homeFor(res.locals.user.role) : '/';
    if (err && (err.code === 'LIMIT_FILE_SIZE' || err.message === 'FORMAT')) {
      const message = err.message === 'FORMAT'
        ? 'Format accepté : PDF, DOC ou DOCX.'
        : 'Fichier trop lourd (5 Mo maximum).';
      if (req.session) {
        flash(req, 'ko', message);
        return res.redirect(retourSur(req, fallback));
      }
    }
    console.error(err);
    res.status(500).type('html').send('<!DOCTYPE html><meta charset="utf-8"><title>Erreur</title><p>Une erreur est survenue. <a href="/">Retour</a></p>');
  });

  app.db = db;
  return app;
}

if (require.main === module) {
  createApp().then((app) => {
    const port = Number(process.env.PORT) || 3000;
    app.listen(port, () => {
      console.log(`StageHub http://localhost:${port}`);
    });
  }).catch((err) => {
    console.error(err);
    process.exit(1);
  });
}

module.exports = { createApp };
