<h2>Create your account</h2>
<p class="muted">Customers can open tickets and follow their progress.</p>

<form method="post" action="<?= e(url('/register')) ?>" class="form">
  <?= csrf_field() ?>
  <label>Full name<input type="text" name="name" value="<?= e(old('name')) ?>" required maxlength="80" autocomplete="name"></label>
  <label>Email<input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email"></label>
  <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"><small>At least 8 characters.</small></label>
  <button class="btn primary block" type="submit">Create account</button>
</form>

<p class="muted center">Already registered? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
