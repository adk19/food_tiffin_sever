<?php
require_once 'includes/init.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!cust_is_authed()) {
    header('Location: login.php');
    exit;
}

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Get current user
$currentUser = cust_current();
if (!$currentUser) {
    // This should theoretically never happen because of the cust_is_authed() check above
    header('Location: login.php');
    exit;
}

// Get order details
$stmt = $pdo->prepare('
    SELECT o.*, u.full_name, u.email, u.phone, u.address 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
');
$stmt->execute([$orderId, $currentUser['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    header('Location: menu.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare('
    SELECT oi.*, i.name, i.image 
    FROM order_items oi 
    JOIN menu_items i ON oi.item_id = i.id 
    WHERE oi.order_id = ?
');
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container confirmation-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card confirmation-card">
                <div class="card-header confirmation-header">
                    <div class="d-flex justify-content-between align-items-center"
                    style="display: flex; align-items: center; gap: 1rem;>
                        <span class="order-number">#<?php echo $orderId; ?></span>
                        <h4 class="mb-0">Order Confirmation</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h5>Thank you for your order!</h5>
                        <p>Your order has been placed successfully and is being processed. We'll notify you once it's ready for delivery.</p>
                    </div>
                    
                    <div class="row confirmation-content">
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-header">
                                    <h6>Order Summary</h6>
                                </div>
                                <div class="info-body">
                                    <div class="order-items">
                                        <?php foreach ($orderItems as $item): ?>
                                        <div class="order-item">
                                            <div class="item-details">
                                                <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php endif; ?>
                                                <div class="item-info">
                                                    <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <span class="item-qty">Qty: <?php echo $item['quantity']; ?></span>
                                                </div>
                                            </div>
                                            <div class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="order-total">
                                        <div class="total-row">
                                            <span>Subtotal:</span>
                                            <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                        <div class="total-row">
                                            <span>Delivery Fee:</span>
                                            <span>Free</span>
                                        </div>
                                        <div class="total-row final">
                                            <span>Total:</span>
                                            <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-header">
                                    <h6>Delivery Information</h6>
                                </div>
                                <div class="info-body">
                                    <div class="delivery-info">
                                        <h6><?php echo htmlspecialchars($order['full_name']); ?></h6>
                                        <div class="info-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo nl2br(htmlspecialchars($order['address'])); ?></span>
                                        </div>
                                        <?php if (!empty($order['phone'])): ?>
                                        <div class="info-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($order['phone']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="info-item">
                                            <i class="fas fa-envelope"></i>
                                            <span><?php echo htmlspecialchars($order['email']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-header">
                                    <h6>Order Status</h6>
                                </div>
                                <div class="info-body">
                                    <div class="status-timeline">
                                        <div class="status-item completed">
                                            <div class="status-icon">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div class="status-content">
                                                <h6>Order Placed</h6>
                                                <small><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="status-item">
                                            <div class="status-icon">
                                                <i class="fas fa-utensils"></i>
                                            </div>
                                            <div class="status-content">
                                                <h6>Preparing Your Order</h6>
                                                <small>Your food is being prepared</small>
                                            </div>
                                        </div>
                                        
                                        <div class="status-item">
                                            <div class="status-icon">
                                                <i class="fas fa-motorcycle"></i>
                                            </div>
                                            <div class="status-content">
                                                <h6>Out for Delivery</h6>
                                                <small>Estimated delivery time: 30-45 mins</small>
                                            </div>
                                        </div>
                                        
                                        <div class="status-item">
                                            <div class="status-icon">
                                                <i class="fas fa-home"></i>
                                            </div>
                                            <div class="status-content">
                                                <h6>Delivered</h6>
                                                <small>Your order will be delivered soon</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="index.php" class="btn-back-home">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Confirmation Page Styles */
.confirmation-container {
    min-height: 100vh;
    padding: 2rem;
    color: white;
}

.confirmation-card {
    border-radius: 10px;
    overflow: hidden;
}

.confirmation-header {
    background-color: #4CAF50;
    border-bottom: 1px solid white;
    padding: 1.5rem;
}

.confirmation-header h4 {
    color: white;
    font-weight: 600;
    margin: 0;
}

.order-number {
    background-color: white;
    color: #4CAF50;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 1rem;
}

.success-message {
    text-align: center;
    padding: 2rem;
    background-color: #3d3d3d;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.success-icon {
    font-size: 3rem;
    color: #4CAF50;
    margin-bottom: 1rem;
}

.success-message h5 {
    color: white;
    margin-bottom: 1rem;
    font-weight: 600;
}

.success-message p {
    color: #a0a0a0;
    margin: 0;
}

.info-card {
    background-color: #3d3d3d;
    border: 1px solid #666666;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.info-header {
    background-color: #4d4d4d;
    border-bottom: 1px solid #666666;
    padding: 1rem;
}

.info-header h6 {
    font-size: 1rem;
    color: white;
    margin: 0;
    font-weight: 600;
}

.info-body {
    padding: 1.5rem;
}

/* Order Items */
.order-items {
    margin-bottom: 1.5rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #666666;
}

.order-item:last-child {
    border-bottom: none;
}

.item-details {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.item-details img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.item-info h6 {
    color: white;
    margin: 0 0 0.25rem 0;
    font-weight: 600;
}

.item-qty {
    color: #a0a0a0;
    font-size: 0.875rem;
}

.item-price {
    color: #4CAF50;
    font-weight: 600;
    font-size: 1.1rem;
}

/* Order Total */
.order-total {
    border-top: 1px solid #666666;
    padding-top: 1rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    color: #a0a0a0;
}

.total-row.final {
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
    border-top: 1px solid #666666;
    padding-top: 1rem;
    margin-top: 0.5rem;
}

.total-row.final span:last-child {
    color: #4CAF50;
}

/* Delivery Info */
.delivery-info h6 {
    color: white;
    margin-bottom: 1rem;
    font-weight: 600;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
    color: #a0a0a0;
}

.info-item i {
    color: #4CAF50;
    margin-top: 0.25rem;
    min-width: 20px;
}

.info-item span {
    line-height: 1.5;
}

/* Status Timeline */
.status-timeline {
    position: relative;
    padding-left: 2rem;
}

.status-timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 1rem;
    bottom: 1rem;
    width: 2px;
    background-color: #666666;
}

.status-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.status-icon {
    position: absolute;
    left: -2rem;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background-color: #666666;
    color: #a0a0a0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    z-index: 1;
}

.status-item.completed .status-icon {
    background-color: #4CAF50;
    color: white;
}

.status-content h6 {
    color: white;
    margin: 0 0 0.25rem 0;
    font-weight: 600;
}

.status-content small {
    color: #a0a0a0;
    font-size: 0.875rem;
}

/* Action Buttons */
.action-buttons {
    text-align: center;
    margin-top: 2rem;
}

.btn-back-home {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 2rem;
    background-color: transparent;
    color: white;
    border: 1px solid white;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-back-home:hover {
    background-color: white;
    color: #1a1a1a;
    text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .confirmation-container {
        padding: 1rem;
    }
    
    .confirmation-header {
        padding: 1rem;
    }
    
    .confirmation-header h4 {
        font-size: 1.25rem;
    }
    
    .order-number {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    .success-message {
        padding: 1.5rem;
    }
    
    .success-icon {
        font-size: 2.5rem;
    }
    
    .item-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .item-details img {
        width: 50px;
        height: 50px;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .item-price {
        align-self: flex-end;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
