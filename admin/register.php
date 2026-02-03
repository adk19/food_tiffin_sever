<?php
require_once __DIR__ . '/../includes/init.php';
// If already logged in, go to dashboard
if (admin_is_authed()) {
  redirect('index.php');
}

$pdo = db();
// Allow open registration ONLY if no admins exist yet
$adminCount = (int) ($pdo->query('SELECT COUNT(*) c FROM admins')->fetch()['c'] ?? 0);
$openRegistration = ($adminCount === 0);

$err = null;
$ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$openRegistration) {
    $err = 'Registration is disabled. Ask an existing admin to add you.';
  } else {
    $name = trim((string) ($_POST['fullName'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['confirm'] ?? '');
    if ($name === '' || $email === '' || $pass === '') {
      $err = 'All fields are required';
    } elseif ($pass !== $pass2) {
      $err = 'Passwords do not match';
    } else {
      try {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $st = $pdo->prepare('INSERT INTO admins (full_name, email, password, created_at) VALUES (?,?,?,?)');
        $st->execute([$name, $email, $hash, time()]);
        redirect('login.php');
      } catch (Throwable $e) {
        $err = 'Could not create admin (maybe email already exists)';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Registration</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
  <div class="auth-screen">
    <div class="auth-card">
      <div class="auth-head">
        <div class="auth-head-text">
          <div class="badge">🍱</div>
          <div class="title">Tiffin Admin</div>
        </div>
        <div class="subtitle"><?php echo $openRegistration ? 'Create an admin account' : 'Registration disabled (admins exist)'; ?></div>
      </div>

      <?php if ($err): ?><div style="position:static; margin-bottom:10px; padding: 12px 16px; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #ef4444; font-weight: 500;">&nbsp;<?php echo h($err); ?></div><?php endif; ?>

      <form method="post" class="auth-form">
        <label class="label">Full Name</label>
        <input class="input" type="text" name="fullName" placeholder="Your name" required <?php echo $openRegistration ? '' : 'disabled'; ?> />

        <label class="label" style="margin-top:10px;">Email</label>
        <input class="input" type="email" name="email" placeholder="admin@example.com" required <?php echo $openRegistration ? '' : 'disabled'; ?> />

        <label class="label" style="margin-top:10px;">Password</label>
        <input class="input" type="password" name="password" placeholder="Choose a password" required <?php echo $openRegistration ? '' : 'disabled'; ?> />

        <label class="label" style="margin-top:10px;">Confirm Password</label>
        <input class="input" type="password" name="confirm" placeholder="Re-enter password" required <?php echo $openRegistration ? '' : 'disabled'; ?> />

        <div class="actions">
          <button class="btn" type="submit" <?php echo $openRegistration ? '' : 'disabled style="opacity:.6;cursor:not-allowed"'; ?>>Create Account</button>
          <a class="btn btn-outline" href="login.php">Back to Login</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
