<?php

require_once __DIR__ . '/pdf.php';
require_once __DIR__ . '/xlsx.php';

function route_list(): array
{
    return [
        ['GET', '#^/$#', 'page_connexion'],
        ['POST', '#^/connexion$#', 'action_connexion'],
        ['GET', '#^/inscription$#', 'page_inscription'],
        ['POST', '#^/inscription$#', 'action_inscription'],
        ['GET', '#^/mot-de-passe-oublie$#', 'page_oubli'],
        ['POST', '#^/mot-de-passe-oublie$#', 'action_oubli'],
        ['POST', '#^/deconnexion$#', 'action_deconnexion'],

        ['GET', '#^/offres$#', 'page_offres'],
        ['GET', '#^/offres/(\d+)$#', 'page_offre'],
        ['GET', '#^/offres/(\d+)/postuler$#', 'page_postuler'],
        ['POST', '#^/offres/(\d+)/postuler$#', 'action_postuler'],
        ['GET', '#^/candidatures$#', 'page_candidatures'],
        ['GET', '#^/documents$#', 'page_documents'],
        ['POST', '#^/documents/convention$#', 'action_convention'],
        ['POST', '#^/documents/rapport$#', 'action_rapport'],
        ['GET', '#^/suivi$#', 'page_suivi'],
        ['POST', '#^/suivi/journal$#', 'action_journal'],
        ['POST', '#^/suivi/commentaire$#', 'action_reponse'],
        ['GET', '#^/historique$#', 'page_historique'],
        ['GET', '#^/profil$#', 'page_profil'],
        ['POST', '#^/profil$#', 'action_profil'],
        ['GET', '#^/notifications$#', 'page_notifications'],

        ['GET', '#^/entreprise/offres$#', 'page_mes_offres'],
        ['GET', '#^/entreprise/offres/nouvelle$#', 'page_form_offre'],
        ['POST', '#^/entreprise/offres$#', 'action_creer_offre'],
        ['GET', '#^/entreprise/offres/(\d+)/modifier$#', 'page_form_offre'],
        ['POST', '#^/entreprise/offres/(\d+)$#', 'action_modifier_offre'],
        ['POST', '#^/entreprise/offres/(\d+)/supprimer$#', 'action_supprimer_offre'],
        ['GET', '#^/entreprise/candidatures$#', 'page_recues'],
        ['GET', '#^/entreprise/candidatures/(\d+)$#', 'page_dossier'],
        ['POST', '#^/entreprise/candidatures/(\d+)/decision$#', 'action_decision'],
        ['GET', '#^/entreprise/evaluations$#', 'page_evaluations'],
        ['GET', '#^/entreprise/stages/(\d+)/evaluation$#', 'page_evaluer'],
        ['POST', '#^/entreprise/stages/(\d+)/evaluation$#', 'action_evaluer'],

        ['GET', '#^/encadrant/stagiaires$#', 'page_stagiaires'],
        ['GET', '#^/encadrant/stages/(\d+)$#', 'page_suivi_enc'],
        ['POST', '#^/encadrant/journal/(\d+)/feedback$#', 'action_feedback'],
        ['POST', '#^/encadrant/stages/(\d+)/commentaire$#', 'action_commentaire'],
        ['POST', '#^/encadrant/stages/(\d+)/note$#', 'action_note'],
        ['POST', '#^/encadrant/stages/(\d+)/cloturer$#', 'action_cloture'],
        ['GET', '#^/encadrant/documents$#', 'page_docs_enc'],
        ['POST', '#^/encadrant/documents/(\d+)/valider$#', 'action_valider'],
        ['POST', '#^/encadrant/documents/(\d+)/rejeter$#', 'action_rejeter'],

        ['GET', '#^/admin/comptes$#', 'page_comptes'],
        ['POST', '#^/admin/comptes/reinitialiser$#', 'action_reset'],
        ['POST', '#^/admin/comptes/basculer$#', 'action_basculer'],
        ['POST', '#^/admin/encadrants$#', 'action_creer_encadrant'],
        ['GET', '#^/admin/affectations$#', 'page_affectations'],
        ['POST', '#^/admin/affectations$#', 'action_affecter'],
        ['GET', '#^/admin/statistiques$#', 'page_stats'],
        ['GET', '#^/admin/statistiques/pdf$#', 'action_stats_pdf'],
        ['GET', '#^/admin/statistiques/excel$#', 'action_stats_excel'],

        ['GET', '#^/fichiers/offre/(\d+)$#', 'fichier_offre'],
        ['GET', '#^/fichiers/candidature/(\d+)/(cv|convention)$#', 'fichier_candidature'],
        ['GET', '#^/fichiers/document/(\d+)$#', 'fichier_document'],
        ['GET', '#^/fichiers/cv/(\d+)$#', 'fichier_cv'],
    ];
}

function email_ok(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($email) <= 160;
}

function page_connexion(): void
{
    if ($u = user()) {
        redirect(home_for($u['role']));
    }
    $emails = [
        'youssef.benali@stagehub.ma', 'sara.elidrissi@stagehub.ma', 'adam.tazi@stagehub.ma', 'lina.chraibi@stagehub.ma',
        'contact@technord.ma', 'contact@datamaroc.ma', 'contact@atlasconseil.ma', 'contact@secunet.ma', 'contact@pixelstudio.ma',
        'alaoui@stagehub.ma', 'idrissi@stagehub.ma', 'admin@stagehub.ma',
    ];
    $in = implode(',', array_fill(0, count($emails), '?'));
    view('connexion', [
        'titre' => 'Connexion',
        'authLayout' => true,
        'demos' => q("SELECT email, role, nom, prenom, raison_sociale FROM utilisateurs WHERE actif = 1 AND email IN ($in) ORDER BY CASE role WHEN 'etudiant' THEN 1 WHEN 'entreprise' THEN 2 WHEN 'encadrant' THEN 3 ELSE 4 END, nom", $emails),
    ]);
}

function action_connexion(): void
{
    check_csrf();
    $email = strtolower(str_clean($_POST['email'] ?? '', 160));
    $password = (string) ($_POST['mot_de_passe'] ?? '');
    $row = one('SELECT * FROM utilisateurs WHERE email = ?', [$email]);
    if (!$row || !password_verify($password, $row['mot_de_passe'])) {
        fail('E-mail ou mot de passe incorrect.', '/');
    }
    if (!(int) $row['actif']) {
        fail('Ce compte est désactivé. Contacte l\'administrateur.', '/');
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
    redirect(home_for($row['role']));
}

function page_inscription(): void
{
    if ($u = user()) {
        redirect(home_for($u['role']));
    }
    view('inscription', ['titre' => 'Inscription', 'authLayout' => true]);
}

function action_inscription(): void
{
    check_csrf();
    $role = $_POST['role'] ?? '';
    $nom = str_clean($_POST['nom'] ?? '', 80);
    $prenom = str_clean($_POST['prenom'] ?? '', 80);
    $email = strtolower(str_clean($_POST['email'] ?? '', 160));
    $password = (string) ($_POST['mot_de_passe'] ?? '');
    $filiere = str_clean($_POST['filiere'] ?? '', 120);
    $raison = str_clean($_POST['raison_sociale'] ?? '', 140);
    if (!in_array($role, ['etudiant', 'entreprise'], true)) {
        fail('Choisis un type de compte.', '/inscription');
    }
    if ($nom === '' || $prenom === '') {
        fail('Indique le nom et le prénom.', '/inscription');
    }
    if (!email_ok($email)) {
        fail('Adresse e-mail invalide.', '/inscription');
    }
    if (mb_strlen($password) < 8) {
        fail('Le mot de passe doit contenir au moins 8 caractères.', '/inscription');
    }
    if ($role === 'etudiant' && $filiere === '') {
        fail('Indique ta filière.', '/inscription');
    }
    if ($role === 'entreprise' && $raison === '') {
        fail('Indique la raison sociale de l\'entreprise.', '/inscription');
    }
    if (one('SELECT id FROM utilisateurs WHERE email = ?', [$email])) {
        fail('Un compte existe déjà avec cet e-mail.', '/inscription');
    }
    run(
        'INSERT INTO utilisateurs (email, mot_de_passe, role, nom, prenom, filiere, raison_sociale, actif, cree_le) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)',
        [$email, password_hash($password, PASSWORD_DEFAULT), $role, $nom, $prenom, $role === 'etudiant' ? $filiere : '', $role === 'entreprise' ? $raison : '', now_iso()]
    );
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) db()->lastInsertId();
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
    flash('ok', 'Compte créé. Bienvenue sur StageHub.');
    redirect(home_for($role));
}

