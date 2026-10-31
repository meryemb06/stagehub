const fs = require('fs');
const path = require('path');
const bcrypt = require('bcryptjs');
const Database = require('better-sqlite3');
const { pdfBuffer } = require('./pdf');
const { UPLOADS } = require('./files');

const DB_PATH = process.env.DB_PATH || path.join(__dirname, '..', 'data', 'stagehub.sqlite');

function migrate(db) {
  db.exec(`
    CREATE TABLE IF NOT EXISTS utilisateurs (
      id INTEGER PRIMARY KEY,
      email TEXT NOT NULL UNIQUE,
      mot_de_passe TEXT NOT NULL,
      role TEXT NOT NULL CHECK (role IN ('etudiant', 'entreprise', 'encadrant', 'admin')),
      nom TEXT NOT NULL,
      prenom TEXT NOT NULL DEFAULT '',
      filiere TEXT NOT NULL DEFAULT '',
      raison_sociale TEXT NOT NULL DEFAULT '',
      competences TEXT NOT NULL DEFAULT '',
      cv_stocke TEXT,
      cv_nom TEXT,
      actif INTEGER NOT NULL DEFAULT 1,
      cree_le TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS offres (
      id INTEGER PRIMARY KEY,
      entreprise_id INTEGER NOT NULL REFERENCES utilisateurs(id),
      titre TEXT NOT NULL,
      description TEXT NOT NULL,
      missions TEXT NOT NULL DEFAULT '',
      competences TEXT NOT NULL DEFAULT '',
      duree_mois INTEGER NOT NULL,
      ville TEXT NOT NULL,
      document_stocke TEXT,
      document_nom TEXT,
      retiree INTEGER NOT NULL DEFAULT 0,
      cree_le TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS candidatures (
      id INTEGER PRIMARY KEY,
      offre_id INTEGER NOT NULL REFERENCES offres(id),
      etudiant_id INTEGER NOT NULL REFERENCES utilisateurs(id),
      cv_stocke TEXT,
      cv_nom TEXT,
      lettre TEXT NOT NULL,
      convention_stocke TEXT,
      convention_nom TEXT,
      statut TEXT NOT NULL DEFAULT 'en_attente' CHECK (statut IN ('en_attente', 'acceptee', 'refusee')),
      cree_le TEXT NOT NULL,
      UNIQUE (offre_id, etudiant_id)
    );

    CREATE TABLE IF NOT EXISTS stages (
      id INTEGER PRIMARY KEY,
      candidature_id INTEGER NOT NULL UNIQUE REFERENCES candidatures(id),
      etudiant_id INTEGER NOT NULL REFERENCES utilisateurs(id),
      entreprise_id INTEGER NOT NULL REFERENCES utilisateurs(id),
      encadrant_id INTEGER REFERENCES utilisateurs(id),
      offre_id INTEGER NOT NULL REFERENCES offres(id),
      statut TEXT NOT NULL DEFAULT 'en_cours' CHECK (statut IN ('en_cours', 'termine')),
      sujet TEXT NOT NULL DEFAULT '',
      description_convention TEXT NOT NULL DEFAULT '',
      date_debut TEXT,
      date_fin TEXT,
      contact_entreprise TEXT NOT NULL DEFAULT '',
      note_entreprise REAL,
      commentaire_entreprise TEXT,
      assiduite TEXT,
      competences_tech TEXT,
      autonomie TEXT,
      communication TEXT,
      qualite TEXT,
      note_encadrant REAL,
      appreciation_encadrant TEXT,
      cree_le TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS documents (
      id INTEGER PRIMARY KEY,
      stage_id INTEGER NOT NULL REFERENCES stages(id),
      type TEXT NOT NULL CHECK (type IN ('convention', 'rapport')),
      fichier_stocke TEXT,
      fichier_nom TEXT,
      statut TEXT NOT NULL DEFAULT 'en_attente' CHECK (statut IN ('en_attente', 'valide', 'rejete')),
      commentaire TEXT NOT NULL DEFAULT '',
      depose_le TEXT NOT NULL,
      traite_le TEXT,
      UNIQUE (stage_id, type)
    );

    CREATE TABLE IF NOT EXISTS journal (
      id INTEGER PRIMARY KEY,
      stage_id INTEGER NOT NULL REFERENCES stages(id),
      date_entree TEXT NOT NULL,
      taches TEXT NOT NULL,
      difficultes TEXT NOT NULL DEFAULT '',
      feedback TEXT NOT NULL DEFAULT '',
      feedback_le TEXT,
      cree_le TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS commentaires (
      id INTEGER PRIMARY KEY,
      stage_id INTEGER NOT NULL REFERENCES stages(id),
      auteur_id INTEGER NOT NULL REFERENCES utilisateurs(id),
      contenu TEXT NOT NULL,
      cree_le TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS notifications (
      id INTEGER PRIMARY KEY,
      utilisateur_id INTEGER NOT NULL REFERENCES utilisateurs(id),
      titre TEXT NOT NULL,
      message TEXT NOT NULL,
      lien TEXT NOT NULL DEFAULT '',
      lu INTEGER NOT NULL DEFAULT 0,
      cree_le TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS reinitialisations (
      id INTEGER PRIMARY KEY,
      utilisateur_id INTEGER NOT NULL REFERENCES utilisateurs(id),
      demande_le TEXT NOT NULL,
      traite INTEGER NOT NULL DEFAULT 0
    );

    CREATE INDEX IF NOT EXISTS idx_offres_entreprise ON offres(entreprise_id);
    CREATE INDEX IF NOT EXISTS idx_cand_offre ON candidatures(offre_id);
    CREATE INDEX IF NOT EXISTS idx_cand_etudiant ON candidatures(etudiant_id);
    CREATE INDEX IF NOT EXISTS idx_stages_etudiant ON stages(etudiant_id);
    CREATE INDEX IF NOT EXISTS idx_stages_enc ON stages(encadrant_id);
    CREATE INDEX IF NOT EXISTS idx_stages_ent ON stages(entreprise_id);
    CREATE INDEX IF NOT EXISTS idx_docs_stage ON documents(stage_id);
    CREATE INDEX IF NOT EXISTS idx_journal_stage ON journal(stage_id);
    CREATE INDEX IF NOT EXISTS idx_comm_stage ON commentaires(stage_id);
    CREATE INDEX IF NOT EXISTS idx_notif_user ON notifications(utilisateur_id, lu);
  `);
}

