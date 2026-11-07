<?php
require_once 'includes/header.php';
if (!est_connecte()) {
    header('Location: login.php');
    exit;
}
$uid = $_SESSION['user']['id'];
$req = $pdo->prepare('SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY date_creation DESC, id DESC');
$req->execute([$uid]);
$liste = $req->fetchAll();
$pdo->prepare('UPDATE notifications SET lu = 1 WHERE utilisateur_id = ? AND lu = 0')->execute([$uid]);
?>
<h1 class="ecran">Notifications</h1>
<?php if (!$liste): ?><p class="meta">Aucune notification.</p><?php endif; ?>
<?php foreach ($liste as $n): ?>
    <div class="notif<?= $n['lu'] ? '' : ' non-lue' ?>">
        <strong><?= e($n['message']) ?></strong>
        <div class="meta"><?= e(date('d/m/Y H:i', strtotime($n['date_creation']))) ?></div>
    </div>
<?php endforeach; ?>
<?php require_once 'includes/footer.php'; ?>