function page_oubli(): void
{
    if ($u = user()) {
        redirect(home_for($u['role']));
    }
    view('oubli', ['titre' => 'Mot de passe oublié', 'authLayout' => true]);
}

function action_oubli(): void
{
    check_csrf();
    $email = strtolower(str_clean($_POST['email'] ?? '', 160));
    $row = one('SELECT id, actif FROM utilisateurs WHERE email = ?', [$email]);
    if ($row && (int) $row['actif'] && !one('SELECT id FROM reinitialisations WHERE utilisateur_id = ? AND traite = 0', [$row['id']])) {
        run('INSERT INTO reinitialisations (utilisateur_id, demande_le, traite) VALUES (?, ?, 0)', [$row['id'], now_iso()]);
    }
    flash('ok', 'Si un compte actif correspond à cet e-mail, l\'administrateur peut maintenant réinitialiser le mot de passe.');
    redirect('/');
}

function action_deconnexion(): void
{
    check_csrf();
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
    redirect('/');
}

function offre_visible(int $id): ?array
{
    return one(
        'SELECT o.*, u.raison_sociale FROM offres o JOIN utilisateurs u ON u.id = o.entreprise_id WHERE o.id = ? AND o.retiree = 0',
        [$id]
    );
}

function page_offres(): void
{
    $u = require_role('etudiant');
    $recherche = str_clean($_GET['recherche'] ?? '', 80);
    $ville = str_clean($_GET['ville'] ?? '', 80);
    $duree = str_clean($_GET['duree'] ?? '', 4);
    $like = '%' . str_replace(['%', '_'], ' ', $recherche) . '%';
    $dureeNum = ctype_digit($duree) ? (int) $duree : 0;
    $sql = 'SELECT o.*, u.raison_sociale,
            (SELECT statut FROM candidatures c WHERE c.offre_id = o.id AND c.etudiant_id = ?) AS deja
         FROM offres o
         JOIN utilisateurs u ON u.id = o.entreprise_id
         WHERE o.retiree = 0';
    $params = [$u['id']];
    if ($recherche !== '') {
        $sql .= ' AND (o.titre LIKE ? OR o.description LIKE ? OR o.ville LIKE ? OR u.raison_sociale LIKE ? OR o.missions LIKE ?
                OR EXISTS (SELECT 1 FROM offre_competences oc JOIN competences c ON c.id = oc.competence_id WHERE oc.offre_id = o.id AND c.libelle LIKE ?))';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    if ($ville !== '') {
        $sql .= ' AND o.ville = ?';
        $params[] = $ville;
    }
    if ($dureeNum > 0) {
        $sql .= ' AND o.duree_mois = ?';
        $params[] = $dureeNum;
    }
    $sql .= ' ORDER BY o.cree_le DESC, o.id DESC';
    $offres = q($sql, $params);
    foreach ($offres as &$offre) {
        $offre['competences'] = competences_de('offre_competences', 'offre_id', (int) $offre['id']);
    }
    unset($offre);
    view('offres', [
        'titre' => 'Offres de stage',
        'active' => 'offres',
        'offres' => $offres,
        'villes' => array_column(q('SELECT DISTINCT ville FROM offres WHERE retiree = 0 ORDER BY ville'), 'ville'),
        'durees' => array_column(q('SELECT DISTINCT duree_mois FROM offres WHERE retiree = 0 ORDER BY duree_mois'), 'duree_mois'),
        'recherche' => $recherche,
        'ville' => $ville,
        'duree' => $duree,
    ]);
}

function page_offre(array $m): void
{
    $u = require_role('etudiant');
    $offre = offre_visible((int) $m[1]);
    if (!$offre) {
        http_response_code(404);
        exit('Offre introuvable.');
    }
    $offre['competences'] = competences_de('offre_competences', 'offre_id', (int) $offre['id']);
    $cand = one('SELECT statut FROM candidatures WHERE offre_id = ? AND etudiant_id = ?', [$offre['id'], $u['id']]);
    $offre['deja'] = $cand['statut'] ?? null;
    view('offre', ['titre' => $offre['titre'], 'active' => 'offres', 'offre' => $offre]);
}

function page_postuler(array $m): void
{
    $u = require_role('etudiant');
    $offre = offre_visible((int) $m[1]);
    if (!$offre) {
        fail('Cette offre n\'est plus disponible.', '/offres');
    }
    if (one('SELECT id FROM candidatures WHERE offre_id = ? AND etudiant_id = ?', [$offre['id'], $u['id']])) {
        fail('Tu as déjà postulé à cette offre.', '/candidatures');
    }
    view('postuler', ['titre' => 'Postuler', 'active' => 'offres', 'offre' => $offre]);
}

function action_postuler(array $m): void
{
    $u = require_role('etudiant');
    check_csrf();
    $id = (int) $m[1];
    $offre = offre_visible($id);
    $retour = '/offres/' . $id . '/postuler';
    if (!$offre) {
        fail('Cette offre n\'est plus disponible.', '/offres');
    }
    if (one('SELECT id FROM candidatures WHERE offre_id = ? AND etudiant_id = ?', [$id, $u['id']])) {
        fail('Tu as déjà postulé à cette offre.', '/candidatures');
    }
    $lettre = trim((string) ($_POST['lettre'] ?? ''));
    if (mb_strlen($lettre) < 20 || mb_strlen($lettre) > 4000) {
        fail('La lettre de motivation doit faire entre 20 et 4000 caractères.', $retour);
    }
    $cv = prendre_fichier('cv', !$u['cv_stocke'], $retour);
    $convention = prendre_fichier('convention', false, $retour);
    if (!$cv && $u['cv_stocke']) {
        $src = chemin_fichier($u['cv_stocke']);
        if ($src) {
            $ext = pathinfo($src, PATHINFO_EXTENSION);
            $nom = bin2hex(random_bytes(16)) . '.' . $ext;
            copy($src, upload_dir() . '/' . $nom);
            $cv = ['stocke' => $nom, 'nom' => $u['cv_nom'] ?: 'cv.' . $ext];
        }
    }
    if (!$cv) {
        fail('Dépose ton CV au format PDF, DOC ou DOCX.', $retour);
    }
    run(
        'INSERT INTO candidatures (offre_id, etudiant_id, cv_stocke, cv_nom, lettre, convention_stocke, convention_nom, statut, cree_le) VALUES (?, ?, ?, ?, ?, ?, ?, \'en_attente\', ?)',
        [$id, $u['id'], $cv['stocke'], $cv['nom'], $lettre, $convention['stocke'] ?? null, $convention['nom'] ?? null, now_iso()]
    );
    if (!$u['cv_stocke']) {
        run('UPDATE utilisateurs SET cv_stocke = ?, cv_nom = ? WHERE id = ?', [$cv['stocke'], $cv['nom'], $u['id']]);
    }
    notifier((int) $offre['entreprise_id'], 'Nouvelle candidature', $u['prenom'] . ' ' . $u['nom'] . ' a postulé à « ' . $offre['titre'] . ' ».', '/entreprise/candidatures?offre=' . $id);
    flash('ok', 'Candidature envoyée.');
    redirect('/candidatures');
}

function page_candidatures(): void
{
    $u = require_role('etudiant');
    $rows = q(
        'SELECT c.*, o.titre, o.ville, o.duree_mois, u.raison_sociale
         FROM candidatures c
         JOIN offres o ON o.id = c.offre_id
         JOIN utilisateurs u ON u.id = o.entreprise_id
         WHERE c.etudiant_id = ?
         ORDER BY c.cree_le DESC',
        [$u['id']]
    );
    view('candidatures', ['titre' => 'Mes candidatures', 'active' => 'candidatures', 'candidatures' => $rows]);
}

