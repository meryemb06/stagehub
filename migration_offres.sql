-- À exécuter UNE SEULE FOIS dans phpMyAdmin (base stagehub, onglet SQL)
-- si vous avez déjà importé l'ancienne version de database.sql et voulez garder vos données.
USE stagehub;
ALTER TABLE offres
    ADD COLUMN missions TEXT DEFAULT NULL AFTER ville,
    ADD COLUMN competences VARCHAR(255) DEFAULT NULL AFTER missions;
