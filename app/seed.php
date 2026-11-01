<?php

function seed(PDO $pdo): void
{
    $hash = password_hash(DEMO_PASSWORD, PASSWORD_DEFAULT);
    $jour = static function (string $iso): string {
        return $iso . 'T12:00:00+00:00';
    };
    $ins = $pdo->prepare(
        'INSERT INTO utilisateurs (email, mot_de_passe, role, nom, prenom, filiere, raison_sociale, cv_stocke, cv_nom, actif, cree_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $cv = [
        'youssef' => ecrire_pdf('seed-cv-youssef.pdf', 'CV — Youssef Benali', ['Étudiant en génie informatique.', 'Compétences : PHP, MySQL, HTML/CSS.']),
        'sara' => ecrire_pdf('seed-cv-sara.pdf', 'CV — Sara El Idrissi', ['Étudiante en génie informatique.', 'Compétences : Python, SQL.']),
        'adam' => ecrire_pdf('seed-cv-adam.pdf', 'CV — Adam Tazi', ['Étudiant en réseaux et systèmes.', 'Compétences : Linux, routage.']),
        'lina' => ecrire_pdf('seed-cv-lina.pdf', 'CV — Lina Chraibi', ['Étudiante en gestion de projet.', 'Compétences : planning, comptes rendus.']),
    ];
    $users = [
        ['admin@stagehub.ma', 'admin', 'Administrateur', '', '', '', null, null, 1, '2026-09-01'],
        ['youssef.benali@stagehub.ma', 'etudiant', 'Benali', 'Youssef', 'Génie informatique', '', $cv['youssef'], 'CV_Youssef_Benali.pdf', 1, '2026-09-12'],
        ['sara.elidrissi@stagehub.ma', 'etudiant', 'El Idrissi', 'Sara', 'Génie informatique', '', $cv['sara'], 'CV_Sara_El_Idrissi.pdf', 1, '2026-09-12'],
        ['adam.tazi@stagehub.ma', 'etudiant', 'Tazi', 'Adam', 'Réseaux et systèmes', '', $cv['adam'], 'CV_Adam_Tazi.pdf', 1, '2026-09-18'],
        ['lina.chraibi@stagehub.ma', 'etudiant', 'Chraibi', 'Lina', 'Gestion de projet', '', $cv['lina'], 'CV_Lina_Chraibi.pdf', 1, '2026-09-20'],
        ['contact@technord.ma', 'entreprise', 'Cherkaoui', 'Mehdi', '', 'TechNord', null, null, 1, '2026-09-05'],
        ['contact@datamaroc.ma', 'entreprise', 'Berrada', 'Salma', '', 'DataMaroc', null, null, 1, '2026-09-05'],
        ['contact@atlasconseil.ma', 'entreprise', 'Amrani', 'Hicham', '', 'Atlas Conseil', null, null, 1, '2026-09-06'],
        ['contact@secunet.ma', 'entreprise', 'Tounsi', 'Imane', '', 'SecuNet', null, null, 1, '2026-09-06'],
        ['contact@pixelstudio.ma', 'entreprise', 'Kettani', 'Yasmine', '', 'Pixel Studio', null, null, 1, '2026-09-07'],
        ['alaoui@stagehub.ma', 'encadrant', 'Alaoui', 'Karim', '', '', null, null, 1, '2026-09-02'],
        ['bennani@stagehub.ma', 'encadrant', 'Bennani', 'Nadia', '', '', null, null, 0, '2026-09-02'],
        ['idrissi@stagehub.ma', 'encadrant', 'Idrissi', 'Omar', '', '', null, null, 1, '2026-09-03'],
    ];
    foreach ($users as $u) {
        $ins->execute([$u[0], $hash, $u[1], $u[2], $u[3], $u[4], $u[5], $u[6], $u[7], $u[8], $jour($u[9])]);
    }
    $id = static function (string $email): int {
        return (int) one('SELECT id FROM utilisateurs WHERE email = ?', [$email])['id'];
    };
    $youssef = $id('youssef.benali@stagehub.ma');
    $sara = $id('sara.elidrissi@stagehub.ma');
    $adam = $id('adam.tazi@stagehub.ma');
    $lina = $id('lina.chraibi@stagehub.ma');
    $technord = $id('contact@technord.ma');
    $datamaroc = $id('contact@datamaroc.ma');
    $atlas = $id('contact@atlasconseil.ma');
    $secunet = $id('contact@secunet.ma');
    $pixel = $id('contact@pixelstudio.ma');
    $alaoui = $id('alaoui@stagehub.ma');

    sync_competences('utilisateur_competences', 'utilisateur_id', $youssef, 'PHP, MySQL, HTML/CSS');
    sync_competences('utilisateur_competences', 'utilisateur_id', $sara, 'Python, SQL, visualisation');
    sync_competences('utilisateur_competences', 'utilisateur_id', $adam, 'Linux, réseaux');
    sync_competences('utilisateur_competences', 'utilisateur_id', $lina, 'Organisation, rédaction');

    $docs = [
        'php' => ecrire_pdf('seed-offre-php.pdf', 'TechNord — Développeur web PHP', ['Stage de 3 mois à Tanger.', 'Modules internes de gestion.']),
        'flutter' => ecrire_pdf('seed-offre-flutter.pdf', 'TechNord — Développeur mobile Flutter', ['Stage de 4 mois à Tanger.']),
        'data' => ecrire_pdf('seed-offre-data.pdf', 'DataMaroc — Stage data analyst', ['Tableaux de bord des ventes, Casablanca.']),
        'atlas' => ecrire_pdf('seed-offre-atlas.pdf', 'Atlas Conseil — Assistant gestion de projet', ['Suivi de projets, Rabat.']),
        'secu' => ecrire_pdf('seed-offre-secu.pdf', 'SecuNet — Stage réseaux et sécurité', ['Supervision réseau, Casablanca.']),
        'design' => ecrire_pdf('seed-offre-design.pdf', 'Pixel Studio — Designer UI/UX', ['Maquettes d\'application, Marrakech.']),
        'convY' => ecrire_pdf('seed-convention-youssef.pdf', 'Convention — Youssef Benali', ['Sujet : module de gestion.', 'Entreprise : TechNord, Tanger.']),
        'convS' => ecrire_pdf('seed-convention-sara.pdf', 'Convention — Sara El Idrissi', ['Sujet : tableaux de bord des ventes.']),
        'rapS' => ecrire_pdf('seed-rapport-sara.pdf', 'Rapport — Sara El Idrissi', ['Synthèse des indicateurs livrés.']),
        'convOld' => ecrire_pdf('seed-convention-ancien.pdf', 'Convention — stage d\'initiation', ['Support technique, été 2025.']),
        'rapOld' => ecrire_pdf('seed-rapport-ancien.pdf', 'Rapport — stage d\'initiation', ['Bilan du support technique chez SecuNet.']),
        'convA' => ecrire_pdf('seed-convention-adam.pdf', 'Convention — Adam Tazi', ['Projet de candidature, en attente de décision.']),
        'convL' => ecrire_pdf('seed-convention-lina.pdf', 'Convention — Lina Chraibi', ['Suivi des projets conseil.']),
    ];

    $offres = [
        [$technord, 'Développeur web PHP', 'Concevoir et développer des modules d\'une application web de gestion interne.', "Créer des pages et formulaires\nÉcrire des requêtes SQL\nTester et documenter", 3, 'Tanger', $docs['php'], 'Presentation_PHP.pdf', 0, '2026-10-01', 'PHP, MySQL, HTML/CSS'],
        [$technord, 'Développeur mobile Flutter', 'Développer une application mobile de suivi des interventions terrain.', "Construire les écrans\nBrancher l'API\nTester sur téléphone", 4, 'Tanger', $docs['flutter'], 'Presentation_Flutter.pdf', 0, '2026-10-02', 'Flutter, API'],
        [$datamaroc, 'Stage data analyst', 'Analyse de données de vente et création de tableaux de bord.', "Nettoyer les exports\nConstruire un tableau de bord\nPrésenter les indicateurs", 4, 'Casablanca', $docs['data'], 'Presentation_Data.pdf', 0, '2026-10-03', 'SQL, Python, visualisation'],
        [$atlas, 'Assistant gestion de projet', 'Suivi de projets et rédaction de comptes rendus pour les équipes conseil.', "Tenir le planning\nRédiger les comptes rendus", 2, 'Rabat', $docs['atlas'], 'Presentation_Atlas.pdf', 0, '2026-10-04', 'Organisation, rédaction'],
        [$secunet, 'Stage réseaux et sécurité', 'Supervision réseau et participation aux tests de sécurité internes.', "Surveiller les alertes\nDocumenter les incidents", 6, 'Casablanca', $docs['secu'], 'Presentation_SecuNet.pdf', 0, '2026-10-04', 'Réseaux, Linux, sécurité'],
        [$pixel, 'Designer UI/UX', 'Concevoir les maquettes et prototypes d\'une application mobile.', "Réaliser les parcours\nPrototyper les écrans", 3, 'Marrakech', $docs['design'], 'Presentation_Pixel.pdf', 0, '2026-10-05', 'Design, prototypage'],
        [$secunet, 'Support technique', 'Stage d\'initiation au support des postes et du réseau local.', "Accueillir les demandes\nDiagnostiquer les pannes", 2, 'Casablanca', null, null, 1, '2025-05-20', 'Support, réseau local'],
    ];
    $insOffre = $pdo->prepare(
        'INSERT INTO offres (entreprise_id, titre, description, missions, duree_mois, ville, document_stocke, document_nom, retiree, cree_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($offres as $o) {
        $insOffre->execute([$o[0], $o[1], $o[2], $o[3], $o[4], $o[5], $o[6], $o[7], $o[8], $jour($o[9])]);
        sync_competences('offre_competences', 'offre_id', (int) $pdo->lastInsertId(), $o[10]);
    }
    $offre = static function (string $titre): int {
        return (int) one('SELECT id FROM offres WHERE titre = ?', [$titre])['id'];
    };
    $php = $offre('Développeur web PHP');
    $data = $offre('Stage data analyst');
    $design = $offre('Designer UI/UX');
    $atlasOffre = $offre('Assistant gestion de projet');
    $support = $offre('Support technique');

    $insCand = $pdo->prepare(
        'INSERT INTO candidatures (offre_id, etudiant_id, cv_stocke, cv_nom, lettre, convention_stocke, convention_nom, statut, cree_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $cands = [
        [$php, $youssef, $cv['youssef'], 'CV_Youssef_Benali.pdf', 'Je souhaite rejoindre TechNord pour développer des modules web et mettre en pratique PHP et MySQL.', $docs['convY'], 'Convention_Youssef.pdf', 'acceptee', '2026-10-12'],
        [$data, $youssef, $cv['youssef'], 'CV_Youssef_Benali.pdf', 'La data m\'intéresse aussi, notamment la mise en forme des indicateurs.', null, null, 'en_attente', '2026-10-14'],
        [$design, $youssef, $cv['youssef'], 'CV_Youssef_Benali.pdf', 'Je candidate pour découvrir le design d\'interface en complément du développement.', null, null, 'refusee', '2026-10-16'],
        [$data, $sara, $cv['sara'], 'CV_Sara_El_Idrissi.pdf', 'Je travaille déjà avec SQL et Python et je veux les appliquer à des données de vente réelles.', $docs['convS'], 'Convention_Sara.pdf', 'acceptee', '2026-10-08'],
        [$php, $adam, $cv['adam'], 'CV_Adam_Tazi.pdf', 'Mon parcours réseaux me donne envie de comprendre aussi le développement des outils internes.', $docs['convA'], 'Convention_Adam.pdf', 'en_attente', '2026-10-18'],
        [$atlasOffre, $lina, $cv['lina'], 'CV_Lina_Chraibi.pdf', 'Le suivi de projet correspond à ma filière. Je suis à l\'aise avec les comptes rendus.', $docs['convL'], 'Convention_Lina.pdf', 'acceptee', '2026-10-11'],
        [$support, $youssef, $cv['youssef'], 'CV_Youssef_Benali.pdf', 'Stage d\'initiation réalisé à l\'été 2025.', $docs['convOld'], 'Convention_initiation.pdf', 'acceptee', '2025-06-02'],
    ];
    foreach ($cands as $c) {
        $insCand->execute([$c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $c[6], $c[7], $jour($c[8])]);
    }
    $cand = static function (int $offreId, int $etudiantId): int {
        return (int) one('SELECT id FROM candidatures WHERE offre_id = ? AND etudiant_id = ?', [$offreId, $etudiantId])['id'];
    };
    $insStage = $pdo->prepare(
        'INSERT INTO stages (candidature_id, etudiant_id, entreprise_id, encadrant_id, offre_id, statut, sujet, description_convention, date_debut, date_fin, contact_entreprise, cree_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stages = [
        [$cand($php, $youssef), $youssef, $technord, $alaoui, $php, 'en_cours', 'Développement d\'un module de gestion', 'Réalisation des écrans et des formulaires du module interne.', '2026-10-15', '2027-01-15', 'Mehdi Cherkaoui, contact@technord.ma', '2026-10-12'],
        [$cand($data, $sara), $sara, $datamaroc, $alaoui, $data, 'en_cours', 'Tableaux de bord des ventes', 'Consolidation des ventes et restitution aux équipes.', '2026-10-06', '2027-02-06', 'Salma Berrada, contact@datamaroc.ma', '2026-10-08'],
        [$cand($atlasOffre, $lina), $lina, $atlas, null, $atlasOffre, 'en_cours', 'Suivi des projets conseil', '', '2026-10-20', '2026-12-20', 'Hicham Amrani', '2026-10-11'],
        [$cand($support, $youssef), $youssef, $secunet, $alaoui, $support, 'termine', 'Initiation au support technique', 'Accueil des demandes et diagnostic des pannes courantes.', '2025-06-15', '2025-08-15', 'Imane Tounsi', '2025-06-02'],
    ];
    foreach ($stages as $s) {
        $insStage->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], $jour($s[11])]);
    }
    $stage = static function (int $etudiantId, string $statut): int {
        return (int) one('SELECT id FROM stages WHERE etudiant_id = ? AND statut = ? ORDER BY id DESC', [$etudiantId, $statut])['id'];
    };
    $stageY = $stage($youssef, 'en_cours');
    $stageS = $stage($sara, 'en_cours');
    $stageOld = $stage($youssef, 'termine');

    $insDoc = $pdo->prepare(
        'INSERT INTO documents (stage_id, type, fichier_stocke, fichier_nom, statut, commentaire, depose_le, traite_le) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insDoc->execute([$stageY, 'convention', $docs['convY'], 'Convention_Youssef.pdf', 'en_attente', '', $jour('2026-10-15'), null]);
    $insDoc->execute([$stageS, 'convention', $docs['convS'], 'Convention_Sara.pdf', 'valide', '', $jour('2026-10-08'), $jour('2026-10-10')]);
    $insDoc->execute([$stageS, 'rapport', $docs['rapS'], 'Rapport_Sara.pdf', 'valide', '', $jour('2026-10-20'), $jour('2026-10-22')]);
    $insDoc->execute([$stageOld, 'convention', $docs['convOld'], 'Convention_initiation.pdf', 'valide', '', $jour('2025-06-15'), $jour('2025-06-20')]);
    $insDoc->execute([$stageOld, 'rapport', $docs['rapOld'], 'Rapport_initiation.pdf', 'valide', '', $jour('2025-08-20'), $jour('2025-08-25')]);

    run(
        'INSERT INTO journal (stage_id, date_entree, taches, difficultes, feedback, feedback_le, cree_le) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$stageY, '2026-10-20', 'Prise en main du dépôt et lecture du module existant.', 'Le schéma SQL n\'était pas documenté.', 'Bon départ. Note le schéma au fur et à mesure.', $jour('2026-10-21'), $jour('2026-10-20')]
    );
    run(
        'INSERT INTO commentaires (stage_id, auteur_id, contenu, cree_le) VALUES (?, ?, ?, ?)',
        [$stageY, $alaoui, 'Pense à dater chaque entrée du journal, même les journées courtes.', $jour('2026-10-21')]
    );

    $insEval = $pdo->prepare(
        'INSERT INTO evaluations (stage_id, auteur_id, type, note, commentaire, assiduite, competences_tech, autonomie, communication, qualite, cree_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insEval->execute([$stageS, $datamaroc, 'entreprise', 15, 'Sara est autonome sur le traitement des données et claire à l\'oral.', 'Très bien', 'Très bien', 'Bien', 'Très bien', 'Bien', $jour('2026-10-25')]);
    $insEval->execute([$stageS, $alaoui, 'encadrant', 16, 'Très bonne progression sur l\'analyse des ventes.', null, null, null, null, null, $jour('2026-10-26')]);
    $insEval->execute([$stageOld, $secunet, 'entreprise', 14, 'Stage d\'initiation sérieux, bonne écoute des utilisateurs.', 'Bien', 'Bien', 'Bien', 'Très bien', 'Bien', $jour('2025-08-28')]);
    $insEval->execute([$stageOld, $alaoui, 'encadrant', 15, 'Bilan clair et régularité dans le journal.', null, null, null, null, null, $jour('2025-08-29')]);
}
