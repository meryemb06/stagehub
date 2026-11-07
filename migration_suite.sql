-- À exécuter une fois si la base stagehub existe déjà (phpMyAdmin, onglet SQL).
USE stagehub;

ALTER TABLE stages
    ADD COLUMN IF NOT EXISTS date_debut DATE DEFAULT NULL AFTER encadrant_id,
    ADD COLUMN IF NOT EXISTS date_fin DATE DEFAULT NULL AFTER date_debut;

ALTER TABLE conventions
    ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL AFTER sujet,
    MODIFY fichier VARCHAR(255) DEFAULT NULL;

ALTER TABLE evaluations
    ADD COLUMN IF NOT EXISTS auteur_id INT DEFAULT NULL AFTER stage_id;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);