function stages_etudiant(int $id, ?string $statut = 'en_cours'): array
{
    $sql = 'SELECT s.*, o.titre, o.ville, u.raison_sociale, enc.nom AS enc_nom
            FROM stages s
            JOIN offres o ON o.id = s.offre_id
            JOIN utilisateurs u ON u.id = s.entreprise_id
            LEFT JOIN utilisateurs enc ON enc.id = s.encadrant_id
            WHERE s.etudiant_id = ?';
    $params = [$id];
    if ($statut) {
        $sql .= ' AND s.statut = ?';
        $params[] = $statut;
    }
    $sql .= ' ORDER BY s.cree_le DESC';
    return q($sql, $params);
}

function choisir_stage(array $stages): ?array
{
    if (!$stages) {
        return null;
    }
    $voulu = (int) ($_GET['stage'] ?? $_POST['stage_id'] ?? 0);
    foreach ($stages as $stage) {
        if ((int) $stage['id'] === $voulu) {
            return $stage;
        }
    }
    return $stages[0];
}

function page_documents(): void
{
    $u = require_role('etudiant');
    $stages = stages_etudiant((int) $u['id']);
    $stage = choisir_stage($stages);
    $docs = [];
    if ($stage) {
        foreach (q('SELECT * FROM documents WHERE stage_id = ?', [$stage['id']]) as $doc) {
            $docs[$doc['type']] = $doc;
        }
    }
    view('documents', ['titre' => 'Documents', 'active' => 'documents', 'stages' => $stages, 'stage' => $stage, 'docs' => $docs]);
}

function stage_etudiant_en_cours(int $userId, int $stageId): ?array
{
    return one('SELECT * FROM stages WHERE id = ? AND etudiant_id = ? AND statut = \'en_cours\'', [$stageId, $userId]);
}

function deposer_document(string $type): void
{
    $u = require_role('etudiant');
    check_csrf();
    $stageId = (int) ($_POST['stage_id'] ?? 0);
    $retour = '/documents?stage=' . $stageId;
    $stage = stage_etudiant_en_cours((int) $u['id'], $stageId);
    if (!$stage) {
        fail('Stage introuvable.', '/documents');
    }
    if ($type === 'convention') {
        $sujet = str_clean($_POST['sujet'] ?? '', 180);
        $description = str_clean($_POST['description'] ?? '', 4000);
        $debut = str_clean($_POST['date_debut'] ?? '', 10);
        $fin = str_clean($_POST['date_fin'] ?? '', 10);
        if ($sujet === '') {
            fail('Indique le sujet de la convention.', $retour);
        }
        if (($debut !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $debut)) || ($fin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin))) {
            fail('Les dates de la convention sont invalides.', $retour);
        }
        if ($debut !== '' && $fin !== '' && $fin < $debut) {
            fail('La date de fin doit suivre la date de début.', $retour);
        }
        $existant = one('SELECT * FROM documents WHERE stage_id = ? AND type = \'convention\'', [$stageId]);
        $fichier = prendre_fichier('fichier', !$existant || !$existant['fichier_stocke'], $retour);
        run(
            'UPDATE stages SET sujet = ?, description_convention = ?, date_debut = ?, date_fin = ?, contact_entreprise = ? WHERE id = ?',
            [$sujet, $description, $debut ?: null, $fin ?: null, str_clean($_POST['contact'] ?? '', 180), $stageId]
        );
        if ($existant) {
            run(
                'UPDATE documents SET fichier_stocke = COALESCE(?, fichier_stocke), fichier_nom = COALESCE(?, fichier_nom), statut = \'en_attente\', commentaire = \'\', depose_le = ?, traite_le = NULL WHERE id = ?',
                [$fichier['stocke'] ?? null, $fichier['nom'] ?? null, now_iso(), $existant['id']]
            );
        } else {
            run(
                'INSERT INTO documents (stage_id, type, fichier_stocke, fichier_nom, statut, commentaire, depose_le) VALUES (?, \'convention\', ?, ?, \'en_attente\', \'\', ?)',
                [$stageId, $fichier['stocke'], $fichier['nom'], now_iso()]
            );
        }
        flash('ok', 'Convention enregistrée. Elle est en attente de validation.');
    } else {
        $fichier = prendre_fichier('fichier', true, $retour);
        $existant = one('SELECT * FROM documents WHERE stage_id = ? AND type = \'rapport\'', [$stageId]);
        if ($existant) {
            run(
                'UPDATE documents SET fichier_stocke = ?, fichier_nom = ?, statut = \'en_attente\', commentaire = \'\', depose_le = ?, traite_le = NULL WHERE id = ?',
                [$fichier['stocke'], $fichier['nom'], now_iso(), $existant['id']]
            );
        } else {
            run(
                'INSERT INTO documents (stage_id, type, fichier_stocke, fichier_nom, statut, commentaire, depose_le) VALUES (?, \'rapport\', ?, ?, \'en_attente\', \'\', ?)',
                [$stageId, $fichier['stocke'], $fichier['nom'], now_iso()]
            );
        }
        flash('ok', 'Rapport déposé. Il est en attente de validation.');
    }
    if ($stage['encadrant_id']) {
        notifier((int) $stage['encadrant_id'], 'Document déposé', 'Un document de stage attend ta validation.', '/encadrant/documents');
    }
    redirect($retour);
}

function action_convention(): void
{
    deposer_document('convention');
}

function action_rapport(): void
{
    deposer_document('rapport');
}

function page_suivi(): void
{
    $u = require_role('etudiant');
    $stages = stages_etudiant((int) $u['id']);
    $stage = choisir_stage($stages);
    $journal = $stage ? q('SELECT * FROM journal WHERE stage_id = ? ORDER BY date_entree DESC, id DESC', [$stage['id']]) : [];
    $commentaires = $stage ? q(
        'SELECT c.*, u.nom, u.prenom, u.role, u.raison_sociale FROM commentaires c JOIN utilisateurs u ON u.id = c.auteur_id WHERE c.stage_id = ? ORDER BY c.cree_le',
        [$stage['id']]
    ) : [];
    $evals = [
        'entreprise' => $stage ? evaluation_de((int) $stage['id'], 'entreprise') : null,
        'encadrant' => $stage ? evaluation_de((int) $stage['id'], 'encadrant') : null,
    ];
    view('suivi', [
        'titre' => 'Suivi du stage',
        'active' => 'suivi',
        'stages' => $stages,
        'stage' => $stage,
        'journal' => $journal,
        'commentaires' => $commentaires,
        'evals' => $evals,
        'aujourdhui' => today_iso(),
        'passes' => (int) one('SELECT COUNT(*) AS n FROM stages WHERE etudiant_id = ? AND statut = \'termine\'', [$u['id']])['n'],
    ]);
}

function action_journal(): void
{
    $u = require_role('etudiant');
    check_csrf();
    $stageId = (int) ($_POST['stage_id'] ?? 0);
    $stage = stage_etudiant_en_cours((int) $u['id'], $stageId);
    $retour = '/suivi?stage=' . $stageId;
    if (!$stage) {
        fail('Stage introuvable.', '/suivi');
    }
    $date = str_clean($_POST['date_entree'] ?? '', 10);
    $taches = trim((string) ($_POST['taches'] ?? ''));
    $difficultes = str_clean($_POST['difficultes'] ?? '', 4000);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > today_iso()) {
        fail('Choisis une date passée ou aujourd\'hui.', $retour);
    }
    if ($taches === '' || mb_strlen($taches) > 4000) {
        fail('Décris les tâches réalisées (4000 caractères maximum).', $retour);
    }
    run(
        'INSERT INTO journal (stage_id, date_entree, taches, difficultes, cree_le) VALUES (?, ?, ?, ?, ?)',
        [$stageId, $date, $taches, $difficultes, now_iso()]
    );
    if ($stage['encadrant_id']) {
        notifier((int) $stage['encadrant_id'], 'Journal mis à jour', 'Une nouvelle entrée de journal a été ajoutée.', '/encadrant/stages/' . $stageId);
    }
    flash('ok', 'Entrée ajoutée au journal de bord.');
    redirect($retour);
}

