<?php
require_once 'includes/header.php';

// Le rôle vient du lien cliqué (étudiant ou entreprise)
$role = $_POST['role'] ?? $_GET['role'] ?? 'ETUDIANT';
if (!in_array($role, ['ETUDIANT', 'ENTREPRISE'])) {
    $role = 'ETUDIANT';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';
    $filiere = trim($_POST['filiere'] ?? '');
    $raison = trim($_POST['raison_sociale'] ?? '');

    if ($nom === '' || $prenom === '' || $email === '' || strlen($mdp) < 6) {
        message('Remplissez tous les champs (mot de passe : 6 caractères minimum).', 'erreur');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        message("L'adresse e-mail n'est pas valide.", 'erreur');
    } elseif ($role === 'ENTREPRISE' && $raison === '') {
        message('Indiquez la raison sociale de votre entreprise.', 'erreur');
    } else {
        $verif = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
        $verif->execute([$email]);
        if ($verif->fetch()) {
            message('Cette adresse e-mail est déjà utilisée.', 'erreur');
        } else {
            $ins = $pdo->prepare('INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, filiere, raison_sociale)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)');
            $ins->execute([$nom, $prenom, $email, password_hash($mdp, PASSWORD_DEFAULT), $role,
                           $filiere ?: null, $raison ?: null]);
            message('Compte créé. Vous pouvez vous connecter.');
            header('Location: login.php');
            exit;
        }
    }
    header('Location: register.php?role=' . $role);
    exit;
}
?>
<h1><?= $role === 'ENTREPRISE' ? 'Inscrire une entreprise' : 'Inscription étudiant' ?></h1>
        <form method="post">
            <input type="hidden" name="role" value="<?= e($role) ?>">
            <div class="deux">
                <div><label>Nom</label><input type="text" name="nom" required></div>
                <div><label>Prénom</label><input type="text" name="prenom" required></div>
            </div>
            <label>E-mail</label><input type="email" name="email" autocomplete="off" required>
            <label>Mot de passe (6 caractères minimum)</label><input type="password" name="mot_de_passe" autocomplete="new-password" required>
            <?php if ($role === 'ENTREPRISE'): ?>
                <label>Raison sociale</label><input type="text" name="raison_sociale" required>
            <?php else: ?>
                <label>Filière</label><input type="text" name="filiere">
            <?php endif; ?>
            <button class="btn bloc" type="submit">Créer mon compte</button>
        </form>
        <div class="ou">OU</div>
        <a class="btn btn-contour bloc" href="login.php">J'ai déjà un compte</a>
<?php require_once 'includes/footer.php'; ?>
