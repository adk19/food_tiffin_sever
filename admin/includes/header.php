<?php
require_once __DIR__ . '/../../includes/init.php';
$authed = admin_is_authed();
$me = admin_current();
$pageTitle = $pageTitle ?? 'Dashboard';
$active = $active ?? '/dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tiffin Admin - <?php echo h($pageTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
</head>
<body>
<?php if ($authed): ?>
  <div class="app">
    <aside class="sidebar">
      <div class="brand">
        <div class="logo">🍱</div>
        <div class="name">Tiffin Admin</div>
      </div>
      <nav class="nav">
        <a href="index.php?p=/dashboard" class="nav-link <?php echo $active === '/dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="index.php?p=/users" class="nav-link <?php echo $active === '/users' ? 'active' : ''; ?>">Users</a>
        <a href="index.php?p=/categories" class="nav-link <?php echo $active === '/categories' ? 'active' : ''; ?>">Categories</a>
        <a href="index.php?p=/menu" class="nav-link <?php echo $active === '/menu' ? 'active' : ''; ?>">Menu</a>
        <a href="index.php?p=/orders" class="nav-link <?php echo $active === '/orders' ? 'active' : ''; ?>">Orders</a>
        <a href="index.php?p=/contacts" class="nav-link <?php echo $active === '/contacts' ? 'active' : ''; ?>">Contact Messages</a>
      </nav>
      <div class="sidebar-footer">
        <div class="show-mobile" style="margin-top:10px; width:100%;">
          <a href="logout.php" class="btn btn-outline" style="display: flex;width:100%;text-align:center;align-items: center;justify-content: center;">Logout</a>
        </div>
      </div>
    </aside>

    <div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>

    <main class="main">
      <header class="topbar">
        <div class="topbar-left">
          <button id="sidebarToggle" class="icon-btn" title="Toggle Sidebar">☰</button>
          <h1 id="pageTitle" class="page-title"><?php echo h($pageTitle); ?></h1>
        </div>
        <div class="topbar-right">
          <div class="today" id="todayText"><?php echo date('l, M d, Y'); ?></div>
          <div class="avatar" title="<?php echo h($me['fullName'] ?? 'A'); ?>"><?php echo strtoupper(substr($me['fullName'] ?? 'A', 0, 1)); ?></div>
          <a href="logout.php" class="icon-btn hide-mobile" title="Logout" style="text-decoration:none; display:inline-grid; place-items:center;">Logout</a>
        </div>
      </header>

      <section id="content" class="content">
<?php else: ?>
  <div class="content" style="padding:24px;">
<?php endif; ?>

