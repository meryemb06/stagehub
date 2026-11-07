<?php
require_once 'includes/header.php';
exiger_role('ETUDIANT');
$stage = stage_actif($pdo, $_SESSION['user']['id']);
if (!$stage) {
    message("Vous n'avez pas encore de stage en cours.", 'erreur');
    header('Location: mon_stage.php');
    exit;
}

$req = $pdo->prepare('SELECT * FROM conventions WHERE candidature_id = ?');
$req->execute([$stage['candidature_id']]);
$convention = $req->fetch();
$peut_deposer = !$convention || $convention['statut'] === 'REFUSEE';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $peut_deposer) {
    $mode = ($_POST['mode'] ?? '') === 'pdf' ? 'pdf' : 'formulaire';
    $sujet = trim($_POST['sujet'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $debut = $_POST['date_debut'] ?? '';
    $fin = $_POST['date_fin'] ?? '';
    $contact = trim($_POST['contact_entreprise'] ?? '');

    if ($sujet === '' || $debut === '' || $fin === '' || $contact === '') {
        message('Remplissez le sujet, la période et l\'e-mail.', 'erreur');
    } elseif (!filter_var($contact, FILTER_VALIDATE_EMAIL)) {
        message('Indiquez un e-mail de contact valide.', 'erreur');
    } elseif ($fin <= $debut) {
        message('La date de fin doit être après la date de début.', 'erreur');
    } elseif ($mode === 'formulaire' && $description === '') {
        message('Décrivez le stage, ou déposez la convention en PDF.', 'erreur');
    } else {
        list($nom_fichier, $erreur) = enregistrer_pdf('fichier', 'conventions', $mode === 'pdf');
        if ($erreur) {
            message($erreur, 'erreur');
        } else {
            $sql = "INSERT INTO conventions (candidature_id, sujet, description, date_debut, date_fin, contact_entreprise, fichier)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE sujet = VALUES(sujet), description = VALUES(description),
                    date_debut = VALUES(date_debut), date_fin = VALUES(date_fin),
                    contact_entreprise = VALUES(contact_entreprise),
                    fichier = COALESCE(VALUES(fichier), fichier), statut = 'EN_COURS', commentaire = NULL";
            $pdo->prepare($sql)->execute([
                $stage['candidature_id'], $sujet, $description ?: null, $debut, $fin, $contact, $nom_fichier
            ]);
            $pdo->prepare('UPDATE stages SET date_debut = ?, date_fin = ? WHERE candidature_id = ?')
                ->execute([$debut, $fin, $stage['candidature_id']]);
            if (!empty($stage['encadrant_id'])) {
                notifier($pdo, $stage['encadrant_id'], 'Une convention a été déposée : ' . $sujet);
            }
            message('Convention envoyée à votre encadrant.');
        }
    }
    header('Location: convention.php');
    exit;
}
?>
<?php if ($convention && !$peut_deposer): ?>
    <h1 class="ecran">Convention</h1>
    <div class="carte">
        <h3><?= e($convention['sujet']) ?> <span class="badge <?= e($convention['statut']) ?>"><?= e($convention['statut']) ?></span></h3>
        <?php if (!empty($convention['description'])): ?><p><?= nl2br(e($convention['description'])) ?></p><?php endif; ?>
        <p class="meta">Du <?= e(date('d/m/Y', strtotime($convention['date_debut']))) ?>
            au <?= e(date('d/m/Y', strtotime($convention['date_fin']))) ?> — <?= e($convention['contact_entreprise']) ?></p>
        <?php if ($convention['commentaire']): ?><p><strong>Commentaire de l'encadrant :</strong> <?= nl2br(e($convention['commentaire'])) ?></p><?php endif; ?>
        <?php if ($convention['fichier']): ?>
            <a class="btn btn-contour" href="uploads/conventions/<?= e($convention['fichier']) ?>" target="_blank">Voir le PDF</a>
        <?php endif; ?>
    </div>
<?php else: ?>
<form class="formulaire" method="post" enctype="multipart/form-data">
    <div class="segment">
        <label>Saisir une convention (formulaire)
            <input type="radio" name="mode" value="formulaire" checked>
        </label>
        <label>Déposer une convention (PDF)
            <input type="radio" name="mode" value="pdf">
        </label>
    </div>
    <label for="sujet">Sujet de Stage (Titre)</label>
    <input type="text" id="sujet" name="sujet" value="<?= e($convention['sujet'] ?? '') ?>" required>
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4"><?= e($convention['description'] ?? '') ?></textarea>
    <label>Période (Date Début - Fin)</label>
    <div class="periode">
        <input type="date" name="date_debut" value="<?= e($convention['date_debut'] ?? '') ?>" required>
        <span>-</span>
        <input type="date" name="date_fin" value="<?= e($convention['date_fin'] ?? '') ?>" required>
    </div>
    <label for="contact">Contact Entreprise (Email)</label>
    <input type="email" id="contact" name="contact_entreprise" value="<?= e($convention['contact_entreprise'] ?? '') ?>" required>
    <div class="si-pdf">
        <label class="btn btn-contour bloc fichier">Choisir le PDF de la convention
            <input type="file" name="fichier" accept="application/pdf,.pdf">
            <span class="nom-fichier"></span>
        </label>
    </div>
    <button class="btn bloc" type="submit">Envoyer la convention</button>
</form>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
