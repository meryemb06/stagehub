const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const test = require('node:test');

const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'stagehub-'));
process.env.DB_PATH = path.join(dir, 'test.sqlite');
process.env.UPLOADS_DIR = path.join(dir, 'uploads');
process.env.SESSION_SECRET = 'test-secret-stagehub';

const { createApp } = require('../server');

function client() {
  const jar = new Map();
  return {
    clear() { jar.clear(); },
    async fetch(url, opts = {}) {
      const headers = new Headers(opts.headers || {});
      if ([...jar.keys()].length) {
        headers.set('cookie', [...jar].map(([k, v]) => `${k}=${v}`).join('; '));
      }
      const res = await fetch(url, { redirect: 'manual', ...opts, headers });
      const cookies = typeof res.headers.getSetCookie === 'function' ? res.headers.getSetCookie() : [];
      for (const cookie of cookies) {
        const pair = cookie.split(';')[0];
        const i = pair.indexOf('=');
        jar.set(pair.slice(0, i).trim(), pair.slice(i + 1).trim());
      }
      return res;
    },
  };
}

async function suivre(c, res, base) {
  let current = res;
  let hops = 0;
  let url = base;
  while (current.status >= 300 && current.status < 400 && hops < 6) {
    url = new URL(current.headers.get('location'), url);
    current = await c.fetch(url);
    hops += 1;
  }
  return { res: current, html: await current.text(), url: String(url) };
}

async function ouvrir(c, url) {
  return suivre(c, await c.fetch(url), url);
}

function jeton(html) {
  const match = html.match(/name="_csrf" value="([^"]+)"/);
  assert.ok(match, 'jeton CSRF introuvable');
  return match[1];
}

async function connecter(c, base, email, motDePasse) {
  c.clear();
  const accueil = await ouvrir(c, `${base}/`);
  const body = new URLSearchParams({
    _csrf: jeton(accueil.html),
    email,
    mot_de_passe: motDePasse,
  });
  const res = await c.fetch(`${base}/connexion`, {
    method: 'POST',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body,
  });
  return suivre(c, res, `${base}/connexion`);
}

async function envoyerForm(c, action, champs) {
  const body = new URLSearchParams(champs);
  const res = await c.fetch(action, {
    method: 'POST',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body,
  });
  return suivre(c, res, action);
}

const pdf = Buffer.from('%PDF-1.1\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n');

