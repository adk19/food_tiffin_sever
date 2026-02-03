<?php
require_once __DIR__ . '/../includes/init.php';
if (admin_is_authed()) {
    redirect('index.php');
}
$err = null;
$openRegistration = false;
try {
    $openRegistration = ((int) (db()->query('SELECT COUNT(*) c FROM admins')->fetch()['c'] ?? 0)) === 0;
} catch (Throwable $e) {  /* ignore */
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    if (admin_login($email, $pass)) {
        redirect('index.php');
    } else {
        $err = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <div class="subtitle">Sign in to your account</div>
            </div>
            <?php if ($err): ?>
                <div class="toast error" style="position:static; margin-bottom:10px;">&nbsp;<?php echo h($err); ?></div>
            <?php endif; ?>
            <form method="post" class="auth-form">
                <label class="label">Email</label>
                <input class="input" type="email" name="email" placeholder="admin@example.com" required />
                
                <label class="label" style="margin-top:10px;">Password</label>
                <div class="password-input-wrapper">
                    <input 
                        class="input" 
                        type="password" 
                        name="password" 
                        id="password"
                        placeholder="••••••••" 
                        required 
                    />
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <div class="actions">
                    <button class="btn" type="submit">Login</button>
                    <?php if ($openRegistration): ?>
                        <a class="btn btn-outline" href="register.php">Create Account</a>
                    <?php else: ?>
                        <a class="btn btn-outline" href="register.php">Create Account</a>
                    <?php endif; ?>
                </div>
                <div class="forgot-password">
                    <a href="forgot-password.php">
                        Forgot your password?
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            // Toggle input type
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            // Toggle icon
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
    </script>
</body>
</html>