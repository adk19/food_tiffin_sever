<?php
require_once __DIR__ . '/init.php';
$me = cust_current();
$page = $page ?? 'Home';
$active = $active ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tiffin Service - <?php echo h($page); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/public.css" />
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a href="index.php" class="brand">
        <div class="logo">🍱</div>
        <div class="name">Tiffin</div>
      </a>
      <nav class="nav" aria-label="Primary">
        <a class="nav-link <?php echo $active === 'home' ? 'active' : ''; ?>" href="index.php">Home</a>
        <a class="nav-link <?php echo $active === 'about' ? 'active' : ''; ?>" href="about.php">About</a>
        <a class="nav-link <?php echo $active === 'menu' ? 'active' : ''; ?>" href="menu.php">Menu</a>
        <a class="nav-link <?php echo $active === 'contact' ? 'active' : ''; ?>" href="contact.php">Contact</a>
        <a class="nav-link <?php echo $active === 'cart' ? 'active' : ''; ?>" href="cart.php">Cart</a>
      </nav>
      <div class="auth-actions">
        <?php if (!$me): ?>
          <a class="btn btn-outline" href="login.php">Login</a>
          <a class="btn" href="register.php" style="margin-left:8px;">Register</a>
        <?php else: ?>
          <span class="welcome">Hi, <?php echo h($me['fullName']); ?></span>
          <a class="btn" href="logout.php">Sign Out</a>
        <?php endif; ?>
      </div>
      <button id="navToggle" class="hamburger" aria-label="Toggle menu" aria-controls="mobileMenu" aria-expanded="false">☰</button>
    </div>
    <div id="mobileMenu" class="mobile-menu" aria-hidden="true">
      <div class="container">
        <nav class="mobile-nav">
          <a class="nav-link <?php echo $active === 'home' ? 'active' : ''; ?>" href="index.php">Home</a>
          <a class="nav-link <?php echo $active === 'about' ? 'active' : ''; ?>" href="about.php">About</a>
          <a class="nav-link <?php echo $active === 'menu' ? 'active' : ''; ?>" href="menu.php">Menu</a>
          <a class="nav-link <?php echo $active === 'contact' ? 'active' : ''; ?>" href="contact.php">Contact</a>
          <a class="nav-link <?php echo $active === 'cart' ? 'active' : ''; ?>" href="cart.php">Cart</a>
        </nav>
        <div class="mobile-auth">
          <?php if (!$me): ?>
            <a class="btn btn-outline" href="login.php">Login</a>
            <a class="btn" href="register.php" style="margin-left:8px;">Register</a>
          <?php else: ?>
            <a class="btn" href="logout.php">Sign Out</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>
  <main>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      var btn = document.getElementById('navToggle');
      var menu = document.getElementById('mobileMenu');
      if (!btn || !menu) return;
      function toggle(){
        var open = menu.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        menu.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.classList.toggle('no-scroll', open);
      }
      btn.addEventListener('click', toggle);
      menu.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', function(){ if(menu.classList.contains('open')) toggle(); }); });
    });
  </script>