test('parcours complet des quatre rôles', async (t) => {
  const app = await createApp();
  const server = app.listen(0);
  await new Promise((resolve) => server.once('listening', resolve));
  const base = `http://127.0.0.1:${server.address().port}`;
  const c = client();
  t.after(() => new Promise((resolve) => {
    server.close(() => {
      app.db.close();
      resolve();
    });
  }));

  const accueil = await ouvrir(c, `${base}/`);
  assert.equal(accueil.res.status, 200);
  assert.match(accueil.html, /StageHub/);
  assert.match(accueil.html, /youssef\.benali@stagehub\.ma/);
  assert.match(accueil.html, /contact@technord\.ma/);

  const bloque = await connecter(c, base, 'bennani@stagehub.ma', 'Stagehub1');
  assert.match(bloque.html, /désactivé/);
  assert.match(bloque.url, /\/$/);

  const etudiant = await connecter(c, base, 'youssef.benali@stagehub.ma', 'Stagehub1');
  assert.match(etudiant.url, /\/offres$/);
  assert.match(etudiant.html, /Développeur web PHP/);
  assert.doesNotMatch(etudiant.html, /Support technique/);
  const filtre = await ouvrir(c, `${base}/offres?ville=${encodeURIComponent('Tanger')}&recherche=PHP`);
  assert.match(filtre.html, /Développeur web PHP/);
  assert.doesNotMatch(filtre.html, /Designer UI\/UX/);

  const historique = await ouvrir(c, `${base}/historique`);
  assert.match(historique.html, /Support technique/);
  assert.match(historique.html, /14\/20/);

  const interdit = await c.fetch(`${base}/admin/comptes`);
  assert.equal(interdit.status, 302);
  assert.match(interdit.headers.get('location'), /\/offres$/);

  const detail = await ouvrir(c, `${base}/offres?recherche=${encodeURIComponent('réseaux')}`);
  const offreSecu = detail.html.match(/data-offre="(\d+)" data-titre="Stage réseaux et sécurité"/);
  assert.ok(offreSecu);
  const fichierOffre = await c.fetch(`${base}/fichiers/offre/${offreSecu[1]}`);
  assert.equal(fichierOffre.status, 200);
  assert.match(fichierOffre.headers.get('content-type'), /pdf/);

  c.clear();
  const inscription = await ouvrir(c, `${base}/inscription`);
  const compte = await envoyerForm(c, `${base}/inscription`, {
    _csrf: jeton(inscription.html),
    role: 'etudiant',
    nom: 'Test',
    prenom: 'Nora',
    filiere: 'Informatique',
    email: 'nora.test@example.com',
    mot_de_passe: 'Stagehub1',
  });
  assert.match(compte.html, /Bienvenue/);
  assert.match(compte.url, /\/offres$/);

  c.clear();
  const entreprise = await connecter(c, base, 'contact@technord.ma', 'Stagehub1');
  assert.match(entreprise.url, /\/entreprise\/offres$/);
  const nouvelle = await ouvrir(c, `${base}/entreprise/offres/nouvelle`);
  const offreForm = new FormData();
  offreForm.set('_csrf', jeton(nouvelle.html));
  offreForm.set('titre', 'Stage test automatisé');
  offreForm.set('description', 'Participation aux tests d\'une application de gestion.');
  offreForm.set('missions', 'Écrire des scénarios\nExécuter les tests');
  offreForm.set('competences', 'Tests, SQL');
  offreForm.set('duree_mois', '2');
  offreForm.set('ville', 'Fès');
  offreForm.set('document', new Blob([pdf], { type: 'application/pdf' }), 'presentation.pdf');
  const creee = await suivre(c, await c.fetch(`${base}/entreprise/offres`, { method: 'POST', body: offreForm }), `${base}/entreprise/offres`);
  assert.match(creee.html, /Offre publiée/);
  const idOffre = creee.html.match(/data-offre="(\d+)" data-titre="Stage test automatisé"/);
  assert.ok(idOffre);

  const nora = await connecter(c, base, 'nora.test@example.com', 'Stagehub1');
  const postuler = await ouvrir(c, `${base}/offres/${idOffre[1]}/postuler`);
  assert.match(postuler.html, /Stage test automatisé/);
  const candidature = new FormData();
  candidature.set('_csrf', jeton(postuler.html));
  candidature.set('lettre', 'Je souhaite rejoindre ce stage pour mettre en pratique les tests et le SQL vus en cours.');
  candidature.set('cv', new Blob([pdf], { type: 'application/pdf' }), 'cv-nora.pdf');
  candidature.set('convention', new Blob([pdf], { type: 'application/pdf' }), 'convention-nora.pdf');
  const envoyee = await suivre(c, await c.fetch(`${base}/offres/${idOffre[1]}/postuler`, { method: 'POST', body: candidature }), base);
  assert.match(envoyee.html, /Candidature envoyée/);

  const tech = await connecter(c, base, 'contact@technord.ma', 'Stagehub1');
  const recues = await ouvrir(c, `${base}/entreprise/candidatures?offre=${idOffre[1]}`);
  const idCand = recues.html.match(/data-candidature="(\d+)">\s*<td>Nora Test/);
  assert.ok(idCand);
  const dossier = await ouvrir(c, `${base}/entreprise/candidatures/${idCand[1]}`);
  assert.match(dossier.html, /mettre en pratique les tests/);
  const decision = await envoyerForm(c, `${base}/entreprise/candidatures/${idCand[1]}/decision`, {
    _csrf: jeton(dossier.html),
    decision: 'acceptee',
  });
  assert.match(decision.html, /Candidature acceptée/);

  const admin = await connecter(c, base, 'admin@stagehub.ma', 'Stagehub1');
  const affectations = await ouvrir(c, `${base}/admin/affectations`);
  const idStage = affectations.html.match(/data-stage="(\d+)" data-email="nora\.test@example\.com"/);
  const idEnc = affectations.html.match(/<option value="(\d+)" data-email="idrissi@stagehub\.ma"/);
  assert.ok(idStage);
  assert.ok(idEnc);
  const affecte = await envoyerForm(c, `${base}/admin/affectations`, {
    _csrf: jeton(affectations.html),
    stage_id: idStage[1],
    encadrant_id: idEnc[1],
  });
  assert.match(affecte.html, /Pr\. Idrissi/);

  const stats = await ouvrir(c, `${base}/admin/statistiques`);
  assert.match(stats.html, /Étudiants inscrits/);
  const pdfRes = await c.fetch(`${base}/admin/statistiques/pdf`);
  const pdfBuf = Buffer.from(await pdfRes.arrayBuffer());
  assert.equal(pdfRes.status, 200);
  assert.equal(pdfBuf.subarray(0, 4).toString(), '%PDF');
  const xlsxRes = await c.fetch(`${base}/admin/statistiques/excel`);
  const xlsxBuf = Buffer.from(await xlsxRes.arrayBuffer());
  assert.equal(xlsxRes.status, 200);
  assert.equal(xlsxBuf.subarray(0, 2).toString(), 'PK');

  const retourNora = await connecter(c, base, 'nora.test@example.com', 'Stagehub1');
  assert.match(retourNora.url, /\/offres/);
  const documents = await ouvrir(c, `${base}/documents`);
  assert.match(documents.html, /Convention/);
  const rapport = new FormData();
  rapport.set('_csrf', jeton(documents.html));
  rapport.set('stage_id', idStage[1]);
  rapport.set('fichier', new Blob([pdf], { type: 'application/pdf' }), 'rapport-nora.pdf');
  const depose = await suivre(c, await c.fetch(`${base}/documents/rapport`, { method: 'POST', body: rapport }), base);
  assert.match(depose.html, /Rapport déposé/);
  const idRapport = depose.html.match(/data-rapport="(\d+)"/);
  assert.ok(idRapport);

  const aujourd = new Intl.DateTimeFormat('en-CA', { timeZone: 'Africa/Casablanca', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
  const suivi = await ouvrir(c, `${base}/suivi`);
  const journal = await envoyerForm(c, `${base}/suivi/journal`, {
    _csrf: jeton(suivi.html),
    stage_id: idStage[1],
    date_entree: aujourd,
    taches: 'Prise en main du dépôt et lecture du cahier des charges.',
    difficultes: 'Aucune.',
  });
  assert.match(journal.html, /Entrée ajoutée/);
  const commentaire = await envoyerForm(c, `${base}/suivi/commentaire`, {
    _csrf: jeton(journal.html),
    stage_id: idStage[1],
    contenu: 'Je commence par les scénarios de test.',
  });
  assert.match(commentaire.html, /Réponse envoyée/);

  const prof = await connecter(c, base, 'idrissi@stagehub.ma', 'Stagehub1');
  assert.match(prof.url, /\/encadrant\/stagiaires/);
  assert.match(prof.html, /Nora Test/);
  const docs = await ouvrir(c, `${base}/encadrant/documents`);
  const idConvention = docs.html.match(/data-document="(\d+)" data-type="convention" data-etudiant="Nora Test"/);
  assert.ok(idConvention);
  const rejet = await envoyerForm(c, `${base}/encadrant/documents/${idRapport[1]}/rejeter`, {
    _csrf: jeton(docs.html),
    commentaire: 'Ajoute le sommaire et la conclusion.',
  });
  assert.match(rejet.html, /Document rejeté/);
  const valide = await envoyerForm(c, `${base}/encadrant/documents/${idConvention[1]}/valider`, {
    _csrf: jeton(rejet.html),
    });
  assert.match(valide.html, /Document validé/);

  const corrige = await connecter(c, base, 'nora.test@example.com', 'Stagehub1');
  const docsNora = await ouvrir(c, `${base}/documents`);
  assert.match(docsNora.html, /Ajoute le sommaire/);
  const rapport2 = new FormData();
  rapport2.set('_csrf', jeton(docsNora.html));
  rapport2.set('stage_id', idStage[1]);
  rapport2.set('fichier', new Blob([pdf], { type: 'application/pdf' }), 'rapport-nora-v2.pdf');
  await suivre(c, await c.fetch(`${base}/documents/rapport`, { method: 'POST', body: rapport2 }), base);

  const prof2 = await connecter(c, base, 'idrissi@stagehub.ma', 'Stagehub1');
  const docs2 = await ouvrir(c, `${base}/encadrant/documents`);
  const rapportAttente = docs2.html.match(/data-document="(\d+)" data-type="rapport" data-etudiant="Nora Test"/);
  assert.ok(rapportAttente);
  await envoyerForm(c, `${base}/encadrant/documents/${rapportAttente[1]}/valider`, { _csrf: jeton(docs2.html) });
  const fiche = await ouvrir(c, `${base}/encadrant/stagiaires/${idStage[1]}`);
  assert.match(fiche.html, /Prise en main du dépôt/);
  const entree = fiche.html.match(/action="\/encadrant\/journal\/(\d+)\/feedback"/);
  assert.ok(entree);
  const feedback = await envoyerForm(c, `${base}/encadrant/journal/${entree[1]}/feedback`, {
    _csrf: jeton(fiche.html),
    feedback: 'Bon démarrage, précise les scénarios couverts.',
  });
  assert.match(feedback.html, /Feedback enregistré/);
  const note = await envoyerForm(c, `${base}/encadrant/stages/${idStage[1]}/note`, {
    _csrf: jeton(feedback.html),
    note: '17',
    appreciation: 'Travail rigoureux sur les tests.',
  });
  assert.match(note.html, /Note enregistrée/);
  await envoyerForm(c, `${base}/encadrant/stages/${idStage[1]}/commentaire`, {
    _csrf: jeton(note.html),
    contenu: 'Pense à illustrer un scénario dans le rapport.',
  });

  const tech2 = await connecter(c, base, 'contact@technord.ma', 'Stagehub1');
  const evalPage = await ouvrir(c, `${base}/entreprise/stages/${idStage[1]}/evaluation`);
  const evaluation = await envoyerForm(c, `${base}/entreprise/stages/${idStage[1]}/evaluation`, {
    _csrf: jeton(evalPage.html),
    note: '16',
    assiduite: 'Très bien',
    competences_tech: 'Bien',
    autonomie: 'Bien',
    communication: 'Très bien',
    qualite: 'Bien',
    commentaire: 'Nora a tenu le planning de tests.',
  });
  assert.match(evaluation.html, /Évaluation enregistrée/);

  const clotureSession = await connecter(c, base, 'idrissi@stagehub.ma', 'Stagehub1');
  const cloture = await envoyerForm(c, `${base}/encadrant/stages/${idStage[1]}/cloturer`, {
    _csrf: jeton(clotureSession.html),
  });
  assert.match(cloture.html, /Stage clôturé/);

  const fin = await connecter(c, base, 'nora.test@example.com', 'Stagehub1');
  const passe = await ouvrir(c, `${base}/historique`);
  assert.match(passe.html, /Stage test automatisé/);
  assert.match(passe.html, /17\/20/);
  assert.match(passe.html, /16\/20/);
  const autreFichier = await c.fetch(`${base}/fichiers/document/${idRapport[1]}`);
  assert.equal(autreFichier.status, 200);

  const youssef = await connecter(c, base, 'youssef.benali@stagehub.ma', 'Stagehub1');
  const vol = await c.fetch(`${base}/fichiers/document/${idRapport[1]}`);
  assert.equal(vol.status, 403);
  const profil = await ouvrir(c, `${base}/profil`);
  const profilForm = new FormData();
  profilForm.set('_csrf', jeton(profil.html));
  profilForm.set('nom', 'Benali');
  profilForm.set('prenom', 'Youssef');
  profilForm.set('filiere', 'Génie logiciel');
  profilForm.set('competences', 'PHP, tests');
  const profilMaj = await suivre(c, await c.fetch(`${base}/profil`, { method: 'POST', body: profilForm }), base);
  assert.match(profilMaj.html, /Profil mis à jour/);
  assert.match(profilMaj.html, /Génie logiciel/);

  c.clear();
  const oubli = await ouvrir(c, `${base}/mot-de-passe-oublie`);
  await envoyerForm(c, `${base}/mot-de-passe-oublie`, {
    _csrf: jeton(oubli.html),
    email: 'nora.test@example.com',
  });
  const admin2 = await connecter(c, base, 'admin@stagehub.ma', 'Stagehub1');
  const comptes = await ouvrir(c, `${base}/admin/comptes`);
  assert.match(comptes.html, /nora\.test@example\.com/);
  const reset = await envoyerForm(c, `${base}/admin/comptes/reinitialiser`, {
    _csrf: jeton(comptes.html),
    utilisateur_id: String(await idUtilisateur(app, 'nora.test@example.com')),
  });
  const nouveau = reset.html.match(/Nouveau mot de passe pour nora\.test@example\.com : ([A-Za-z0-9]+)/);
  assert.ok(nouveau);
  const desactive = await envoyerForm(c, `${base}/admin/comptes/basculer`, {
    _csrf: jeton(reset.html),
    utilisateur_id: String(await idUtilisateur(app, 'nora.test@example.com')),
  });
  assert.match(desactive.html, /désactivé/i);

  const refuse = await connecter(c, base, 'nora.test@example.com', nouveau[1]);
  assert.match(refuse.html, /désactivé/);
  assert.ok(youssef);
});

function idUtilisateur(app, email) {
  return app.db.prepare('SELECT id FROM utilisateurs WHERE email = ?').get(email).id;
}
