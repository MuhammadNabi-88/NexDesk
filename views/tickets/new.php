<section class="card narrow">
  <h2>Describe the problem</h2>
  <p class="muted">The priority sets the response time (SLA). Pick the one that matches the impact on your work.</p>

  <form method="post" action="<?= e(url('/tickets')) ?>" class="form">
    <?= csrf_field() ?>
    <label>Title<input type="text" name="title" value="<?= e(old('title')) ?>" required minlength="5" maxlength="160" placeholder="Short summary, for example: VPN drops every 10 minutes"></label>
    <div class="row">
      <label>Category
        <select name="category" required>
          <option value="">Choose…</option>
          <?php foreach (Tickets::CATEGORIES as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= old('category') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Priority
        <select name="priority" required>
          <?php foreach (Tickets::PRIORITIES as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= (old('priority', 'medium') === $key) ? 'selected' : '' ?>><?= e($label) ?> (<?= (int) Tickets::SLA_HOURS[$key] ?> h)</option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <label>Description<textarea name="description" rows="7" required minlength="10" maxlength="5000" placeholder="What happened, since when, and what have you tried already?"><?= e(old('description')) ?></textarea></label>
    <div class="actions">
      <a class="btn" href="<?= e(url('/tickets')) ?>">Cancel</a>
      <button class="btn primary" type="submit">Create ticket</button>
    </div>
  </form>
</section>
