<?php
// À ouvrir UNE SEULE FOIS dans le navigateur, puis à supprimer.
require_once 'config.php';

$comptes = [
    ['Admin',     'StageHub', 'admin@stagehub.ma',     'admin123',     'ADMIN'],
    ['Encadrant', 'Test',     'encadrant@stagehub.ma', 'encadrant123', 'ENCADRANT'],
];

foreach ($comptes as $c) {
    $verif = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
    $verif->execute([$c[2]]);
    if ($verif->fetch()) {
        echo 'Déjà existant : ' . e($c[2]) . '<br>';
        continue;
    }
    $ins = $pdo->prepare('INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$c[0], $c[1], $c[2], password_hash($c[3], PASSWORD_DEFAULT), $c[4]]);
    echo 'Créé : ' . e($c[2]) . '<br>';
}
echo '<p><strong>Terminé. Supprimez maintenant le fichier creer_comptes_test.php.</strong></p>';
