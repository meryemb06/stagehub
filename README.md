# StageHub

Application de gestion des stages : étudiants, entreprises partenaires, encadrants et administrateur.

PHP 8 et SQLite (PDO). Les fichiers déposés (CV, convention, rapport, présentation) restent hors du web et ne sont servis qu’après contrôle du rôle.

## Lancer

PHP 8.3 avec les extensions `pdo_sqlite`, `mbstring` et `zip`.

```bash
php -S localhost:3000 -t public public/router.php
```

Ou `npm start`. La base `storage/stagehub.sqlite` est créée et remplie au premier lancement.

Mot de passe des comptes de démonstration : `Stagehub1`.

| Rôle | Courriel |
| --- | --- |
| Administrateur | admin@stagehub.ma |
| Étudiant | youssef.benali@stagehub.ma |
| Entreprise | contact@technord.ma |
| Encadrant | alaoui@stagehub.ma |

L’inscription publique est ouverte aux étudiants et aux entreprises. Les encadrants sont créés par l’administrateur, qui les affecte ensuite à chaque stage.

## Parcours

```bash
php tests/parcours.php
```