function action_reponse(): void
{
    $u = require_role('etudiant');
    check_csrf();
    $stageId = (int) ($_POST['stage_id'] ?? 0);
    $stage = stage_etudiant_en_cours((int) $u['id'], $stageId);
    $retour = '/suivi?stage=' . $stageId;
    if (!$stage || !$stage['encadrant_id']) {
        fail('Tu pourras répondre dès qu\'un encadrant sera affecté.', $retour);
    }
    $contenu = trim((string) ($_POST['contenu'] ?? ''));
    if ($contenu === '' || mb_strlen($contenu) > 2000) {
        fail('Écris un commentaire (2000 caractères maximum).', $retour);
    }
    run('INSERT INTO commentaires (stage_id, auteur_id, contenu, cree_le) VALUES (?, ?, ?, ?)', [$stageId, $u['id'], $contenu, now_iso()]);
    notifier((int) $stage['encadrant_id'], 'Réponse de l\'étudiant', $u['prenom'] . ' a répondu sur le suivi.', '/encadrant/stages/' . $stageId);
    flash('ok', 'Réponse envoyée.');
    redirect($retour);
}

function page_historique(): void
{
    $u = require_role('etudiant');
    $stages = stages_etudiant((int) $u['id'], 'termine');
    foreach ($stages as &$stage) {
        $stage['entreprise_eval'] = evaluation_de((int) $stage['id'], 'entreprise');
        $stage['encadrant_eval'] = evaluation_de((int) $stage['id'], 'encadrant');
        $stage['documents'] = q('SELECT * FROM documents WHERE stage_id = ?', [$stage['id']]);
    }
    unset($stage);
    view('historique', ['titre' => 'Stages précédents', 'active' => 'historique', 'stages' => $stages]);
}

function page_profil(): void
{
    $u = require_login();
    if ($u['role'] === 'admin') {
        redirect('/admin/comptes');
    }
    $u['competences'] = competences_de('utilisateur_competences', 'utilisateur_id', (int) $u['id']);
    view('profil', ['titre' => 'Mon profil', 'active' => 'profil', 'profil' => $u]);
}

function action_profil(): void
{
    $u = require_login();
    check_csrf();
    if ($u['role'] === 'admin') {
        redirect('/admin/comptes');
    }
    $nom = str_clean($_POST['nom'] ?? '', 80);
    $prenom = str_clean($_POST['prenom'] ?? '', 80);
    if ($nom === '') {
        fail('Le nom est obligatoire.', '/profil');
    }
    if ($u['role'] === 'etudiant') {
        $filiere = str_clean($_POST['filiere'] ?? '', 120);
        if ($prenom === '' || $filiere === '') {
            fail('Indique ton prénom et ta filière.', '/profil');
        }
        $cv = prendre_fichier('cv', false, '/profil');
        run('UPDATE utilisateurs SET nom = ?, prenom = ?, filiere = ?, cv_stocke = COALESCE(?, cv_stocke), cv_nom = COALESCE(?, cv_nom) WHERE id = ?', [$nom, $prenom, $filiere, $cv['stocke'] ?? null, $cv['nom'] ?? null, $u['id']]);
        sync_competences('utilisateur_competences', 'utilisateur_id', (int) $u['id'], (string) ($_POST['competences'] ?? ''));
    } elseif ($u['role'] === 'entreprise') {
        $raison = str_clean($_POST['raison_sociale'] ?? '', 140);
        if ($raison === '' || $prenom === '') {
            fail('Indique la raison sociale et le prénom du contact.', '/profil');
        }
        run('UPDATE utilisateurs SET nom = ?, prenom = ?, raison_sociale = ? WHERE id = ?', [$nom, $prenom, $raison, $u['id']]);
    } else {
        if ($prenom === '') {
            fail('Indique le prénom.', '/profil');
        }
        run('UPDATE utilisateurs SET nom = ?, prenom = ? WHERE id = ?', [$nom, $prenom, $u['id']]);
    }
    flash('ok', 'Profil mis à jour.');
    redirect('/profil');
}

function page_notifications(): void
{
    $u = require_login();
    if ($u['role'] === 'admin') {
        redirect('/admin/comptes');
    }
    run('UPDATE notifications SET lu = 1 WHERE utilisateur_id = ?', [$u['id']]);
    $rows = q('SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY cree_le DESC LIMIT 40', [$u['id']]);
    view('notifications', ['titre' => 'Notifications', 'active' => 'notifications', 'notifications' => $rows]);
}

function lire_offre(string $retour): array
{
    $titre = str_clean($_POST['titre'] ?? '', 140);
    $description = trim((string) ($_POST['description'] ?? ''));
    $missions = str_clean($_POST['missions'] ?? '', 4000);
    $ville = str_clean($_POST['ville'] ?? '', 80);
    $duree = (int) ($_POST['duree_mois'] ?? 0);
    $competences = str_clean($_POST['competences'] ?? '', 300);
    if ($titre === '' || mb_strlen($description) < 10 || $ville === '' || $duree < 1 || $duree > 24) {
        fail('Indique un titre, une description, une durée (1 à 24 mois) et une ville.', $retour);
    }
    return compact('titre', 'description', 'missions', 'ville', 'duree', 'competences');
}

function page_mes_offres(): void
{
    $u = require_role('entreprise');
    $offres = q(
        'SELECT o.*, (SELECT COUNT(*) FROM candidatures c WHERE c.offre_id = o.id) AS nb FROM offres o WHERE o.entreprise_id = ? ORDER BY o.cree_le DESC',
        [$u['id']]
    );
    view('entreprise-offres', ['titre' => 'Mes offres', 'active' => 'offres', 'offres' => $offres]);
}

function page_form_offre(?array $m = null): void
{
    $u = require_role('entreprise');
    $offre = null;
    if (!empty($m[1])) {
        $offre = one('SELECT * FROM offres WHERE id = ? AND entreprise_id = ? AND retiree = 0', [(int) $m[1], $u['id']]);
        if (!$offre) {
            fail('Offre introuvable.', '/entreprise/offres');
        }
        $offre['competences'] = competences_de('offre_competences', 'offre_id', (int) $offre['id']);
    }
    view('entreprise-form', ['titre' => $offre ? 'Modifier l\'offre' : 'Publier une offre', 'active' => $offre ? 'offres' : 'publier', 'offre' => $offre]);
}

function action_creer_offre(): void
{
    $u = require_role('entreprise');
    check_csrf();
    $data = lire_offre('/entreprise/offres/nouvelle');
    $fichier = prendre_fichier('document', false, '/entreprise/offres/nouvelle');
    run(
        'INSERT INTO offres (entreprise_id, titre, description, missions, duree_mois, ville, document_stocke, document_nom, cree_le) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$u['id'], $data['titre'], $data['description'], $data['missions'], $data['duree'], $data['ville'], $fichier['stocke'] ?? null, $fichier['nom'] ?? null, now_iso()]
    );
    sync_competences('offre_competences', 'offre_id', (int) db()->lastInsertId(), $data['competences']);
    flash('ok', 'Offre publiée.');
    redirect('/entreprise/offres');
}

function action_modifier_offre(array $m): void
{
    $u = require_role('entreprise');
    check_csrf();
    $id = (int) $m[1];
    $offre = one('SELECT * FROM offres WHERE id = ? AND entreprise_id = ? AND retiree = 0', [$id, $u['id']]);
    $retour = '/entreprise/offres/' . $id . '/modifier';
    if (!$offre) {
        fail('Offre introuvable.', '/entreprise/offres');
    }
    $data = lire_offre($retour);
    $fichier = prendre_fichier('document', false, $retour);
    run(
        'UPDATE offres SET titre = ?, description = ?, missions = ?, duree_mois = ?, ville = ?, document_stocke = COALESCE(?, document_stocke), document_nom = COALESCE(?, document_nom) WHERE id = ?',
        [$data['titre'], $data['description'], $data['missions'], $data['duree'], $data['ville'], $fichier['stocke'] ?? null, $fichier['nom'] ?? null, $id]
    );
    sync_competences('offre_competences', 'offre_id', $id, $data['competences']);
    flash('ok', 'Offre mise à jour.');
    redirect('/entreprise/offres');
}

