-- StageHub : base de données (à importer dans phpMyAdmin)
CREATE DATABASE IF NOT EXISTS stagehub CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE stagehub;

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('ETUDIANT','ENTREPRISE','ENCADRANT','ADMIN') NOT NULL,
    filiere VARCHAR(100) DEFAULT NULL,
    raison_sociale VARCHAR(100) DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS offres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entreprise_id INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    duree_mois INT NOT NULL,
    ville VARCHAR(80) NOT NULL,
    missions TEXT DEFAULT NULL,
    competences VARCHAR(255) DEFAULT NULL,
    statut ENUM('OUVERTE','CLOTUREE') NOT NULL DEFAULT 'OUVERTE',
    date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS candidatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offre_id INT NOT NULL,
    etudiant_id INT NOT NULL,
    cv VARCHAR(255) NOT NULL,
    lettre_motivation TEXT DEFAULT NULL,
    statut ENUM('EN_ATTENTE','ACCEPTEE','REFUSEE') NOT NULL DEFAULT 'EN_ATTENTE',
    date_candidature DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (offre_id, etudiant_id),
    FOREIGN KEY (offre_id) REFERENCES offres(id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);


-- Un stage est créé automatiquement quand une entreprise accepte une candidature
CREATE TABLE IF NOT EXISTS stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidature_id INT NOT NULL UNIQUE,
    encadrant_id INT DEFAULT NULL,
    date_debut DATE DEFAULT NULL,
    date_fin DATE DEFAULT NULL,
    statut ENUM('ACTIF','TERMINE') NOT NULL DEFAULT 'ACTIF',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidature_id) REFERENCES candidatures(id) ON DELETE CASCADE,
    FOREIGN KEY (encadrant_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS conventions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidature_id INT NOT NULL UNIQUE,
    sujet VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    contact_entreprise VARCHAR(100) NOT NULL,
    fichier VARCHAR(255) DEFAULT NULL,
    commentaire TEXT DEFAULT NULL,
    statut ENUM('EN_COURS','VALIDEE','REFUSEE') NOT NULL DEFAULT 'EN_COURS',
    FOREIGN KEY (candidature_id) REFERENCES candidatures(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rapports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_id INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    date_depot DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT DEFAULT NULL,
    statut ENUM('DEPOSE','VALIDE','REJETE') NOT NULL DEFAULT 'DEPOSE',
    FOREIGN KEY (stage_id) REFERENCES stages(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS entrees_journal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_id INT NOT NULL,
    date_entree DATE NOT NULL,
    taches_realisees TEXT NOT NULL,
    difficultes TEXT DEFAULT NULL,
    feedback_encadrant TEXT DEFAULT NULL,
    FOREIGN KEY (stage_id) REFERENCES stages(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS commentaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_id INT NOT NULL,
    auteur_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    contenu TEXT NOT NULL,
    date_commentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stage_id) REFERENCES stages(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES commentaires(id) ON DELETE SET NULL
);

-- Une évaluation par type (ENTREPRISE ou ENCADRANT) et par stage
CREATE TABLE IF NOT EXISTS evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_id INT NOT NULL,
    auteur_id INT DEFAULT NULL,
    type ENUM('ENTREPRISE','ENCADRANT') NOT NULL,
    note DECIMAL(4,2) NOT NULL,
    commentaire TEXT DEFAULT NULL,
    UNIQUE (stage_id, type),
    FOREIGN KEY (stage_id) REFERENCES stages(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);
