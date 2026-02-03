<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include necessary files
require_once 'includes/init.php';

// Set default site name if not defined
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Tiffin Service');
}
// Set page title
$pageTitle = 'Payment Failed';

// Set page title
$pageTitle = 'Payment Failed';

// Get order ID if available
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

// Get error message from session if available
$errorMessage = $_SESSION['error'] ?? 'Your payment could not be processed.';
unset($_SESSION['error']);  // Clear the error after displaying

// Start output
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .error-container {
            max-width: 600px;
            margin: 5rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background: #fff;
        }
        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 1.5rem;
        }
        .btn-retry {
            min-width: 180px;
        }
        .error-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="error-container text-center">
            <div class="error-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            
            <h2 class="mb-4">Payment Failed</h2>
            
            <p class="lead text-muted mb-4">We're sorry, but we couldn't process your payment.</p>
            
            <div class="error-details">
                <p class="mb-2"><strong>What happened?</strong></p>
                <ul class="text-start">
                    <li>Your payment was not completed successfully</li>
                    <li>No amount has been deducted from your account</li>
                    <li>Please try again with a different payment method</li>
                </ul>
                
                <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger mt-3 mb-0" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mt-4">
                <?php if ($orderId > 0): ?>
                <a href="payment.php?order_id=<?php echo $orderId; ?>" 
                   class="btn btn-primary btn-lg btn-retry mb-2 mb-sm-0">
                    <i class="fas fa-credit-card me-2"></i>Try Again
                </a>
                <?php endif; ?>
                
                <a href="menu.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-utensils me-2"></i>Back to Menu
                </a>
            </div>

            <div class="mt-5 pt-4 border-top">
                <h5 class="mb-3">Need help with your payment?</h5>
                <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                    <a href="contact.php" class="text-decoration-none">
                        <i class="fas fa-envelope me-1"></i> Contact Support
                    </a>
                    <span class="d-none d-md-inline">|</span>
                    <a href="tel:+911234567890" class="text-decoration-none">
                        <i class="fas fa-phone me-1"></i> +91 12345 67890
                    </a>
                    <span class="d-none d-md-inline">|</span>
                    <a href="mailto:support@example.com" class="text-decoration-none">
                        <i class="fas fa-at me-1"></i> support@example.com
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Output the page
echo ob_get_clean();