function action_supprimer_offre(array $m): void
{
    $u = require_role('entreprise');
    check_csrf();
    $id = (int) $m[1];
    $offre = one('SELECT * FROM offres WHERE id = ? AND entreprise_id = ?', [$id, $u['id']]);
    if (!$offre) {
        fail('Offre introuvable.', '/entreprise/offres');
    }
    $liee = one('SELECT id FROM stages WHERE offre_id = ?', [$id]);
    if ($liee || $offre['retiree']) {
        run('UPDATE offres SET retiree = 1 WHERE id = ?', [$id]);
        flash('ok', 'Offre retirée des annonces. Les stages déjà acceptés sont conservés.');
    } else {
        run('DELETE FROM candidatures WHERE offre_id = ?', [$id]);
        run('DELETE FROM offres WHERE id = ?', [$id]);
        flash('ok', 'Offre supprimée.');
    }
    redirect('/entreprise/offres');
}

function page_recues(): void
{
    $u = require_role('entreprise');
    $filtre = (int) ($_GET['offre'] ?? 0);
    $offres = q('SELECT id, titre FROM offres WHERE entreprise_id = ? ORDER BY titre', [$u['id']]);
    $sql = 'SELECT c.*, etu.prenom, etu.nom, etu.filiere, etu.email, o.titre
            FROM candidatures c
            JOIN utilisateurs etu ON etu.id = c.etudiant_id
            JOIN offres o ON o.id = c.offre_id
            WHERE o.entreprise_id = ?';
    $params = [$u['id']];
    if ($filtre) {
        $sql .= ' AND o.id = ?';
        $params[] = $filtre;
    }
    $sql .= ' ORDER BY c.cree_le DESC';
    view('entreprise-candidatures', [
        'titre' => 'Candidatures reçues',
        'active' => 'candidatures',
        'candidatures' => q($sql, $params),
        'offres' => $offres,
        'filtre' => $filtre,
    ]);
}

function candidature_entreprise(int $id, int $entrepriseId): ?array
{
    return one(
        'SELECT c.*, etu.prenom, etu.nom, etu.email, etu.filiere, o.titre, o.ville, o.duree_mois, o.entreprise_id
         FROM candidatures c
         JOIN utilisateurs etu ON etu.id = c.etudiant_id
         JOIN offres o ON o.id = c.offre_id
         WHERE c.id = ? AND o.entreprise_id = ?',
        [$id, $entrepriseId]
    );
}

function page_dossier(array $m): void
{
    $u = require_role('entreprise');
    $dossier = candidature_entreprise((int) $m[1], (int) $u['id']);
    if (!$dossier) {
        fail('Candidature introuvable.', '/entreprise/candidatures');
    }
    view('entreprise-dossier', ['titre' => 'Dossier', 'active' => 'candidatures', 'dossier' => $dossier]);
}

function action_decision(array $m): void
{
    $u = require_role('entreprise');
    check_csrf();
    $id = (int) $m[1];
    $dossier = candidature_entreprise($id, (int) $u['id']);
    $decision = $_POST['decision'] ?? '';
    if (!$dossier || $dossier['statut'] !== 'en_attente' || !in_array($decision, ['acceptee', 'refusee'], true)) {
        fail('Cette candidature est introuvable ou déjà traitée.', '/entreprise/candidatures');
    }
    if ($decision === 'acceptee') {
        db()->beginTransaction();
        run('UPDATE candidatures SET statut = \'acceptee\' WHERE id = ?', [$id]);
        run(
            'INSERT INTO stages (candidature_id, etudiant_id, entreprise_id, offre_id, statut, sujet, contact_entreprise, cree_le) VALUES (?, ?, ?, ?, \'en_cours\', ?, ?, ?)',
            [$id, $dossier['etudiant_id'], $u['id'], $dossier['offre_id'], $dossier['titre'], $u['prenom'] . ' ' . $u['nom'], now_iso()]
        );
        $stageId = (int) db()->lastInsertId();
        if ($dossier['convention_stocke']) {
            run(
                'INSERT INTO documents (stage_id, type, fichier_stocke, fichier_nom, statut, commentaire, depose_le) VALUES (?, \'convention\', ?, ?, \'en_attente\', \'\', ?)',
                [$stageId, $dossier['convention_stocke'], $dossier['convention_nom'], now_iso()]
            );
        }
        db()->commit();
        notifier((int) $dossier['etudiant_id'], 'Candidature acceptée', 'Ton dossier pour « ' . $dossier['titre'] . ' » est accepté. Tu peux déposer tes documents.', '/documents?stage=' . $stageId);
        flash('ok', 'Candidature acceptée. Un stage est ouvert, l\'administrateur peut affecter un encadrant.');
    } else {
        run('UPDATE candidatures SET statut = \'refusee\' WHERE id = ?', [$id]);
        notifier((int) $dossier['etudiant_id'], 'Candidature refusée', 'Ton dossier pour « ' . $dossier['titre'] . ' » n\'a pas été retenu.', '/candidatures');
        flash('ok', 'Candidature refusée.');
    }
    redirect('/entreprise/candidatures');
}

function stages_entreprise(int $id): array
{
    return q(
        'SELECT s.*, etu.prenom, etu.nom, etu.filiere, o.titre,
                ev.note AS note_entreprise
         FROM stages s
         JOIN utilisateurs etu ON etu.id = s.etudiant_id
         JOIN offres o ON o.id = s.offre_id
         LEFT JOIN evaluations ev ON ev.stage_id = s.id AND ev.type = \'entreprise\'
         WHERE s.entreprise_id = ?
         ORDER BY s.cree_le DESC',
        [$id]
    );
}

function page_evaluations(): void
{
    $u = require_role('entreprise');
    view('entreprise-evaluations', ['titre' => 'Évaluer un stagiaire', 'active' => 'evaluations', 'stages' => stages_entreprise((int) $u['id'])]);
}

function stage_entreprise(int $id, int $entrepriseId): ?array
{
    return one(
        'SELECT s.*, etu.prenom, etu.nom, etu.filiere, o.titre FROM stages s JOIN utilisateurs etu ON etu.id = s.etudiant_id JOIN offres o ON o.id = s.offre_id WHERE s.id = ? AND s.entreprise_id = ?',
        [$id, $entrepriseId]
    );
}

function page_evaluer(array $m): void
{
    $u = require_role('entreprise');
    $stage = stage_entreprise((int) $m[1], (int) $u['id']);
    if (!$stage) {
        fail('Stage introuvable.', '/entreprise/evaluations');
    }
    view('entreprise-evaluer', [
        'titre' => 'Grille d\'évaluation',
        'active' => 'evaluations',
        'stage' => $stage,
        'evaluation' => evaluation_de((int) $stage['id'], 'entreprise'),
    ]);
}

function action_evaluer(array $m): void
{
    $u = require_role('entreprise');
    check_csrf();
    $id = (int) $m[1];
    $stage = stage_entreprise($id, (int) $u['id']);
    $retour = '/entreprise/stages/' . $id . '/evaluation';
    if (!$stage) {
        fail('Stage introuvable.', '/entreprise/evaluations');
    }
    $niveaux = [];
    foreach (['assiduite', 'competences_tech', 'autonomie', 'communication', 'qualite'] as $champ) {
        $valeur = str_clean($_POST[$champ] ?? '', 20);
        if (!niveau_ok($valeur)) {
            fail('Complète chaque critère de la grille.', $retour);
        }
        $niveaux[$champ] = $valeur;
    }
    $note = note_ok($_POST['note'] ?? '');
    if ($note === null) {
        fail('La note doit être comprise entre 0 et 20.', $retour);
    }
    $commentaire = trim((string) ($_POST['commentaire'] ?? ''));
    if (mb_strlen($commentaire) > 4000) {
        fail('Le commentaire est trop long.', $retour);
    }
    $existant = evaluation_de($id, 'entreprise');
    if ($existant) {
        run(
            'UPDATE evaluations SET note = ?, commentaire = ?, assiduite = ?, competences_tech = ?, autonomie = ?, communication = ?, qualite = ?, cree_le = ? WHERE id = ?',
            [$note, $commentaire, $niveaux['assiduite'], $niveaux['competences_tech'], $niveaux['autonomie'], $niveaux['communication'], $niveaux['qualite'], now_iso(), $existant['id']]
        );
    } else {
        run(
            'INSERT INTO evaluations (stage_id, auteur_id, type, note, commentaire, assiduite, competences_tech, autonomie, communication, qualite, cree_le) VALUES (?, ?, \'entreprise\', ?, ?, ?, ?, ?, ?, ?, ?)',
            [$id, $u['id'], $note, $commentaire, $niveaux['assiduite'], $niveaux['competences_tech'], $niveaux['autonomie'], $niveaux['communication'], $niveaux['qualite'], now_iso()]
        );
    }
    notifier((int) $stage['etudiant_id'], 'Évaluation de l\'entreprise', 'Une évaluation a été enregistrée pour « ' . $stage['titre'] . ' ».', '/suivi?stage=' . $id);
    flash('ok', 'Évaluation enregistrée.');
    redirect('/entreprise/evaluations');
}

