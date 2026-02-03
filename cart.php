<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/razorpay.php';
$page = 'Cart';
$active = 'cart';

// Cart structure: $_SESSION['cart'] = [ itemId => qty, ... ]
if (!isset($_SESSION['cart']))
  $_SESSION['cart'] = [];

function cart_add($id, $qty = 1)
{
  $id = (int) $id;
  $qty = max(1, (int) $qty);
  if ($id <= 0)
    return;
  $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
}

function cart_set($id, $qty)
{
  $id = (int) $id;
  $qty = max(0, (int) $qty);
  if ($id <= 0)
    return;
  if ($qty === 0)
    unset($_SESSION['cart'][$id]);
  else
    $_SESSION['cart'][$id] = $qty;
}

function cart_clear()
{
  $_SESSION['cart'] = [];
}

// Simple GET handler for add
if (($_GET['action'] ?? '') === 'add') {
  cart_add($_GET['id'] ?? 0, $_GET['qty'] ?? 1);
  redirect('cart.php');
}

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      cart_set($id, 0);  // This will remove the item
    }
    redirect('cart.php');
  } elseif ($action === 'clear') {
    cart_clear();
    redirect('cart.php');
  } elseif ($action === 'checkout') {
    if (!cust_is_authed()) {
      redirect('login.php?redirect=' . urlencode('cart.php'));
    }
    
    // Get customer data
    $customer = cust_current();
    if (!$customer) {
      redirect('login.php?redirect=' . urlencode('cart.php'));
    }

    try {
      $pdo->beginTransaction();
      $orderIds = [];
      $totalAmount = 0;

      // Process each cart item as a separate order
      foreach ($_SESSION['cart'] as $id => $qty) {
        // Get item details
        $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
          error_log("Skipping invalid item ID: $id");
          continue; // Skip invalid items
        }

        // Calculate total amount for this item
        $itemTotal = $item['price'] * $qty;
        $totalAmount += $itemTotal;

        // Create order in database
        $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, 
                    total_amount,
                    item_id,
                    qty,
                    price,
                    status, 
                    payment_status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', 'pending', NOW())
            ");
        
        // Debug: Log the SQL and parameters
        error_log("SQL: INSERT INTO orders (user_id, total_amount, item_id, qty, price, status, payment_status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        error_log("Parameters: " . json_encode([$customer['id'], $itemTotal, $id, $qty, $item['price']]));
        
        if (!$stmt->execute([$customer['id'], $itemTotal, $id, $qty, $item['price']])) {
          $error = "Execute failed: " . json_encode($stmt->errorInfo());
          error_log("Database error: " . $error);
          
          // Show error to user immediately
          $_SESSION['error'] = "Error creating order for item: $item[name]. Error: $error";
          $pdo->rollBack();
          redirect('cart.php');
        }
        
        $orderId = $pdo->lastInsertId();
        if (!$orderId) {
          $error = "Failed to get last insert ID: " . json_encode($stmt->errorInfo());
          error_log("Database error: " . $error);
          
          // Show error to user immediately
          $_SESSION['error'] = "Failed to create order. Please try again.";
          $pdo->rollBack();
          redirect('cart.php');
        }
        $orderIds[] = $orderId;

        // Create Razorpay order for each item
        require_once 'vendor/autoload.php';
        $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

        $razorpayOrder = $api->order->create([
          'receipt' => 'order_' . $orderId,
          'amount' => $itemTotal * 100,  // Convert to paise
          'currency' => 'INR',
          'payment_capture' => 1
        ]);

        $razorpayOrderId = $razorpayOrder->id;

        // Update order with Razorpay order ID
        $stmt = $pdo->prepare('UPDATE orders SET razorpay_order_id = ? WHERE id = ?');
        $stmt->execute([$razorpayOrderId, $orderId]);
      }

      $pdo->commit();

      // Store order data in session for verification
      $_SESSION['checkout_orders'] = $orderIds;
      $_SESSION['payment_data'] = [
        'order_ids' => $orderIds,
        'amount' => $totalAmount * 100,
        'total_items' => count($orderIds)
      ];

      cart_clear();
      redirect('payment.php?checkout=cart&total=' . $totalAmount);
    } catch (Exception $e) {
      if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      error_log('Checkout processing error: ' . $e->getMessage());
      $_SESSION['error'] = 'Error processing checkout: ' . $e->getMessage();
      redirect('cart.php');
    }
  }
}

