<?php
// final_code/includes/init.php - DB bootstrap, helpers, auth, data operations
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Define SITE_URL - base URL of the application
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
define('SITE_URL', $protocol . '://' . $_SERVER['HTTP_HOST'] . $basePath);

require_once __DIR__ . '/db.php';  // provides $pdo

// --- Basic helpers ---
function h($s)
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Set in your environment (e.g., web server config, system env, or a .env loader)
// For local development, you can temporarily set them via putenv as shown below:
// Cloud image uploads have been removed. Only direct image URLs are supported now.

function redirect($to)
{
  header("Location: $to");
  exit;
}

// --- DB accessor ---
function db()
{
  global $pdo;
  return $pdo;
}

function cust_current()
{
  return $_SESSION['customer_session'] ?? null;
}

function cust_is_authed()
{
  return !!cust_current();
}

function cust_require()
{
  if (!cust_is_authed())
    redirect('login.php');
}

// Creates or fetches a user by email, then logs them in
function cust_login_or_register($fullName, $email, $phone, $address, $password = null)
{
  $pdo = db();
  $email = trim((string) $email);
  if ($email === '')
    return [false, 'Email required'];

  // Check if this is a login attempt (no password provided) or registration (password provided)
  if ($password === null) {
    // Login attempt - we need to get the password from the form
    return [false, 'Password required'];
  }

  try {
    $stmt = $pdo->prepare('SELECT id, full_name, email, password FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if (!$u) {
      // Register new user
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $ins = $pdo->prepare('INSERT INTO users (full_name, email, password, phone, address, created_at) VALUES (?,?,?,?,?,?)');
      $ins->execute([$fullName ?: 'Customer', $email, $hashedPassword, $phone ?: '', $address ?: '', time()]);
      $id = (int) $pdo->lastInsertId();
      $u = ['id' => $id, 'full_name' => $fullName ?: 'Customer', 'email' => $email];
    } else {
      // User exists, verify password
      if (!password_verify($password, $u['password'])) {
        return [false, 'Invalid password'];
      }
    }

    $_SESSION['customer_session'] = ['id' => $u['id'], 'email' => $u['email'], 'fullName' => $u['full_name'] ?? $u['fullName'] ?? 'Customer', 'ts' => time()];
    return [true, null];
  } catch (Throwable $e) {
    return [false, 'DB error'];
  }
}

function cust_logout()
{
  unset($_SESSION['customer_session']);
}

// --- Data helpers ---
function fetch_categories_all()
{
  $pdo = db();
  $rows = $pdo->query('SELECT id, name, detail, image FROM categories ORDER BY id DESC')->fetchAll();
  return $rows ?: [];
}

function get_menu_item_by_id($id)
{
  $pdo = db();
  $st = $pdo->prepare('SELECT id, category_id as categoryId, name, detail, price, discount, image FROM menu_items WHERE id = ?');
  $st->execute([(int) $id]);
  return $st->fetch() ?: null;
}

function fetch_menu_items_paginated($categoryId = 'all', $pageNo = 1, $size = 12)
{
  $pdo = db();
  $where = '';
  $params = [];
  if ($categoryId !== 'all') {
    $where = 'WHERE category_id = ?';
    $params[] = (int) $categoryId;
  }
  $cnt = $pdo->prepare("SELECT COUNT(*) c FROM menu_items $where");
  $cnt->execute($params);
  $total = (int) ($cnt->fetch()['c'] ?? 0);
  $offset = max(0, ($pageNo - 1) * $size);
  if ($where) {
    $st = $pdo->prepare("SELECT id, category_id as categoryId, name, detail, price, discount, image FROM menu_items $where ORDER BY id DESC LIMIT ? OFFSET ?");
    $st->bindValue(1, (int) $params[0], PDO::PARAM_INT);
    $st->bindValue(2, (int) $size, PDO::PARAM_INT);
    $st->bindValue(3, (int) $offset, PDO::PARAM_INT);
  } else {
    $st = $pdo->prepare('SELECT id, category_id as categoryId, name, detail, price, discount, image FROM menu_items ORDER BY id DESC LIMIT ? OFFSET ?');
    $st->bindValue(1, (int) $size, PDO::PARAM_INT);
    $st->bindValue(2, (int) $offset, PDO::PARAM_INT);
  }
  $st->execute();
  return [$st->fetchAll() ?: [], $total];
}

