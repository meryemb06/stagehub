<?php

const DEMO_PASSWORD = 'Stagehub1';

function app_root(): string
{
    return dirname(__DIR__);
}

function storage_dir(): string
{
    $dir = getenv('STORAGE_DIR') ?: app_root() . '/storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function upload_dir(): string
{
    $dir = getenv('UPLOADS_DIR') ?: storage_dir() . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $path = getenv('DB_PATH') ?: storage_dir() . '/stagehub.sqlite';
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec(file_get_contents(app_root() . '/sql/schema.sql'));
    if ((int) $pdo->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn() === 0) {
        require_once __DIR__ . '/seed.php';
        seed($pdo);
    }
    return $pdo;
}

function boot(): void
{
    date_default_timezone_set('Africa/Casablanca');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    db();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function str_clean($value, int $max = 4000): string
{
    $text = trim(str_replace("\0", '', (string) $value));
    if (mb_strlen($text) > $max) {
        $text = mb_substr($text, 0, $max);
    }
    return $text;
}

function today_iso(): string
{
    return (new DateTime('now', new DateTimeZone('Africa/Casablanca')))->format('Y-m-d');
}

function now_iso(): string
{
    return (new DateTime('now', new DateTimeZone('Africa/Casablanca')))->format('Y-m-d\TH:i:sP');
}

function date_fr(?string $value): string
{
    if (!$value) {
        return '—';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }
    try {
        $dt = new DateTime($value);
        $dt->setTimezone(new DateTimeZone('Africa/Casablanca'));
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return '—';
    }
}

function date_heure_fr(?string $value): string
{
    if (!$value) {
        return '—';
    }
    try {
        $dt = new DateTime($value);
        $dt->setTimezone(new DateTimeZone('Africa/Casablanca'));
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return '—';
    }
}

function nl2br_e(string $text): string
{
    return nl2br(e($text));
}

function lignes(string $text): array
{
    $out = [];
    foreach (preg_split('/\R/', $text) as $line) {
        $line = trim(preg_replace('/^[-•]\s*/', '', $line));
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function q(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row ?: null;
}

function run(string $sql, array $params = []): void
{
    $st = db()->prepare($sql);
    $st->execute($params);
}

function user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $row = one(
        'SELECT id, email, role, nom, prenom, filiere, raison_sociale, cv_stocke, cv_nom, actif FROM utilisateurs WHERE id = ?',
        [$_SESSION['user_id']]
    );
    if (!$row || !(int) $row['actif']) {
        return null;
    }
    return $row;
}

function nom_affiche(?array $u): string
{
    if (!$u) {
        return '';
    }
    if (($u['role'] ?? '') === 'entreprise') {
        return $u['raison_sociale'] ?: $u['nom'];
    }
    if (($u['role'] ?? '') === 'encadrant') {
        return 'Pr. ' . $u['nom'];
    }
    if (($u['role'] ?? '') === 'admin') {
        return 'Administrateur';
    }
    return trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
}

function role_label(string $role): string
{
    return [
        'etudiant' => 'Étudiant',
        'entreprise' => 'Entreprise',
        'encadrant' => 'Encadrant',
        'admin' => 'Administrateur',
    ][$role] ?? $role;
}

function home_for(string $role): string
{
    return [
        'etudiant' => '/offres',
        'entreprise' => '/entreprise/offres',
        'encadrant' => '/encadrant/stagiaires',
        'admin' => '/admin/comptes',
    ][$role] ?? '/';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf(): string
{
    return $_SESSION['csrf'] ?? '';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf()) . '">';
}

function check_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf(), $token)) {
        http_response_code(419);
        exit('Session expirée. Recharge la page.');
    }
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function fail(string $message, string $back): never
{
    flash('ko', $message);
    redirect($back);
}

function require_login(): array
{
    $u = user();
    if (!$u) {
        redirect('/');
    }
    return $u;
}

function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        redirect(home_for($u['role']));
    }
    return $u;
}

function notif_count(int $userId): int
{
    return (int) db()->query('SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ' . (int) $userId . ' AND lu = 0')->fetchColumn();
}

function notifier(int $userId, string $titre, string $message, string $lien = ''): void
{
    if ($userId <= 0) {
        return;
    }
    if ($lien !== '' && $lien[0] !== '/') {
        $lien = '';
    }
    run(
        'INSERT INTO notifications (utilisateur_id, titre, message, lien, lu, cree_le) VALUES (?, ?, ?, ?, 0, ?)',
        [$userId, $titre, $message, $lien, now_iso()]
    );
}

