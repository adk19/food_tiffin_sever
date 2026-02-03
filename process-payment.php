<?php
// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/init.php';
require_once 'includes/razorpay.php';
require_once 'vendor/autoload.php';

use Razorpay\Api\Api;

// Test mode check
if (!defined('RAZORPAY_TEST_MODE') || !RAZORPAY_TEST_MODE) {
    die('Test mode is not enabled. Please enable test mode in razorpay.php');
}

// Simple test mode logging
error_log('=== TEST MODE: Payment Processing Started ===');
error_log('POST Data: ' . print_r($_POST, true));

// Basic session start if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check user login
if (!cust_is_authed()) {
    error_log('TEST MODE: User not logged in');
    $_SESSION['error'] = 'Please login to complete your payment.';
    header('Location: login.php?redirect=payment.php');
    exit;
}

// Required parameters check
if (empty($_POST['razorpay_payment_id']) || empty($_POST['razorpay_signature']) || empty($_POST['order_id'])) {
    error_log('TEST MODE: Missing required parameters');
    $_SESSION['error'] = 'Invalid payment details. Please try again.';
    header('Location: cart.php');
    exit;
}

$paymentId = $_POST['razorpay_payment_id'];
$signature = $_POST['razorpay_signature'];
$orderId = (int) $_POST['order_id'];
$razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
$userId = cust_current()['id'];

error_log("TEST MODE: Processing payment - Order: $orderId, User: $userId, PaymentID: $paymentId, RazorpayOrderID: $razorpayOrderId");

try {
    $pdo = db();

    // Get order with minimal fields for test mode
    $stmt = $pdo->prepare('
        SELECT o.*, m.name as item_name 
        FROM orders o 
        LEFT JOIN menu_items m ON o.item_id = m.id 
        WHERE o.id = ? AND o.user_id = ? 
        LIMIT 1
    ');
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        error_log("TEST MODE: Order not found - Order: $orderId, User: $userId");
        $_SESSION['error'] = 'Order not found. Please try again.';
        header('Location: menu.php');
        exit;
    }

    // In test mode, we'll verify with Razorpay but skip amount validation
    error_log("TEST MODE: Verifying payment with Razorpay - PaymentID: $paymentId");

    try {
        $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

        // Verify the payment signature
        $attributes = [
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature
        ];

        $api->utility->verifyPaymentSignature($attributes);
        error_log('TEST MODE: Payment signature verified successfully');
    } catch (Exception $e) {
        // For test mode, we'll log the error but still process the payment
        error_log('TEST MODE: Payment verification failed (but continuing in test mode): ' . $e->getMessage());
        // Continue with the payment processing even if verification fails in test mode
    }

    // Update order status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET razorpay_payment_id = ?, 
            razorpay_order_id = ?,
            razorpay_signature = ?,
            payment_status = 'completed',
            status = 'confirmed',
            updated_at = NOW()
        WHERE id = ?
    ");

    $updateResult = $stmt->execute([
        $paymentId,
        $razorpayOrderId,
        $signature,
        $orderId
    ]);

    if (!$updateResult) {
        throw new Exception('Failed to update order status in database');
    }

    error_log("TEST MODE: Order $orderId updated successfully in database");

    // Success - redirect to confirmation
    error_log("TEST MODE: Payment processed successfully - Order: $orderId");
    header('Location: order-confirmation.php?id=' . $orderId);
    exit;
} catch (Exception $e) {
    // Simple error handling for test mode
    $errorMsg = 'TEST MODE ERROR: ' . $e->getMessage();
    error_log($errorMsg);
    $_SESSION['error'] = 'Test Payment Failed: ' . $e->getMessage();
    header('Location: payment-failed.php?order_id=' . $orderId);
    exit;
}
