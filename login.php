<?php
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($mdp, $u['mot_de_passe'])) {
        message('E-mail ou mot de passe incorrect.', 'erreur');
    } elseif (!$u['actif']) {
        message('Ce compte est désactivé. Contactez l\'administrateur.', 'erreur');
    } else {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $u['id'], 'nom' => $u['nom'],
            'prenom' => $u['prenom'], 'role' => $u['role']
        ];
        $accueil = [
            'ETUDIANT'   => 'mon_stage.php',
            'ENTREPRISE' => 'mes_offres.php',
            'ENCADRANT'  => 'encadrant_stagiaires.php',
            'ADMIN'      => 'admin_comptes.php',
        ];
        header('Location: ' . $accueil[$u['role']]);
        exit;
    }
    header('Location: login.php');
    exit;
}
?>
<h1>Connexion</h1>
<form method="post">
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required>
    <label for="mot_de_passe">Mot de passe</label>
    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
    <button class="btn bloc" type="submit">Se connecter</button>
</form>
<a class="btn btn-contour bloc" href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>
<div class="ou">OU</div>
<a class="btn btn-contour bloc" href="register.php?role=ETUDIANT">S'inscrire (Étudiant)</a>
<a class="btn btn-contour bloc" href="register.php?role=ENTREPRISE">Inscrire une entreprise</a>
<?php require_once 'includes/footer.php'; ?>
