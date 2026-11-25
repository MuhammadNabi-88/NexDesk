<?php foreach ($_SESSION['flash'] ?? [] as [$flashType, $flashMessage]): ?>
  <div class="flash <?= e($flashType) ?>" role="status"><?= e($flashMessage) ?></div>
<?php endforeach; unset($_SESSION['flash']); ?>
