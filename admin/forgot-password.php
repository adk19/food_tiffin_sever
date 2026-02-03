<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/email_functions.php';
if (admin_is_authed()) {
  redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

  if (empty($email)) {
    $error = 'Please enter your email address.';
  } else {
    // Check if admin exists
    $st = db()->prepare('SELECT id, full_name FROM admins WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $admin = $st->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
      // Generate token
      $token = bin2hex(random_bytes(32));
      $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

      // Save token to database
      $st = db()->prepare('UPDATE admins SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
      if ($st->execute([$token, $expires, $admin['id']])) {
        // Send email with reset link
        $baseUrl = (defined('SITE_URL') ? SITE_URL : 'http://' . $_SERVER['HTTP_HOST']);
        $resetLink = rtrim($baseUrl, '/') . '/reset-password.php?token=' . $token;
        $subject = 'Admin Password Reset Request';
        $message = "
                    <h2>Admin Password Reset Request</h2>
                    <p>Hello {$admin['full_name']},</p>
                    <p>You have requested to reset your admin password. Click the link below to set a new password:</p>
                    <p><a href='{$resetLink}' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email and contact the system administrator.</p>
                ";

        if (sendEmail($email, $admin['full_name'], $subject, $message)) {
          $success = 'Password reset link has been sent to your email.';
        } else {
          $error = 'Failed to send reset email. Please try again.';
        }
      } else {
        $error = 'Error processing your request. Please try again.';
      }
    } else {
      // For security, don't reveal if email exists
      $success = 'If your email exists in our system, you will receive a password reset link.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password - Admin</title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/style.css" />
  <style>
    /* Forgot Password Specific Styles */
  .auth-wrap {
    max-width: 100%;
    padding: 20px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 80px);
  }
  
  .form-field {
    margin-bottom: 1.25rem;
  }

  .form-actions .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    font-size: 0.9375rem;
    font-weight: 500;
    line-height: 1.5;
    color: white;
    background: linear-gradient(135deg, var(--brand), var(--brand-2));
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    width: 100%;
  }

  .toast {
    padding: 0.875rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.25rem;
    font-size: 0.875rem;
    line-height: 1.5;
  }

  .error {
    background-color: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
  }

  .success {
    background-color: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.2);
  }

  .auth-foot {
    margin-top: 1.5rem;
    text-align: center;
    color: var(--muted);
    font-size: 0.875rem;
  }

  .auth-foot a {
    color: var(--brand);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
  }

  .auth-foot a:hover {
    color: var(--brand-2);
    text-decoration: underline;
  }

  .auth-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 0.5rem;
    text-align: center;
  }

  /* Responsive adjustments */
  @media (max-width: 480px) {
    .auth-card {
      padding: 1.5rem;
    }

    .auth-title {
      font-size: 1.25rem;
    }
    
    .auth-sub {
      font-size: 0.875rem;
    }
  }
  </style>
</head>
<body>
  <div style="width:100%; max-width:1120px; margin:0 auto; padding:20px 16px; text-align:right;">
    <a href="login.php" class="btn btn-outline" style="padding:8px 16px; font-size:14px;">← Back to Login</a>
  </div>
  <main class="auth-wrap">
    <div class="auth-card">
      <div class="auth-head" style="text-align: center;">
        <h1 class="auth-title" style="font-size: 2rem; margin: 10px 0; ">Forgot Password</h1>
        <div class="auth-sub" style="text-align: center;">Enter your email to reset your password</div>
      </div>
      
      <?php if ($error): ?>
        <div class="toast error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="toast success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      
      <form method="post" class="auth-form" novalidate>
        <div class="form-field">
          <label class="label" for="email">Email</label>
          <input id="email" class="input" type="email" name="email" placeholder="you@example.com" required />
        </div>
        <div class="form-actions">
          <button class="btn btn-block" type="submit">Send Reset Link</button>
        </div>
      </form>
      
      <div class="auth-foot text-center mt-4">
        Remember your password? <a href="login.php" class="link">Login here</a>
      </div>
    </div>
  </main>
</body>
</html>