// --- Orders ---
function place_order_item($itemId, $qty = 1)
{
  $me = cust_current();
  if (!$me)
    return [false, 'Not logged in'];
  $m = get_menu_item_by_id($itemId);
  if (!$m)
    return [false, 'Invalid item'];
  $price = (float) $m['price'];
  $disc = (int) $m['discount'];
  $final = $price * (1 - $disc / 100);
  $line = $final * max(1, (int) $qty);
  $st = db()->prepare('INSERT INTO orders (user_id, item_id, qty, status, price, created_at) VALUES (?,?,?,?,?,?)');
  $st->execute([$me['id'], (int) $itemId, max(1, (int) $qty), 'pending', $line, time()]);
  return [true, 'Order placed'];
}

// --- Admin Auth ---
function admin_current()
{
  return $_SESSION['admin_session'] ?? null;
}

function admin_is_authed()
{
  return !!admin_current();
}

function admin_require()
{
  if (!admin_is_authed())
    redirect('admin/login.php');
}

function admin_login($email, $password)
{
  try {
    $st = db()->prepare('SELECT id, full_name, email, password FROM admins WHERE email = ? LIMIT 1');
    $st->execute([trim((string) $email)]);
    $u = $st->fetch();
    if ($u && ($u['password'] === $password || password_verify($password, $u['password']))) {
      $_SESSION['admin_session'] = ['id' => $u['id'], 'email' => $u['email'], 'fullName' => $u['full_name'] ?? 'Admin', 'ts' => time()];
      return true;
    }
  } catch (Throwable $e) {  /* ignore */
  }
  return false;
}

function admin_logout()
{
  unset($_SESSION['admin_session']);
}

// --- Dashboard totals ---
function get_totals()
{
  $pdo = db();
  $todayStart = strtotime(date('Y-m-d 00:00:00'));
  $todayEnd = $todayStart + 86400;
  $monthStart = strtotime(date('Y-m-01 00:00:00'));
  $nextMonthStart = strtotime(date('Y-m-01 00:00:00', strtotime('+1 month')));
  $categoryCount = (int) ($pdo->query('SELECT COUNT(*) c FROM categories')->fetch()['c'] ?? 0);

  // Contact messages stats (with error handling)
  $totalContacts = 0;
  $unreadContacts = 0;
  $todayContacts = 0;

  try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
    if ($tableCheck->fetch()) {
      $totalContacts = (int) ($pdo->query('SELECT COUNT(*) c FROM contact_messages')->fetch()['c'] ?? 0);
      $unreadContacts = (int) ($pdo->query('SELECT COUNT(*) c FROM contact_messages WHERE status = "unread"')->fetch()['c'] ?? 0);

      $stmtContacts = $pdo->prepare('SELECT COUNT(*) c FROM contact_messages WHERE created_at >= ? AND created_at < ?');
      $stmtContacts->execute([$todayStart, $todayEnd]);
      $todayContacts = (int) ($stmtContacts->fetch()['c'] ?? 0);
    }
  } catch (Exception $e) {
    // Table doesn't exist or other error - use default values (0)
  }

  $stmt = $pdo->prepare('SELECT status, COUNT(*) c, SUM(price) s FROM orders WHERE created_at >= ? AND created_at < ? GROUP BY status');
  $stmt->execute([$todayStart, $todayEnd]);
  $todayMap = ['pending' => 0, 'confirmed' => 0, 'delivered' => 0, 'cancelled' => 0];
  $todaySum = 0;
  $todayCount = 0;
  foreach ($stmt->fetchAll() as $r) {
    $todayMap[$r['status']] = (int) $r['c'];
    if ($r['status'] !== 'cancelled')
      $todaySum += (float) $r['s'];
    $todayCount += (int) $r['c'];
  }
  $stm2 = $pdo->prepare('SELECT status, COUNT(*) c, SUM(price) s FROM orders WHERE created_at >= ? AND created_at < ? GROUP BY status');
  $stm2->execute([$monthStart, $nextMonthStart]);
  $monthSum = 0;
  $monthCount = 0;
  foreach ($stm2->fetchAll() as $r) {
    if ($r['status'] !== 'cancelled')
      $monthSum += (float) $r['s'];
    $monthCount += (int) $r['c'];
  }

  return [
    'categoryCount' => $categoryCount,
    'todayOrders' => $todayCount,
    'monthOrders' => $monthCount,
    'todayCancelled' => $todayMap['cancelled'] ?? 0,
    'todayConfirmed' => $todayMap['confirmed'] ?? 0,
    'todayDelivered' => $todayMap['delivered'] ?? 0,
    'todayPending' => $todayMap['pending'] ?? 0,
    'monthRevenue' => $monthSum,
    'todayRevenue' => $todaySum,
    'totalContacts' => $totalContacts,
    'unreadContacts' => $unreadContacts,
    'todayContacts' => $todayContacts,
  ];
}
