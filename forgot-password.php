<?php
require_once 'includes/init.php';
require_once 'includes/email_functions.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token to database
            $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
            if ($stmt->execute([$token, $expires, $user['id']])) {
                // Send email with reset link
                $baseUrl = (defined('SITE_URL') ? SITE_URL : 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));
                $resetLink = rtrim($baseUrl, '/') . '/reset-password.php?token=' . $token;
                $subject = 'Password Reset Request';
                $message = "
                    <h2>Password Reset Request</h2>
                    <p></p>
                    <p>Hello {$user['full_name']},</p>
                    <p>You have requested to reset your password. Click the link below to set a new password:</p>
                    <p><a href='{$resetLink}' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";

                if (sendEmail($email, $user['full_name'], $subject, $message)) {
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

<style>
    /* Forgot Password Page Styles */
    .forgot-password-container {
        max-width: 400px;
        margin: 2rem auto;
        padding: 2rem;
        background-color: #1a1a1a;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .forgot-password-container h1 {
        color: #ffffff;
        text-align: center;
        margin-bottom: 1.5rem;
        font-size: 1.8rem;
    }

    .forgot-password-container p {
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
</style>

<div class="forgot-password-container">
    <h1>Forgot Password</h1>
    <p>Enter your email address and we'll send you a link to reset your password.</p>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                placeholder="Enter your email address" 
                required
                value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
            >
        </div>
        
        <button type="submit" class="btn">Send Reset Link</button>
    </form>
    
    <div class="back-to-login">
        <a href="login.php">← Back to Login</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>