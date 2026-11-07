<?php
// Configuration générale + connexion à la base de données
session_start();
ob_start();   // évite l'erreur « headers already sent » lors des redirections

// XAMPP : root sans mot de passe. Sinon, variables STAGEHUB_DB_HOST, _NAME, _USER, _PASS.
$dbHost = getenv('STAGEHUB_DB_HOST') ?: 'localhost';
$dbName = getenv('STAGEHUB_DB_NAME') ?: 'stagehub';
$dbUser = getenv('STAGEHUB_DB_USER') ?: 'root';
$dbPass = getenv('STAGEHUB_DB_PASS');
if ($dbPass === false) {
    $dbPass = '';
}

try {
    $pdo = new PDO(
        'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $ex) {
    die('Erreur de connexion à la base de données : ' . $ex->getMessage());
}

// Protège l'affichage contre les failles XSS
function e($texte) {
    return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8');
}

function est_connecte() {
    return isset($_SESSION['user']);
}

// Redirige vers la connexion si le rôle ne correspond pas
function exiger_role($role) {
    if (!est_connecte() || $_SESSION['user']['role'] !== $role) {
        header('Location: login.php');
        exit;
    }
}

// Message temporaire (affiché une seule fois)
function message($texte, $type = 'succes') {
    $_SESSION['message'] = ['texte' => $texte, 'type' => $type];
}

// Retourne le stage en cours d'un étudiant (ou false s'il n'en a pas)
function stage_actif($pdo, $etudiant_id) {
    $req = $pdo->prepare("SELECT s.*, o.titre, ent.raison_sociale
                          FROM stages s
                          JOIN candidatures c ON c.id = s.candidature_id
                          JOIN offres o ON o.id = c.offre_id
                          JOIN utilisateurs ent ON ent.id = o.entreprise_id
                          WHERE c.etudiant_id = ? AND s.statut = 'ACTIF'
                          ORDER BY s.id DESC LIMIT 1");
    $req->execute([$etudiant_id]);
    return $req->fetch();
}

// Enregistre un PDF (2 Mo max) dans uploads/<dossier>/
// Retourne [nom_du_fichier, null] si tout va bien, ou [null, message_d_erreur]
function enregistrer_pdf($champ, $dossier, $obligatoire = true) {
    $absent = !isset($_FILES[$champ]) || $_FILES[$champ]['error'] === UPLOAD_ERR_NO_FILE;
    if ($absent) {
        return $obligatoire ? [null, 'Veuillez joindre un fichier PDF.'] : [null, null];
    }
    if ($_FILES[$champ]['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Veuillez joindre un fichier PDF.'];
    }
    $f = $_FILES[$champ];
    $extension = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf' || $f['size'] > 2 * 1024 * 1024) {
        return [null, 'Le fichier doit être un PDF de 2 Mo maximum.'];
    }
    $dossier_complet = __DIR__ . '/uploads/' . $dossier . '/';
    if (!is_dir($dossier_complet)) {
        mkdir($dossier_complet, 0777, true);
    }
    $nom = bin2hex(random_bytes(8)) . '.pdf';
    move_uploaded_file($f['tmp_name'], $dossier_complet . $nom);
    return [$nom, null];
}

// Notification interne. La plateforme tourne en local, sans envoi d'e-mail.
function notifier($pdo, $utilisateur_id, $message) {
    $utilisateur_id = (int)$utilisateur_id;
    $message = trim($message);
    if ($utilisateur_id < 1 || $message === '') {
        return;
    }
    $req = $pdo->prepare('INSERT INTO notifications (utilisateur_id, message) VALUES (?, ?)');
    $req->execute([$utilisateur_id, mb_substr($message, 0, 255)]);
}

function notifications_non_lues($pdo, $utilisateur_id) {
    $req = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lu = 0');
    $req->execute([(int)$utilisateur_id]);
    return (int)$req->fetchColumn();
}

// Pastille colorée d'une entreprise, comme sur la liste d'offres du wireframe
function tuile_entreprise($nom) {
    $fonds = ['#e8d7b6', '#f6c6dc', '#1c1c1e', '#d5e6f8', '#d5f0e2', '#efe4c2'];
    $encres = ['#3d3424', '#9a2b60', '#ffffff', '#1c4d86', '#1a6a42', '#5a4810'];
    $i = abs(crc32((string)$nom)) % count($fonds);
    $court = trim((string)$nom);
    if (mb_strlen($court) > 10) {
        $court = mb_substr($court, 0, 8);
    }
    return [$fonds[$i], $encres[$i], $court];
}

// Affiche la liste des commentaires d'un stage
function afficher_commentaires($pdo, $stage_id) {
    $req = $pdo->prepare('SELECT c.*, u.prenom, u.nom, u.role
                          FROM commentaires c JOIN utilisateurs u ON u.id = c.auteur_id
                          WHERE c.stage_id = ? ORDER BY c.date_commentaire');
    $req->execute([$stage_id]);
    $liste = $req->fetchAll();
    if (!$liste) {
        echo '<p>Aucun commentaire pour le moment.</p>';
    }
    foreach ($liste as $c) {
        echo '<div class="carte"><div class="meta">' . e($c['prenom'] . ' ' . $c['nom']) . ' (' . e(strtolower($c['role'])) . ') — '
             . e(date('d/m/Y H:i', strtotime($c['date_commentaire']))) . '</div>'
             . '<p>' . nl2br(e($c['contenu'])) . '</p></div>';
    }
}

// Petites icônes (SVG) utilisées dans la navigation et les listes
function icone($nom) {
    $formes = [
        'accueil'  => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'offres'   => '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5"/><path d="M8 13h8M8 17h5"/>',
        'document' => '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'oeil'     => '<path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="2.5"/>',
        'suivi'    => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
        'profil'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'cloche'   => '<path d="M6 16V10a6 6 0 1 1 12 0v6"/><path d="M4 16h16"/><path d="M10 19a2 2 0 0 0 4 0"/>',
        'filtre'   => '<path d="M4 5h16l-6 7v6l-4 2V12z"/>',
        'journal'  => '<path d="M7 3h8l4 4v14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M15 3v5h5M8 13h8M8 17h5"/>',
        'check'    => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 12l3 3 5-6"/>',
        'sortie'   => '<path d="M9 21H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'fleche'   => '<path d="M9 6l6 6-6 6"/>',
        'recherche'=> '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'equipe'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.8c2.3.6 4 2.3 4 5.2"/>',
        'stats'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'valide'   => '<path d="M5 12l5 5 9-10"/>',
    ];
    return '<svg class="ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($formes[$nom] ?? '') . '</svg>';
}

// Première lettre du prénom (pour la pastille ronde)
function initiale($texte) {
    return e(mb_strtoupper(mb_substr(trim($texte), 0, 1)));
}
