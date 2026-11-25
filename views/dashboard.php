<?php
$max = max(1, ...array_column($stats['days'], 'count'));
$activeTotal = max(1, array_sum($stats['by_priority']));
?>
<section class="kpis">
  <article class="kpi"><span>Active tickets</span><strong><?= (int) $stats['active'] ?></strong><small>open, in progress or waiting</small></article>
  <article class="kpi <?= $stats['overdue'] > 0 ? 'alert' : '' ?>"><span>Past SLA</span><strong><?= (int) $stats['overdue'] ?></strong><small>active tickets that are overdue</small></article>
  <article class="kpi"><span>Resolved</span><strong><?= (int) $stats['done'] ?></strong><small>resolved or closed</small></article>
  <?php if ($stats['mine'] !== null): ?>
    <article class="kpi"><span>Assigned to me</span><strong><?= (int) $stats['mine'] ?></strong><small>active tickets</small></article>
  <?php else: ?>
    <article class="kpi"><span>All my tickets</span><strong><?= (int) $stats['total'] ?></strong><small>since you joined</small></article>
  <?php endif; ?>
</section>

<section class="two">
  <article class="card">
    <h2>Created in the last 7 days</h2>
    <div class="bars">
      <?php foreach ($stats['days'] as $d): ?>
        <div class="bar"><span class="val"><?= (int) $d['count'] ?></span><i style="height: <?= max(6, (int) round($d['count'] / $max * 150)) ?>px"></i><small><?= e($d['label']) ?></small></div>
      <?php endforeach; ?>
    </div>
  </article>
  <article class="card">
    <h2>Active tickets by priority</h2>
    <ul class="hbars">
      <?php foreach (array_reverse(Tickets::PRIORITIES, true) as $key => $label): $n = $stats['by_priority'][$key]; ?>
        <li><span><?= e($label) ?></span><div class="track"><i class="p-<?= e($key) ?>" style="width: <?= (int) round($n / $activeTotal * 100) ?>%"></i></div><b><?= (int) $n ?></b></li>
      <?php endforeach; ?>
    </ul>
    <p class="muted small">SLA targets: critical 4 h, high 8 h, medium 24 h, low 72 h.</p>
  </article>
</section>

<section class="card flush">
  <div class="card-head"><h2>Latest activity</h2><a href="<?= e(url('/tickets')) ?>">View all tickets</a></div>
  <?php if ($recent): $tableRows = $recent; require __DIR__ . '/tickets/_table.php'; else: ?>
    <div class="empty"><p>No tickets yet.</p><a class="btn primary" href="<?= e(url('/tickets/new')) ?>">Open the first ticket</a></div>
  <?php endif; ?>
</section>