function stages_encadrant(int $id): array
{
    return q(
        'SELECT s.*, etu.prenom, etu.nom, etu.filiere, etu.email, o.titre, ent.raison_sociale,
                (SELECT COUNT(*) FROM journal j WHERE j.stage_id = s.id) AS nb_journal
         FROM stages s
         JOIN utilisateurs etu ON etu.id = s.etudiant_id
         JOIN offres o ON o.id = s.offre_id
         JOIN utilisateurs ent ON ent.id = s.entreprise_id
         WHERE s.encadrant_id = ?
         ORDER BY s.statut, s.cree_le DESC',
        [$id]
    );
}

function page_stagiaires(): void
{
    $u = require_role('encadrant');
    view('encadrant-stagiaires', ['titre' => 'Mes stagiaires', 'active' => 'stagiaires', 'stages' => stages_encadrant((int) $u['id'])]);
}

function stage_encadrant(int $id, int $encadrantId): ?array
{
    return one(
        'SELECT s.*, etu.prenom, etu.nom, etu.filiere, etu.email, o.titre, ent.raison_sociale
         FROM stages s
         JOIN utilisateurs etu ON etu.id = s.etudiant_id
         JOIN offres o ON o.id = s.offre_id
         JOIN utilisateurs ent ON ent.id = s.entreprise_id
         WHERE s.id = ? AND s.encadrant_id = ?',
        [$id, $encadrantId]
    );
}

function page_suivi_enc(array $m): void
{
    $u = require_role('encadrant');
    $stage = stage_encadrant((int) $m[1], (int) $u['id']);
    if (!$stage) {
        fail('Stagiaire introuvable.', '/encadrant/stagiaires');
    }
    view('encadrant-suivi', [
        'titre' => 'Suivi',
        'active' => 'stagiaires',
        'stage' => $stage,
        'journal' => q('SELECT * FROM journal WHERE stage_id = ? ORDER BY date_entree DESC', [$stage['id']]),
        'commentaires' => q('SELECT c.*, u.nom, u.prenom, u.role FROM commentaires c JOIN utilisateurs u ON u.id = c.auteur_id WHERE c.stage_id = ? ORDER BY c.cree_le', [$stage['id']]),
        'note' => evaluation_de((int) $stage['id'], 'encadrant'),
        'entreprise' => evaluation_de((int) $stage['id'], 'entreprise'),
        'documents' => q('SELECT * FROM documents WHERE stage_id = ?', [$stage['id']]),
    ]);
}

function action_feedback(array $m): void
{
    $u = require_role('encadrant');
    check_csrf();
    $entree = one(
        'SELECT j.*, s.etudiant_id, s.encadrant_id FROM journal j JOIN stages s ON s.id = j.stage_id WHERE j.id = ? AND s.encadrant_id = ?',
        [(int) $m[1], $u['id']]
    );
    if (!$entree) {
        fail('Entrée introuvable.', '/encadrant/stagiaires');
    }
    $feedback = trim((string) ($_POST['feedback'] ?? ''));
    if ($feedback === '' || mb_strlen($feedback) > 2000) {
        fail('Écris un feedback (2000 caractères maximum).', '/encadrant/stages/' . $entree['stage_id']);
    }
    run('UPDATE journal SET feedback = ?, feedback_le = ? WHERE id = ?', [$feedback, now_iso(), $entree['id']]);
    notifier((int) $entree['etudiant_id'], 'Feedback sur le journal', 'Ton encadrant a commenté une entrée du journal.', '/suivi?stage=' . $entree['stage_id']);
    flash('ok', 'Feedback enregistré.');
    redirect('/encadrant/stages/' . $entree['stage_id']);
}

function action_commentaire(array $m): void
{
    $u = require_role('encadrant');
    check_csrf();
    $stage = stage_encadrant((int) $m[1], (int) $u['id']);
    if (!$stage) {
        fail('Stage introuvable.', '/encadrant/stagiaires');
    }
    $contenu = trim((string) ($_POST['contenu'] ?? ''));
    if ($contenu === '' || mb_strlen($contenu) > 2000) {
        fail('Écris un commentaire (2000 caractères maximum).', '/encadrant/stages/' . $stage['id']);
    }
    run('INSERT INTO commentaires (stage_id, auteur_id, contenu, cree_le) VALUES (?, ?, ?, ?)', [$stage['id'], $u['id'], $contenu, now_iso()]);
    notifier((int) $stage['etudiant_id'], 'Commentaire de l\'encadrant', 'Un nouveau commentaire est disponible sur ton suivi.', '/suivi?stage=' . $stage['id']);
    flash('ok', 'Commentaire envoyé à l\'étudiant.');
    redirect('/encadrant/stages/' . $stage['id']);
}

function action_note(array $m): void
{
    $u = require_role('encadrant');
    check_csrf();
    $stage = stage_encadrant((int) $m[1], (int) $u['id']);
    $retour = $stage ? '/encadrant/stages/' . $stage['id'] : '/encadrant/stagiaires';
    if (!$stage) {
        fail('Stage introuvable.', '/encadrant/stagiaires');
    }
    $note = note_ok($_POST['note'] ?? '');
    $appreciation = trim((string) ($_POST['appreciation'] ?? ''));
    if ($note === null) {
        fail('La note doit être comprise entre 0 et 20.', $retour);
    }
    if ($appreciation === '' || mb_strlen($appreciation) > 4000) {
        fail('Ajoute une appréciation.', $retour);
    }
    $existant = evaluation_de((int) $stage['id'], 'encadrant');
    if ($existant) {
        run('UPDATE evaluations SET note = ?, commentaire = ?, cree_le = ? WHERE id = ?', [$note, $appreciation, now_iso(), $existant['id']]);
    } else {
        run(
            'INSERT INTO evaluations (stage_id, auteur_id, type, note, commentaire, cree_le) VALUES (?, ?, \'encadrant\', ?, ?, ?)',
            [$stage['id'], $u['id'], $note, $appreciation, now_iso()]
        );
    }
    flash('ok', 'Note enregistrée.');
    redirect($retour);
}

function action_cloture(array $m): void
{
    $u = require_role('encadrant');
    check_csrf();
    $stage = stage_encadrant((int) $m[1], (int) $u['id']);
    if (!$stage) {
        fail('Stage introuvable.', '/encadrant/stagiaires');
    }
    if (!evaluation_de((int) $stage['id'], 'encadrant')) {
        fail('Attribue une note avant de clôturer le stage.', '/encadrant/stages/' . $stage['id']);
    }
    run('UPDATE stages SET statut = \'termine\' WHERE id = ?', [$stage['id']]);
    notifier((int) $stage['etudiant_id'], 'Stage clôturé', 'Ton stage « ' . $stage['titre'] . ' » est terminé. Il reste dans ton historique.', '/historique');
    flash('ok', 'Stage clôturé.');
    redirect('/encadrant/stagiaires');
}

function page_docs_enc(): void
{
    $u = require_role('encadrant');
    $docs = q(
        'SELECT d.*, s.id AS stage_id, etu.prenom, etu.nom, o.titre, ent.raison_sociale
         FROM documents d
         JOIN stages s ON s.id = d.stage_id
         JOIN utilisateurs etu ON etu.id = s.etudiant_id
         JOIN offres o ON o.id = s.offre_id
         JOIN utilisateurs ent ON ent.id = s.entreprise_id
         WHERE s.encadrant_id = ?
         ORDER BY CASE d.statut WHEN \'en_attente\' THEN 0 ELSE 1 END, d.depose_le DESC',
        [$u['id']]
    );
    view('encadrant-documents', ['titre' => 'Validation des documents', 'active' => 'documents', 'documents' => $docs]);
}

