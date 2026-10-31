const crypto = require('crypto');

function str(v) {
  return String(v ?? '').replace(/\0/g, '').trim();
}

function homeFor(role) {
  switch (role) {
    case 'etudiant': return '/offres';
    case 'entreprise': return '/entreprise/offres';
    case 'encadrant': return '/encadrant/stagiaires';
    case 'admin': return '/admin/comptes';
    default: return '/';
  }
}

function publicUser(u) {
  return {
    id: u.id,
    email: u.email,
    role: u.role,
    nom: u.nom,
    prenom: u.prenom || '',
    filiere: u.filiere || '',
    raison_sociale: u.raison_sociale || '',
    competences: u.competences || '',
    actif: u.actif,
    cv_nom: u.cv_nom || '',
  };
}

function nomAffiche(u) {
  if (!u) return '';
  if (u.role === 'entreprise') return u.raison_sociale || u.nom;
  if (u.role === 'encadrant') return `Pr. ${u.nom}`;
  if (u.role === 'admin') return 'Administrateur';
  return `${u.prenom || ''} ${u.nom || ''}`.trim();
}

function roleLabel(role) {
  return {
    etudiant: 'Étudiant',
    entreprise: 'Entreprise',
    encadrant: 'Encadrant',
    admin: 'Administrateur',
  }[role] || role;
}

function todayISO() {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Africa/Casablanca',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date());
}

function dateFr(value) {
  if (!value) return '—';
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    const [y, m, d] = value.split('-');
    return `${d}/${m}/${y}`;
  }
  const dt = new Date(value);
  if (Number.isNaN(dt.getTime())) return '—';
  return new Intl.DateTimeFormat('fr-FR', {
    timeZone: 'Africa/Casablanca',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(dt);
}

function dateHeureFr(value) {
  if (!value) return '—';
  const dt = new Date(value);
  if (Number.isNaN(dt.getTime())) return '—';
  return new Intl.DateTimeFormat('fr-FR', {
    timeZone: 'Africa/Casablanca',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(dt);
}

function nl2br(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/\n/g, '<br>');
}

function lignes(value) {
  return String(value ?? '')
    .split(/\n/)
    .map((line) => line.replace(/^[-•]\s*/, '').trim())
    .filter(Boolean);
}

function idParam(value) {
  const n = Number(value);
  if (!Number.isInteger(n) || n <= 0) return null;
  return n;
}

function flash(req, type, message) {
  req.session.flash = { type, message };
}

function badge(kind, statut) {
  const map = {
    candidature: {
      en_attente: ['En attente', 'e-att'],
      acceptee: ['Acceptée', 'e-ok'],
      refusee: ['Refusée', 'e-ko'],
    },
    document: {
      en_attente: ['À valider', 'e-att'],
      valide: ['Validé', 'e-ok'],
      rejete: ['Rejeté', 'e-ko'],
    },
    stage: {
      en_cours: ['En cours', 'e-att'],
      termine: ['Terminé', 'e-ok'],
    },
    compte: {
      1: ['Actif', 'e-ok'],
      0: ['Désactivé', 'e-ko'],
    },
  };
  const entry = (map[kind] || {})[statut] || [String(statut), 'e-att'];
  return `<span class="etat ${entry[1]}">${entry[0]}</span>`;
}

function notifier(db, utilisateurId, titre, message, lien) {
  if (!utilisateurId) return;
  const safeLien = typeof lien === 'string' && lien.startsWith('/') ? lien : '';
  db.prepare(`
    INSERT INTO notifications (utilisateur_id, titre, message, lien, lu, cree_le)
    VALUES (?, ?, ?, ?, 0, ?)
  `).run(utilisateurId, titre, message, safeLien, new Date().toISOString());
}

function motDePasseTemporaire() {
  const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
  const bytes = crypto.randomBytes(10);
  let out = '';
  for (const b of bytes) out += alphabet[b % alphabet.length];
  return out;
}

function dateIsoValide(value, { futurAutorise = false } = {}) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return false;
  const dt = new Date(`${value}T12:00:00Z`);
  if (Number.isNaN(dt.getTime())) return false;
  if (value < '2000-01-01') return false;
  if (!futurAutorise && value > todayISO()) return false;
  return true;
}

function retourSur(req, fallback) {
  try {
    const ref = new URL(req.get('Referrer') || '', `http://${req.headers.host}`);
    if (ref.host === req.headers.host && ref.pathname.startsWith('/')) {
      return ref.pathname + ref.search;
    }
  } catch {
    /* referrer illisible */
  }
  return fallback;
}

const NIVEAUX = ['Très bien', 'Bien', 'Moyen', 'Insuffisant'];

const DEMO = [
  { role: 'Étudiant', nom: 'Youssef Benali', email: 'youssef.benali@stagehub.ma' },
  { role: 'Entreprise', nom: 'TechNord', email: 'contact@technord.ma' },
  { role: 'Encadrant', nom: 'Pr. Alaoui', email: 'alaoui@stagehub.ma' },
  { role: 'Administrateur', nom: 'Admin', email: 'admin@stagehub.ma' },
];

const DEMO_PASSWORD = 'Stagehub1';

module.exports = {
  str,
  homeFor,
  publicUser,
  nomAffiche,
  roleLabel,
  todayISO,
  dateFr,
  dateHeureFr,
  nl2br,
  lignes,
  idParam,
  flash,
  badge,
  notifier,
  motDePasseTemporaire,
  dateIsoValide,
  retourSur,
  NIVEAUX,
  DEMO,
  DEMO_PASSWORD,
};