function view(string $name, array $data = []): void
{
    $u = user();
    $data['user'] = $u;
    $data['flash'] = take_flash();
    $data['titre'] = $data['titre'] ?? 'StageHub';
    $data['active'] = $data['active'] ?? '';
    $data['notifCount'] = $u ? notif_count((int) $u['id']) : 0;
    extract($data);
    ob_start();
    include app_root() . '/vues/' . $name . '.php';
    $contenu = ob_get_clean();
    $layout = !empty($authLayout) ? 'auth-layout' : 'layout';
    include app_root() . '/vues/' . $layout . '.php';
}

function badge(string $kind, $statut): string
{
    $map = [
        'candidature' => [
            'en_attente' => ['En attente', 'attente'],
            'acceptee' => ['Acceptée', 'ok'],
            'refusee' => ['Refusée', 'ko'],
        ],
        'document' => [
            'en_attente' => ['À valider', 'attente'],
            'valide' => ['Validé', 'ok'],
            'rejete' => ['Rejeté', 'ko'],
        ],
        'stage' => [
            'en_cours' => ['En cours', 'attente'],
            'termine' => ['Terminé', 'ok'],
        ],
        'compte' => [
            '1' => ['Actif', 'ok'],
            '0' => ['Désactivé', 'ko'],
        ],
    ];
    $entry = $map[$kind][(string) $statut] ?? [(string) $statut, 'attente'];
    return '<span class="badge ' . $entry[1] . '">' . e($entry[0]) . '</span>';
}

function competences_de(string $table, string $fk, int $id): string
{
    $allowed = [
        'utilisateur_competences' => 'utilisateur_id',
        'offre_competences' => 'offre_id',
    ];
    if (($allowed[$table] ?? '') !== $fk) {
        return '';
    }
    $rows = q(
        "SELECT c.libelle FROM $table l JOIN competences c ON c.id = l.competence_id WHERE l.$fk = ? ORDER BY c.libelle",
        [$id]
    );
    return implode(', ', array_column($rows, 'libelle'));
}

function sync_competences(string $table, string $fk, int $id, string $raw): void
{
    $allowed = [
        'utilisateur_competences' => 'utilisateur_id',
        'offre_competences' => 'offre_id',
    ];
    if (($allowed[$table] ?? '') !== $fk) {
        return;
    }
    run("DELETE FROM $table WHERE $fk = ?", [$id]);
    $find = db()->prepare('SELECT id FROM competences WHERE libelle = ?');
    $insert = db()->prepare('INSERT INTO competences (libelle) VALUES (?) ON CONFLICT(libelle) DO NOTHING');
    $link = db()->prepare("INSERT OR IGNORE INTO $table ($fk, competence_id) VALUES (?, ?)");
    foreach (preg_split('/[,;]+/', $raw) as $part) {
        $libelle = trim($part);
        if ($libelle === '' || mb_strlen($libelle) > 60) {
            continue;
        }
        $insert->execute([$libelle]);
        $find->execute([$libelle]);
        $cid = $find->fetchColumn();
        if ($cid) {
            $link->execute([$id, $cid]);
        }
    }
}

function prendre_fichier(string $champ, bool $obligatoire, string $retour): ?array
{
    $file = $_FILES[$champ] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($obligatoire) {
            fail('Le fichier est obligatoire (PDF, DOC ou DOCX, 5 Mo maximum).', $retour);
        }
        return null;
    }
    if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
        fail('Le fichier n\'a pas pu être reçu.', $retour);
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        fail('Le fichier dépasse 5 Mo.', $retour);
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        fail('Formats acceptés : PDF, DOC, DOCX.', $retour);
    }
    $stocke = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], upload_dir() . '/' . $stocke)) {
        fail('Impossible d\'enregistrer le fichier.', $retour);
    }
    return ['stocke' => $stocke, 'nom' => basename((string) $file['name'])];
}

function chemin_fichier(string $stocke): ?string
{
    $base = basename($stocke);
    if ($base === '' || $base !== $stocke) {
        return null;
    }
    $path = upload_dir() . '/' . $base;
    return is_file($path) ? $path : null;
}

function envoyer_fichier(?string $stocke, string $nom): void
{
    $path = $stocke ? chemin_fichier($stocke) : null;
    if (!$path) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Content-Disposition: inline; filename="' . rawurlencode($nom ?: basename($path)) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function niveau_ok(string $valeur): bool
{
    return in_array($valeur, ['Insuffisant', 'Moyen', 'Bien', 'Très bien'], true);
}

function note_ok($valeur): ?float
{
    if ($valeur === '' || $valeur === null || !is_numeric($valeur)) {
        return null;
    }
    $n = (float) $valeur;
    if ($n < 0 || $n > 20) {
        return null;
    }
    return round($n, 2);
}

function evaluation_de(int $stageId, string $type): ?array
{
    return one('SELECT * FROM evaluations WHERE stage_id = ? AND type = ?', [$stageId, $type]);
}
