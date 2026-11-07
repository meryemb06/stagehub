<?php
require_once 'includes/header.php';
exiger_role('ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Cas 1 : créer un compte encadrant
    if (isset($_POST['creer_encadrant'])) {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mdp = $_POST['mot_de_passe'] ?? '';

        if ($nom === '' || $prenom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($mdp) < 6) {
            message('Champs invalides (mot de passe : 6 caractères minimum).', 'erreur');
        } else {
            $verif = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
            $verif->execute([$email]);
            if ($verif->fetch()) {
                message('Cette adresse e-mail est déjà utilisée.', 'erreur');
            } else {
                $ins = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role)
                                      VALUES (?, ?, ?, ?, 'ENCADRANT')");
                $ins->execute([$nom, $prenom, $email, password_hash($mdp, PASSWORD_DEFAULT)]);
                message('Compte encadrant créé.');
            }
        }

    // Cas 2 : réinitialiser un mot de passe (un mot de passe temporaire est affiché à l'administrateur)
    } elseif (isset($_POST['reset_id'])) {
        $id = (int)$_POST['reset_id'];
        $temporaire = substr(bin2hex(random_bytes(4)), 0, 8);
        $upd = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
        $upd->execute([password_hash($temporaire, PASSWORD_DEFAULT), $id]);
        message('Nouveau mot de passe temporaire : ' . $temporaire . ' (à communiquer à l\'utilisateur).');

    // Cas 3 : activer ou désactiver un compte (sauf le sien)
    } else {
        $id = (int)$_POST['user_id'];
        if ($id !== (int)$_SESSION['user']['id']) {
            $upd = $pdo->prepare('UPDATE utilisateurs SET actif = 1 - actif WHERE id = ?');
            $upd->execute([$id]);
            message('Compte mis à jour.');
        }
    }
    header('Location: admin_comptes.php');
    exit;
}

$utilisateurs = $pdo->query('SELECT * FROM utilisateurs ORDER BY role, nom')->fetchAll();
?>
<h1>Gestion des comptes</h1>

<form class="formulaire" method="post" style="margin-bottom:24px">
    <h3 style="margin-top:0">Créer un compte encadrant</h3>
    <label>Nom</label><input type="text" name="nom" required>
    <label>Prénom</label><input type="text" name="prenom" required>
    <label>E-mail</label><input type="email" name="email" required>
    <label>Mot de passe</label><input type="password" name="mot_de_passe" required>
    <button class="btn" type="submit" name="creer_encadrant" value="1">Créer</button>
</form>

<table>
    <tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Actif</th><th>Action</th></tr>
    <?php foreach ($utilisateurs as $u): ?>
    <tr>
        <td><?= e($u['prenom'] . ' ' . $u['nom']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['role']) ?></td>
        <td><?= $u['actif'] ? 'Oui' : 'Non' ?></td>
        <td>
            <?php if ((int)$u['id'] !== (int)$_SESSION['user']['id']): ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button class="btn <?= $u['actif'] ? 'rouge' : 'vert' ?>" style="margin:0">
                    <?= $u['actif'] ? 'Désactiver' : 'Activer' ?>
                </button>
            </form>
            <form method="post" style="display:inline" onsubmit="return confirm('Réinitialiser le mot de passe de cet utilisateur ?');">
                <input type="hidden" name="reset_id" value="<?= (int)$u['id'] ?>">
                <button class="btn btn-clair" style="margin:0">Réinitialiser le mot de passe</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require_once 'includes/footer.php'; ?>
