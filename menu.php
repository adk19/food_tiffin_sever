<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/razorpay.php';
// Check if user is logged in
if (!($customer = cust_current())) {
  $currentUrl = 'menu.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
  header('Location: login.php?redirect=' . urlencode($currentUrl));
  exit;
}
$page = 'Menu';
$active = 'menu';
$pdo = db();
$categories = fetch_categories_all();
$filterCat = $_GET['category'] ?? 'all';
$pageNo = max(1, (int) ($_GET['page'] ?? 1));
$size = 12;
[$items, $total] = fetch_menu_items_paginated($filterCat, $pageNo, $size);
$pages = max(1, (int) ceil($total / $size));
if ($pageNo > $pages)
  $pageNo = $pages;
// Handle direct order with payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'order_now') {
  $id = (int) ($_POST['id'] ?? 0);
  $qty = max(1, (int) ($_POST['qty'] ?? 1));
  $back = 'menu.php?' . http_build_query(['category' => $filterCat, 'page' => $pageNo]);

  // Get customer data
  $customer = cust_current();
  if (!$customer) {
    redirect('login.php?redirect=' . urlencode($back));
  }

  try {
    // Get item details
    $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
      throw new Exception('Item not found');
    }

    // Calculate total amount
    $totalAmount = $item['price'] * $qty;

    // Create order in database
    $pdo->beginTransaction();

    // Create order
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
    $stmt->execute([
      $customer['id'],  // Use the customer ID from the session
      $totalAmount,
      $id,
      $qty,
      $item['price']
    ]);
    $orderId = $pdo->lastInsertId();

    // Create Razorpay order
    require_once 'vendor/autoload.php';
    $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

    $razorpayOrder = $api->order->create([
      'receipt' => 'order_' . $orderId,
      'amount' => $totalAmount * 100,  // Convert to paise
      'currency' => 'INR',
      'payment_capture' => 1
    ]);

    $razorpayOrderId = $razorpayOrder->id;

    // Update order with Razorpay order ID
    $stmt = $pdo->prepare('UPDATE orders SET razorpay_order_id = ? WHERE id = ?');
    $stmt->execute([$razorpayOrderId, $orderId]);

    $pdo->commit();

    // Store order ID in session for verification
    $_SESSION['current_order_id'] = $orderId;

    // Redirect to payment page
    $_SESSION['payment_data'] = [
      'order_id' => $orderId,
      'amount' => $totalAmount * 100,
      'item_name' => $item['name'],
      'razorpay_order_id' => $razorpayOrderId
    ];

    redirect('payment.php?order_id=' . $orderId);
  } catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('Order processing error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error processing your order: ' . $e->getMessage();
    redirect($back);
  }
}
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero" style="--hero:url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1600&auto=format&fit=crop');">
  <div class="hero-overlay">
    <h1>Menu</h1>
    <div class="crumbs"><a href="index.php">Home</a> › <span>Menu</span></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-title" style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
      <span>Our Menu</span>
    </div>
    
    <form method="get" class="form-row" style="grid-template-columns: 1fr auto; align-items:center; margin-bottom: 20px;">
      <div>
        <label class="label">Filter by Category</label>
        <select class="select" name="category" onchange="this.form.submit()">
          <option value="all" <?php echo $filterCat === 'all' ? 'selected' : ''; ?>>All Categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo h($c['id']); ?>" <?php echo ((string) $filterCat === (string) $c['id']) ? 'selected' : ''; ?>>
              <?php echo h($c['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger">
        <?php
        echo h($_SESSION['error']);
        unset($_SESSION['error']);
        ?>
      </div>
    <?php endif; ?>

    <div class="menu-grid" style="margin-top:16px;">
      <?php if (count($items) > 0): ?>
        <?php foreach ($items as $item): ?>
          <div class="menu-card" id="item-<?php echo h($item['id']); ?>">
            <?php if (!empty($item['image'])): ?>
              <img src="<?php echo h($item['image']); ?>" alt="<?php echo h($item['name']); ?>" class="menu-item-image">
            <?php else: ?>
              <div class="no-image">No Image</div>
            <?php endif; ?>
            
            <div class="menu-item-details">
              <h3 class="menu-item-name"><?php echo h($item['name']); ?></h3>
              <p class="menu-item-description"><?php echo h($item['detail']); ?></p>
              <div class="menu-item-price">₹<?php echo number_format($item['price'], 2); ?></div>
              
              <form method="post" class="order-form">
                <input type="hidden" name="action" value="order_now" />
                <input type="hidden" name="id" value="<?php echo h($item['id']); ?>" />
                <div class="quantity-controls">
                  <button type="button" class="qty-btn minus" onclick="updateQuantity(this, -1)">-</button>
                  <input type="number" 
                         name="qty" 
                         class="qty-input" 
                         value="1" 
                         min="1" 
                         data-item-id="<?php echo h($item['id']); ?>">
                  <button type="button" class="qty-btn plus" onclick="updateQuantity(this, 1)">+</button>
                </div>
                <button type="submit" class="btn btn-order-now">
                  <i class="fas fa-shopping-cart"></i> Order Now
                </button>
              </form>
              
              <a href="cart.php?action=add&id=<?php echo h($item['id']); ?>&qty=1" class="btn btn-add-to-cart">
                <i class="fas fa-plus"></i> Add to Cart
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-items">
          <p>No items found in this category.</p>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php $baseUrl = 'menu.php?category=' . urlencode($filterCat); ?>
        
        <?php if ($pageNo > 1): ?>
          <a href="<?php echo $baseUrl . '&page=1'; ?>" class="page-link first" title="First Page">&laquo;</a>
          <a href="<?php echo $baseUrl . '&page=' . ($pageNo - 1); ?>" class="page-link prev" title="Previous Page">&lsaquo;</a>
        <?php endif; ?>
        
        <?php
        // Show page numbers
        $start = max(1, $pageNo - 2);
        $end = min($pages, $start + 4);
        $start = max(1, $end - 4);

        for ($i = $start; $i <= $end; $i++):
          ?>
          <a href="<?php echo $baseUrl . '&page=' . $i; ?>" 
             class="page-link <?php echo $i == $pageNo ? 'active' : ''; ?>">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>
        
        <?php if ($pageNo < $pages): ?>
          <a href="<?php echo $baseUrl . '&page=' . ($pageNo + 1); ?>" class="page-link next" title="Next Page">&rsaquo;</a>
          <a href="<?php echo $baseUrl . '&page=' . $pages; ?>" class="page-link last" title="Last Page">&raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
  margin: 20px 0;
}

.menu-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.menu-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.menu-item-image {
  width: 100%;
  height: 200px;
  object-fit: cover;
}

.no-image {
  height: 200px;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #999;
}

.menu-item-details {
  padding: 15px;
}

.menu-item-name {
  margin: 0 0 8px;
  font-size: 1.2rem;
  color: #333;
}

.menu-item-description {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 12px;
  min-height: 40px;
}

.menu-item-price {
  font-weight: bold;
  color: #e74c3c;
  font-size: 1.2rem;
  margin-bottom: 15px;
}

.quantity-controls {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.qty-btn {
  background: #f8f9fa;
  border: 1px solid #ddd;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1rem;
  user-select: none;
  color: black;
}

.qty-btn:hover {
  background: #e9ecef;
}

.qty-input {
  width: 50px;
  height: 30px;
  text-align: center;
  border: 1px solid #ddd;
  margin: 0 5px;
  -moz-appearance: textfield;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.btn-order-now {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  align-items: center;
  width: 100%;
  background: #e74c3c;
  color: white;
  border: none;
  padding: 8px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  margin-bottom: 8px;
  transition: background 0.3s;
}

.btn-order-now:hover {
  background: #c0392b;
}

.btn-add-to-cart {
  width: 100%;
  background: white;
  color: #e74c3c;
  border: 1px solid #e74c3c;
  padding: 8px;
  border-radius: 4px;
  cursor: pointer;
  text-align: center;
  display: block;
  transition: all 0.3s;
}

.btn-add-to-cart:hover {
  background: #f8f9fa;
  color: #c0392b;
  text-decoration: none;
}

.pagination {
  display: flex;
  justify-content: center;
  margin-top: 30px;
  flex-wrap: wrap;
  gap: 5px;
}

.page-link {
  padding: 8px 12px;
  border: 1px solid #ddd;
  margin: 0 2px;
  color: #333;
  text-decoration: none;
  border-radius: 4px;
  transition: all 0.3s;
}

.page-link:hover {
  background: #f8f9fa;
}

.page-link.active {
  background: #e74c3c;
  color: white;
  border-color: #e74c3c;
}

.alert {
  padding: 12px 15px;
  margin-bottom: 20px;
  border: 1px solid transparent;
  border-radius: 4px;
}

.alert-danger {
  color: #721c24;
  background-color: #f8d7da;
  border-color: #f5c6cb;
}

@media (max-width: 768px) {
  .menu-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  }
  
  .pagination {
    gap: 3px;
  }
  
  .page-link {
    padding: 6px 10px;
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .menu-grid {
    grid-template-columns: 1fr;
  }
  
  .pagination {
    flex-wrap: wrap;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize quantity inputs
    document.querySelectorAll('.qty-input').forEach(input => {
        // Ensure minimum value
        input.addEventListener('change', function() {
            if (this.value < 1) this.value = 1;
            if (this.value > 100) this.value = 100; // Optional: Set a max limit
        });
        
        // Prevent non-numeric input
        input.addEventListener('keypress', function(e) {
            if (e.key === 'e' || e.key === 'E' || e.key === '-' || e.key === '+') {
                e.preventDefault();
            }
        });
    });
    
    // Show loading state on form submission
    document.querySelectorAll('.order-form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    });
});

// Update quantity with plus/minus buttons
function updateQuantity(button, change) {
    const container = button.closest('.quantity-controls');
    const input = container.querySelector('.qty-input');
    let value = parseInt(input.value) || 1;
    value += change;
    
    if (value < 1) value = 1;
    if (value > 100) value = 100; // Optional: Set a max limit
    
    input.value = value;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>