function document_encadrant(int $id, int $encadrantId): ?array
{
    return one(
        'SELECT d.*, s.etudiant_id, s.encadrant_id FROM documents d JOIN stages s ON s.id = d.stage_id WHERE d.id = ? AND s.encadrant_id = ?',
        [$id, $encadrantId]
    );
}

function action_valider(array $m): void
{
    $u = require_role('encadrant');
    check_csrf();
    $doc = document_encadrant((int) $m[1], (int) $u['id']);
    if (!$doc) {
        fail('Document introuvable.', '/encadrant/documents');
    }
    run('UPDATE documents SET statut = \'valide\', commentaire = \'\', traite_le = ? WHERE id = ?', [now_iso(), $doc['id']]);
    notifier((int) $doc['etudiant_id'], 'Document validé', 'Ton ' . ($doc['type'] === 'rapport' ? 'rapport' : 'convention') . ' a été validé.', '/documents?stage=' . $doc['stage_id']);
    flash('ok', 'Document validé.');
    redirect('/encadrant/documents');
}

function action_rejeter(array $m): void
{
    $u = require_role('encadrant');
    check_csrf();
    $doc = document_encadrant((int) $m[1], (int) $u['id']);
    if (!$doc) {
        fail('Document introuvable.', '/encadrant/documents');
    }
    $motif = trim((string) ($_POST['commentaire'] ?? ''));
    if ($motif === '' || mb_strlen($motif) > 2000) {
        fail('Indique le motif du rejet.', '/encadrant/documents');
    }
    run('UPDATE documents SET statut = \'rejete\', commentaire = ?, traite_le = ? WHERE id = ?', [$motif, now_iso(), $doc['id']]);
    notifier((int) $doc['etudiant_id'], 'Document rejeté', 'Ton ' . ($doc['type'] === 'rapport' ? 'rapport' : 'convention') . ' a été rejeté : ' . $motif, '/documents?stage=' . $doc['stage_id']);
    flash('ok', 'Document rejeté, l\'étudiant est prévenu.');
    redirect('/encadrant/documents');
}

function page_comptes(): void
{
    require_role('admin');
    $qtext = str_clean($_GET['q'] ?? '', 80);
    $like = '%' . str_replace(['%', '_'], ' ', $qtext) . '%';
    $comptes = q(
        'SELECT id, email, role, nom, prenom, filiere, raison_sociale, actif FROM utilisateurs
         WHERE ? = \'\' OR nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR filiere LIKE ? OR raison_sociale LIKE ?
         ORDER BY role, nom',
        [$qtext, $like, $like, $like, $like, $like]
    );
    $demandes = q(
        'SELECT r.*, u.email, u.role, u.nom, u.prenom, u.raison_sociale
         FROM reinitialisations r JOIN utilisateurs u ON u.id = r.utilisateur_id
         WHERE r.traite = 0 ORDER BY r.demande_le'
    );
    view('admin-comptes', ['titre' => 'Comptes', 'active' => 'comptes', 'comptes' => $comptes, 'demandes' => $demandes, 'q' => $qtext]);
}

