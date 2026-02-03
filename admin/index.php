<?php
require_once __DIR__ . '/../includes/init.php';
if (!admin_is_authed()) {
  redirect('login.php');
}
$p = $_GET['p'] ?? '/dashboard';
$pageTitleMap = [
  '/dashboard' => 'Dashboard',
  '/users' => 'Users',
  '/categories' => 'Categories',
  '/menu' => 'Menu',
  '/orders' => 'Orders',
  '/contacts' => 'Contact Messages',
];
$pageTitle = $pageTitleMap[$p] ?? 'Dashboard';
$active = $p;
include __DIR__ . '/includes/header.php';

function money($n)
{
  return '₹' . number_format((float) $n, 0);
}

switch ($p) {
  case '/dashboard':
    $t = get_totals();
    echo '<div class="grid">';
    $stats = [
      ['title' => 'Categories', 'value' => $t['categoryCount'], 'icon' => '📦', 'tone' => 'info'],
      ['title' => 'Today Orders', 'value' => $t['todayOrders'], 'icon' => '🧾', 'tone' => 'info'],
      ['title' => 'Month Orders', 'value' => $t['monthOrders'], 'icon' => '📅', 'tone' => 'info'],
      ['title' => 'Pending', 'value' => $t['todayPending'], 'icon' => '⏳', 'tone' => 'warn'],
      ['title' => 'Confirmed', 'value' => $t['todayConfirmed'], 'icon' => '✅', 'tone' => 'good'],
      ['title' => 'Delivered', 'value' => $t['todayDelivered'], 'icon' => '🚚', 'tone' => 'good'],
      ['title' => 'Cancelled', 'value' => $t['todayCancelled'], 'icon' => '🗑️', 'tone' => 'danger'],
      ['title' => 'Unread Messages', 'value' => $t['unreadContacts'], 'icon' => '📧', 'tone' => 'warn'],
      ['title' => 'Today Earning', 'value' => money(round($t['todayRevenue'])), 'icon' => '₹', 'tone' => 'good'],
    ];
    foreach ($stats as $s) {
      $val = is_numeric($s['value']) ? h($s['value']) : $s['value'];
      echo '<div class="col-3">'
        . '<div class="kpi kpi-' . h($s['tone']) . '">'
        . '<div class="kpi-head">'
        . '<div class="kpi-icon">' . h($s['icon']) . '</div>'
        . '<div class="kpi-label">' . h($s['title']) . '</div>'
        . '</div>'
        . '<div class="kpi-value">' . $val . '</div>'
        . '</div>'
        . '</div>';
    }
    // Month earning card
    echo '<div class="col-6"><div class="card">'
      . '<div class="card-header"><div class="card-title">This Month Earning</div><div class="card-subtitle">Total revenue (non-cancelled orders)</div></div>'
      . '<div class="stat"><div class="value">' . money(round($t['monthRevenue'])) . '</div></div>'
      . '</div></div>';
    echo '</div>';
    // Expose data for JS chart
    echo '<script>window.dashboardData = {'
      . 'pending:' . (int) $t['todayPending'] . ',confirmed:' . (int) $t['todayConfirmed'] . ',delivered:' . (int) $t['todayDelivered'] . ',cancelled:' . (int) $t['todayCancelled']
      . '};</script>';
    break;

  case '/users':
    $pdo = db();

    // Handle create user
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
      $full_name = trim((string) ($_POST['full_name'] ?? ''));
      $email = trim((string) ($_POST['email'] ?? ''));
      $phone = trim((string) ($_POST['phone'] ?? ''));
      $address = trim((string) ($_POST['address'] ?? ''));
      $password = trim((string) ($_POST['password'] ?? ''));
      if ($full_name !== '' && $email !== '' && $password !== '') {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $st = db()->prepare('INSERT INTO users (full_name, email, phone, address, password, created_at) VALUES (?,?,?,?,?,?)');
        $st->execute([$full_name, $email, $phone, $address, $hashed_password, time()]);
        redirect('index.php?p=/users');
      }
    }

    // Handle update user
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
      $id = (int) ($_POST['id'] ?? 0);
      $full_name = trim((string) ($_POST['full_name'] ?? ''));
      $email = trim((string) ($_POST['email'] ?? ''));
      $phone = trim((string) ($_POST['phone'] ?? ''));
      $address = trim((string) ($_POST['address'] ?? ''));
      $password = trim((string) ($_POST['password'] ?? ''));
      if ($id && $full_name !== '' && $email !== '') {
        if ($password !== '') {
          $hashed_password = password_hash($password, PASSWORD_DEFAULT);
          $st = db()->prepare('UPDATE users SET full_name=?, email=?, phone=?, address=?, password=? WHERE id=?');
          $st->execute([$full_name, $email, $phone, $address, $hashed_password, $id]);
        } else {
          $st = db()->prepare('UPDATE users SET full_name=?, email=?, phone=?, address=? WHERE id=?');
          $st->execute([$full_name, $email, $phone, $address, $id]);
        }
        redirect('index.php?p=/users');
      }
    }

    // Handle delete user
    if (($_GET['action'] ?? '') === 'del') {
      $id = (int) ($_GET['id'] ?? 0);
      if ($id) {
        db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        redirect('index.php?p=/users');
      }
    }

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $size = max(1, (int) ($_GET['size'] ?? 15));
    $offset = ($page - 1) * $size;
    $total = (int) ($pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, full_name, email, phone, address FROM users ORDER BY id DESC LIMIT ? OFFSET ?');
    $stmt->bindValue(1, (int) $size, PDO::PARAM_INT);
    $stmt->bindValue(2, (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    $slice = $stmt->fetchAll();
    $pages = max(1, (int) ceil($total / $size));
    if ($page > $pages)
      $page = $pages;
    $base = 'index.php?p=/users&size=' . h($size);

    // If editing, fetch the target row and render modal
    $editingId = null;
    $editingUser = null;
    if (($_GET['action'] ?? '') === 'edit') {
      $editingId = (int) ($_GET['id'] ?? 0);
      if ($editingId) {
        foreach ($slice as $u) {
          if ((int) $u['id'] === $editingId) {
            $editingUser = $u;
            break;
          }
        }
      }
    }
    echo '<div class="card">'
      . '<div class="card-header">'
      . '<div><div class="card-title">Login Users</div><div class="card-subtitle">Total: ' . h($total) . '</div></div>'
      . '<div style="display:flex; gap:8px; align-items:center;">'
      . '<button class="btn" onclick="document.getElementById(\'userModal\').style.display=\'block\';">Add User</button>'
      . '<a class="btn" href="' . $base . '&page=' . h($page) . '">Refresh</a>'
      . '</div>'
      . '</div>'
      . '<div class="table-section"><div class="table-wrap">'
      . '<table class="table"><thead><tr><th>Full Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Actions</th></tr></thead><tbody>';
    foreach ($slice as $u) {
      echo '<tr class="table-row">'
        . '<td data-label="Full Name">' . h($u['full_name']) . '</td>'
        . '<td data-label="Email">' . h($u['email']) . '</td>'
        . '<td data-label="Phone">' . h($u['phone']) . '</td>'
        . '<td data-label="Address">' . h($u['address']) . '</td>'
        . '<td data-label="Actions">'
        . '<a class="btn btn-outline btn-sm" href="index.php?p=/users&action=edit&id=' . h($u['id']) . '">Edit</a>'
        . ' <a class="btn btn-danger btn-sm" href="index.php?p=/users&action=del&id=' . h($u['id']) . '" onclick="return confirm(\'Delete this user?\')">Delete</a>'
        . '</td>'
        . '</tr>';
    }
    echo '</tbody></table>';
    echo '<div class="pagination" style="display:flex; gap:6px; justify-content:flex-end; padding:12px;">';
    $prevPage = max(1, $page - 1);
    $nextPage = min($pages, $page + 1);
    echo '<a class="btn" ' . ($page === 1 ? 'style="opacity:0.6; pointer-events:none;"' : '') . ' href="' . $base . '&page=' . $prevPage . '">Prev</a>';
    $toShow = [1, $page - 1, $page, $page + 1, $pages];
    $toShow = array_values(array_unique(array_filter($toShow, function ($n) use ($pages) {
      return $n >= 1 && $n <= $pages;
    })));
    sort($toShow);
    $last = 0;
    foreach ($toShow as $n) {
      if ($last && $n > $last + 1)
        echo '<span style="display:inline-block; padding:6px 8px;">…</span>';
      $isActive = ($n === $page);
      $style = $isActive ? 'style="background:#2a2f3a; border-color:#2a2f3a;"' : '';
      echo '<a class="btn" ' . $style . ' href="' . $base . '&page=' . $n . '">' . h($n) . '</a>';
      $last = $n;
    }
    echo '<a class="btn" ' . ($page === $pages ? 'style="opacity:0.6; pointer-events:none;"' : '') . ' href="' . $base . '&page=' . $nextPage . '">Next</a>';
    echo '</div>';
    echo '</div></div></div>';

    // Add User Modal
    echo '<div id="userModal" class="modal-backdrop" style="display:none;">'
      . '<div class="modal">'
      . '<div class="card">'
      . '<div class="modal-header"><div class="card-title">Add New User</div><a class="icon-btn" href="javascript:void(0)" onclick="document.getElementById(\'userModal\').style.display=\'none\';">✕</a></div>'
      . '<div class="modal-body">'
      . '<form method="post" class="form-row">'
      . '<input type="hidden" name="action" value="create" />'
      . '<div><label class="label">Full Name</label><input class="input" name="full_name" placeholder="Enter full name" required /></div>'
      . '<div><label class="label">Email</label><input class="input" type="email" name="email" placeholder="Enter email" required /></div>'
      . '<div><label class="label">Phone</label><input class="input" name="phone" placeholder="Enter phone number" /></div>'
      . '<div><label class="label">Address</label><textarea class="textarea" name="address" rows="2" placeholder="Enter address"></textarea></div>'
      . '<div><label class="label">Password</label><input class="input" type="password" name="password" placeholder="Enter password" required /></div>'
      . '<div class="modal-footer"><a class="btn btn-outline" href="javascript:void(0)" onclick="document.getElementById(\'userModal\').style.display=\'none\';">Cancel</a><button class="btn" type="submit">Add User</button></div>'
      . '</form>'
      . '</div>'
      . '</div>'
      . '</div>'
      . '</div>';

    // Render edit modal if needed
    if ($editingUser) {
      echo '<div id="editUserModal" class="modal-backdrop">'
        . '<div class="modal">'
        . '<div class="card">'
        . '<div class="modal-header"><div class="card-title">Edit User</div><a class="icon-btn" href="index.php?p=/users">✕</a></div>'
        . '<div class="modal-body">'
        . '<form method="post" class="form-row edit-form">'
        . '<input type="hidden" name="action" value="update" />'
        . '<input type="hidden" name="id" value="' . h($editingUser['id']) . '" />'
        . '<div><label class="label">Full Name</label><input class="input" name="full_name" value="' . h($editingUser['full_name']) . '" placeholder="Enter full name" required /></div>'
        . '<div><label class="label">Email</label><input class="input" type="email" name="email" value="' . h($editingUser['email']) . '" placeholder="Enter email" required /></div>'
        . '<div><label class="label">Phone</label><input class="input" name="phone" value="' . h($editingUser['phone']) . '" placeholder="Enter phone number" /></div>'
        . '<div><label class="label">Address</label><textarea class="textarea" name="address" rows="2" placeholder="Enter address">' . h($editingUser['address']) . '</textarea></div>'
        . '<div><label class="label">New Password (leave blank to keep current)</label><input class="input" type="password" name="password" placeholder="Enter new password" /></div>'
        . '<div class="modal-footer"><a class="btn btn-outline" href="index.php?p=/users">Cancel</a><button class="btn" type="submit">Update User</button></div>'
        . '</form>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '</div>';
    }
    break;

  case '/categories':
    // Handle create (Image URL only)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
      $name = trim((string) ($_POST['name'] ?? ''));
      $detail = trim((string) ($_POST['detail'] ?? ''));
      $image = trim((string) ($_POST['image'] ?? ''));
      if ($name !== '') {
        $st = db()->prepare('INSERT INTO categories (name, detail, image) VALUES (?,?,?)');
        $st->execute([$name, $detail, $image]);
        redirect('index.php?p=/categories');
      } else {
        // Name is required
      }
    }
    // Handle update (Image URL only)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
      $id = (int) ($_POST['id'] ?? 0);
      $name = trim((string) ($_POST['name'] ?? ''));
      $detail = trim((string) ($_POST['detail'] ?? ''));
      $image = trim((string) ($_POST['image'] ?? ''));
      if ($id && $name !== '') {
        $st = db()->prepare('UPDATE categories SET name=?, detail=?, image=? WHERE id=?');
        $st->execute([$name, $detail, $image, $id]);
        redirect('index.php?p=/categories');
      } else {  /* Name is required */
      }
    }
    // Handle delete
    if (($_GET['action'] ?? '') === 'del') {
      $id = (int) ($_GET['id'] ?? 0);
      if ($id) {
        db()->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
        redirect('index.php?p=/categories');
      }
    }
    // List
    $cats = fetch_categories_all();
    // If editing, fetch the target row and render modal
    $editingId = null;
    $editingCat = null;
    if (($_GET['action'] ?? '') === 'edit') {
      $editingId = (int) ($_GET['id'] ?? 0);
      if ($editingId) {
        foreach ($cats as $c) {
          if ((int) $c['id'] === $editingId) {
            $editingCat = $c;
            break;
          }
        }
      }
    }
    echo '<div class="card"'
      . '<div class="card-header"><div><div class="card-title">Categories</div><div class="card-subtitle">Total: ' . h(count($cats)) . '</div></div></div>'
      . '<div class="modal-body">'
      . '<form method="post" class="form-row line-form">'
      . '<input type="hidden" name="action" value="create" />'
      . '<div class="form-col-12"><label class="label">Name</label><input class="input" name="name" placeholder="e.g., Veg Thali" required /></div>'
      . '<div class="form-col-12"><label class="label">Detail</label><textarea class="textarea" name="detail" rows="2" placeholder="Short description (optional)"></textarea></div>'
      . '<div class="form-col-12"><label class="label">Image URL</label><input class="input" type="url" name="image" placeholder="https://..." /></div>'
      . '<div class="form-col-12" style="display:flex; justify-content:flex-end;"><button class="btn" type="submit">Add</button></div>'
      . '</form>'
      . '</div>'
      . '<div class="table-section"><div class="table-wrap">'
      . '<table class="table"><thead><tr><th>Name</th><th>Detail</th><th>Image</th><th>Actions</th></tr></thead><tbody>';
    foreach ($cats as $c) {
      echo '<tr class="table-row">'
        . '<td data-label="Name">' . h($c['name']) . '</td>'
        . '<td data-label="Detail">' . h($c['detail']) . '</td>'
        . '<td data-label="Image">' . ($c['image'] ? '<img src="' . h($c['image']) . '" alt="" style="width:60px;height:40px;object-fit:cover;" />' : '-') . '</td>'
        . '<td data-label="Actions">'
        . '<a class="btn btn-outline btn-sm" href="index.php?p=/categories&action=edit&id=' . h($c['id']) . '">Edit</a>'
        . ' <a class="btn btn-danger btn-sm" href="index.php?p=/categories&action=del&id=' . h($c['id']) . '" onclick="return confirm(\'Delete this category?\')">Delete</a>'
        . '</td>'
        . '</tr>';
    }
    echo '</tbody></table></div></div></div>';

    // Render edit modal if needed
    if ($editingCat) {
      echo '<div id="catModal" class="modal-backdrop">'
        . '<div class="modal">'
        . '<div class="card">'
        . '<div class="modal-header"><div class="card-title">Edit Category</div><a class="icon-btn" href="index.php?p=/categories">✕</a></div>'
        . '<div class="modal-body">'
        . '<form method="post" class="form-row edit-form">'
        . '<input type="hidden" name="action" value="update" />'
        . '<input type="hidden" name="id" value="' . h($editingCat['id']) . '" />'
        . '<div><label class="label">Name</label><input class="input" name="name" value="' . h($editingCat['name']) . '" placeholder="e.g., Veg Thali" required /></div>'
        . '<div><label class="label">Detail</label><textarea class="textarea" name="detail" rows="2" placeholder="Short description (optional)">' . h($editingCat['detail']) . '</textarea></div>'
        . '<div><label class="label">Image URL</label><input class="input" type="url" name="image" value="' . h($editingCat['image']) . '" placeholder="https://..." />'
        . '<span class="help">Provide a public image URL. File uploads are disabled.</span></div>'
        . '<div class="modal-footer"><a class="btn btn-outline" href="index.php?p=/categories">Cancel</a><button class="btn" type="submit">Save</button></div>'
        . '</form>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '</div>';
    }
    break;

  case '/menu':
    // Create item (Image URL only)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
      $cat = (int) ($_POST['category_id'] ?? 0);
      $name = trim((string) ($_POST['name'] ?? ''));
      $detail = trim((string) ($_POST['detail'] ?? ''));
      $price = (float) ($_POST['price'] ?? 0);
      $discount = (int) ($_POST['discount'] ?? 0);
      $image = trim((string) ($_POST['image'] ?? ''));
      if ($cat && $name !== '') {
        $st = db()->prepare('INSERT INTO menu_items (category_id, name, detail, price, discount, image) VALUES (?,?,?,?,?,?)');
        $st->execute([$cat, $name, $detail, $price, $discount, $image]);
        redirect('index.php?p=/menu');
      } else {  /* Category and Name required */
      }
    }
    // Update item
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
      $id = (int) ($_POST['id'] ?? 0);
      $cat = (int) ($_POST['category_id'] ?? 0);
      $name = trim((string) ($_POST['name'] ?? ''));
      $detail = trim((string) ($_POST['detail'] ?? ''));
      $price = (float) ($_POST['price'] ?? 0);
      $discount = (int) ($_POST['discount'] ?? 0);
      $image = trim((string) ($_POST['image'] ?? ''));
      if ($id && $cat && $name !== '') {
        $st = db()->prepare('UPDATE menu_items SET category_id=?, name=?, detail=?, price=?, discount=?, image=? WHERE id=?');
        $st->execute([$cat, $name, $detail, $price, $discount, $image, $id]);
        redirect('index.php?p=/menu');
      } else {  /* Category and Name required */
      }
    }
    // Delete item
    if (($_GET['action'] ?? '') === 'del') {
      $id = (int) ($_GET['id'] ?? 0);
      if ($id) {
        db()->prepare('DELETE FROM menu_items WHERE id=?')->execute([$id]);
        redirect('index.php?p=/menu');
      }
    }
    $pdo = db();
    $cats = fetch_categories_all();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $size = 8;
    $offset = ($page - 1) * $size;
    $total = (int) ($pdo->query('SELECT COUNT(*) c FROM menu_items')->fetch()['c'] ?? 0);
    $stmt = $pdo->prepare('SELECT m.id, m.name, m.detail, m.price, m.discount, m.image, c.name as cat FROM menu_items m JOIN categories c ON c.id=m.category_id ORDER BY m.id DESC LIMIT ? OFFSET ?');
    $stmt->bindValue(1, (int) $size, PDO::PARAM_INT);
    $stmt->bindValue(2, (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    $slice = $stmt->fetchAll();
    $pages = max(1, (int) ceil($total / $size));
    if ($page > $pages)
      $page = $pages;
    $base = 'index.php?p=/menu';
    // If editing, fetch target row for modal
    $editingId = null;
    $editingItem = null;
    if (($_GET['action'] ?? '') === 'edit') {
      $editingId = (int) ($_GET['id'] ?? 0);
      if ($editingId) {
        $st = $pdo->prepare('SELECT id, category_id, name, detail, price, discount, image FROM menu_items WHERE id=? LIMIT 1');
        $st->execute([$editingId]);
        $editingItem = $st->fetch();
      }
    }
    echo '<div class="card">'
      . '<div class="card-header"><div><div class="card-title">Menu Items</div><div class="card-subtitle">Total: ' . h($total) . '</div></div></div>'
      . '<div class="modal-body">'
      . '<form method="post" class="form-row line-form-menu">'
      . '<input type="hidden" name="action" value="create" />'
      . '<div class="form-col-12"><label class="label">Category</label><select class="select" name="category_id" required>';
    foreach ($cats as $c) {
      echo '<option value="' . h($c['id']) . '">' . h($c['name']) . '</option>';
    }
    echo '</select></div>'
      . '<div class="form-col-12"><label class="label">Name</label><input class="input" name="name" placeholder="e.g., Paneer Butter Masala" required /></div>'
      . '<div class="form-col-12"><label class="label">Detail</label><textarea class="textarea" name="detail" rows="2" placeholder="Short description (optional)"></textarea></div>'
      . '<div class="form-col-12"><label class="label">Price</label><input class="input" type="number" step="0.01" min="0" name="price" value="0" placeholder="0.00" required /></div>'
      . '<div class="form-col-12"><label class="label">Discount %</label><input class="input" type="number" name="discount" value="0" min="0" max="100" step="1" />'
      . '<span class="help">Enter a percentage from 0 to 100.</span></div>'
      . '<div class="form-col-12"><label class="label">Image URL</label><input class="input" type="url" name="image" placeholder="https://..." />'
      . '<span class="help">Paste a public image URL. File uploads are disabled.</span></div>'
      . '<div class="form-col-12" style="grid-column:1/-1; display:flex; justify-content:flex-end;"><button class="btn" type="submit">Add</button></div>'
      . '</form>'
      . '</div>'
      . '<div class="table-section"><div class="table-wrap">'
      . '<table class="table"><thead><tr><th>Category</th><th>Name</th><th>Price</th><th>Disc</th><th>Image</th><th>Actions</th></tr></thead><tbody>';
    foreach ($slice as $m) {
      echo '<tr class="table-row">'
        . '<td data-label="Category">' . h($m['cat']) . '</td>'
        . '<td data-label="Name">' . h($m['name']) . '<div class="table-sub">' . h($m['detail']) . '</div></td>'
        . '<td data-label="Price">' . h($m['price']) . '</td>'
        . '<td data-label="Disc">' . h($m['discount']) . '%</td>'
        . '<td data-label="Image">' . ($m['image'] ? '<img src="' . h($m['image']) . '" style="width:60px;height:40px;object-fit:cover;" />' : '-') . '</td>'
        . '<td data-label="Actions"><a class="btn btn-outline btn-sm" href="index.php?p=/menu&action=edit&id=' . h($m['id']) . '">Edit</a> '
        . '<a class="btn btn-danger btn-sm" href="index.php?p=/menu&action=del&id=' . h($m['id']) . '" onclick="return confirm(\'Delete this item?\')">Delete</a></td>'
        . '</tr>';
    }
    echo '</tbody></table></div>'
      . '<div class="pagination" style="display:flex; gap:6px; justify-content:flex-end; padding:12px;">'
      . '<a class="btn" ' . ($page === 1 ? 'style="opacity:0.6; pointer-events:none;"' : '') . ' href="' . $base . '&page=' . (max(1, $page - 1)) . '">Prev</a>'
      . '<a class="btn" ' . ($page === $pages ? 'style="opacity:0.6; pointer-events:none;"' : '') . ' href="' . $base . '&page=' . (min($pages, $page + 1)) . '">Next</a>'
      . '</div>'
      . '</div>'
      . '</div>';
    // Render edit modal if needed
    if ($editingItem) {
      echo '<div id="menuModal" class="modal-backdrop">'
        . '<div class="modal">'
        . '<div class="card">'
        . '<div class="card-header"><div><div class="card-title">Edit Menu Item</div></div>'
        . '<a class="icon-btn" href="index.php?p=/menu" aria-label="Close">✕</a>'
        . '</div>'
        . '<div class="modal-body">'
        . '<form method="post" class="edit-form">'
        . '<input type="hidden" name="action" value="update" />'
        . '<input type="hidden" name="id" value="' . h($editingItem['id']) . '" />'
        . '<div><label class="label">Category</label><select class="select" name="category_id" required>';
      foreach ($cats as $c) {
        $sel = ((int) $editingItem['category_id'] === (int) $c['id']) ? ' selected' : '';
        echo '<option value="' . h($c['id']) . '"' . $sel . '>' . h($c['name']) . '</option>';
      }
      echo '</select></div>'
        . '<div><label class="label">Name</label><input class="input" name="name" value="' . h($editingItem['name']) . '" required /></div>'
        . '<div><label class="label">Detail</label><textarea class="textarea" name="detail" rows="3">' . h($editingItem['detail']) . '</textarea></div>'
        . '<div><label class="label">Price</label><input class="input" type="number" step="0.01" min="0" name="price" value="' . h($editingItem['price']) . '" required /></div>'
        . '<div><label class="label">Discount %</label><input class="input" type="number" name="discount" value="' . h($editingItem['discount']) . '" min="0" max="100" step="1" /></div>'
        . '<div><label class="label">Image URL</label><input class="input" type="url" name="image" value="' . h($editingItem['image']) . '" />'
        . '<span class="help">Provide a public image URL. File uploads are disabled.</span></div>'
        . '<div class="modal-footer"><a class="btn btn-outline" href="index.php?p=/menu">Cancel</a><button class="btn" type="submit">Save</button></div>'
        . '</form>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '</div>';
    }
    echo '';
    break;

  case '/fixed':
    // Determine day index
    $days = fetch_days();
    $dayIdx = isset($_GET['day']) ? (int) $_GET['day'] : (int) date('w');
    $dayIdx = max(0, min(6, $dayIdx));
    // Resolve day id
    $st = db()->prepare('SELECT id FROM fixed_menu_days WHERE day_index = ?');
    $st->execute([$dayIdx]);
    $day = $st->fetch();
    if (!$day) { /* Days not seeded */ break;
    }
    $dayId = (int) $day['id'];
    // Create item (Image URL only)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
      $name = trim((string) ($_POST['name'] ?? ''));
      $detail = trim((string) ($_POST['detail'] ?? ''));
      $price = (float) ($_POST['price'] ?? 0);
      $discount = (int) ($_POST['discount'] ?? 0);
      $image = trim((string) ($_POST['image'] ?? ''));
      $comboIdsRaw = trim((string) ($_POST['combo_item_ids'] ?? ''));
      // Server-side validation: require name, image URL, and at least one selected item
      if ($name === '') {
        redirect('index.php?p=/fixed&day=' . $dayIdx);
      }
      if ($image === '') {
        redirect('index.php?p=/fixed&day=' . $dayIdx);
      }
      if ($comboIdsRaw === '') {
        redirect('index.php?p=/fixed&day=' . $dayIdx);
      }
      if ($name !== '') {
        // If a combo selection exists, compute detail and price from selected menu items
        if ($comboIdsRaw !== '') {
          $ids = array_values(array_filter(array_map('intval', explode(',', $comboIdsRaw))));
          if (count($ids) > 0) {
            $place = implode(',', array_fill(0, count($ids), '?'));
            $stc = db()->prepare('SELECT id, name, price FROM menu_items WHERE id IN (' . $place . ')');
            $stc->execute($ids);
            $rows = $stc->fetchAll();
            if ($rows) {
              if ($detail === '') {
                $detail = implode(', ', array_map(function ($r) {
                  return (string) $r['name'];
                }, $rows));
              }
              // If price not provided or zero, sum selected prices
              if (!$price) {
                $sum = 0;
                foreach ($rows as $r) {
                  $sum += (float) $r['price'];
                }
                $price = $sum;
              }
            }
          }
        }
        $s = db()->prepare('INSERT INTO fixed_menu_items (day_id, name, detail, price, discount, image) VALUES (?,?,?,?,?,?)');
        $s->execute([$dayId, $name, $detail, $price, $discount, $image]);
        redirect('index.php?p=/fixed&day=' . $dayIdx);
      } else {  /* Name is required */
      }
    }
    // Delete item
    if (($_GET['action'] ?? '') === 'del') {
      $id = (int) ($_GET['id'] ?? 0);
      if ($id) {
        db()->prepare('DELETE FROM fixed_menu_items WHERE id=?')->execute([$id]);
        redirect('index.php?p=/fixed&day=' . $dayIdx);
      }
    }
    // Fetch items
    $stmt = db()->prepare('SELECT id, name, detail, price, discount, image FROM fixed_menu_items WHERE day_id = ? ORDER BY id');
    $stmt->execute([$dayId]);
    $items = $stmt->fetchAll();
    echo '<div class="card">'
      . '<div class="card-header">'
      . '<div><div class="card-title">Fixed Tiffin</div><div class="card-subtitle">Day: ' . h($days[$dayIdx] ?? $dayIdx) . '</div></div>'
      . '<form method="get" class="form-row" style="grid-template-columns:1fr auto;">'
      . '<input type="hidden" name="p" value="/fixed" />'
      . '<div><label class="label">Day</label><select class="select" name="day" onchange="this.form.submit()">';
    for ($i = 0; $i < 7; $i++) {
      $nm = $days[$i] ?? $i;
      echo '<option value="' . $i . '" ' . ($i === $dayIdx ? 'selected' : '') . '>' . h($nm) . '</option>';
    }
    echo '</select></div></form></div>'
      . '<div class="modal-body">'
      . '<form method="post" class="form-row line-form-fixed">'
      . '<div class="form-col-12"><label class="label">Category (from Menu)</label><select class="select" id="fixedCatSelect"><option value="">Select category</option>';
    $allCatsFixed = fetch_categories_all();
    foreach ($allCatsFixed as $c) {
      echo '<option value="' . h($c['id']) . '">' . h($c['name']) . '</option>';
    }
    echo '</select><span class="help">Optional: pick a category to choose an existing menu item.</span></div>'
      . '<div class="form-col-12"><label class="label">Item (from Menu)</label><div style="display:flex; gap:8px; align-items:end;"><select class="select" id="fixedItemSelect" disabled style="flex:1"><option value="">Select category first</option></select><button class="btn" type="button" id="fixedAddItemBtn">Add Item</button></div><span class="help">Build a combo: add multiple items; you can remove them before saving.</span></div>'
      . '<input type="hidden" name="combo_item_ids" id="comboItemIds" value="" />'
      . '<div class="form-col-12"><div id="comboChips" style="display:flex; flex-wrap:wrap; gap:8px;"></div><span class="help">Selected items will appear here. Click × to remove.</span></div>'
      . '<input type="hidden" name="action" value="create" />'
      . '<div class="form-col-12"><label class="label">Name</label><input class="input" id="fixedName" name="name" placeholder="e.g., Friday Special" required /></div>'
      . '<div class="form-col-12"><label class="label">Detail</label><textarea class="textarea" id="fixedDetail" name="detail" rows="2" placeholder="Short description (optional)"></textarea></div>'
      . '<div class="form-col-12"><label class="label">Price</label><input class="input" id="fixedPrice" type="number" step="0.01" min="0" name="price" value="0" placeholder="0.00" required /></div>'
      . '<div class="form-col-12"><label class="label">Discount %</label><input class="input" id="fixedDiscount" type="number" name="discount" value="0" min="0" max="100" step="1" />'
      . '<span class="help">Enter a percentage from 0 to 100.</span></div>'
      . '<div class="form-col-12"><label class="label">Image URL</label><input class="input" id="fixedImage" type="url" name="image" placeholder="https://..." required />'
      . '<span class="help">Paste a public image URL. File uploads are disabled.</span></div>'
      . '<div class="form-col-12" style="grid-column:1/-1; display:flex; justify-content:flex-end;"><button class="btn" type="submit">Add</button></div>'
      . '</form>'
      . '</div>'
      . '<div class="table-section"><div class="table-wrap">'
      . '<table class="table"><thead><tr><th>Name</th><th>Detail</th><th>Price</th><th>Disc</th><th>Actions</th></tr></thead><tbody>';
    foreach ($items as $m) {
      echo '<tr class="table-row">'
        . '<td data-label="Name">' . h($m['name']) . '</td>'
        . '<td data-label="Detail">' . h($m['detail']) . '</td>'
        . '<td data-label="Price">' . h($m['price']) . '</td>'
        . '<td data-label="Disc">' . h($m['discount']) . '%</td>'
        . '<td data-label="Actions"><a class="btn btn-danger btn-sm" href="index.php?p=/fixed&day=' . h($dayIdx) . '&action=del&id=' . h($m['id']) . '" onclick="return confirm(\'Delete this item?\')">Delete</a></td>'
        . '</tr>';
    }
    echo '</tbody></table></div></div></div>';
    // Expose menu items for category->item population on Fixed page
    $menuForJs = db()->query('SELECT id, category_id, name, detail, price, discount, image FROM menu_items ORDER BY name')->fetchAll();
    echo '<script>
        window.fixedMenuData = ' . json_encode($menuForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';

        document.addEventListener("DOMContentLoaded", function() {
            const catSelect = document.getElementById("fixedCatSelect");
            const itemSelect = document.getElementById("fixedItemSelect");
            const addItemBtn = document.getElementById("fixedAddItemBtn");
            const comboChips = document.getElementById("comboChips");
            const comboItemIds = document.getElementById("comboItemIds");
            const fixedDetail = document.getElementById("fixedDetail");
            const fixedPrice = document.getElementById("fixedPrice");
            let selectedItems = [];

            function loadItemsForCategory(categoryId) {
                itemSelect.innerHTML = "<option value=\"\">Select item</option>";

                if (categoryId && window.fixedMenuData) {
                    const items = window.fixedMenuData.filter(item => parseInt(item.category_id) === parseInt(categoryId));

                    if (items.length > 0) {
                        itemSelect.disabled = false;
                        items.forEach(item => {
                            const option = document.createElement("option");
                            option.value = item.id;
                            option.textContent = item.name + " (₹" + item.price + ")";
                            itemSelect.appendChild(option);
                        });
                    } else {
                        itemSelect.innerHTML = "<option value=\"\">No items in this category</option>";
                        itemSelect.disabled = true;
                    }
                } else {
                    itemSelect.disabled = true;
                }
            }

            // Auto-select first category on page load
            if (catSelect && catSelect.options.length > 1) {
                catSelect.selectedIndex = 1;
                loadItemsForCategory(catSelect.value);
            }

            // Handle category change
            if (catSelect) {
                catSelect.addEventListener("change", function() {
                    loadItemsForCategory(this.value);
                });
            }

            // Handle add item
            if (addItemBtn) {
                addItemBtn.addEventListener("click", function() {
                    const itemId = parseInt(itemSelect.value);

                    if (!itemId) {
                        alert("Please select an item first");
                        return;
                    }

                    const item = window.fixedMenuData.find(i => parseInt(i.id) === itemId);

                    if (!item) {
                        alert("Item not found");
                        return;
                    }

                    if (selectedItems.find(i => parseInt(i.id) === itemId)) {
                        alert("Item already selected!");
                        return;
                    }

                    selectedItems.push(item);
                    updateDisplay();
                    itemSelect.value = "";
                });
            }

            function updateDisplay() {
                // Update chips
                comboChips.innerHTML = "";
                selectedItems.forEach(item => {
                    const chip = document.createElement("div");
                    chip.style.cssText = "display:inline-flex; align-items:center; gap:8px; background:#4CAF50; color:#fff; padding:6px 12px; border-radius:20px; font-size:12px; margin:4px;";
                    chip.innerHTML = item.name + " (₹" + item.price + ") <span style=\"cursor:pointer; font-weight:bold; margin-left:4px;\" onclick=\"removeItem(" + item.id + ")\">×</span>";
                    comboChips.appendChild(chip);
                });

                // Update form fields
                comboItemIds.value = selectedItems.map(item => item.id).join(",");
                fixedDetail.value = selectedItems.map(item => item.name).join(", ");
                const totalPrice = selectedItems.reduce((sum, item) => sum + parseFloat(item.price || 0), 0);
                fixedPrice.value = totalPrice.toFixed(2);
            }

            window.removeItem = function(itemId) {
                selectedItems = selectedItems.filter(item => parseInt(item.id) !== parseInt(itemId));
                updateDisplay();
            };
        });
        </script>';
    break;

  case '/orders':
    // Update status
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
      $id = (int) ($_POST['id'] ?? 0);
      $status = $_POST['status'] ?? 'pending';
      if (in_array($status, ['pending', 'confirmed', 'delivered', 'cancelled'], true) && $id) {
        db()->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $id]);
        redirect('index.php?p=/orders');
      } else {  /* Invalid status */
      }
    }
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $size = 6;
    $offset = ($page - 1) * $size;
    $statusFilter = $_GET['status'] ?? '';
    $pdo = db();

    // Build query with optional status filter
    $whereClause = '';
    $params = [];
    if ($statusFilter && in_array($statusFilter, ['pending', 'confirmed', 'delivered', 'cancelled'])) {
      $whereClause = ' WHERE o.status = ?';
      $params[] = $statusFilter;
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) c FROM orders o' . $whereClause);
    $countStmt->execute($params);
    $total = (int) ($countStmt->fetch()['c'] ?? 0);
    $stmt = $pdo->prepare('SELECT o.id, o.qty, o.status, o.price, o.created_at, u.full_name as user, u.email, m.name as item
                         FROM orders o
                         JOIN users u ON u.id=o.user_id
                         JOIN menu_items m ON m.id=o.item_id'
      . $whereClause
      . ' ORDER BY o.id DESC LIMIT ? OFFSET ?');

    // Bind parameters
    $bindIndex = 1;
    foreach ($params as $param) {
      $stmt->bindValue($bindIndex++, $param);
    }
    $stmt->bindValue($bindIndex++, (int) $size, PDO::PARAM_INT);
    $stmt->bindValue($bindIndex, (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    $slice = $stmt->fetchAll();
    $pages = max(1, (int) ceil($total / $size));
    if ($page > $pages)
      $page = $pages;
    $base = 'index.php?p=/orders';
    echo '<div class="card">'
      . '<style>'
      . '@media (max-width: 768px) {'
      . '  .orders-header { flex-direction: column; gap: 12px; }'
      . '  .orders-filter { width: 100%; }'
      . '  .orders-filter select { width: 100%; }'
      . '  .pagination { justify-content: center !important; flex-wrap: wrap; }'
      . '}'
      . '@media (min-width: 769px) {'
      . '  .orders-header { flex-direction: row; justify-content: space-between; align-items: center; }'
      . '  .orders-filter { width: auto; }'
      . '}'
      . '</style>'
      . '<div class="card-header orders-header" style="display: flex;">'
      . '  <div><div class="card-title">Orders Management</div><div class="card-subtitle">Total Orders: ' . h($total) . ' | Page ' . h($page) . ' of ' . h($pages) . '</div></div>'
      . '  <div class="orders-filter" style="display:flex; gap:8px;">'
      . '    <select onchange="window.location.href=\'index.php?p=/orders&status=\'+this.value" style="padding:8px 12px; border-radius:4px; border:1px solid #444; background:#2a2f3a; color:#fff; min-width: 120px;">'
      . '      <option value="">All Status</option>'
      . '      <option value="pending" ' . ((($_GET['status'] ?? '') === 'pending') ? 'selected' : '') . '>Pending</option>'
      . '      <option value="confirmed" ' . ((($_GET['status'] ?? '') === 'confirmed') ? 'selected' : '') . '>Confirmed</option>'
      . '      <option value="delivered" ' . ((($_GET['status'] ?? '') === 'delivered') ? 'selected' : '') . '>Delivered</option>'
      . '      <option value="cancelled" ' . ((($_GET['status'] ?? '') === 'cancelled') ? 'selected' : '') . '>Cancelled</option>'
      . '    </select>'
      . '  </div>'
      . '</div>'
      . '<div class="table-section"><div class="table-wrap">'
      . '<style>'
      . '/* Mobile Responsive Styles */'
      . '@media (max-width: 768px) {'
      . '  .orders-table { display: block; width: 100%; }'
      . '  .orders-table thead { display: none; }'
      . '  .orders-table tbody { display: block; width: 100%; }'
      . '  .orders-table tr { display: block; margin-bottom: 20px; background: #2a2f3a; border-radius: 12px; padding: 20px; border: 1px solid #444; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }'
      . '  .orders-table td { display: flex; justify-content: space-between; align-items: flex-start; padding: 10px 0; border-bottom: 1px solid #444; flex-wrap: wrap; }'
      . '  .orders-table td:last-child { border-bottom: none; padding-top: 15px; }'
      . '  .orders-table td:before { content: attr(data-label); font-weight: 600; color: #888; min-width: 120px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }'
      . '  .mobile-actions { display: flex; flex-direction: row; gap: 8px; width: 100%; align-items: center; }'
      . '  .mobile-actions form { width: 100%; display: flex; gap: 8px; align-items: center; }'
      . '  .mobile-actions select { flex: 1; padding: 6px 8px; border-radius: 4px; font-size: 11px; min-height: 32px; }'
      . '  .mobile-actions button { padding: 6px 12px; font-weight: 600; font-size: 11px; white-space: nowrap; min-height: 32px; }'
      . '  .order-id-mobile { font-size: 18px; font-weight: bold; color: #4CAF50; }'
      . '  .customer-mobile { font-size: 16px; font-weight: 500; }'
      . '  .item-mobile { font-size: 15px; font-weight: 500; color: #17a2b8; }'
      . '}'
      . '/* Tablet Styles */'
      . '@media (min-width: 769px) and (max-width: 1024px) {'
      . '  .orders-table th, .orders-table td { padding: 10px 6px; font-size: 13px; }'
      . '  .mobile-actions { display: flex; flex-direction: column; gap: 6px; }'
      . '  .mobile-actions select { margin-bottom: 6px; }'
      . '}'
      . '/* Desktop Styles */'
      . '@media (min-width: 1025px) {'
      . '  .orders-table { width: 100%; }'
      . '  .orders-table th, .orders-table td { padding: 12px 8px; }'
      . '  .mobile-actions { display: flex; flex-direction: row; gap: 6px; align-items: center; }'
      . '  .mobile-actions select { min-width: 120px; }'
      . '}'
      . '/* Status Badge Enhancements */'
      . '.status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }'
      . '/* Hover Effects */'
      . '.orders-table tr:hover { background: #343a46; transition: background 0.2s ease; }'
      . '</style>'
      . '<table class="table orders-table"><thead><tr><th>Customer</th><th>Email</th><th>Item</th><th>Qty</th><th>Price</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
    foreach ($slice as $o) {
      // Status badge styling
      $statusClass = '';
      switch ($o['status']) {
        case 'pending':
          $statusClass = 'background:#ffc107; color:#000;';
          break;
        case 'confirmed':
          $statusClass = 'background:#17a2b8; color:#fff;';
          break;
        case 'delivered':
          $statusClass = 'background:#28a745; color:#fff;';
          break;
        case 'cancelled':
          $statusClass = 'background:#dc3545; color:#fff;';
          break;
        default:
          $statusClass = 'background:#6c757d; color:#fff;';
      }

      echo '<tr class="table-row">'
        //  .'<td data-label="Order ID"><span class="order-id-mobile">#'.h($o['id']).'</span></td>'
        . '<td data-label="Customer"><span class="customer-mobile">' . h($o['user']) . '</span></td>'
        . '<td data-label="Email"><small style="color:#888;">' . h($o['email']) . '</small></td>'
        . '<td data-label="Item"><span class="item-mobile">' . h($o['item']) . '</span></td>'
        . '<td data-label="Quantity"><span style="background:#e9ecef; padding:4px 10px; border-radius:15px; font-weight:600; color:#000; font-size:12px;">×' . h($o['qty']) . '</span></td>'
        . '<td data-label="Price"><strong style="color:#4CAF50; font-size:14px;">₹' . h($o['price']) . '</strong></td>'
        . '<td data-label="Status"><span class="status-badge" style="' . $statusClass . '">' . h($o['status']) . '</span></td>'
        . '<td data-label="Date"><div style="font-size:13px;"><strong>' . date('M d, Y', strtotime($o['created_at'])) . '</strong><br><small style="color:#888;">' . date('H:i A', strtotime($o['created_at'])) . '</small></div></td>'
        . '<td data-label="Update Status">'
        . '<div class="mobile-actions">'
        . '<form method="post">'
        . '<input type="hidden" name="action" value="status" />'
        . '<input type="hidden" name="id" value="' . h($o['id']) . '" />'
        . '<select class="select" name="status">'
        . '<option ' . ($o['status'] === 'pending' ? 'selected' : '') . ' value="pending">🟡 Pending</option>'
        . '<option ' . ($o['status'] === 'confirmed' ? 'selected' : '') . ' value="confirmed">🔵 Confirmed</option>'
        . '<option ' . ($o['status'] === 'delivered' ? 'selected' : '') . ' value="delivered">🟢 Delivered</option>'
        . '<option ' . ($o['status'] === 'cancelled' ? 'selected' : '') . ' value="cancelled">🔴 Cancelled</option>'
        . '</select>'
        . '<button class="btn btn-sm" type="submit" style="background:#4CAF50; border:none; color:#fff; border-radius:4px;">Update</button>'
        . '</form>'
        . '</div>'
        . '</td>'
        . '</tr>';
    }
    echo '</tbody></table></div>'
      . '<div class="pagination" style="display:flex; gap:6px; justify-content:flex-end; padding:12px;">'
      . '<a class="btn" ' . ($page === 1 ? 'style="opacity:0.6; pointer-events:none;"' : '') . ' href="' . $base . '&page=' . (max(1, $page - 1)) . '">Prev</a>'
      . '<a class="btn" ' . ($page === $pages ? 'style="opacity:0.6; pointer-events:none;"' : '') . ' href="' . $base . '&page=' . (min($pages, $page + 1)) . '">Next</a>'
      . '</div>'
      . '</div>'
      . '</div>';
    break;

  case '/contacts':
    // Check if contact_messages table exists
    try {
      $pdo = db();
      $tableCheck = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
      if (!$tableCheck->fetch()) {
        echo '<div class="card">'
          . '<div class="card-header">'
          . '<div class="card-title">Contact Messages - Setup Required</div>'
          . '</div>'
          . '<div style="padding: 20px;">'
          . '<div style="background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 16px; border-radius: 8px; margin-bottom: 20px;">'
          . '<strong>⚠️ Database Setup Required</strong><br>'
          . 'The contact_messages table does not exist in your database. Please run the following SQL to create it:'
          . '</div>'
          . '<div style="background: #1f2937; color: #e5e7eb; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 14px; overflow-x: auto;">'
          . 'CREATE TABLE `contact_messages` (<br>'
          . '&nbsp;&nbsp;`id` int(11) NOT NULL AUTO_INCREMENT,<br>'
          . '&nbsp;&nbsp;`name` varchar(120) NOT NULL,<br>'
          . '&nbsp;&nbsp;`email` varchar(160) NOT NULL,<br>'
          . "&nbsp;&nbsp;`phone` varchar(40) NOT NULL DEFAULT '',<br>"
          . '&nbsp;&nbsp;`message` text NOT NULL,<br>'
          . '&nbsp;&nbsp;`user_id` int(11) DEFAULT NULL,<br>'
          . "&nbsp;&nbsp;`status` enum('unread','read','replied') NOT NULL DEFAULT 'unread',<br>"
          . '&nbsp;&nbsp;`created_at` int(11) NOT NULL,<br>'
          . '&nbsp;&nbsp;PRIMARY KEY (`id`),<br>'
          . '&nbsp;&nbsp;KEY `user_id` (`user_id`),<br>'
          . '&nbsp;&nbsp;KEY `status` (`status`),<br>'
          . '&nbsp;&nbsp;KEY `created_at` (`created_at`),<br>'
          . '&nbsp;&nbsp;CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL<br>'
          . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;'
          . '</div>'
          . '<div style="margin-top: 16px; color: #6b7280;">'
          . '<strong>Instructions:</strong><br>'
          . '1. Open phpMyAdmin or your MySQL client<br>'
          . '2. Select your "tiffin" database<br>'
          . '3. Copy and paste the SQL above into the SQL tab<br>'
          . '4. Click "Go" to execute<br>'
          . '5. Refresh this page'
          . '</div>'
          . '</div>'
          . '</div>';
        break;
      }
    } catch (Exception $e) {
      echo '<div class="card"><div style="padding: 20px; color: #dc2626;">Database Error: ' . h($e->getMessage()) . '</div></div>';
      break;
    }

    // Handle update status
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
      $id = (int) ($_POST['id'] ?? 0);
      $status = $_POST['status'] ?? 'unread';
      if (in_array($status, ['unread', 'read', 'replied'], true) && $id) {
        db()->prepare('UPDATE contact_messages SET status=? WHERE id=?')->execute([$status, $id]);
        redirect('index.php?p=/contacts');
      }
    }

    // Handle delete message
    if (($_GET['action'] ?? '') === 'del') {
      $id = (int) ($_GET['id'] ?? 0);
      if ($id) {
        db()->prepare('DELETE FROM contact_messages WHERE id=?')->execute([$id]);
        redirect('index.php?p=/contacts');
      }
    }

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $size = 10;
    $offset = ($page - 1) * $size;
    $statusFilter = $_GET['status'] ?? '';
    $pdo = db();

    // Build query with optional status filter
    $whereClause = '';
    $params = [];
    if ($statusFilter && in_array($statusFilter, ['unread', 'read', 'replied'])) {
      $whereClause = ' WHERE cm.status = ?';
      $params[] = $statusFilter;
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) c FROM contact_messages cm' . $whereClause);
    $countStmt->execute($params);
    $total = (int) ($countStmt->fetch()['c'] ?? 0);

    $stmt = $pdo->prepare('SELECT cm.id, cm.name, cm.email, cm.phone, cm.message, cm.status, cm.created_at, u.full_name as user_name
                         FROM contact_messages cm
                         LEFT JOIN users u ON u.id=cm.user_id'
      . $whereClause
      . ' ORDER BY cm.id DESC LIMIT ? OFFSET ?');

    // Bind parameters
    $bindIndex = 1;
    foreach ($params as $param) {
      $stmt->bindValue($bindIndex++, $param);
    }
    $stmt->bindValue($bindIndex++, (int) $size, PDO::PARAM_INT);
    $stmt->bindValue($bindIndex, (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    $slice = $stmt->fetchAll();
    $pages = max(1, (int) ceil($total / $size));
    if ($page > $pages)
      $page = $pages;
    $base = 'index.php?p=/contacts';

    echo '<div class="card">'
      . '<style>'
      . '@media (max-width: 768px) {'
      . '  .contacts-header { flex-direction: column; gap: 12px; }'
      . '  .contacts-filter { width: 100%; }'
      . '  .contacts-filter select { width: 100%; }'
      . '  .pagination { justify-content: center !important; flex-wrap: wrap; }'
      . '  .message-preview { max-width: 200px; }'
      . '}'
      . '@media (min-width: 769px) {'
      . '  .contacts-header { flex-direction: row; justify-content: space-between; align-items: center; }'
      . '  .contacts-filter { width: auto; }'
      . '  .message-preview { max-width: 300px; }'
      . '}'
      . '.message-preview { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }'
      . '</style>'
      . '<div class="card-header contacts-header" style="display: flex;">'
      . '  <div><div class="card-title">Contact Messages</div><div class="card-subtitle">Total Messages: ' . h($total) . ' | Page ' . h($page) . ' of ' . h($pages) . '</div></div>'
      . '  <div class="contacts-filter" style="display:flex; gap:8px;">'
      . '    <select onchange="window.location.href=\'index.php?p=/contacts&status=\'+this.value" style="padding:8px 12px; border-radius:4px; border:1px solid #444; background:#2a2f3a; color:#fff; min-width: 120px;">'
      . '      <option value="">All Status</option>'
      . '      <option value="unread" ' . ((($_GET['status'] ?? '') === 'unread') ? 'selected' : '') . '>Unread</option>'
      . '      <option value="read" ' . ((($_GET['status'] ?? '') === 'read') ? 'selected' : '') . '>Read</option>'
      . '      <option value="replied" ' . ((($_GET['status'] ?? '') === 'replied') ? 'selected' : '') . '>Replied</option>'
      . '    </select>'
      . '  </div>'
      . '</div>'
      . '<div class="table-section"><div class="table-wrap">'
      . '<style>'
      . '/* Contact Messages Table Responsive Styles */'
      . '@media (max-width: 768px) {'
      . '  .contacts-table { display: block; width: 100%; }'
      . '  .contacts-table thead { display: none; }'
      . '  .contacts-table tbody { display: block; width: 100%; }'
      . '  .contacts-table tr { display: block; margin-bottom: 20px; background: #2a2f3a; border-radius: 12px; padding: 20px; border: 1px solid #444; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }'
      . '  .contacts-table td { display: block; padding: 8px 0; border-bottom: 1px solid #444; }'
      . '  .contacts-table td:last-child { border-bottom: none; padding-top: 15px; }'
      . '  .contacts-table td:before { content: attr(data-label) ": "; font-weight: 600; color: #888; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; min-width: 80px; }'
      . '  .mobile-actions { display: flex; flex-direction: column; gap: 10px; width: 100%; margin-top: 10px; }'
      . '  .mobile-actions form { width: 100%; display: flex; gap: 8px; align-items: center; }'
      . '  .mobile-actions select { flex: 1; padding: 8px 12px; border-radius: 6px; font-size: 12px; min-height: 36px; background: #1a1f2a; border: 1px solid #444; color: #fff; }'
      . '  .mobile-actions button { padding: 8px 16px; font-weight: 600; font-size: 12px; white-space: nowrap; min-height: 36px; border-radius: 6px; }'
      . '  .mobile-actions .btn-danger { margin-top: 5px; width: 100%; }'
      . '  .message-preview { max-width: none; white-space: normal; word-wrap: break-word; }'
      . '}'
      . '@media (min-width: 769px) {'
      . '  .contacts-table td { vertical-align: middle; }'
      . '  .mobile-actions { display: flex; flex-direction: row; gap: 6px; align-items: center; justify-content: flex-end; }'
      . '  .mobile-actions form { display: flex; gap: 6px; align-items: center; }'
      . '  .mobile-actions select { min-width: 100px; padding: 6px 10px; font-size: 11px; }'
      . '  .mobile-actions button { padding: 6px 12px; font-size: 11px; }'
      . '}'
      . '/* Status Badge Enhancements */'
      . '.status-badge { display: inline-block; padding: 4px 10px; border-radius: 15px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }'
      . '/* Message Preview */'
      . '.message-preview { overflow: hidden; text-overflow: ellipsis; cursor: help; }'
      . '/* Hover Effects */'
      . '.contacts-table tr:hover { background: #343a46; transition: background 0.2s ease; }'
      . '/* Table Improvements */'
      . '.contacts-table th { padding: 12px 8px; font-size: 12px; font-weight: 600; }'
      . '.contacts-table td { padding: 12px 8px; font-size: 13px; }'
      . '</style>'
      . '<table class="table contacts-table"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Message</th><th>User</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>';

    foreach ($slice as $c) {
      // Status badge styling
      $statusClass = '';
      switch ($c['status']) {
        case 'unread':
          $statusClass = 'background:#ffc107; color:#000;';
          break;
        case 'read':
          $statusClass = 'background:#17a2b8; color:#fff;';
          break;
        case 'replied':
          $statusClass = 'background:#28a745; color:#fff;';
          break;
        default:
          $statusClass = 'background:#6c757d; color:#fff;';
      }

      $messagePreview = strlen($c['message']) > 50 ? substr($c['message'], 0, 50) . '...' : $c['message'];

      echo '<tr class="table-row">'
        . '<td data-label="Name"><strong style="color:#4CAF50;">' . h($c['name']) . '</strong></td>'
        . '<td data-label="Email"><span style="color:#888; font-size:12px;">' . h($c['email']) . '</span></td>'
        . '<td data-label="Phone"><span style="color:#ccc;">' . h($c['phone'] ?: '-') . '</span></td>'
        . '<td data-label="Message"><div class="message-preview" title="' . h($c['message']) . '" style="max-width:250px;">' . h($messagePreview) . '</div></td>'
        . '<td data-label="User"><span style="color:#17a2b8; font-weight:500;">' . h($c['user_name'] ?: 'Guest') . '</span></td>'
        . '<td data-label="Status"><span class="status-badge" style="' . $statusClass . '">' . h($c['status']) . '</span></td>'
        . '<td data-label="Date"><div style="font-size:12px; line-height:1.4;"><strong style="color:#fff;">' . date('M d, Y', $c['created_at']) . '</strong><br><small style="color:#888;">' . date('H:i A', $c['created_at']) . '</small></div></td>'
        . '<td data-label="Actions">'
        . '<div class="mobile-actions">'
        . '<form method="post">'
        . '<input type="hidden" name="action" value="status" />'
        . '<input type="hidden" name="id" value="' . h($c['id']) . '" />'
        . '<select class="select" name="status">'
        . '<option ' . ($c['status'] === 'unread' ? 'selected' : '') . ' value="unread">🔴 Unread</option>'
        . '<option ' . ($c['status'] === 'read' ? 'selected' : '') . ' value="read">🔵 Read</option>'
        . '<option ' . ($c['status'] === 'replied' ? 'selected' : '') . ' value="replied">🟢 Replied</option>'
        . '</select>'
        . '<button class="btn btn-sm" type="submit" style="background:#4CAF50; border:none; color:#fff;">Update</button>'
        . '</form>'
        . '<a class="btn btn-danger btn-sm" href="index.php?p=/contacts&action=del&id=' . h($c['id']) . '" onclick="return confirm(\'Delete this message?\')">Delete</a>'
        . '</div>'
        . '</td>'
        . '</tr>';
    }
    echo '</tbody></table></div>'
      . '<div class="pagination" style="display:flex; gap:6px; justify-content:flex-end; padding:12px;">'
      . '<a class="btn" ' . ($page === 1 ? 'style="opacity:0.6; pointer-events:none;"' : '') . ' href="' . $base . '&page=' . (max(1, $page - 1)) . '">Prev</a>'
      . '<a class="btn" ' . ($page === $pages ? 'style="opacity:0.6; pointer-events:none;"' : '') . ' href="' . $base . '&page=' . (min($pages, $page + 1)) . '">Next</a>'
      . '</div>'
      . '</div>'
      . '</div>';
    break;

    // Additional pages (Categories/Menu/Fixed/Orders) can be added similarly with DB CRUD.
}

include __DIR__ . '/includes/footer.php';
