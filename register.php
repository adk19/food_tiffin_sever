<?php
require_once __DIR__ . '/includes/init.php';
$page = 'Register';
$active = 'register';
$redirect = $_GET['redirect'] ?? 'index.php';
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full = $_POST['fullName'] ?? '';
  $email = $_POST['email'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $addr = $_POST['address'] ?? '';
  $password = $_POST['password'] ?? '';

  // Validate password
  if (strlen($password) < 6) {
    $err = 'Password must be at least 6 characters long';
  } else {
    [$ok, $msg] = cust_login_or_register($full, $email, $phone, $addr, $password);
    if ($ok) {
      redirect($redirect);
    } else {
      $err = $msg ?: 'Registration failed';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/public.css" />
</head>
<body>
  <div style="width:100%; max-width:1120px; margin:0 auto; padding:20px 16px; text-align:right;">
    <a href="index.php" class="btn btn-outline" style="padding:8px 16px; font-size:14px;">← Home</a>
  </div>
  <main class="auth-wrap">
    <div class="auth-card">
      <div class="auth-head">
        <h1 class="auth-title">Create Account</h1>
        <div class="auth-sub">Fill in your details to sign up</div>
      </div>
      <?php if ($err): ?><div style="position:static; margin-bottom:10px; padding: 12px 16px; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #ef4444; font-weight: 500;">&nbsp;<?php echo h($err); ?></div><?php endif; ?>
      <form method="post" class="auth-form" novalidate>
        <input type="hidden" name="redirect" value="<?php echo h($redirect); ?>" />
        <div class="form-field">
          <label class="label" for="fullName">Full Name</label>
          <input id="fullName" class="input" name="fullName" placeholder="Miss Bansi" required />
        </div>
        <div class="form-field">
          <label class="label" for="email">Email</label>
          <input id="email" class="input" type="email" name="email" placeholder="you@example.com" required />
        </div>
        <div class="form-field">
          <label class="label" for="phone">Phone</label>
          <input id="phone" class="input" name="phone" placeholder="+91 9xxxxxxxxx" />
        </div>
        <div class="form-field">
          <label class="label" for="address">Address</label>
          <input id="address" class="input" name="address" placeholder="Your address" />
        </div>
        <div class="form-field">
          <label class="label" for="password">Password</label>
          <input id="password" class="input" type="password" name="password" placeholder="At least 6 characters" required />
        </div>
        <div class="form-actions">
          <button class="btn" type="submit" style="width:100%">Create Account</button>
        </div>
      </form>
      <div class="auth-foot">Already have an account? <a class="link" href="login.php?redirect=<?php echo urlencode($redirect); ?>">Login</a></div>
    </div>
  </main>
</body>
</html>
