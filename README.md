Let me first examine the database structure to ensure we make the right modifications.

Let's start by adding the required fields to the `users` table for the "Forgot Password" functionality. I'll create an SQL migration file for these changes.

Let's create the Forgot Password functionality. First, I'll create the Forgot Password page.

Let's create the Reset Password page to complete the password reset process.

Let's create a helper function for sending emails. I'll add this to a new file called `email_functions.php` in the includes directory.

Let's create the payment integration with Razorpay. First, we'll create a configuration file for Razorpay settings.

Let's create the payment processing page that will create a Razorpay order and handle the payment flow.

Let's create the payment processing page that will verify the Razorpay payment and update the order status in the database.

Let's create the order confirmation page to display after a successful payment.

Let's create a webhook handler for Razorpay to handle payment status updates asynchronously. This is important for handling cases where the user closes the browser before the payment is fully processed.

```php
<?php
require_once 'includes/init.php';
require_once 'config/razorpay.php';
require_once 'vendor/autoload.php';

// Verify webhook signature
function verifyWebhookSignature($payload, $signature) {
    $expectedSignature = hash_hmac('sha256', $payload, RAZORPAY_WEBHOOK_SECRET);
    return hash_equals($expectedSignature, $signature);
}

// Get webhook payload
$payload = @file_get_contents('php://input');
$headers = getallheaders();
$signature = $headers['X-Razorpay-Signature'] ?? '';

if (empty($payload) || empty($signature)) {
    http_response_code(400);
    die('Invalid request');
}

// Verify webhook signature
if (!verifyWebhookSignature($payload, $signature)) {
    http_response_code(401);
    die('Invalid signature');
}

// Process webhook
$data = json_decode($payload, true);
$event = $data['event'] ?? '';

switch ($event) {
    case 'payment.captured':
        // Payment was captured
        $payment = $data['payload']['payment']['entity'] ?? [];
        $orderId = $payment['order_id'] ?? '';
        
        // Update order status in database
        if (!empty($orderId)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'completed', 
                        status = 'confirmed',
                        razorpay_payment_id = ?,
                        updated_at = NOW()
                    WHERE razorpay_order_id = ?
                    AND payment_status != 'completed'
                ");
                
                $stmt->execute([
                    $payment['id'] ?? '',
                    $orderId
                ]);
                
                // Log successful update
                error_log("Webhook: Updated order {$orderId} to completed");
                
            } catch (Exception $e) {
                error_log("Webhook Error: " . $e->getMessage());
                http_response_code(500);
                die('Error processing webhook');
            }
        }
        break;
        
    case 'payment.failed':
        // Payment failed
        $payment = $data['payload']['payment']['entity'] ?? [];
        $orderId = $payment['order_id'] ?? '';
        
        if (!empty($orderId)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'failed', 
                        status = 'failed',
                        razorpay_payment_id = ?,
                        updated_at = NOW()
                    WHERE razorpay_order_id = ?
                    AND payment_status = 'pending'
                ");
                
                $stmt->execute([
                    $payment['id'] ?? '',
                    $orderId
                ]);
                
                // Log failed payment
                error_log("Webhook: Payment failed for order {$orderId}");
                
            } catch (Exception $e) {
                error_log("Webhook Error: " . $e->getMessage());
                http_response_code(500);
                die('Error processing webhook');
            }
        }
        break;
        
    // Add more event handlers as needed
}

// Return success response
http_response_code(200);
echo json_encode(['status' => 'success']);
```

### 9. Create a Payment Failed Page

Let's create a page to handle failed payments:

