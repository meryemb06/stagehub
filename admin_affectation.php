<?php
require_once 'includes/header.php';
exiger_role('ADMIN');

// Affecter un encadrant à un stage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stage_id = (int)($_POST['stage_id'] ?? 0);
    $encadrant_id = (int)($_POST['encadrant_id'] ?? 0);

    // On vérifie que l'utilisateur choisi est bien un encadrant actif
    $verif = $pdo->prepare("SELECT id FROM utilisateurs WHERE id = ? AND role = 'ENCADRANT' AND actif = 1");
    $verif->execute([$encadrant_id]);

    if ($verif->fetch()) {
        $upd = $pdo->prepare('UPDATE stages SET encadrant_id = ? WHERE id = ?');
        $upd->execute([$encadrant_id, $stage_id]);
        message('Encadrant affecté.');
    } else {
        message('Choisissez un encadrant valide.', 'erreur');
    }
    header('Location: admin_affectation.php');
    exit;
}

$encadrants = $pdo->query("SELECT id, nom, prenom FROM utilisateurs
                           WHERE role = 'ENCADRANT' AND actif = 1 ORDER BY nom")->fetchAll();

$stages = $pdo->query("SELECT s.id, s.encadrant_id, s.statut,
                              etu.nom AS etu_nom, etu.prenom AS etu_prenom, etu.filiere,
                              o.titre, ent.raison_sociale,
                              enc.nom AS enc_nom, enc.prenom AS enc_prenom
                       FROM stages s
                       JOIN candidatures c ON c.id = s.candidature_id
                       JOIN utilisateurs etu ON etu.id = c.etudiant_id
                       JOIN offres o ON o.id = c.offre_id
                       JOIN utilisateurs ent ON ent.id = o.entreprise_id
                       LEFT JOIN utilisateurs enc ON enc.id = s.encadrant_id
                       ORDER BY s.id DESC")->fetchAll();
?>
<h1>Affecter un encadrant</h1>

<?php if (!$stages): ?>
    <p>Aucun stage pour le moment. Un stage est créé quand une entreprise accepte une candidature.</p>
<?php else: ?>
<table>
    <tr><th>Stagiaire</th><th>Filière</th><th>Stage</th><th>Encadrant actuel</th><th>Affecter</th></tr>
    <?php foreach ($stages as $s): ?>
    <tr>
        <td><?= e($s['etu_prenom'] . ' ' . $s['etu_nom']) ?></td>
        <td><?= e($s['filiere']) ?></td>
        <td><?= e($s['titre']) ?> (<?= e($s['raison_sociale']) ?>)</td>
        <td><?= $s['encadrant_id'] ? e($s['enc_prenom'] . ' ' . $s['enc_nom']) : '<em>Non affecté</em>' ?></td>
        <td>
            <form method="post">
                <input type="hidden" name="stage_id" value="<?= (int)$s['id'] ?>">
                <select name="encadrant_id" required>
                    <option value="">Choisir...</option>
                    <?php foreach ($encadrants as $enc): ?>
                        <option value="<?= (int)$enc['id'] ?>" <?= $enc['id'] == $s['encadrant_id'] ? 'selected' : '' ?>>
                            <?= e($enc['prenom'] . ' ' . $enc['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" style="margin-top:6px" type="submit">Valider</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
