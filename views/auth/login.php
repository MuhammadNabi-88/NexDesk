<h2>Sign in</h2>
<p class="muted">Welcome back. Enter your details to continue.</p>

<form method="post" action="<?= e(url('/login')) ?>" class="form">
  <?= csrf_field() ?>
  <label>Email<input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email"></label>
  <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
  <button class="btn primary block" type="submit">Sign in</button>
</form>

<?php if ($demoMode): ?>
  <div class="demo">
    <p><strong>Try the live demo</strong> with one click, no password needed.</p>
    <div class="demo-grid">
      <?php foreach (['customer' => 'Opens and follows own tickets', 'agent' => 'Works tickets, adds internal notes', 'admin' => 'Sees everything, manages users'] as $role => $text): ?>
        <form method="post" action="<?= e(url('/demo-login')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="role" value="<?= e($role) ?>">
          <button class="demo-btn" type="submit"><strong><?= e(ucfirst($role)) ?></strong><span><?= e($text) ?></span></button>
        </form>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<p class="muted center">No account yet? <a href="<?= e(url('/register')) ?>">Create one</a></p>
