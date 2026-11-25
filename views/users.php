<section class="card flush">
  <div class="table-wrap">
    <table>
      <thead><tr><th>User</th><th>Tickets opened</th><th>Joined</th><th>Role</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): $self = (int) $u['id'] === (int) Auth::user()['id']; ?>
          <tr>
            <td><strong><?= e($u['name']) ?></strong><small><?= e($u['email']) ?></small></td>
            <td><?= (int) $u['ticket_count'] ?></td>
            <td class="muted nowrap"><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
            <td>
              <form method="post" action="<?= e(url('/users/' . $u['id'] . '/role')) ?>" class="inline">
                <?= csrf_field() ?>
                <select name="role" aria-label="Role for <?= e($u['name']) ?>" <?= $self ? 'disabled' : '' ?>>
                  <?php foreach (['customer', 'agent', 'admin'] as $r): ?><option value="<?= e($r) ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= e(ucfirst($r)) ?></option><?php endforeach; ?>
                </select>
                <?php if (!$self): ?><button class="btn small" type="submit">Save</button><?php else: ?><span class="muted small">You</span><?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
