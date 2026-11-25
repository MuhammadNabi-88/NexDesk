<?php $me = Auth::user(); $active = $active ?? ''; ?>
<!doctype html>
<html lang="en">
<head><?php require __DIR__ . '/_head.php'; ?></head>
<body class="app">
  <div class="shell">
    <aside class="side" id="side">
      <a class="brand" href="<?= e(url('/dashboard')) ?>"><span class="mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12.5l4 4 8-9"/></svg></span>Nexdesk</a>
      <nav aria-label="Main">
        <a href="<?= e(url('/dashboard')) ?>" class="<?= $active === 'dashboard' ? 'on' : '' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9zm0 7h6v-5H4v5zm10 0h6v-9h-6v9zm0-16v5h6V4h-6z"/></svg>Dashboard</a>
        <a href="<?= e(url('/tickets')) ?>" class="<?= $active === 'tickets' ? 'on' : '' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h10v2H4v-2z"/></svg>Tickets</a>
        <a href="<?= e(url('/tickets/new')) ?>" class="<?= $active === 'new' ? 'on' : '' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5z"/></svg>New ticket</a>
        <?php if ($me['role'] === 'admin'): ?>
          <a href="<?= e(url('/users')) ?>" class="<?= $active === 'users' ? 'on' : '' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zm0 2c-3 0-6 1.5-6 4v2h12v-2c0-2.5-3-4-6-4zm7-2a3 3 0 100-6 3 3 0 000 6zm1 2.2c2.4.4 4 1.7 4 3.8v2h-4v-2c0-1.4-.4-2.6-1.2-3.5.4-.2.8-.3 1.2-.3z"/></svg>Users</a>
        <?php endif; ?>
      </nav>
      <div class="me">
        <span class="avatar"><?= e(initials($me['name'])) ?></span>
        <div><strong><?= e($me['name']) ?></strong><small><?= e(ucfirst($me['role'])) ?></small></div>
        <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="link-btn" type="submit">Sign out</button></form>
      </div>
    </aside>
    <div class="main">
      <header class="top">
        <button class="menu" id="menu" type="button" aria-label="Open menu" aria-controls="side" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
        <h1><?= e($title ?? '') ?></h1>
        <a class="btn primary" href="<?= e(url('/tickets/new')) ?>">New ticket</a>
      </header>
      <?php require __DIR__ . '/_flash.php'; ?>
      <main><?= $content ?></main>
    </div>
  </div>
  <script src="<?= e(url('/assets/app.js')) ?>" defer></script>
</body>
</html>