async function ecrirePdf(nom, titre, lignes) {
  const buf = await pdfBuffer(titre, lignes);
  fs.writeFileSync(path.join(UPLOADS, nom), buf);
  return nom;
}

async function seed(db) {
  const hash = bcrypt.hashSync('Stagehub1', 10);
  const insUser = db.prepare(`
    INSERT INTO utilisateurs (email, mot_de_passe, role, nom, prenom, filiere, raison_sociale, competences, cv_stocke, cv_nom, actif, cree_le)
    VALUES (@email, @mot_de_passe, @role, @nom, @prenom, @filiere, @raison_sociale, @competences, @cv_stocke, @cv_nom, @actif, @cree_le)
  `);

  const cvY = await ecrirePdf('seed-cv-youssef.pdf', 'CV — Youssef Benali', [
    'Étudiant en génie informatique.',
    'Compétences : PHP, MySQL, HTML/CSS.',
    'Projet académique : module de gestion des stages.',
  ]);
  const cvS = await ecrirePdf('seed-cv-sara.pdf', 'CV — Sara El Idrissi', [
    'Étudiante en génie informatique.',
    'Compétences : Python, SQL, tableaux de bord.',
  ]);
  const cvA = await ecrirePdf('seed-cv-adam.pdf', 'CV — Adam Tazi', [
    'Étudiant en réseaux et systèmes.',
    'Compétences : Linux, routage, supervision.',
  ]);
  const cvL = await ecrirePdf('seed-cv-lina.pdf', 'CV — Lina Chraibi', [
    'Étudiante en gestion de projet.',
    'Compétences : suivi de planning, comptes rendus.',
  ]);

  const jour = (iso) => `${iso}T12:00:00.000Z`;
  const base = {
    mot_de_passe: hash,
    filiere: '',
    raison_sociale: '',
    competences: '',
    cv_stocke: null,
    cv_nom: null,
    actif: 1,
    prenom: '',
  };

  const users = [
    { ...base, email: 'admin@stagehub.ma', role: 'admin', nom: 'Administrateur', cree_le: jour('2026-09-01') },
    { ...base, email: 'youssef.benali@stagehub.ma', role: 'etudiant', nom: 'Benali', prenom: 'Youssef', filiere: 'Génie informatique', competences: 'PHP, MySQL, HTML/CSS', cv_stocke: cvY, cv_nom: 'CV_Youssef_Benali.pdf', cree_le: jour('2026-09-12') },
    { ...base, email: 'sara.elidrissi@stagehub.ma', role: 'etudiant', nom: 'El Idrissi', prenom: 'Sara', filiere: 'Génie informatique', competences: 'Python, SQL, Power BI', cv_stocke: cvS, cv_nom: 'CV_Sara_El_Idrissi.pdf', cree_le: jour('2026-09-12') },
    { ...base, email: 'adam.tazi@stagehub.ma', role: 'etudiant', nom: 'Tazi', prenom: 'Adam', filiere: 'Réseaux et systèmes', competences: 'Linux, Cisco', cv_stocke: cvA, cv_nom: 'CV_Adam_Tazi.pdf', cree_le: jour('2026-09-18') },
    { ...base, email: 'lina.chraibi@stagehub.ma', role: 'etudiant', nom: 'Chraibi', prenom: 'Lina', filiere: 'Gestion de projet', competences: 'Excel, communication', cv_stocke: cvL, cv_nom: 'CV_Lina_Chraibi.pdf', cree_le: jour('2026-09-20') },
    { ...base, email: 'contact@technord.ma', role: 'entreprise', nom: 'Cherkaoui', prenom: 'Mehdi', raison_sociale: 'TechNord', cree_le: jour('2026-09-05') },
    { ...base, email: 'contact@datamaroc.ma', role: 'entreprise', nom: 'Berrada', prenom: 'Salma', raison_sociale: 'DataMaroc', cree_le: jour('2026-09-05') },
    { ...base, email: 'contact@atlasconseil.ma', role: 'entreprise', nom: 'Amrani', prenom: 'Hicham', raison_sociale: 'Atlas Conseil', cree_le: jour('2026-09-06') },
    { ...base, email: 'contact@secunet.ma', role: 'entreprise', nom: 'Tounsi', prenom: 'Imane', raison_sociale: 'SecuNet', cree_le: jour('2026-09-06') },
    { ...base, email: 'contact@pixelstudio.ma', role: 'entreprise', nom: 'Kettani', prenom: 'Yasmine', raison_sociale: 'Pixel Studio', cree_le: jour('2026-09-07') },
    { ...base, email: 'alaoui@stagehub.ma', role: 'encadrant', nom: 'Alaoui', prenom: 'Karim', cree_le: jour('2026-09-02') },
    { ...base, email: 'bennani@stagehub.ma', role: 'encadrant', nom: 'Bennani', prenom: 'Nadia', actif: 0, cree_le: jour('2026-09-02') },
    { ...base, email: 'idrissi@stagehub.ma', role: 'encadrant', nom: 'Idrissi', prenom: 'Omar', cree_le: jour('2026-09-03') },
  ];

  const txUsers = db.transaction((rows) => {
    for (const row of rows) insUser.run(row);
  });
  txUsers(users);

  const id = (email) => db.prepare('SELECT id FROM utilisateurs WHERE email = ?').get(email).id;
  const youssef = id('youssef.benali@stagehub.ma');
  const sara = id('sara.elidrissi@stagehub.ma');
  const adam = id('adam.tazi@stagehub.ma');
  const lina = id('lina.chraibi@stagehub.ma');
  const technord = id('contact@technord.ma');
  const datamaroc = id('contact@datamaroc.ma');
  const atlas = id('contact@atlasconseil.ma');
  const secunet = id('contact@secunet.ma');
  const pixel = id('contact@pixelstudio.ma');
  const alaoui = id('alaoui@stagehub.ma');

  const docs = {
    php: await ecrirePdf('seed-offre-php.pdf', 'TechNord — Développeur web PHP', [
      'Stage de 3 mois à Tanger.',
      'Tu rejoins l\'équipe qui construit les modules internes de gestion.',
    ]),
    flutter: await ecrirePdf('seed-offre-flutter.pdf', 'TechNord — Développeur mobile Flutter', [
      'Stage de 4 mois à Tanger.',
      'Application mobile de suivi des interventions.',
    ]),
    data: await ecrirePdf('seed-offre-data.pdf', 'DataMaroc — Stage data analyst', [
      'Analyse des ventes et tableaux de bord.',
      'Casablanca, 4 mois.',
    ]),
    atlas: await ecrirePdf('seed-offre-atlas.pdf', 'Atlas Conseil — Assistant gestion de projet', [
      'Suivi de projets et rédaction de comptes rendus.',
      'Rabat, 2 mois.',
    ]),
    secu: await ecrirePdf('seed-offre-secu.pdf', 'SecuNet — Stage réseaux et sécurité', [
      'Supervision réseau et tests de sécurité.',
      'Casablanca, 6 mois.',
    ]),
    design: await ecrirePdf('seed-offre-design.pdf', 'Pixel Studio — Designer UI/UX', [
      'Maquettes et prototypes d\'applications mobiles.',
      'Marrakech, 3 mois.',
    ]),
    convY: await ecrirePdf('seed-convention-youssef.pdf', 'Convention de stage — Youssef Benali', [
      'Sujet : développement d\'un module de gestion.',
      'Entreprise d\'accueil : TechNord, Tanger.',
    ]),
    convS: await ecrirePdf('seed-convention-sara.pdf', 'Convention de stage — Sara El Idrissi', [
      'Sujet : tableaux de bord des ventes.',
      'Entreprise d\'accueil : DataMaroc.',
    ]),
    rapS: await ecrirePdf('seed-rapport-sara.pdf', 'Rapport de stage — Sara El Idrissi', [
      'Synthèse des indicateurs de vente et des tableaux de bord livrés.',
    ]),
    convOld: await ecrirePdf('seed-convention-ancien.pdf', 'Convention — stage d\'initiation', [
      'Stage d\'initiation au support technique, été 2025.',
    ]),
    rapOld: await ecrirePdf('seed-rapport-ancien.pdf', 'Rapport — stage d\'initiation', [
      'Bilan du stage de support technique chez SecuNet.',
    ]),
  };

  const insOffre = db.prepare(`
    INSERT INTO offres (entreprise_id, titre, description, missions, competences, duree_mois, ville, document_stocke, document_nom, retiree, cree_le)
    VALUES (@entreprise_id, @titre, @description, @missions, @competences, @duree_mois, @ville, @document_stocke, @document_nom, @retiree, @cree_le)
  `);

  const offres = [
    {
      entreprise_id: technord,
      titre: 'Développeur web PHP',
      description: 'Concevoir et développer des modules d\'une application web de gestion interne.',
      missions: 'Créer des pages et formulaires\nÉcrire des requêtes SQL\nTester et documenter',
      competences: 'PHP, MySQL, HTML/CSS',
      duree_mois: 3,
      ville: 'Tanger',
      document_stocke: docs.php,
      document_nom: 'Presentation_PHP.pdf',
      retiree: 0,
      cree_le: jour('2026-10-01'),
    },
    {
      entreprise_id: technord,
      titre: 'Développeur mobile Flutter',
      description: 'Développer une application mobile de suivi des interventions terrain.',
      missions: 'Construire les écrans Flutter\nBrancher l\'API existante\nTester sur téléphone',
      competences: 'Dart, Flutter, REST',
      duree_mois: 4,
      ville: 'Tanger',
      document_stocke: docs.flutter,
      document_nom: 'Presentation_Flutter.pdf',
      retiree: 0,
      cree_le: jour('2026-10-02'),
    },
    {
      entreprise_id: datamaroc,
      titre: 'Stage data analyst',
      description: 'Analyse de données de vente et création de tableaux de bord.',
      missions: 'Nettoyer les exports de ventes\nConstruire un tableau de bord\nPrésenter les indicateurs',
      competences: 'SQL, Python, visualisation',
      duree_mois: 4,
      ville: 'Casablanca',
      document_stocke: docs.data,
      document_nom: 'Presentation_Data.pdf',
      retiree: 0,
      cree_le: jour('2026-10-03'),
    },
    {
      entreprise_id: atlas,
      titre: 'Assistant gestion de projet',
      description: 'Suivi de projets et rédaction de comptes rendus pour les équipes conseil.',
      missions: 'Tenir le planning\nRédiger les comptes rendus\nRelancer les livrables',
      competences: 'Organisation, rédaction, tableur',
      duree_mois: 2,
      ville: 'Rabat',
      document_stocke: docs.atlas,
      document_nom: 'Presentation_Atlas.pdf',
      retiree: 0,
      cree_le: jour('2026-10-04'),
    },
    {
      entreprise_id: secunet,
      titre: 'Stage réseaux et sécurité',
      description: 'Supervision réseau et participation aux tests de sécurité internes.',
      missions: 'Surveiller les alertes\nDocumenter les incidents\nParticiper aux tests',
      competences: 'Réseaux, Linux, sécurité',
      duree_mois: 6,
      ville: 'Casablanca',
      document_stocke: docs.secu,
      document_nom: 'Presentation_SecuNet.pdf',
      retiree: 0,
      cree_le: jour('2026-10-04'),
    },
    {
      entreprise_id: pixel,
      titre: 'Designer UI/UX',
      description: 'Concevoir les maquettes et prototypes d\'une application mobile.',
      missions: 'Réaliser les parcours\nPrototyper les écrans\nPréparer la passation aux développeurs',
      competences: 'Figma, UX, design d\'interface',
      duree_mois: 3,
      ville: 'Marrakech',
      document_stocke: docs.design,
      document_nom: 'Presentation_Pixel.pdf',
      retiree: 0,
      cree_le: jour('2026-10-05'),
    },
    {
      entreprise_id: secunet,
      titre: 'Support technique',
      description: 'Stage d\'initiation au support des postes et du réseau local.',
      missions: 'Accueillir les demandes\nDiagnostiquer les pannes courantes',
      competences: 'Support, Windows, réseau local',
      duree_mois: 2,
      ville: 'Casablanca',
      document_stocke: null,
      document_nom: null,
      retiree: 1,
      cree_le: jour('2025-05-20'),
    },
  ];
  const txOffres = db.transaction((rows) => { for (const row of rows) insOffre.run(row); });
  txOffres(offres);

  const offreId = (titre) => db.prepare('SELECT id FROM offres WHERE titre = ?').get(titre).id;
  const php = offreId('Développeur web PHP');
  const data = offreId('Stage data analyst');
  const design = offreId('Designer UI/UX');
  const atlasOffre = offreId('Assistant gestion de projet');
  const support = offreId('Support technique');

  const insCand = db.prepare(`
    INSERT INTO candidatures (offre_id, etudiant_id, cv_stocke, cv_nom, lettre, convention_stocke, convention_nom, statut, cree_le)
    VALUES (@offre_id, @etudiant_id, @cv_stocke, @cv_nom, @lettre, @convention_stocke, @convention_nom, @statut, @cree_le)
  `);
  const candidatures = [
    { offre_id: php, etudiant_id: youssef, cv_stocke: cvY, cv_nom: 'CV_Youssef_Benali.pdf', lettre: 'Je souhaite rejoindre TechNord pour développer des modules web et mettre en pratique PHP et MySQL.', convention_stocke: docs.convY, convention_nom: 'Convention_Youssef.pdf', statut: 'acceptee', cree_le: jour('2026-10-12') },
    { offre_id: data, etudiant_id: youssef, cv_stocke: cvY, cv_nom: 'CV_Youssef_Benali.pdf', lettre: 'La data m\'intéresse aussi, notamment la mise en forme des indicateurs.', convention_stocke: null, convention_nom: null, statut: 'en_attente', cree_le: jour('2026-10-14') },
    { offre_id: design, etudiant_id: youssef, cv_stocke: cvY, cv_nom: 'CV_Youssef_Benali.pdf', lettre: 'Je candidate pour découvrir le design d\'interface en complément du développement.', convention_stocke: null, convention_nom: null, statut: 'refusee', cree_le: jour('2026-10-16') },
    { offre_id: data, etudiant_id: sara, cv_stocke: cvS, cv_nom: 'CV_Sara_El_Idrissi.pdf', lettre: 'Je travaille déjà avec SQL et Python et je veux les appliquer à des données de vente réelles.', convention_stocke: docs.convS, convention_nom: 'Convention_Sara.pdf', statut: 'acceptee', cree_le: jour('2026-10-08') },
    { offre_id: php, etudiant_id: adam, cv_stocke: cvA, cv_nom: 'CV_Adam_Tazi.pdf', lettre: 'Mon parcours réseaux me donne envie de comprendre aussi le développement des outils internes.', convention_stocke: null, convention_nom: null, statut: 'en_attente', cree_le: jour('2026-10-18') },
    { offre_id: atlasOffre, etudiant_id: lina, cv_stocke: cvL, cv_nom: 'CV_Lina_Chraibi.pdf', lettre: 'Le suivi de projet correspond à ma filière. Je suis à l\'aise avec les comptes rendus.', convention_stocke: null, convention_nom: null, statut: 'acceptee', cree_le: jour('2026-10-11') },
    { offre_id: support, etudiant_id: youssef, cv_stocke: cvY, cv_nom: 'CV_Youssef_Benali.pdf', lettre: 'Stage d\'initiation réalisé à l\'été 2025.', convention_stocke: docs.convOld, convention_nom: 'Convention_initiation.pdf', statut: 'acceptee', cree_le: jour('2025-06-02') },
  ];
  const txCand = db.transaction((rows) => { for (const row of rows) insCand.run(row); });
  txCand(candidatures);

  const candId = (offre, etudiant) => db.prepare(
    'SELECT id FROM candidatures WHERE offre_id = ? AND etudiant_id = ?'
  ).get(offre, etudiant).id;

  const insStage = db.prepare(`
    INSERT INTO stages (
      candidature_id, etudiant_id, entreprise_id, encadrant_id, offre_id, statut, sujet,
      description_convention, date_debut, date_fin, contact_entreprise,
      note_entreprise, commentaire_entreprise, assiduite, competences_tech, autonomie, communication, qualite,
      note_encadrant, appreciation_encadrant, cree_le
    ) VALUES (
      @candidature_id, @etudiant_id, @entreprise_id, @encadrant_id, @offre_id, @statut, @sujet,
      @description_convention, @date_debut, @date_fin, @contact_entreprise,
      @note_entreprise, @commentaire_entreprise, @assiduite, @competences_tech, @autonomie, @communication, @qualite,
      @note_encadrant, @appreciation_encadrant, @cree_le
    )
  `);

  const stageBase = {
    sujet: '',
    description_convention: '',
    date_debut: null,
    date_fin: null,
    contact_entreprise: '',
    note_entreprise: null,
    commentaire_entreprise: null,
    assiduite: null,
    competences_tech: null,
    autonomie: null,
    communication: null,
    qualite: null,
    note_encadrant: null,
    appreciation_encadrant: null,
  };

  const stages = [
    {
      ...stageBase,
      candidature_id: candId(php, youssef),
      etudiant_id: youssef,
      entreprise_id: technord,
      encadrant_id: alaoui,
      offre_id: php,
      statut: 'en_cours',
      sujet: 'Développement d\'un module de gestion',
      description_convention: 'Réalisation des écrans et des formulaires du module interne.',
      date_debut: '2026-10-15',
      date_fin: '2027-01-15',
      contact_entreprise: 'Mehdi Cherkaoui, contact@technord.ma',
      cree_le: jour('2026-10-12'),
    },
    {
      ...stageBase,
      candidature_id: candId(data, sara),
      etudiant_id: sara,
      entreprise_id: datamaroc,
      encadrant_id: alaoui,
      offre_id: data,
      statut: 'en_cours',
      sujet: 'Tableaux de bord des ventes',
      description_convention: 'Consolidation des ventes et restitution aux équipes.',
      date_debut: '2026-10-06',
      date_fin: '2027-02-06',
      contact_entreprise: 'Salma Berrada, contact@datamaroc.ma',
      note_entreprise: 15,
      commentaire_entreprise: 'Sara est autonome sur le traitement des données et claire à l\'oral.',
      assiduite: 'Très bien',
      competences_tech: 'Très bien',
      autonomie: 'Bien',
      communication: 'Très bien',
      qualite: 'Bien',
      note_encadrant: 16,
      appreciation_encadrant: 'Très bonne progression sur l\'analyse des ventes.',
      cree_le: jour('2026-10-08'),
    },
    {
      ...stageBase,
      candidature_id: candId(atlasOffre, lina),
      etudiant_id: lina,
      entreprise_id: atlas,
      encadrant_id: null,
      offre_id: atlasOffre,
      statut: 'en_cours',
      sujet: 'Suivi des projets conseil',
      description_convention: '',
      date_debut: '2026-10-20',
      date_fin: '2026-12-20',
      contact_entreprise: 'Hicham Amrani',
      cree_le: jour('2026-10-11'),
    },
    {
      ...stageBase,
      candidature_id: candId(support, youssef),
      etudiant_id: youssef,
      entreprise_id: secunet,
      encadrant_id: alaoui,
      offre_id: support,
      statut: 'termine',
      sujet: 'Initiation au support technique',
      description_convention: 'Accueil des demandes internes et diagnostic de premier niveau.',
      date_debut: '2025-06-15',
      date_fin: '2025-08-15',
      contact_entreprise: 'Imane Tounsi',
      note_entreprise: 15,
      commentaire_entreprise: 'Bon investissement et relation correcte avec les utilisateurs.',
      assiduite: 'Bien',
      competences_tech: 'Bien',
      autonomie: 'Bien',
      communication: 'Très bien',
      qualite: 'Bien',
      note_encadrant: 14,
      appreciation_encadrant: 'Stage sérieux, avec une montée en autonomie régulière.',
      cree_le: jour('2025-06-10'),
    },
  ];
  const txStages = db.transaction((rows) => { for (const row of rows) insStage.run(row); });
  txStages(stages);

  const stageId = (offre, etudiant) => db.prepare(
    'SELECT id FROM stages WHERE offre_id = ? AND etudiant_id = ?'
  ).get(offre, etudiant).id;
  const stageY = stageId(php, youssef);
  const stageS = stageId(data, sara);
  const stageOld = stageId(support, youssef);

  const insDoc = db.prepare(`
    INSERT INTO documents (stage_id, type, fichier_stocke, fichier_nom, statut, commentaire, depose_le, traite_le)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  `);
  insDoc.run(stageY, 'convention', docs.convY, 'Convention_Youssef.pdf', 'en_attente', '', jour('2026-10-18'), null);
  insDoc.run(stageS, 'convention', docs.convS, 'Convention_Sara.pdf', 'valide', '', jour('2026-10-09'), jour('2026-10-10'));
  insDoc.run(stageS, 'rapport', docs.rapS, 'Rapport_Sara.pdf', 'valide', '', jour('2026-10-22'), jour('2026-10-23'));
  insDoc.run(stageOld, 'convention', docs.convOld, 'Convention_initiation.pdf', 'valide', '', jour('2025-06-12'), jour('2025-06-14'));
  insDoc.run(stageOld, 'rapport', docs.rapOld, 'Rapport_initiation.pdf', 'valide', '', jour('2025-08-20'), jour('2025-08-22'));

  const insJournal = db.prepare(`
    INSERT INTO journal (stage_id, date_entree, taches, difficultes, feedback, feedback_le, cree_le)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  `);
  insJournal.run(stageY, '2026-10-13', 'Installation de l\'environnement et prise en main du dépôt.', 'Aucune pour le moment.', '', null, jour('2026-10-13'));
  insJournal.run(stageY, '2026-10-20', 'Création des pages d\'authentification.', 'La validation des formulaires m\'a pris plus de temps que prévu.', 'Bon début. Détaille davantage les difficultés rencontrées.', jour('2026-10-20'), jour('2026-10-20'));
  insJournal.run(stageS, '2026-10-10', 'Nettoyage du fichier des ventes du dernier trimestre.', 'Quelques dates au mauvais format.', '', null, jour('2026-10-10'));
  insJournal.run(stageS, '2026-10-21', 'Première version du tableau de bord des ventes par ville.', 'Le filtre par période était incorrect.', 'Le correctif du filtre est le bon réflexe. Ajoute une capture au rapport.', jour('2026-10-22'), jour('2026-10-21'));
  insJournal.run(stageOld, '2025-07-02', 'Prise en charge des demandes d\'accès au réseau interne.', 'La procédure n\'était pas écrite.', '', null, jour('2025-07-02'));

  const insCom = db.prepare(`
    INSERT INTO commentaires (stage_id, auteur_id, contenu, cree_le) VALUES (?, ?, ?, ?)
  `);
  insCom.run(stageY, alaoui, 'Bon début, détaille davantage les difficultés rencontrées.', jour('2026-10-20'));
  insCom.run(stageY, youssef, 'D\'accord, je complète mon journal.', jour('2026-10-21'));
  insCom.run(stageS, alaoui, 'Le rapport est solide. Prépare la soutenance autour de deux indicateurs.', jour('2026-10-24'));

  const insNotif = db.prepare(`
    INSERT INTO notifications (utilisateur_id, titre, message, lien, lu, cree_le) VALUES (?, ?, ?, ?, ?, ?)
  `);
  insNotif.run(youssef, 'Candidature acceptée', 'TechNord a accepté ta candidature pour Développeur web PHP.', '/candidatures', 0, jour('2026-10-12'));
  insNotif.run(youssef, 'Nouveau commentaire', 'Pr. Alaoui a commenté ton suivi.', '/suivi', 0, jour('2026-10-20'));
  insNotif.run(youssef, 'Convention à faire valider', 'Ta convention est en attente de validation.', '/documents', 1, jour('2026-10-18'));
  insNotif.run(technord, 'Nouvelle candidature', 'Adam Tazi a postulé à Développeur web PHP.', '/entreprise/candidatures', 0, jour('2026-10-18'));
  insNotif.run(alaoui, 'Journal mis à jour', 'Youssef Benali a ajouté une entrée de journal.', '/encadrant/stagiaires', 1, jour('2026-10-20'));
}

async function openDatabase() {
  fs.mkdirSync(path.dirname(DB_PATH), { recursive: true });
  const db = new Database(DB_PATH);
  db.pragma('journal_mode = WAL');
  db.pragma('foreign_keys = ON');
  migrate(db);
  const n = db.prepare('SELECT COUNT(*) AS n FROM utilisateurs').get().n;
  if (n === 0) await seed(db);
  return db;
}

module.exports = { openDatabase, DB_PATH };