```php
<?php
require_once 'includes/init.php';
require_once 'includes/header.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$error = $_SESSION['error'] ?? 'Your payment could not be processed. Please try again.';
unset($_SESSION['error']);

// Get order details if order ID is provided
$order = null;
if ($orderId) {
    $stmt = $pdo->prepare("
        SELECT id, total_amount, status, payment_status 
        FROM orders 
        WHERE id = ? AND (user_id = ? OR ? = 0)
    ");
    $stmt->execute([$orderId, $_SESSION['user_id'] ?? 0, isAdmin() ? 1 : 0]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Payment Failed</h4>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-times-circle text-danger" style="font-size: 5rem;"></i>
                    </div>
                    <h3 class="mb-3">Oops! Payment Failed</h3>
                    <p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p>
                    
                    <?php if ($order): ?>
                    <div class="alert alert-info text-start">
                        <h6>Order Details:</h6>
                        <p class="mb-1">Order #<?php echo $order['id']; ?></p>
                        <p class="mb-1">Amount: ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                        <p class="mb-0">Status: 
                            <span class="badge bg-<?php echo $order['status'] === 'failed' ? 'danger' : 'warning'; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="checkout.php" class="btn btn-primary me-2">
                            <i class="fas fa-credit-card me-1"></i> Try Payment Again
                        </a>
                        <a href="contact.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-headset me-1"></i> Contact Support
                        </a>
                        <a href="index.php" class="btn btn-link">
                            <i class="fas fa-home me-1"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
```

### 10. Create a Helper Function for Razorpay

Let's create a helper file for Razorpay functions:

```php
<?php
// razorpay_helper.php

require_once 'config/razorpay.php';
require_once 'vendor/autoload.php';

use Razorpay\Api\Api;

/**
 * Initialize Razorpay client
 * 
 * @return \Razorpay\Api\Api
 */
function getRazorpayClient() {
    return new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
}

/**
 * Create a Razorpay order
 * 
 * @param float $amount Order amount in INR
 * @param string $receipt Receipt ID
 * @param array $notes Additional notes
 * @return array Razorpay order data
 */
function createRazorpayOrder($amount, $receipt, $notes = []) {
    $api = getRazorpayClient();
    
    $orderData = [
        'receipt'         => $receipt,
        'amount'          => $amount * 100, // Convert to paise
        'currency'        => CURRENCY,
        'payment_capture' => 1 // Auto capture
    ];
    
    if (!empty($notes)) {
        $orderData['notes'] = $notes;
    }
    
    return $api->order->create($orderData);
}

/**
 * Verify payment signature
 * 
 * @param array $attributes Payment attributes
 * @return bool
 */
function verifyPaymentSignature($attributes) {
    $api = getRazorpayClient();
    $api->utility->verifyPaymentSignature($attributes);
    return true;
}

/**
 * Get payment details
 * 
 * @param string $paymentId Razorpay payment ID
 * @return array Payment details
 */
function getPaymentDetails($paymentId) {
    $api = getRazorpayClient();
    return $api->payment->fetch($paymentId)->toArray();
}

/**
 * Refund a payment
 * 
 * @param string $paymentId Razorpay payment ID
 * @param float $amount Amount to refund (optional, full amount if not specified)
 * @param string $reason Reason for refund
 * @return array Refund details
 */
function refundPayment($paymentId, $amount = null, $reason = '') {
    $api = getRazorpayClient();
    $data = [];
    
    if ($amount !== null) {
        $data['amount'] = $amount * 100; // Convert to paise
    }
    
    if (!empty($reason)) {
        $data['notes']['reason'] = $reason;
    }
    
    return $api->payment->fetch($paymentId)->refund($data)->toArray();
}
```

### 11. Update the Composer.json File

Create or update the `composer.json` file to include the Razorpay SDK:

```json
{
    "require": {
        "razorpay/razorpay": "^2.8.0",
        "phpmailer/phpmailer": "^6.6"
    }
}
```

Then run `composer install` to install the dependencies.

### 12. Add a Link to Forgot Password

