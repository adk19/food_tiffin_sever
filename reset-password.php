<?php
require_once 'includes/init.php';
require_once 'includes/header.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if token is valid and not expired
        $currentTime = date('Y-m-d H:i:s');
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > ?');
            $stmt->execute([$token, $currentTime]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?');

                if ($stmt->execute([$hashedPassword, $user['id']])) {
                    $success = 'Your password has been reset successfully. You can now <a href="login.php" class="alert-link">login</a> with your new password.';
                } else {
                    $error = 'Error updating your password. Please try again.';
                }
            } else {
                $error = 'Invalid or expired reset token. Please request a new password reset.';
            }
        } catch (PDOException $e) {
            error_log('Database error in reset-password.php: ' . $e->getMessage());
            $error = 'An error occurred while processing your request. Please try again.';
        }
    }
}
?>

<style>
    /* Reset Password Page Styles */
    .reset-password-container {
        max-width: 400px;
        margin: 2rem auto;
        padding: 2rem;
        background-color: #1a1a1a;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .reset-password-container h1 {
        color: #ffffff;
        text-align: center;
        margin-bottom: 1.5rem;
        font-size: 1.8rem;
    }

    .reset-password-container p {
        color: #a0a0a0;
        text-align: center;
        margin-bottom: 2rem;
        line-height: 1.5;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        color: #ffffff;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #333;
        border-radius: 4px;
        background-color: #2d2d2d;
        color: #ffffff;
        font-size: 1rem;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: #4CAF50;
        box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
    }

    .btn {
        display: inline-block;
        width: 100%;
        padding: 0.75rem;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        transition: background-color 0.3s;
    }

    .btn:hover {
        background-color: #45a049;
    }

    .btn:active {
        transform: translateY(1px);
    }

    .back-to-login {
        text-align: center;
        margin-top: 1.5rem;
    }

    .back-to-login a {
        color: #4CAF50;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .back-to-login a:hover {
        text-decoration: underline;
    }

    /* Error and Success Messages */
    .alert {
        padding: 0.75rem 1rem;
        margin-bottom: 1.5rem;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .password-strength {
        margin-top: 0.5rem;
        font-size: 0.85rem;
        color: #a0a0a0;
    }

    .password-strength.weak { color: #ff6b6b; }
    .password-strength.medium { color: #ffd93d; }
    .password-strength.strong { color: #4CAF50; }
</style>

<div class="reset-password-container">
    <h1>Reset Your Password</h1>
    <p>Enter your new password below.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>
        <form method="POST" action="" id="resetPasswordForm">
            <div class="form-group">
                <label for="password">New Password</label>
                <div style="position: relative;">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter new password" 
                        required
                        minlength="8"
                    >
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #a0a0a0; cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="password-strength" class="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div style="position: relative;">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control" 
                        placeholder="Confirm new password" 
                        required
                        minlength="8"
                    >
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #a0a0a0; cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php">← Back to Login</a>
        </div>
    <?php endif; ?>
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

// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthText = document.getElementById('password-strength');
    
    if (password.length === 0) {
        strengthText.textContent = '';
        return;
    }
    
    let strength = 0;
    let feedback = '';
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
    
    if (strength <= 2) {
        feedback = 'Weak';
        strengthText.className = 'password-strength weak';
    } else if (strength <= 4) {
        feedback = 'Medium';
        strengthText.className = 'password-strength medium';
    } else {
        feedback = 'Strong';
        strengthText.className = 'password-strength strong';
    }
    
    strengthText.textContent = `Password strength: ${feedback}`;
});

// Form validation
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long!');
        return false;
    }
    
    return true;
});
</script>

<?php require_once 'includes/footer.php'; ?>