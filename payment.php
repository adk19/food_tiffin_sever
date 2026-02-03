<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/razorpay.php';
require_once 'vendor/autoload.php';

// Check if user is logged in
if (!cust_is_authed()) {
    header('Location: login.php?redirect=' . urlencode('payment.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')));
    exit;
}

$orderId = $_GET['order_id'] ?? null;
$isCartCheckout = isset($_GET['checkout']) && $_GET['checkout'] === 'cart';
$orders = [];
$order = null;
$error = null;
$razorpayOrder = null;
$totalAmount = 0;

if ($isCartCheckout) {
    // Handle cart checkout - get multiple orders
    if (!isset($_SESSION['checkout_orders']) || empty($_SESSION['checkout_orders'])) {
        $error = 'No checkout orders found';
    } else {
        try {
            $pdo = db();
            $orderIds = $_SESSION['checkout_orders'];
            
            // Create proper placeholders for IN clause
            $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
            $params = array_merge($orderIds, [cust_current()['id']]);
            
            // Get all order details
            $stmt = $pdo->prepare("
                SELECT o.*, m.name as item_name, m.image 
                FROM orders o 
                LEFT JOIN menu_items m ON o.item_id = m.id 
                WHERE o.id IN ($placeholders) AND o.user_id = ? AND o.payment_status = 'pending'
            ");
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($orders)) {
                $error = 'Orders not found or already processed';
            } else {
                // Calculate total amount
                $totalAmount = 0;
                foreach ($orders as $ord) {
                    $totalAmount += $ord['total_amount'];
                }
                
                // Use the first order for display purposes, but show all orders in details
                $order = $orders[0];
            }
        } catch (Exception $e) {
            error_log('Cart checkout error: ' . $e->getMessage());
            $error = 'Error loading checkout orders';
        }
    }
} else {
    // Handle single order (from menu.php)
    if (!$orderId) {
        $error = 'No order specified';
    } else {
        try {
            $pdo = db();

            // Get order details
            $stmt = $pdo->prepare("
                SELECT o.*, m.name as item_name, m.image 
                FROM orders o 
                LEFT JOIN menu_items m ON o.item_id = m.id 
                WHERE o.id = ? AND o.user_id = ? AND o.payment_status = 'pending'
            ");
            $stmt->execute([$orderId, cust_current()['id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $error = 'Order not found or already processed';
            } else {
                $totalAmount = $order['total_amount'];
                $orders = [$order]; // Single order in array for consistent processing
            }
        } catch (Exception $e) {
            error_log('Single order error: ' . $e->getMessage());
            $error = 'Error loading order';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container payment-container">
    <div class="row">
        <!-- Order Summary Card (Left Side) -->
        <div class="col-lg-2">
            <div class="card backgaround">
                <div class="card-header" >
                    <p class="mb-0" style="color: white;">
                        <i class="fas fa-receipt mr-2"></i>
                        <span>Order Summary</span>
                    </p>
                </div>
                <div class="card-body" style="color: white;">
                    <div class="order-details">
                        <?php if (!empty($order['image'])): ?>
                            <img src="<?php echo h($order['image']); ?>" 
                                 alt="<?php echo h($order['item_name']); ?>">
                        <?php endif; ?>
                        <div class="flex-grow">
                            <p class="font-weight-bold mb-2" style="color: white;"><?php echo h($order['item_name']); ?></p>
                            <div class="amount-details">
                                <div >
                                <span style="color: #a0a0a0;">Quantity:</span>
                                <span style="color: white;"><?php echo h($order['qty'] ?? 1); ?></span>
                            </div>
                            <div class="">
                                <span style="color: #a0a0a0;">Price:</span>
                                <span style="color: white;">₹<?php echo number_format(($order['total_amount'] ?? 0) / ($order['qty'] ?? 1), 2); ?></span>
                            </div>
                            </div>
                            <hr style="border-color: #666666;">
                            <div class="final-amount">
                                <p style="color: white;">Total Amount:</p>
                                <p style="color: #4CAF50;">₹<?php echo number_format($order['total_amount'] ?? 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Details -->
                    <div class="Order" style="border-top: 1px solid #444444;">
                        <p class="font-weight-bold mb-3" style="color: white;">Order Details</p>
                        <ul class="list-unstyled small">
                            <li class="d-flex justify-content-between py-1">
                                <span style="color: #a0a0a0;">Order ID:</span>
                                <span class="font-weight-medium" style="color: white;">#<?php echo h($order['id']); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span style="color: #a0a0a0;">Date:</span>
                                <span class="font-weight-medium" style="color: white;"><?php echo date('d M Y, h:i A'); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span style="color: #a0a0a0;">Status:</span>
                                <span class="badge px-2 py-1" style="background-color: #ff9800; color: white; font-size: 0.75rem;">Pending Payment</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Form (Right Side) -->
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body" style="color: white;">
                    <div class="text-center mb-4">
                        <div class="card-header-p" style="color: white;">
                            <span><i class="fas fa-shield-alt mr-3" style="color: #4CAF50;"></i></span>
                            <p>Secure Payment</p>
                        </div>
                        <p class="small mb-0" style="color: #a0a0a0; text-align: center;">Complete your payment to confirm your order</p>
                        <p class="mb-0 small" style="color: #cccccc; padding: 0 1rem; ">Your payment information is processed securely. We do not store your card details.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert" style="background-color: #f44336; border: 1px solid white; color: white;">
                            <h5 class="alert-heading">Payment Error</h5>
                            <p><?php echo h($error); ?></p>
                            <hr style="border-color: white;">
                            <p class="mb-0">Please try again or contact support if the problem persists.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Test Card Information -->
                    <div class="alert mt-3" style="color: white; padding: 0 1rem;">
                        <p class="mb-2 small" style="color: white;">Use the following test card details:</p>
                         <ul class="list-unstyled small mb-2">
                            <li style="color: white;"><strong>Card Number:</strong>5500 6700 0000 1002</li>
                            <li style="color: white;"><strong>Expiry:</strong> 12/30 (any future date)</li>
                            <li style="color: white;"><strong>CVV:</strong> 123</li>
                            <li style="color: white;"><strong>Name:</strong> Test User</li>
                        </ul>
                        <p class="mb-0 small" style="color: #ffeb3b; text-align: center;">
                            <i class="fas fa-exclamation-triangle mr-1"></i> 
                            Make sure to use the exact card number above and a future expiry date.
                        </p>
                    </div>

                      <!-- Razorpay Payment Button -->
                    <div class="mt-4">
                        <form action="process-payment.php" method="POST" id="payment-form">
                            <?php if ($isCartCheckout): ?>
                                <!-- Cart checkout - show multiple orders summary -->
                                <div class="cart-orders-summary" style="margin-bottom: 2rem; padding: 1rem; background-color: #3d3d3d; border-radius: 8px;">
                                    <h6 style="color: white; margin-bottom: 1rem;">Orders Summary (<?php echo count($orders); ?> items)</h6>
                                    <?php foreach ($orders as $index => $ord): ?>
                                    <div class="order-summary-item" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; padding: 0.5rem; background-color: #4d4d4d; border-radius: 4px;">
                                        <span style="color: white;"><?php echo h($ord['item_name']); ?> (x<?php echo $ord['qty']; ?>)</span>
                                        <span style="color: #4CAF50;">₹<?php echo number_format($ord['total_amount'], 2); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                    <hr style="border-color: #666666; margin: 1rem 0;">
                                    <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 600;">
                                        <span style="color: white;">Total Amount:</span>
                                        <span style="color: #4CAF50;">₹<?php echo number_format($totalAmount, 2); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Create a combined Razorpay order for cart checkout -->
                                <?php
                                require_once 'vendor/autoload.php';
                                $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
                                
                                $combinedRazorpayOrder = $api->order->create([
                                    'receipt' => 'cart_' . substr(session_id(), 0, 32),
                                    'amount' => $totalAmount * 100,
                                    'currency' => 'INR',
                                    'payment_capture' => 1
                                ]);
                                $combinedRazorpayOrderId = $combinedRazorpayOrder->id;
                                ?>
                            <?php else: ?>
                                <!-- Single order from menu.php -->
                                <?php
                                // Initialize Razorpay for single order
                                $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
                                
                                // Check if we need to create a Razorpay order
                                if (empty($order['razorpay_order_id'])) {
                                    $razorpayOrder = $api->order->create([
                                        'receipt' => 'order_' . $orderId,
                                        'amount' => $order['total_amount'] * 100,
                                        'currency' => 'INR',
                                        'payment_capture' => 1
                                    ]);

                                    // Update order with Razorpay order ID
                                    $stmt = $pdo->prepare('UPDATE orders SET razorpay_order_id = ? WHERE id = ?');
                                    $stmt->execute([$razorpayOrder->id, $order['id']]);
                                    $order['razorpay_order_id'] = $razorpayOrder->id;
                                }
                                ?>
                            <?php endif; ?>
                            
                            <script
                                src="https://checkout.razorpay.com/v1/checkout.js"
                                data-key="<?php echo RAZORPAY_KEY_ID; ?>"
                                data-amount="<?php echo $totalAmount * 100; ?>"
                                data-currency="INR"
                                data-order_id="<?php echo $isCartCheckout ? $combinedRazorpayOrderId : $order['razorpay_order_id']; ?>"
                                data-buttontext="Pay ₹<?php echo number_format($totalAmount, 2); ?>"
                                data-name="Tiffin Service"
                                data-description="<?php echo $isCartCheckout ? 'Cart Checkout (' . count($orders) . ' items)' : 'Order #' . $order['id']; ?>"
                                data-prefill.name="<?php echo h(cust_current()['fullName'] ?? ''); ?>"
                                data-prefill.email="<?php echo h(cust_current()['email'] ?? ''); ?>"
                                data-theme.color="#4CAF50"
                                data-prefill.contact="<?php echo h(cust_current()['phone'] ?? ''); ?>">
                            </script>
                            <input type="hidden" name="order_id" value="<?php echo $isCartCheckout ? implode(',', $orderIds) : $order['id']; ?>">
                            <input type="hidden" name="is_cart_checkout" value="<?php echo $isCartCheckout ? '1' : '0'; ?>">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .payment-container {
        padding: 2rem;
    }
    
    .row {
        display: flex;
        gap: 2rem;
    }

    .col-lg-4 {
        max-width: 33.333333%;
    }
    .col-lg-18 {
        max-width: 66.666667%;
    }

    /* Order Details */
    .card.backgaround{
        background-color: #2d2d2d;
        border-radius: 10px;
    }

    .card-header{
        border-bottom: 1px solid white !important;
    }

    .card-header p {
        font-size: 1.5rem;
        font-weight: 600;
        display: flex;
        gap:1rem;
        padding: 1rem;
        margin: 0.5rem;
    }

    .order-details{
        display: flex;
        flex-direction: column;
    }

    .order-details img {
        width: 10rem;
        height: 10rem;
        object-fit: cover;
        justify-content: center;
        display: flex;
        align-items: center;
        margin: 1rem auto;
        border-radius: 1rem;
    }

    .flex-grow  {
        flex-grow: 1;
        padding: 0 1rem;
    }

    .flex-grow p {
        text-align: center;
        font-size: 1.25rem;
        margin: 0.5rem;
    }

    .amount-details {
        display: flex;
        gap: 1rem;
        justify-content: center;
        padding-top: 0.5rem;
    }

    .final-amount {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 1rem;
        margin: 1rem 0;
    }

    .Order{
        padding: 0 1rem;
    }

    /* Custom Styles */
    .card {
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
        background-color: #2d2d2d;
    }
    
    .card:hover {
        box-shadow: 0 5px 20px rgba(0,0,0,0.1) !important;
    }

    .card-header-p {
        font-size: 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
         border-bottom: 1px solid white !important;
         gap:1rem;
         padding: 0 1rem;
    }
    
    .razorpay-payment-button {
        background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%) !important;
        color: white !important;
        padding: 12px 24px !important;
        border: none !important;
        border-radius: 6px !important;
        font-size: 16px !important;
        font-weight: 600 !important;
        width: 100% !important;
        cursor: pointer !important;
        transition: all 0.3s ease !important;
        text-transform: none !important;
        letter-spacing: normal !important;
        height: auto !important;
        line-height: 1.5 !important;
        box-shadow: 0 2px 5px rgba(76, 175, 80, 0.3) !important;
    }
    
    .razorpay-payment-button:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4) !important;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 991.98px) {
        .card {
            margin-bottom: 20px;
        }
    }
    
    @media (max-width: 767.98px) {
        .card-body {
            padding: 1.25rem;
        }
        
        .razorpay-payment-button {
            padding: 10px 16px !important;
            font-size: 15px !important;
        }
    }
    
    @media (max-width: 575.98px) {
        .card {
            border-radius: 8px;
        }
        
        .card-header h4 {
            font-size: 1.25rem;
        }
        
        .razorpay-payment-button {
            padding: 8px 12px !important;
            font-size: 14px !important;
        }
    }
</style>

<script>
    // Show loading state when form is submitted
    document.getElementById('payment-form').addEventListener('submit', function() {
        const button = document.querySelector('.razorpay-payment-button');
        if (button) {
            button.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span> Processing...';
            button.disabled = true;
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Handle Razorpay payment errors
        //  if (window.Razorpay) {
        //     window.Razorpay.on('payment.failed', function(response) {
        //         console.error('Payment failed:', response.error);
        //         // You can show an error message to the user
        //         alert('Payment failed: ' + (response.error.description || 'Unknown error occurred'));
                
        //         // Re-enable the payment button
        //         const button = document.querySelector('.razorpay-payment-button');
        //         if (button) {
        //             button.innerHTML = 'Try Again';
        //             button.disabled = false;
        //         }
        //     });
        // }

        // Form submission handler
        const paymentForm = document.getElementById('payment-form');
        if (paymentForm) {
            paymentForm.addEventListener('submit', function() {
                const button = this.querySelector('.razorpay-payment-button');
                if (button) {
                    button.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status"></span> Processing...';
                    button.disabled = true;
                }
            });
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>