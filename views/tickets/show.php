<?php [$slaText, $slaClass] = sla_label($ticket); ?>
<div class="detail">
  <div class="detail-main">
    <article class="card">
      <div class="pills"><?= status_pill($ticket['status']) ?><?= priority_pill($ticket['priority']) ?><span class="sla <?= e($slaClass) ?>"><?= e($slaText) ?></span></div>
      <h2 class="big-title"><?= e($ticket['title']) ?></h2>
      <p class="desc"><?= nl2br(e($ticket['description'])) ?></p>
    </article>

    <section class="card">
      <h2>Conversation and history</h2>
      <ol class="timeline">
        <?php foreach ($events as $ev): ?>
          <?php if (in_array($ev['type'], ['comment', 'internal'], true)): ?>
            <li class="msg <?= $ev['type'] === 'internal' ? 'internal' : '' ?>">
              <span class="avatar sm"><?= e(initials($ev['user_name'])) ?></span>
              <div>
                <header><strong><?= e($ev['user_name']) ?></strong>
                  <?php if ($ev['user_role'] !== 'customer'): ?><span class="tag">Staff</span><?php endif; ?>
                  <?php if ($ev['type'] === 'internal'): ?><span class="tag lock">Internal note</span><?php endif; ?>
                  <time class="muted"><?= e(time_ago($ev['created_at'])) ?></time></header>
                <p><?= nl2br(e($ev['body'])) ?></p>
              </div>
            </li>
          <?php else: ?>
            <li class="sys"><span class="dot"></span><span><?= e($ev['body']) ?> by <?= e($ev['user_name']) ?></span><time class="muted"><?= e(time_ago($ev['created_at'])) ?></time></li>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$events): ?><li class="sys"><span class="dot"></span><span>No replies yet.</span></li><?php endif; ?>
      </ol>

      <?php if ($ticket['status'] !== 'closed'): ?>
        <form method="post" action="<?= e(url('/tickets/' . $ticket['id'] . '/comment')) ?>" class="form reply">
          <?= csrf_field() ?>
          <label>Your reply<textarea name="body" rows="4" required maxlength="3000" placeholder="Write a message…"></textarea></label>
          <div class="actions between">
            <?php if ($isStaff): ?><label class="check"><input type="checkbox" name="internal" value="1"> Internal note (hidden from the customer)</label><?php else: ?><span></span><?php endif; ?>
            <button class="btn primary" type="submit">Send</button>
          </div>
        </form>
      <?php else: ?>
        <p class="muted">This ticket is closed.</p>
      <?php endif; ?>
    </section>
  </div>

  <aside class="detail-side">
    <section class="card">
      <h2>Details</h2>
      <dl class="facts">
        <div><dt>Requester</dt><dd><?= e($ticket['requester_name']) ?></dd></div>
        <div><dt>Assignee</dt><dd><?= $ticket['assignee_name'] ? e($ticket['assignee_name']) : 'Unassigned' ?></dd></div>
        <div><dt>Category</dt><dd><?= e(Tickets::CATEGORIES[$ticket['category']] ?? $ticket['category']) ?></dd></div>
        <div><dt>Created</dt><dd><?= e(date('d M Y, H:i', strtotime($ticket['created_at']))) ?></dd></div>
        <div><dt>Due (SLA)</dt><dd><?= e(date('d M Y, H:i', strtotime($ticket['due_at']))) ?></dd></div>
      </dl>
    </section>

    <?php if ($isStaff): ?>
      <section class="card">
        <h2>Manage ticket</h2>
        <form method="post" action="<?= e(url('/tickets/' . $ticket['id'] . '/update')) ?>" class="form">
          <?= csrf_field() ?>
          <label>Status
            <select name="status">
              <?php foreach (Tickets::STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= $ticket['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
          </label>
          <label>Priority
            <select name="priority">
              <?php foreach (Tickets::PRIORITIES as $key => $label): ?><option value="<?= e($key) ?>" <?= $ticket['priority'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
          </label>
          <label>Assignee
            <select name="assignee_id">
              <option value="">Unassigned</option>
              <?php foreach ($agents as $a): ?><option value="<?= (int) $a['id'] ?>" <?= (int) $ticket['assignee_id'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
            </select>
          </label>
          <button class="btn primary block" type="submit">Save changes</button>
        </form>
      </section>
    <?php endif; ?>
  </aside>
</div>
