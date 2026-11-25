<div class="empty big">
  <p class="code"><?= (int) $code ?></p>
  <h2><?= e($title) ?></h2>
  <p><?= e($message !== '' ? $message : 'Something went wrong.') ?></p>
  <a class="btn primary" href="<?= e(url('/dashboard')) ?>">Back to the dashboard</a>
</div>
