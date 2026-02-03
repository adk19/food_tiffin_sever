<?php
require_once __DIR__ . '/includes/init.php';

// Check if already logged in
if (cust_is_authed()) {
    $redirect = $_GET['redirect'] ?? 'menu.php';
    header('Location: ' . $redirect);
    exit;
}

// Start output buffering
ob_start();
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'menu.php';
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? 'menu.php';

    // Basic validation
    if (empty($email) || empty($password)) {
        $err = 'Please enter both email and password';
    } else {
        // Attempt login
        [$success, $message] = cust_login_or_register('', $email, '', '', $password);

        if ($success) {
            // Validate redirect URL to prevent open redirects
            $redirect = filter_var($redirect, FILTER_SANITIZE_URL);
            $redirect = strtok($redirect, '?');
            $allowed_redirects = ['index.php', 'menu.php', 'cart.php'];

            if (!in_array($redirect, $allowed_redirects)) {
                $redirect = 'menu.php';
            }

            // Clear any existing error and redirect
            unset($_SESSION['error']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $err = $message ?: 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Tiffin Service</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/public.css" />
</head>
<body>
    <div style="width:100%; max-width:1120px; margin:0 auto; padding:20px 16px; text-align:right;">
        <a href="index.php" class="btn btn-outline" style="padding:8px 16px; font-size:14px; background:#f8f9fa; color:#333; text-decoration:none; border:1px solid #ddd; border-radius:4px;">← Home</a>
    </div>
    
    <main class="auth-wrap">
        <div class="auth-card">
            <div class="auth-head">
                <h1 class="auth-title">Welcome Back</h1>
                <div class="auth-sub">Login to your account to continue</div>
            </div>
            
            <?php if ($err): ?>
                <div class="error"><?php echo h($err); ?></div>
            <?php endif; ?>
            
            <form method="post" class="auth-form" novalidate>
                <input type="hidden" name="redirect" value="<?php echo h($redirect); ?>" />
                
                <div class="form-field">
                    <label class="label" for="email">Email Address</label>
                    <input 
                        id="email" 
                        class="input" 
                        type="email" 
                        name="email" 
                        placeholder="you@example.com" 
                        required 
                        value="<?php echo isset($_POST['email']) ? h($_POST['email']) : ''; ?>"
                    />
                </div>
                
                <div class="form-field">
                    <label class="label" for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input 
                            id="password" 
                            class="input" 
                            type="password" 
                            name="password" 
                            placeholder="Enter your password" 
                            required
                        />
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-field" style="margin-top: 24px;">
                    <button type="submit" class="btn">Login</button>
                </div>
                
                <div style="text-align: center; margin: 16px 0;">
                    <a href="forgot-password.php" style="color: #666; text-decoration: none;">Forgot your password?</a>
                </div>
            </form>
            
            <div class="auth-foot">
                Don't have an account? 
                <a href="register.php<?php echo $redirect ? '?redirect=' . urlencode($redirect) : ''; ?>" class="link">Sign up</a>
            </div>
        </div>
    </main>

    <script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
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