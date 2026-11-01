PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS utilisateurs (
  id INTEGER PRIMARY KEY,
  email TEXT NOT NULL UNIQUE,
  mot_de_passe TEXT NOT NULL,
  role TEXT NOT NULL CHECK (role IN ('etudiant', 'entreprise', 'encadrant', 'admin')),
  nom TEXT NOT NULL,
  prenom TEXT NOT NULL DEFAULT '',
  filiere TEXT NOT NULL DEFAULT '',
  raison_sociale TEXT NOT NULL DEFAULT '',
  cv_stocke TEXT,
  cv_nom TEXT,
  actif INTEGER NOT NULL DEFAULT 1,
  cree_le TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS competences (
  id INTEGER PRIMARY KEY,
  libelle TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS utilisateur_competences (
  utilisateur_id INTEGER NOT NULL REFERENCES utilisateurs(id) ON DELETE CASCADE,
  competence_id INTEGER NOT NULL REFERENCES competences(id) ON DELETE CASCADE,
  PRIMARY KEY (utilisateur_id, competence_id)
);

CREATE TABLE IF NOT EXISTS offres (
  id INTEGER PRIMARY KEY,
  entreprise_id INTEGER NOT NULL REFERENCES utilisateurs(id),
  titre TEXT NOT NULL,
  description TEXT NOT NULL,
  missions TEXT NOT NULL DEFAULT '',
  duree_mois INTEGER NOT NULL,
  ville TEXT NOT NULL,
  document_stocke TEXT,
  document_nom TEXT,
  retiree INTEGER NOT NULL DEFAULT 0,
  cree_le TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS offre_competences (
  offre_id INTEGER NOT NULL REFERENCES offres(id) ON DELETE CASCADE,
  competence_id INTEGER NOT NULL REFERENCES competences(id) ON DELETE CASCADE,
  PRIMARY KEY (offre_id, competence_id)
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
  cree_le TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS documents (
  id INTEGER PRIMARY KEY,
  stage_id INTEGER NOT NULL REFERENCES stages(id) ON DELETE CASCADE,
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
  stage_id INTEGER NOT NULL REFERENCES stages(id) ON DELETE CASCADE,
  date_entree TEXT NOT NULL,
  taches TEXT NOT NULL,
  difficultes TEXT NOT NULL DEFAULT '',
  feedback TEXT NOT NULL DEFAULT '',
  feedback_le TEXT,
  cree_le TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS commentaires (
  id INTEGER PRIMARY KEY,
  stage_id INTEGER NOT NULL REFERENCES stages(id) ON DELETE CASCADE,
  auteur_id INTEGER NOT NULL REFERENCES utilisateurs(id),
  contenu TEXT NOT NULL,
  cree_le TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS evaluations (
  id INTEGER PRIMARY KEY,
  stage_id INTEGER NOT NULL REFERENCES stages(id) ON DELETE CASCADE,
  auteur_id INTEGER NOT NULL REFERENCES utilisateurs(id),
  type TEXT NOT NULL CHECK (type IN ('entreprise', 'encadrant')),
  note REAL,
  commentaire TEXT NOT NULL DEFAULT '',
  assiduite TEXT,
  competences_tech TEXT,
  autonomie TEXT,
  communication TEXT,
  qualite TEXT,
  cree_le TEXT NOT NULL,
  UNIQUE (stage_id, type)
);

CREATE TABLE IF NOT EXISTS notifications (
  id INTEGER PRIMARY KEY,
  utilisateur_id INTEGER NOT NULL REFERENCES utilisateurs(id) ON DELETE CASCADE,
  titre TEXT NOT NULL,
  message TEXT NOT NULL,
  lien TEXT NOT NULL DEFAULT '',
  lu INTEGER NOT NULL DEFAULT 0,
  cree_le TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS reinitialisations (
  id INTEGER PRIMARY KEY,
  utilisateur_id INTEGER NOT NULL REFERENCES utilisateurs(id) ON DELETE CASCADE,
  demande_le TEXT NOT NULL,
  traite INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_offres_entreprise ON offres(entreprise_id);
CREATE INDEX IF NOT EXISTS idx_cand_etudiant ON candidatures(etudiant_id);
CREATE INDEX IF NOT EXISTS idx_stages_enc ON stages(encadrant_id);
CREATE INDEX IF NOT EXISTS idx_docs_stage ON documents(stage_id);
CREATE INDEX IF NOT EXISTS idx_journal_stage ON journal(stage_id);
CREATE INDEX IF NOT EXISTS idx_eval_stage ON evaluations(stage_id);