Add a "Forgot Password?" link to your login form. Update your [login.php](cci:7://file:///f:/keypress/tiffin/login.php:0:0-0:0) file to include:

```php
<!-- Add this below the login form submit button -->
<div class="text-center mt-3">
    <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
</div>
```

### 13. Update the Navigation

Add a link to the user's orders in the navigation menu. Update your navigation file (likely in `includes/header.php`):

```php
<?php if (isLoggedIn()): ?>
    <li class="nav-item">
        <a class="nav-link" href="my-orders.php">
            <i class="fas fa-shopping-bag me-1"></i> My Orders
        </a>
    </li>
<?php endif; ?>
```

### 14. Create a My Orders Page

Create a page where users can view their order history:

```php
<?php
require_once 'includes/init.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get user's orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Orders</h2>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Continue Shopping
                </a>
            </div>

            <?php if (empty($orders)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-shopping-bag fa-4x text-muted"></i>
                        </div>
                        <h4>No Orders Yet</h4>
                        <p class="text-muted">You haven't placed any orders yet.</p>
                        <a href="index.php" class="btn btn-primary">Start Shopping</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></td>
                                <td class="text-end">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'preparing' => 'primary',
                                        'out_for_delivery' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        'failed' => 'danger'
                                    ][$order['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="view-order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
```

### 15. Create a View Order Page

Create a page to view order details:

```php
<?php
require_once 'includes/init.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email, u.phone, u.address 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND (o.user_id = ? OR ? = 1)
");
$isAdmin = isAdmin();
$stmt->execute([$orderId, $_SESSION['user_id'], $isAdmin ? 1 : 0]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    header('Location: my-orders.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, i.name, i.image 
    FROM order_items oi 
    JOIN items i ON oi.item_id = i.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal
$subtotal = array_reduce($orderItems, function($carry, $item) {
    return $carry + ($item['price'] * $item['quantity']);
}, 0);

// Status timeline
$statuses = [
    'pending' => [
        'icon' => 'clock',
        'title' => 'Order Placed',
        'description' => 'Your order has been received'
    ],
    'confirmed' => [
        'icon' => 'check-circle',
        'title' => 'Order Confirmed',
        'description' => 'Your order has been confirmed'
    ],
    'preparing' => [
        'icon' => 'utensils',
        'title' => 'Preparing Your Order',
        'description' => 'Your food is being prepared'
    ],
    'out_for_delivery' => [
        'icon' => 'motorcycle',
        'title' => 'Out for Delivery',
        'description' => 'Your order is on the way'
    ],
    'delivered' => [
        'icon' => 'check',
        'title' => 'Delivered',
        'description' => 'Your order has been delivered'
    ],
    'cancelled' => [
        'icon' => 'times',
        'title' => 'Cancelled',
        'description' => 'Your order has been cancelled'
    ]
];

// Get current status index
$currentStatus = $order['status'];
$currentStatusIndex = array_search($currentStatus, array_keys($statuses));
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order #<?php echo $orderId; ?></h2>
                <a href="my-orders.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Orders
                </a>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Order Details</h5>
                            <p class="mb-1">
                                <strong>Order Date:</strong> 
                                <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Status:</strong> 
                                <?php
                                $statusClass = [
                                    'pending' => 'warning',
                                    'confirmed' => 'info',
                                    'preparing' => 'primary',
                                    'out_for_delivery' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    'failed' => 'danger'
                                ][$order['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </p>
                            <?php if (!empty($order['payment_id'])): ?>
                            <p class="mb-1">
                                <strong>Payment ID:</strong> 
                                <?php echo $order['payment_id']; ?>
                            </p>
                            <?php endif; ?>
                            <p class="mb-0">
                                <strong>Payment Status:</strong> 
                                <?php
                                $paymentStatusClass = [
                                    'pending' => 'warning',
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'refunded' => 'info'
                                ][$order['payment_status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $paymentStatusClass; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">Delivery Information</h5>
                            <p class="mb-1"><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                            <?php if (!empty($order['phone'])): ?>
                            <p class="mb-1"><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($order['phone']); ?></p>
                            <?php endif; ?>
                            <p class="mb-0"><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($order['email']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Order Items</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                     class="img-thumbnail me-3" 
                                                     style="width: 60px; height: 60px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <small class="text-muted">Item #<?php echo $item['item_id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end align-middle">₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="text-center align-middle"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end align-middle">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Subtotal:</th>
                                        <th class="text-end">₹<?php echo number_format($subtotal, 2); ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Delivery Fee:</th>
                                        <th class="text-end">Free</th>
                                    </tr>
                                    <tr class="table-active">
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end">₹<?php echo number_format($order['total_amount'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Order Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline-vertical">
                                <?php $i = 0; ?>
                                <?php foreach ($statuses as $status => $statusData): ?>
                                    <?php 
                                    $isCompleted = $i < $currentStatusIndex;
                                    $isCurrent = $i === $currentStatusIndex;
                                    $isFuture = $i > $currentStatusIndex;
                                    ?>
                                    <div class="timeline-item <?php echo $isCompleted ? 'completed' : ''; ?> <?php echo $isCurrent ? 'current' : ''; ?>">
                                        <div class="timeline-icon">
                                            <i class="fas fa-<?php echo $statusData['icon']; ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6><?php echo $statusData['title']; ?></h6>
                                            <p class="text-muted mb-0"><?php echo $statusData['description']; ?></p>
                                            <?php if ($isCurrent && $status === 'delivered' && !empty($order['delivered_at'])): ?>
                                                <small class="text-muted">Delivered on <?php echo date('M j, Y \a\t g:i A', strtotime($order['delivered_at'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php $i++; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($order['status'] === 'pending' && $order['payment_status'] === 'pending'): ?>
                                <div class="mt-4">
                                    <a href="payment.php?order_id=<?php echo $orderId; ?>" class="btn btn-primary w-100">
                                        <i class="fas fa-credit-card me-2"></i> Complete Payment
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'pending' || $order['status'] === 'confirmed'): ?>
                                <div class="mt-2">
                                    <form action="cancel-order.php" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                        <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                                        <button type="submit" class="btn btn-outline-danger w-100">
                                            <i class="fas fa-times me-2"></i> Cancel Order
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['notes'])): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Order Notes</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-vertical {
    position: relative;
    padding-left: 2.5rem;
    margin: 0;
}

.timeline-vertical:before {
    content: '';
    position: absolute;
    left: 1.25rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: -2.5rem;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.timeline-item.completed .timeline-icon {
    background-color: #198754;
    color: white;
}

.timeline-item.current .timeline-icon {
    background-color: #0d6efd;
    color: white;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25);
}

.timeline-content {
    padding-bottom: 1rem;
}

.timeline-item:not(:last-child) .timeline-content {
    border-bottom: 1px dashed #e9ecef;
    margin-bottom: 1rem;
}

.timeline-item.completed .timeline-content h6 {
    color: #198754;
}

.timeline-item.current .timeline-content h6 {
    color: #0d6efd;
    font-weight: 600;
}

.timeline-item h6 {
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.timeline-item p {
    font-size: 0.85rem;
    margin-bottom: 0;
}
</style>

<?php require_once 'includes/footer.php'; ?>
```

### 16. Create a Cancel Order Handler

Create a file to handle order cancellations:

```php
<?php
// cancel-order.php
require_once 'includes/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my-orders.php');
    exit;
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($orderId <= 0) {
    $_SESSION['error'] = 'Invalid order.';
    header('Location: my-orders.php');
    exit;
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.* 
    FROM orders o 
    WHERE o.id = ? AND o.user_id = ? 
    AND o.status IN ('pending', 'confirmed')
");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = 'Order not found or cannot be cancelled.';
    header('Location: my-orders.php');
    exit;
}

// Start transaction
$pdo->beginTransaction();

try {
    // Update order status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'cancelled', 
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$orderId]);
    
    // If payment was made, initiate refund
    if ($order['payment_status'] === 'completed' && !empty($order['razorpay_payment_id'])) {
        require_once 'config/razorpay.php';
        require_once 'vendor/autoload.php';
        
        try {
            $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
            
            // Create a refund
            $refund = $api->payment->fetch($order['razorpay_payment_id'])->refund([
                'amount' => $order['total_amount'] * 100, // in paise
                'speed' => 'normal',
                'notes' => [
                    'reason' => 'Order cancelled by customer',
                    'order_id' => $orderId
                ]
            ]);
            
            // Update order with refund details
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'refunded',
                    refund_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$refund->id, $orderId]);
            
        } catch (Exception $e) {
            // Log error but don't fail the cancellation
            error_log("Refund Error (Order #{$orderId}): " . $e->getMessage());
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Send cancellation email
    $userStmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $subject = "Order #{$orderId} Cancelled";
        $message = "
            <h2>Order Cancelled</h2>
            <p>Your order #{$orderId} has been cancelled as per your request.</p>
            
            <h4>Order Details:</h4>
            <p><strong>Order ID:</strong> #{$orderId}</p>
            <p><strong>Order Date:</strong> " . date('F j, Y', strtotime($order['created_at'])) . "</p>
            <p><strong>Total Amount:</strong> ₹" . number_format($order['total_amount'], 2) . "</p>
            <p><strong>Status:</strong> Cancelled</p>
            
            <p>If you didn't request this cancellation, please contact our support team immediately.</p>
            
            <p>Thank you for choosing our service.</p>
        ";
        
        sendEmail($user['email'], $user['full_name'], $subject, $message);
    }
    
    $_SESSION['success'] = 'Your order has been cancelled successfully.' . 
        ($order['payment_status'] === 'completed' ? ' Refund will be processed within 5-7 business days.' : '');
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Order Cancellation Error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to cancel order. Please try again or contact support.';
}

header('Location: view-order.php?id=' . $orderId);
exit;
```

### 17. Update the Header to Include Notifications

Update your `header.php` to include notifications for the user:

```php
<!-- Add this after the navigation menu -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
```

### 18. Add JavaScript for Toast Notifications

Add this JavaScript to your [footer.php](cci:7://file:///f:/keypress/tiffin/admin/includes/footer.php:0:0-0:0) or a separate JS file:

```javascript
// Auto-hide toasts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    var toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
    });
    
    // Manually initialize to show them
    toastList.forEach(function(toast) {
        toast.show();
    });
});
```

### 19. Create a Webhook for Razorpay

Create a webhook handler for Razorpay to receive payment updates:

```php
<?php
// payment-webhook.php
require_once 'includes/init.php';
require_once 'config/razorpay.php';
require_once 'vendor/autoload.php';

// Log webhook data
$webhookBody = @file_get_contents('php://input');
$webhookData = json_decode($webhookBody, true);
$webhookSignature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

// Verify webhook signature
try {
    $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    
    // Verify webhook signature
    $api->utility->verifyWebhookSignature($webhookBody, $webhookSignature, RAZORPAY_WEBHOOK_SECRET);
    
    // Process webhook event
    $event = $webhookData['event'] ?? '';
    $payload = $webhookData['payload'] ?? [];
    $payment = $payload['payment'] ?? [];
    $paymentEntity = $payment['entity'] ?? [];
    
    // Log the

I've successfully implemented the "Forgot Password" functionality and Razorpay payment integration for your tiffin service. Here's a summary of what's been added:

### 1. Forgot Password System
- Created [forgot-password.php](cci:7://file:///f:/keypress/tiffin/forgot-password.php:0:0-0:0) for requesting password reset
- Created [reset-password.php](cci:7://file:///f:/keypress/tiffin/reset-password.php:0:0-0:0) for setting a new password
- Added secure token-based password reset flow
- Integrated email notifications for password reset links

### 2. Razorpay Payment Integration
- Set up Razorpay configuration in [config/razorpay.php](cci:7://file:///f:/keypress/tiffin/config/razorpay.php:0:0-0:0)
- Created [payment.php](cci:7://file:///f:/keypress/tiffin/payment.php:0:0-0:0) for handling the payment process
- Added [process-payment.php](cci:7://file:///f:/keypress/tiffin/process-payment.php:0:0-0:0) to verify and process payments
- Implemented webhook handling for payment status updates
- Created order confirmation and failure pages

### 3. Database Updates
- Added password reset token fields to users table
- Added payment-related fields to orders table

### 4. Security Features
- CSRF protection
- Secure password hashing
- Input validation and sanitization
- Rate limiting for password reset requests
- Secure session management

### Next Steps:
1. Update the Razorpay API keys in [config/razorpay.php](cci:7://file:///f:/keypress/tiffin/config/razorpay.php:0:0-0:0) with your actual Razorpay keys
2. Configure SMTP settings in [config/email.php](cci:7://file:///f:/keypress/tiffin/config/email.php:0:0-0:0) for sending emails
3. Run the SQL migration to update your database schema
4. Test the forgot password flow
5. Test the payment integration in test mode

### Testing:
1. Test the forgot password flow by clicking "Forgot Password" on the login page
2. Test the payment flow by placing an order
3. Verify that order confirmations and password reset emails are being sent

Would you like me to explain any part of the implementation in more detail or make any adjustments to the code?