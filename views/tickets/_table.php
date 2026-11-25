<div class="table-wrap">
  <table>
    <thead>
      <tr><th>Ticket</th><th>Priority</th><th>Status</th><th>SLA</th><th>Assignee</th><th>Updated</th></tr>
    </thead>
    <tbody>
      <?php foreach ($tableRows as $t): [$slaText, $slaClass] = sla_label($t); ?>
        <tr>
          <td>
            <a class="ticket-link" href="<?= e(url('/tickets/' . $t['id'])) ?>"><?= e($t['title']) ?></a>
            <small>#<?= (int) $t['id'] ?> · <?= e(Tickets::CATEGORIES[$t['category']] ?? $t['category']) ?> · <?= e($t['requester_name']) ?></small>
          </td>
          <td><?= priority_pill($t['priority']) ?></td>
          <td><?= status_pill($t['status']) ?></td>
          <td><span class="sla <?= e($slaClass) ?>"><?= e($slaText) ?></span></td>
          <td><?= $t['assignee_name'] ? e($t['assignee_name']) : '<span class="muted">Unassigned</span>' ?></td>
          <td class="muted nowrap"><?= e(time_ago($t['updated_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
