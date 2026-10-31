const path = require('path');
const fs = require('fs');
const crypto = require('crypto');
const multer = require('multer');

const UPLOADS = process.env.UPLOADS_DIR || path.join(__dirname, '..', 'uploads');
fs.mkdirSync(UPLOADS, { recursive: true });

function nomStocke(original) {
  const ext = path.extname(original || '').toLowerCase();
  const safeExt = ['.pdf', '.doc', '.docx'].includes(ext) ? ext : '';
  return crypto.randomBytes(16).toString('hex') + safeExt;
}

const upload = multer({
  storage: multer.diskStorage({
    destination: (req, file, cb) => cb(null, UPLOADS),
    filename: (req, file, cb) => cb(null, nomStocke(file.originalname)),
  }),
  limits: { fileSize: 5 * 1024 * 1024, files: 3 },
  fileFilter: (req, file, cb) => {
    const ext = path.extname(file.originalname || '').toLowerCase();
    if (!['.pdf', '.doc', '.docx'].includes(ext)) return cb(new Error('FORMAT'));
    cb(null, true);
  },
});

function chemin(basename) {
  if (!basename || !/^[\w.\-]+$/.test(basename)) return null;
  const full = path.resolve(UPLOADS, basename);
  const root = path.resolve(UPLOADS);
  if (full !== root && !full.startsWith(root + path.sep)) return null;
  return full;
}

function nomOriginal(file) {
  return path.basename(file.originalname || 'document').slice(0, 180);
}

function envoyer(res, basename, nom) {
  const full = chemin(basename);
  if (!full || !fs.existsSync(full)) return false;
  res.download(full, (nom || basename).replace(/[\r\n"]/g, ''));
  return true;
}

function supprimer(basename) {
  const full = chemin(basename);
  if (full && fs.existsSync(full)) fs.rmSync(full, { force: true });
}

function fichierPartage(db, basename) {
  if (!basename) return 0;
  return db.prepare(`
    SELECT
      (SELECT COUNT(*) FROM utilisateurs WHERE cv_stocke = ?) +
      (SELECT COUNT(*) FROM offres WHERE document_stocke = ?) +
      (SELECT COUNT(*) FROM candidatures WHERE cv_stocke = ? OR convention_stocke = ?) +
      (SELECT COUNT(*) FROM documents WHERE fichier_stocke = ?)
    AS n
  `).get(basename, basename, basename, basename, basename).n;
}

function supprimerSiInutilise(db, basename) {
  if (basename && fichierPartage(db, basename) === 0) supprimer(basename);
}

function premierFichier(files, champ) {
  if (!files) return null;
  if (Array.isArray(files)) return files[0] || null;
  const list = files[champ];
  return list && list[0] ? list[0] : null;
}

module.exports = {
  UPLOADS,
  upload,
  chemin,
  nomOriginal,
  envoyer,
  supprimer,
  supprimerSiInutilise,
  premierFichier,
};
