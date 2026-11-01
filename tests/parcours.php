<?php

$root = dirname(__DIR__);
$dir = sys_get_temp_dir() . '/stagehub-' . getmypid();
mkdir($dir);
mkdir($dir . '/uploads');
$port = 8765;
$cmd = sprintf(
    'DB_PATH=%s UPLOADS_DIR=%s php -S 127.0.0.1:%d -t %s %s',
    escapeshellarg($dir . '/test.sqlite'),
    escapeshellarg($dir . '/uploads'),
    $port,
    escapeshellarg($root . '/public'),
    escapeshellarg($root . '/public/router.php')
);
$proc = proc_open($cmd, [1 => ['file', $dir . '/out.log', 'w'], 2 => ['file', $dir . '/err.log', 'w']], $pipes, $root);
$base = "http://127.0.0.1:$port";
for ($i = 0; $i < 40; $i++) {
    $ping = @file_get_contents($base . '/');
    if ($ping !== false) {
        break;
    }
    usleep(100000);
}

$fail = 0;
function check(bool $ok, string $message): void
{
    global $fail;
    if ($ok) {
        echo "ok  $message\n";
        return;
    }
    $fail++;
    echo "FAIL $message\n";
}

function http(string $url, array $opts = []): array
{
    $cookie = $opts['cookie'] ?? tempnam(sys_get_temp_dir(), 'ck');
    $headers = ['content-type: application/x-www-form-urlencoded'];
    $cmd = ['curl', '-sS', '-D', '-', '-o', $opts['body'] ?? '/tmp/stagehub-body.html', '-b', $cookie, '-c', $cookie];
    if (!empty($opts['form'])) {
        foreach ($opts['form'] as $k => $v) {
            $cmd[] = '--data-urlencode';
            $cmd[] = $k . '=' . $v;
        }
    }
    if (!empty($opts['multipart'])) {
        foreach ($opts['multipart'] as $k => $v) {
            $cmd[] = '-F';
            $cmd[] = $k . '=' . $v;
        }
    }
    if (empty($opts['follow'])) {
        $cmd[] = '--max-redirs';
        $cmd[] = '0';
    } else {
        $cmd[] = '-L';
    }
    $cmd[] = $url;
    $spec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $p = proc_open($cmd, $spec, $pipes);
    $raw = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($p);
    preg_match_all('/^HTTP\/\d(?:\.\d)?\s+(\d+)/m', $raw, $codes);
    $status = (int) (end($codes[1]) ?: 0);
    $bodyFile = $opts['body'] ?? '/tmp/stagehub-body.html';
    $body = is_file($bodyFile) ? file_get_contents($bodyFile) : '';
    return ['status' => $status, 'headers' => $raw, 'body' => $body, 'cookie' => $cookie];
}

function csrf(string $html): string
{
    preg_match('/name="_csrf" value="([^"]+)"/', $html, $m);
    return $m[1] ?? '';
}

function login(string $base, string $email, string $password): array
{
    $home = http($base . '/', ['follow' => true]);
    return http($base . '/connexion', [
        'cookie' => $home['cookie'],
        'follow' => true,
        'form' => ['_csrf' => csrf($home['body']), 'email' => $email, 'mot_de_passe' => $password],
    ]);
}

$home = http($base . '/', ['follow' => true]);
check($home['status'] === 200 && str_contains($home['body'], 'StageHub'), 'accueil StageHub');
check(str_contains($home['body'], 'youssef.benali@stagehub.ma'), 'compte démo étudiant');

$bloque = login($base, 'bennani@stagehub.ma', 'Stagehub1');
check(str_contains($bloque['body'], 'désactivé'), 'compte désactivé refusé');

$etu = login($base, 'youssef.benali@stagehub.ma', 'Stagehub1');
check(str_contains($etu['body'], 'Développeur web PHP'), 'catalogue PHP');
check(!str_contains($etu['body'], 'Support technique'), 'offre retirée absente du catalogue');
$filtre = http($base . '/offres?ville=' . rawurlencode('Tanger') . '&recherche=PHP', ['cookie' => $etu['cookie'], 'follow' => true]);
check(str_contains($filtre['body'], 'Développeur web PHP') && !str_contains($filtre['body'], 'Designer UI/UX'), 'filtre ville et mot-clé');
$hist = http($base . '/historique', ['cookie' => $etu['cookie'], 'follow' => true]);
check(str_contains($hist['body'], 'Support technique') && str_contains($hist['body'], '15/20'), 'historique et note encadrant');

