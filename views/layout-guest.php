<!doctype html>
<html lang="en">
<head><?php require __DIR__ . '/_head.php'; ?></head>
<body class="guest">
  <section class="pitch">
    <a class="brand" href="<?= e(url('/login')) ?>"><span class="mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12.5l4 4 8-9"/></svg></span>Nexdesk</a>
    <div>
      <h1>IT support that keeps its promises.</h1>
      <p>Track every request from the first message to the fix, with SLA timers, clear ownership and a full history.</p>
      <ul class="points">
        <li><b>SLA timers</b> show what is due next and what is late</li>
        <li><b>Roles</b> for customers, agents and admins</li>
        <li><b>Internal notes</b> stay hidden from customers</li>
      </ul>
    </div>
    <small>PHP 8, PDO, MySQL or SQLite. A portfolio project by Muhammad Nabi.</small>
  </section>
  <main class="panel">
    <div class="panel-inner">
      <?php require __DIR__ . '/_flash.php'; ?>
      <?= $content ?>
    </div>
  </main>
</body>
</html>
