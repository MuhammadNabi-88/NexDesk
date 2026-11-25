<?php
$link = static function (int $p) use ($filters): string {
    $params = array_filter($filters + ['page' => $p], static fn ($v) => $v !== '' && $v !== null);
    $params['page'] = $p;
    return url('/tickets') . '?' . http_build_query($params);
};
?>
<form class="filters" method="get" action="<?= e(url('/tickets')) ?>">
  <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by title or ticket number" aria-label="Search tickets">
  <select name="status" aria-label="Status">
    <option value="">All statuses</option>
    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active only</option>
    <?php foreach (Tickets::STATUSES as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="priority" aria-label="Priority">
    <option value="">All priorities</option>
    <?php foreach (Tickets::PRIORITIES as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $filters['priority'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Filter</button>
</form>

<section class="card flush">
  <?php if ($rows): $tableRows = $rows; require __DIR__ . '/_table.php'; ?>
    <div class="pager">
      <span class="muted"><?= (int) $total ?> ticket<?= $total === 1 ? '' : 's' ?></span>
      <?php if ($pages > 1): ?>
        <nav aria-label="Pages">
          <?php if ($page > 1): ?><a class="btn small" href="<?= e($link($page - 1)) ?>">Previous</a><?php endif; ?>
          <span>Page <?= (int) $page ?> of <?= (int) $pages ?></span>
          <?php if ($page < $pages): ?><a class="btn small" href="<?= e($link($page + 1)) ?>">Next</a><?php endif; ?>
        </nav>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="empty"><p><strong>No tickets found</strong></p><p>Try other filters or open a new ticket.</p></div>
  <?php endif; ?>
</section>