// Fetch items in cart
$items = [];
$sum = 0.0;
foreach ($_SESSION['cart'] as $id => $qty) {
  $m = get_menu_item_by_id($id);
  if ($m) {
    $price = (float) $m['price'];
    $disc = (int) $m['discount'];
    $final = $price * (1 - $disc / 100);
    $line = $final * max(1, (int) $qty);
    $sum += $line;
    $m['qty'] = (int) $qty;
    $m['final'] = $final;
    $m['line'] = $line;
    $items[] = $m;
  }
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero" style="--hero:url('https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=1600&auto=format&fit=crop');">
  <div class="hero-overlay">
    <h1>Your Cart</h1>
    <div class="crumbs"><a href="index.php">Home</a> › <span>Cart</span></div>
  </div>
</section>
<section class="section">
  <div class="container">
    <div class="section-title">Cart Items</div>

    <?php if (empty($items)): ?>
      <div class="cart-card">
        <div class="cart-empty">
          <div class="icon">🛒</div>
          <div class="title">Your Cart is Empty</div>
          <div class="subtitle">Looks like you haven't added any items to your cart yet</div>
          <a href="menu.php" class="btn">Browse Menu</a>
        </div>
      </div>
    <?php else: ?>
      <div class="cart-container">
        <div class="cart-card">
          <div class="cart-header">
            <div>Item</div>
            <div>Price</div>
            <div>Qty</div>
            <div>Total</div>
            <div>Actions</div>
          </div>
          <ul class="cart-items">
            <?php foreach ($items as $m): ?>
              <li class="cart-item">
                <div class="item-info">
                  <?php if (!empty($m['image'])): ?>
                    <img class="item-image" src="<?php echo h($m['image']); ?>" alt="<?php echo h($m['name']); ?>" />
                  <?php else: ?>
                    <div class="item-image" style="background:#0b1220; display:grid; place-items:center;">No Image</div>
                  <?php endif; ?>
                  <div class="item-details">
                    <div class="item-name"><?php echo h($m['name']); ?></div>
                    <div class="item-desc"><?php echo h($m['detail']); ?></div>
                  </div>
                </div>
                <div class="item-price">₹<?php echo number_format($m['final'], 2); ?></div>
                <div class="item-qty"><?php echo h($m['qty']); ?></div>
                <div class="item-price">₹<?php echo number_format($m['line'], 2); ?></div>
                <div class="item-actions">
                  <form method="post">
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?php echo h($m['id']); ?>" />
                    <button class="btn btn-outline" type="submit" style="padding:6px 12px; font-size:14px;">Remove</button>
                  </form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        
        <div class="cart-summary">
          <div class="summary-row">
            <div>Subtotal</div>
            <div>₹<?php echo number_format($sum, 2); ?></div>
          </div>
          <div class="summary-row">
            <div>Delivery Fee</div>
            <div>₹0.00</div>
          </div>
          <div class="summary-row">
            <div>Tax</div>
            <div>₹0.00</div>
          </div>
          <div class="summary-row summary-total">
            <div>Total</div>
            <div>₹<?php echo number_format($sum, 2); ?></div>
          </div>
          
          <div class="cart-actions">
            <form method="post">
              <input type="hidden" name="action" value="clear" />
              <button class="btn btn-outline" type="submit" style="width:100%; cursor: pointer;">Clear Cart</button>
            </form>
            <form method="post">
              <input type="hidden" name="action" value="checkout" />
              <button class="btn" type="submit" style="width:100%; cursor: pointer;">Proceed to Checkout</button>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
