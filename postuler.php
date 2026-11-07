<?php
// Traite l'envoi d'une candidature (le formulaire se trouve dans offre_detail.php)
require_once 'includes/header.php';
exiger_role('ETUDIANT');

$offre_id = (int)($_POST['offre_id'] ?? $_GET['offre_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: offre_detail.php?id=' . $offre_id);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM offres WHERE id = ? AND statut = 'OUVERTE'");
$stmt->execute([$offre_id]);
if (!$stmt->fetch()) {
    message('Offre introuvable ou fermée.', 'erreur');
    header('Location: index.php');
    exit;
}

$etudiant_id = $_SESSION['user']['id'];

// Déjà postulé ?
$deja = $pdo->prepare('SELECT id FROM candidatures WHERE offre_id = ? AND etudiant_id = ?');
$deja->execute([$offre_id, $etudiant_id]);

if ($deja->fetch()) {
    message('Vous avez déjà postulé à cette offre.', 'erreur');
} else {
    list($nom_fichier, $erreur) = enregistrer_pdf('cv', 'cv');
    list($lettre, $erreurLettre) = enregistrer_pdf('lettre', 'lettres', false);
    if ($erreur || $erreurLettre) {
        message($erreur ?: $erreurLettre, 'erreur');
    } else {
        $ins = $pdo->prepare('INSERT INTO candidatures (offre_id, etudiant_id, cv, lettre_motivation) VALUES (?, ?, ?, ?)');
        $ins->execute([$offre_id, $etudiant_id, $nom_fichier, $lettre]);
        $offre = $pdo->prepare('SELECT titre, entreprise_id FROM offres WHERE id = ?');
        $offre->execute([$offre_id]);
        $offre = $offre->fetch();
        if ($offre) {
            notifier($pdo, $offre['entreprise_id'], 'Nouvelle candidature pour « ' . $offre['titre'] . ' ».');
        }
        message('Candidature envoyée.');
        header('Location: mes_candidatures.php');
        exit;
    }
}
header('Location: offre_detail.php?id=' . $offre_id);
exit;