function action_reset(): void
{
    $admin = require_role('admin');
    check_csrf();
    $id = (int) ($_POST['utilisateur_id'] ?? 0);
    $compte = one('SELECT * FROM utilisateurs WHERE id = ?', [$id]);
    if (!$compte) {
        fail('Compte introuvable.', '/admin/comptes');
    }
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $pwd = '';
    for ($i = 0; $i < 10; $i++) {
        $pwd .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    run('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?', [password_hash($pwd, PASSWORD_DEFAULT), $id]);
    run('UPDATE reinitialisations SET traite = 1 WHERE utilisateur_id = ? AND traite = 0', [$id]);
    flash('ok', 'Nouveau mot de passe pour ' . $compte['email'] . ' : ' . $pwd . '. Communique-le à la personne, il ne sera plus affiché.');
    redirect('/admin/comptes');
}

function action_basculer(): void
{
    $admin = require_role('admin');
    check_csrf();
    $id = (int) ($_POST['utilisateur_id'] ?? 0);
    if ($id === (int) $admin['id']) {
        fail('Tu ne peux pas désactiver ton propre compte.', '/admin/comptes');
    }
    $compte = one('SELECT * FROM utilisateurs WHERE id = ?', [$id]);
    if (!$compte) {
        fail('Compte introuvable.', '/admin/comptes');
    }
    $actif = (int) $compte['actif'] ? 0 : 1;
    run('UPDATE utilisateurs SET actif = ? WHERE id = ?', [$actif, $id]);
    flash('ok', nom_affiche($compte) . ($actif ? ' est réactivé.' : ' est désactivé.'));
    redirect('/admin/comptes');
}

function action_creer_encadrant(): void
{
    require_role('admin');
    check_csrf();
    $nom = str_clean($_POST['nom'] ?? '', 80);
    $prenom = str_clean($_POST['prenom'] ?? '', 80);
    $email = strtolower(str_clean($_POST['email'] ?? '', 160));
    $password = (string) ($_POST['mot_de_passe'] ?? '');
    if ($nom === '' || $prenom === '') {
        fail('Indique le nom et le prénom de l\'encadrant.', '/admin/comptes');
    }
    if (!email_ok($email)) {
        fail('Adresse e-mail invalide.', '/admin/comptes');
    }
    if (one('SELECT id FROM utilisateurs WHERE email = ?', [$email])) {
        fail('Un compte existe déjà avec cet e-mail.', '/admin/comptes');
    }
    $genere = false;
    if ($password === '') {
        $genere = true;
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
    } elseif (mb_strlen($password) < 8) {
        fail('Le mot de passe doit contenir au moins 8 caractères.', '/admin/comptes');
    }
    run(
        'INSERT INTO utilisateurs (email, mot_de_passe, role, nom, prenom, actif, cree_le) VALUES (?, ?, \'encadrant\', ?, ?, 1, ?)',
        [$email, password_hash($password, PASSWORD_DEFAULT), $nom, $prenom, now_iso()]
    );
    flash('ok', $genere
        ? 'Encadrant créé. Mot de passe temporaire pour ' . $email . ' : ' . $password . '.'
        : 'Encadrant créé.');
    redirect('/admin/comptes');
}

function page_affectations(): void
{
    require_role('admin');
    $stages = q(
        'SELECT s.*, etu.prenom, etu.nom, etu.email, o.titre, ent.raison_sociale, enc.nom AS enc_nom
         FROM stages s
         JOIN utilisateurs etu ON etu.id = s.etudiant_id
         JOIN offres o ON o.id = s.offre_id
         JOIN utilisateurs ent ON ent.id = s.entreprise_id
         LEFT JOIN utilisateurs enc ON enc.id = s.encadrant_id
         WHERE s.statut = \'en_cours\'
         ORDER BY s.cree_le DESC'
    );
    $encadrants = q("SELECT id, nom, prenom, email FROM utilisateurs WHERE role = 'encadrant' AND actif = 1 ORDER BY nom");
    view('admin-affectations', ['titre' => 'Affectation des encadrants', 'active' => 'affectations', 'stages' => $stages, 'encadrants' => $encadrants]);
}

function action_affecter(): void
{
    require_role('admin');
    check_csrf();
    $stageId = (int) ($_POST['stage_id'] ?? 0);
    $encId = (int) ($_POST['encadrant_id'] ?? 0);
    $stage = one(
        'SELECT s.*, etu.prenom, etu.nom FROM stages s JOIN utilisateurs etu ON etu.id = s.etudiant_id WHERE s.id = ? AND s.statut = \'en_cours\'',
        [$stageId]
    );
    $enc = one("SELECT * FROM utilisateurs WHERE id = ? AND role = 'encadrant' AND actif = 1", [$encId]);
    if (!$stage || !$enc) {
        fail('Choisis un stage en cours et un encadrant actif.', '/admin/affectations');
    }
    run('UPDATE stages SET encadrant_id = ? WHERE id = ?', [$encId, $stageId]);
    notifier($encId, 'Nouveau stagiaire', $stage['prenom'] . ' ' . $stage['nom'] . ' t\'est affecté.', '/encadrant/stages/' . $stageId);
    notifier((int) $stage['etudiant_id'], 'Encadrant affecté', 'Pr. ' . $enc['nom'] . ' suit désormais ton stage.', '/suivi?stage=' . $stageId);
    flash('ok', $stage['prenom'] . ' ' . $stage['nom'] . ' est suivi par Pr. ' . $enc['nom'] . '.');
    redirect('/admin/affectations');
}

function collect_stats(): array
{
    $rows = q('SELECT statut, COUNT(*) AS n FROM candidatures GROUP BY statut');
    $map = [];
    $total = 0;
    foreach ($rows as $row) {
        $map[$row['statut']] = (int) $row['n'];
        $total += (int) $row['n'];
    }
    $part = static function (string $statut) use ($map, $total): array {
        $n = $map[$statut] ?? 0;
        return ['n' => $n, 'pct' => $total ? (int) round($n / $total * 100) : 0];
    };
    $moy = static function (string $type): ?float {
        $n = one('SELECT ROUND(AVG(note), 2) AS n FROM evaluations WHERE type = ? AND note IS NOT NULL', [$type])['n'] ?? null;
        return $n === null ? null : (float) $n;
    };
    return [
        'etudiants' => (int) one("SELECT COUNT(*) AS n FROM utilisateurs WHERE role = 'etudiant'")['n'],
        'entreprises' => (int) one("SELECT COUNT(*) AS n FROM utilisateurs WHERE role = 'entreprise'")['n'],
        'encadrants' => (int) one("SELECT COUNT(*) AS n FROM utilisateurs WHERE role = 'encadrant'")['n'],
        'offres' => (int) one('SELECT COUNT(*) AS n FROM offres WHERE retiree = 0')['n'],
        'candidatures' => $total,
        'acceptees' => $part('acceptee'),
        'enAttente' => $part('en_attente'),
        'refusees' => $part('refusee'),
        'stagesEnCours' => (int) one("SELECT COUNT(*) AS n FROM stages WHERE statut = 'en_cours'")['n'],
        'stagesTermines' => (int) one("SELECT COUNT(*) AS n FROM stages WHERE statut = 'termine'")['n'],
        'moyenneEntreprise' => $moy('entreprise'),
        'moyenneEncadrant' => $moy('encadrant'),
        'villes' => q('SELECT ville, COUNT(*) AS n FROM offres WHERE retiree = 0 GROUP BY ville ORDER BY n DESC, ville'),
    ];
}

function page_stats(): void
{
    require_role('admin');
    view('admin-stats', ['titre' => 'Statistiques', 'active' => 'stats', 'stats' => collect_stats()]);
}

function lignes_stats(array $stats): array
{
    return [
        'Étudiants inscrits : ' . $stats['etudiants'],
        'Entreprises : ' . $stats['entreprises'],
        'Encadrants : ' . $stats['encadrants'],
        'Offres publiées : ' . $stats['offres'],
        'Candidatures : ' . $stats['candidatures'],
        'Acceptées : ' . $stats['acceptees']['n'],
        'En attente : ' . $stats['enAttente']['n'],
        'Refusées : ' . $stats['refusees']['n'],
        'Stages en cours : ' . $stats['stagesEnCours'],
        'Stages terminés : ' . $stats['stagesTermines'],
        'Note moyenne entreprise : ' . ($stats['moyenneEntreprise'] === null ? '—' : $stats['moyenneEntreprise'] . '/20'),
        'Note moyenne encadrant : ' . ($stats['moyenneEncadrant'] === null ? '—' : $stats['moyenneEncadrant'] . '/20'),
    ];
}

function action_stats_pdf(): void
{
    require_role('admin');
    $stats = collect_stats();
    $lignes = lignes_stats($stats);
    $lignes[] = 'Export du ' . date_heure_fr(now_iso()) . ' (heure de Casablanca)';
    foreach ($stats['villes'] as $ville) {
        $lignes[] = 'Ville ' . $ville['ville'] . ' : ' . $ville['n'] . ' offre(s)';
    }
    envoyer_pdf('stagehub-statistiques.pdf', 'StageHub — Rapport statistique', $lignes);
}

function action_stats_excel(): void
{
    require_role('admin');
    $stats = collect_stats();
    $lignes = [['Indicateur', 'Valeur']];
    foreach (lignes_stats($stats) as $ligne) {
        [$label, $valeur] = array_pad(explode(' : ', $ligne, 2), 2, '');
        $lignes[] = [$label, $valeur];
    }
    $lignes[] = [];
    $lignes[] = ['Ville', 'Offres'];
    foreach ($stats['villes'] as $ville) {
        $lignes[] = [$ville['ville'], $ville['n']];
    }
    envoyer_xlsx('stagehub-statistiques.xlsx', $lignes);
}

function fichier_offre(array $m): void
{
    $u = require_login();
    $offre = one('SELECT * FROM offres WHERE id = ?', [(int) $m[1]]);
    if (!$offre || !$offre['document_stocke']) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    $ok = $u['role'] === 'admin'
        || (int) $offre['entreprise_id'] === (int) $u['id']
        || (!$offre['retiree'] && $u['role'] === 'etudiant')
        || one('SELECT id FROM stages WHERE offre_id = ? AND (etudiant_id = ? OR encadrant_id = ?)', [$offre['id'], $u['id'], $u['id']]);
    if (!$ok) {
        http_response_code(403);
        exit('Accès refusé.');
    }
    envoyer_fichier($offre['document_stocke'], $offre['document_nom'] ?: 'presentation.pdf');
}

function fichier_candidature(array $m): void
{
    $u = require_login();
    $cand = one(
        'SELECT c.*, o.entreprise_id FROM candidatures c JOIN offres o ON o.id = c.offre_id WHERE c.id = ?',
        [(int) $m[1]]
    );
    if (!$cand) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    $enc = one('SELECT encadrant_id FROM stages WHERE candidature_id = ?', [$cand['id']]);
    $ok = $u['role'] === 'admin'
        || (int) $cand['etudiant_id'] === (int) $u['id']
        || (int) $cand['entreprise_id'] === (int) $u['id']
        || ($enc && (int) $enc['encadrant_id'] === (int) $u['id']);
    if (!$ok) {
        http_response_code(403);
        exit('Accès refusé.');
    }
    $champ = $m[2] === 'cv' ? 'cv' : 'convention';
    envoyer_fichier($cand[$champ . '_stocke'], $cand[$champ . '_nom'] ?: $champ . '.pdf');
}

function fichier_document(array $m): void
{
    $u = require_login();
    $doc = one(
        'SELECT d.*, s.etudiant_id, s.entreprise_id, s.encadrant_id FROM documents d JOIN stages s ON s.id = d.stage_id WHERE d.id = ?',
        [(int) $m[1]]
    );
    if (!$doc) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    $ok = $u['role'] === 'admin'
        || (int) $doc['etudiant_id'] === (int) $u['id']
        || (int) $doc['entreprise_id'] === (int) $u['id']
        || (int) $doc['encadrant_id'] === (int) $u['id'];
    if (!$ok) {
        http_response_code(403);
        exit('Accès refusé.');
    }
    envoyer_fichier($doc['fichier_stocke'], $doc['fichier_nom'] ?: 'document.pdf');
}

function fichier_cv(array $m): void
{
    $u = require_login();
    $cible = one('SELECT * FROM utilisateurs WHERE id = ?', [(int) $m[1]]);
    if (!$cible || !$cible['cv_stocke']) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    $ok = $u['role'] === 'admin'
        || (int) $cible['id'] === (int) $u['id']
        || one('SELECT c.id FROM candidatures c JOIN offres o ON o.id = c.offre_id WHERE c.etudiant_id = ? AND o.entreprise_id = ?', [$cible['id'], $u['id']])
        || one('SELECT id FROM stages WHERE etudiant_id = ? AND encadrant_id = ?', [$cible['id'], $u['id']]);
    if (!$ok) {
        http_response_code(403);
        exit('Accès refusé.');
    }
    envoyer_fichier($cible['cv_stocke'], $cible['cv_nom'] ?: 'cv.pdf');
}