$ins = http($base . '/inscription', ['follow' => true]);
$compte = http($base . '/inscription', [
    'cookie' => $ins['cookie'],
    'follow' => true,
    'form' => [
        '_csrf' => csrf($ins['body']),
        'role' => 'etudiant',
        'nom' => 'Test',
        'prenom' => 'Nora',
        'filiere' => 'Informatique',
        'email' => 'nora.test@example.com',
        'mot_de_passe' => 'Stagehub1',
    ],
]);
check(str_contains($compte['body'], 'Bienvenue'), 'inscription étudiante');

$ent = login($base, 'contact@technord.ma', 'Stagehub1');
$nouvelle = http($base . '/entreprise/offres/nouvelle', ['cookie' => $ent['cookie'], 'follow' => true]);
check(str_contains($nouvelle['body'], 'Publier une offre'), 'formulaire d\'offre');
$pdf = $dir . '/presentation.pdf';
file_put_contents($pdf, "%PDF-1.1\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
$creee = http($base . '/entreprise/offres', [
    'cookie' => $ent['cookie'],
    'follow' => true,
    'multipart' => [
        '_csrf' => csrf($nouvelle['body']),
        'titre' => 'Stage test automatisé',
        'description' => 'Participation aux tests d\'une application de gestion.',
        'missions' => "Écrire des scénarios\nExécuter les tests",
        'competences' => 'Tests, SQL',
        'duree_mois' => '2',
        'ville' => 'Fès',
        'document' => '@' . $pdf . ';type=application/pdf',
    ],
]);
check(str_contains($creee['body'], 'Offre publiée') && str_contains($creee['body'], 'Stage test automatisé'), 'offre publiée');

$nora = login($base, 'nora.test@example.com', 'Stagehub1');
preg_match('/href="\/offres\/(\d+)"[^>]*>Consulter/', $nora['body'], $idOffre);
$offreId = $idOffre[1] ?? '';
$postuler = http($base . '/offres/' . $offreId . '/postuler', ['cookie' => $nora['cookie'], 'follow' => true]);
$cv = $dir . '/cv.pdf';
$conv = $dir . '/convention.pdf';
file_put_contents($cv, "%PDF-1.1\n%%EOF\n");
file_put_contents($conv, "%PDF-1.1\n%%EOF\n");
$envoyee = http($base . '/offres/' . $offreId . '/postuler', [
    'cookie' => $nora['cookie'],
    'follow' => true,
    'multipart' => [
        '_csrf' => csrf($postuler['body']),
        'lettre' => 'Je souhaite rejoindre ce stage pour mettre en pratique les tests et le SQL vus en cours.',
        'cv' => '@' . $cv . ';type=application/pdf',
        'convention' => '@' . $conv . ';type=application/pdf',
    ],
]);
check(str_contains($envoyee['body'], 'Candidature envoyée'), 'candidature envoyée');

$tech = login($base, 'contact@technord.ma', 'Stagehub1');
$recues = http($base . '/entreprise/candidatures?offre=' . $offreId, ['cookie' => $tech['cookie'], 'follow' => true]);
preg_match('/candidatures\/(\d+)/', $recues['body'], $idCand);
$dossier = http($base . '/entreprise/candidatures/' . ($idCand[1] ?? '0'), ['cookie' => $tech['cookie'], 'follow' => true]);
check(str_contains($dossier['body'], 'mettre en pratique les tests'), 'dossier de candidature');
$decision = http($base . '/entreprise/candidatures/' . ($idCand[1] ?? '0') . '/decision', [
    'cookie' => $tech['cookie'],
    'follow' => true,
    'form' => ['_csrf' => csrf($dossier['body']), 'decision' => 'acceptee'],
]);
check(str_contains($decision['body'], 'Candidature acceptée'), 'acceptation');

$admin = login($base, 'admin@stagehub.ma', 'Stagehub1');
$affect = http($base . '/admin/affectations', ['cookie' => $admin['cookie'], 'follow' => true]);
preg_match('/nora\.test@example\.com[\s\S]{0,800}name="stage_id" value="(\d+)"/', $affect['body'], $idStage);
preg_match('/option value="(\d+)"[^>]*>Pr\. Idrissi/', $affect['body'], $idEnc);
$fait = http($base . '/admin/affectations', [
    'cookie' => $admin['cookie'],
    'follow' => true,
    'form' => ['_csrf' => csrf($affect['body']), 'stage_id' => $idStage[1] ?? '', 'encadrant_id' => $idEnc[1] ?? ''],
]);
check(str_contains($fait['body'], 'Pr. Idrissi'), 'encadrant affecté');
$pdfRes = http($base . '/admin/statistiques/pdf', ['cookie' => $admin['cookie']]);
check(str_starts_with($pdfRes['body'], '%PDF'), 'export PDF');
$xlsx = http($base . '/admin/statistiques/excel', ['cookie' => $admin['cookie']]);
check(str_starts_with($xlsx['body'], 'PK'), 'export Excel');

$docs = http($base . '/documents', ['cookie' => $nora['cookie'], 'follow' => true]);
check(str_contains($docs['body'], 'Convention'), 'espace documents');
$rapport = $dir . '/rapport.pdf';
file_put_contents($rapport, "%PDF-1.1\n%%EOF\n");
preg_match('/name="stage_id" value="(\d+)"/', $docs['body'], $stageNora);
$depose = http($base . '/documents/rapport', [
    'cookie' => $nora['cookie'],
    'follow' => true,
    'multipart' => [
        '_csrf' => csrf($docs['body']),
        'stage_id' => $stageNora[1] ?? '',
        'fichier' => '@' . $rapport . ';type=application/pdf',
    ],
]);
check(str_contains($depose['body'], 'Rapport déposé'), 'rapport déposé');

$journal = http($base . '/suivi', ['cookie' => $nora['cookie'], 'follow' => true]);
$ajoute = http($base . '/suivi/journal', [
    'cookie' => $nora['cookie'],
    'follow' => true,
    'form' => [
        '_csrf' => csrf($journal['body']),
        'stage_id' => $stageNora[1] ?? '',
        'date_entree' => date('Y-m-d'),
        'taches' => 'Prise en main du dépôt et lecture du module.',
        'difficultes' => '',
    ],
]);
check(str_contains($ajoute['body'], 'Entrée ajoutée'), 'journal de bord');

$enc = login($base, 'idrissi@stagehub.ma', 'Stagehub1');
$liste = http($base . '/encadrant/stagiaires', ['cookie' => $enc['cookie'], 'follow' => true]);
check(str_contains($liste['body'], 'Nora Test'), 'stagiaire assigné visible');
$validation = http($base . '/encadrant/documents', ['cookie' => $enc['cookie'], 'follow' => true]);
preg_match('/documents\/(\d+)\/rejeter/', $validation['body'], $docId);
$rejet = http($base . '/encadrant/documents/' . ($docId[1] ?? '0') . '/rejeter', [
    'cookie' => $enc['cookie'],
    'follow' => true,
    'form' => ['_csrf' => csrf($validation['body']), 'commentaire' => 'Ajoute le sommaire.'],
]);
check(str_contains($rejet['body'], 'Document rejeté'), 'rejet avec commentaire');

$docs2 = http($base . '/documents', ['cookie' => $nora['cookie'], 'follow' => true]);
check(str_contains($docs2['body'], 'Ajoute le sommaire'), 'motif visible par l\'étudiant');
$redepose = http($base . '/documents/rapport', [
    'cookie' => $nora['cookie'],
    'follow' => true,
    'multipart' => [
        '_csrf' => csrf($docs2['body']),
        'stage_id' => $stageNora[1] ?? '',
        'fichier' => '@' . $rapport . ';type=application/pdf',
    ],
]);
$validation2 = http($base . '/encadrant/documents', ['cookie' => $enc['cookie'], 'follow' => true]);
preg_match('/documents\/(\d+)\/valider/', $validation2['body'], $doc2);
$valide = http($base . '/encadrant/documents/' . ($doc2[1] ?? '0') . '/valider', [
    'cookie' => $enc['cookie'],
    'follow' => true,
    'form' => ['_csrf' => csrf($validation2['body'])],
]);
check(str_contains($valide['body'], 'Document validé'), 'document validé');

$fiche = http($base . '/encadrant/stages/' . ($stageNora[1] ?? '0'), ['cookie' => $enc['cookie'], 'follow' => true]);
check(str_contains($fiche['body'], 'Prise en main du dépôt'), 'journal lu par l\'encadrant');
$note = http($base . '/encadrant/stages/' . ($stageNora[1] ?? '0') . '/note', [
    'cookie' => $enc['cookie'],
    'follow' => true,
    'form' => ['_csrf' => csrf($fiche['body']), 'note' => '17', 'appreciation' => 'Très bon travail de test.'],
]);
check(str_contains($note['body'], 'Note enregistrée'), 'note encadrant');
$evalPage = http($base . '/entreprise/stages/' . ($stageNora[1] ?? '0') . '/evaluation', ['cookie' => $tech['cookie'], 'follow' => true]);
$eval = http($base . '/entreprise/stages/' . ($stageNora[1] ?? '0') . '/evaluation', [
    'cookie' => $tech['cookie'],
    'follow' => true,
    'form' => [
        '_csrf' => csrf($evalPage['body']),
        'assiduite' => 'Bien',
        'competences_tech' => 'Très bien',
        'autonomie' => 'Bien',
        'communication' => 'Bien',
        'qualite' => 'Très bien',
        'note' => '16',
        'commentaire' => 'Bonne implication.',
    ],
]);
check(str_contains($eval['body'], 'Évaluation enregistrée'), 'grille entreprise');
$cloture = http($base . '/encadrant/stages/' . ($stageNora[1] ?? '0') . '/cloturer', [
    'cookie' => $enc['cookie'],
    'follow' => true,
    'form' => ['_csrf' => csrf($note['body'])],
]);
check(str_contains($cloture['body'], 'Stage clôturé'), 'clôture');
$passe = http($base . '/historique', ['cookie' => $nora['cookie'], 'follow' => true]);
check(str_contains($passe['body'], 'Stage test automatisé') && str_contains($passe['body'], '17/20') && str_contains($passe['body'], '16/20'), 'historique des notes');

$vol = http($base . '/fichiers/document/1', ['cookie' => $nora['cookie']]);
check($vol['status'] === 403, 'fichier d\'un autre stagiaire refusé');

$profil = http($base . '/profil', ['cookie' => $nora['cookie'], 'follow' => true]);
$maj = http($base . '/profil', [
    'cookie' => $nora['cookie'],
    'follow' => true,
    'form' => [
        '_csrf' => csrf($profil['body']),
        'nom' => 'Test',
        'prenom' => 'Nora',
        'filiere' => 'Génie logiciel',
        'competences' => 'PHP, SQL',
    ],
]);
check(str_contains($maj['body'], 'Profil mis à jour') && str_contains($maj['body'], 'Génie logiciel'), 'profil et compétences');

$oubli = http($base . '/mot-de-passe-oublie', ['follow' => true]);
http($base . '/mot-de-passe-oublie', [
    'cookie' => $oubli['cookie'],
    'follow' => true,
    'form' => ['_csrf' => csrf($oubli['body']), 'email' => 'nora.test@example.com'],
]);
$comptes = http($base . '/admin/comptes', ['cookie' => $admin['cookie'], 'follow' => true]);
check(str_contains($comptes['body'], 'nora.test@example.com'), 'demande de réinitialisation');

proc_terminate($proc);
proc_close($proc);
echo $fail === 0 ? "PARCOURS OK\n" : "ÉCHECS $fail\n";
exit($fail === 0 ? 0 : 1);
