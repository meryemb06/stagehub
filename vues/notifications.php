<h1>Notifications</h1>
<div class="carte">
  <?php if (!$notifications): ?>
    <p class="aide">Aucune notification.</p>
  <?php else: ?>
    <ul class="liste">
      <?php foreach ($notifications as $n): ?>
        <li>
          <?php if ($n['lien']): ?><a href="<?= e($n['lien']) ?>"><b><?= e($n['titre']) ?></b></a><?php else: ?><b><?= e($n['titre']) ?></b><?php endif; ?>
          <small><?= e($n['message']) ?> · <?= e(date_heure_fr($n['cree_le'])) ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
