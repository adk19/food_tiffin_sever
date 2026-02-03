<?php
require_once __DIR__ . '/includes/init.php';
$page = 'Home';
$active = 'home';

$categories = fetch_categories_all();
// Latest 8 menu items
[$latestItems, $total] = fetch_menu_items_paginated('all', 1, 8);
// Safe default reviews if not provided elsewhere
$reviews = $reviews ?? [
  ['name' => 'Aarav', 'rating' => 5, 'comment' => 'Tasty and on time! Loved the quality.'],
  ['name' => 'Priya', 'rating' => 4, 'comment' => 'Great portions and very hygienic.'],
  ['name' => 'Rahul', 'rating' => 5, 'comment' => 'Best tiffin service around. Recommended.'],
];
include __DIR__ . '/includes/header.php';

// Direct order handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'order_now') {
  $id = (int) ($_POST['id'] ?? 0);
  $qty = max(1, (int) ($_POST['qty'] ?? 1));
  $back = 'index.php';
  if (!cust_is_authed()) {
    redirect('login.php?redirect=' . urlencode($back));
  }
  [$ok, $msg] = place_order_item($id, $qty);
  redirect($back);
}
?>
<section class="hero" style="--hero:url('https://images.unsplash.com/photo-1478145046317-39f10e56b5e9?q=80&w=2000&auto=format&fit=crop');">
  <div class="hero-overlay">
    <div class="container hero-content">
      <h1>Experience Homely, Healthy Tiffin</h1>
      <p>Freshly cooked meals delivered to your doorstep, everyday.</p>
      <div class="chip-row">
        <span class="chip">Homely</span>
        <span class="chip">Affordable</span>
        <span class="chip">On Time</span>
        <span class="chip">Hygienic</span>
      </div>
      <div class="cta-row">
        <a class="btn" href="menu.php">Explore Menu</a>
      </div>
    </div>
  </div>
</section>

<section class="section info-sections">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow">OUR STORY</div>
      <h2 class="section-title">About Our Restaurant</h2>
    </div>
    <div class="info-grid">
      <div class="info-text m-bottom">
        <p>
          At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis
          praesentium voluptatum deleniti atque corrupti quos dolores et quas
          molestias excepturi sint occaecati cupiditate non provident.
        </p>
        <a class="btn" href="about.php">Our Story</a>
      </div>
      <div class="info-media m-bottom">
        <img class="info-img" src="https://images.unsplash.com/photo-1481833761820-0509d3217039?q=80&w=1200&auto=format&fit=crop" alt="Restaurant interior" />
      </div>

      <div class="info-media">
        <img class="info-img" src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=1200&auto=format&fit=crop" alt="Dishes" />
      </div>
      <div class="info-text m-top">
        <p>
          At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis
          praesentium voluptatum deleniti atque corrupti quos dolores et quas
          molestias excepturi sint occaecati non provident.
        </p>
        <a class="btn" href="menu.php">View More</a>
      </div>
    </div>
  </div>
</section>

<section class="section how-works">
  <div class="container">
    <div class="how-eyebrow">HOW IT WORKS</div>
    <div class="how-grid">
      <div class="how-card">
        <div class="how-icon" aria-hidden="true">▮▮▮</div>
        <div class="how-title">Choose Your Favorite</div>
        <div class="how-text">Choose your favorite meals and order online or by phone. It’s easy to customize your order.</div>
      </div>
      <div class="how-card">
        <div class="how-icon" aria-hidden="true">🛵</div>
        <div class="how-title">We Deliver Your Meals</div>
        <div class="how-text">We prepare and deliver meals right to your door. Enjoy fresh food, always on time.</div>
      </div>
      <div class="how-card">
        <div class="how-icon" aria-hidden="true">🍽️</div>
        <div class="how-title">Eat And Enjoy</div>
        <div class="how-text">No shopping, no cooking, no cleaning. Enjoy your healthy meals with your family.</div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-title">Categories</div>
    <div class="category-grid">
      <?php foreach ($categories as $c): ?>
        <?php $img = trim((string) ($c['image'] ?? ''));
        if ($img === '') {
          $img = 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=1200&auto=format&fit=crop';
        } ?>
        <a class="category-card" href="menu.php?category=<?php echo h($c['id']); ?>" aria-label="View <?php echo h($c['name']); ?>">
          <div class="media">
            <img src="<?php echo h($img); ?>" alt="<?php echo h($c['name']); ?>" />
            <div class="overlay"></div>
            <div class="heading"><?php echo h($c['name']); ?></div>
          </div>
          <div class="body">
            <div class="sub" title="<?php echo h($c['detail']); ?>"><?php echo h($c['detail']); ?></div>
            <div class="cta">Explore</div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-title">Latest Dishes</div>
    <div class="menu-grid">
      <?php foreach ($latestItems as $m): ?>
        <div class="menu-card">
          <img src="<?php echo h($m['image']); ?>" alt="<?php echo h($m['name']); ?>" />
          <div class="body">
            <div class="name"><?php echo h($m['name']); ?></div>
            <div class="sub"><?php echo h($m['detail']); ?></div>
            <div class="actions">
              <form method="post" class="inline">
                <input type="hidden" name="action" value="order_now" />
                <input type="hidden" name="id" value="<?php echo h($m['id']); ?>" />
                <div class="qty-group" data-step="1" data-min="1">
                  <input class="qty" type="number" name="qty" min="1" value="1" />
                </div>
                <button class="btn" type="submit">Order Now</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="stunning-eyebrow">For your comfort</div>
    <div class="stunning-title">Stunning Things</div>
    <div class="stunning-grid">
      <div class="feature-card">
        <div class="feature-icon">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12h18"/><path d="M6 12a6 6 0 0 0 12 0"/></svg>
        </div>
        <div class="feature-title">High Quality Foods</div>
        <div class="feature-sub">Etiam feugiat eleifend est, sed luctus odio tempor vitae. Vivamus maximus scelerisque ipsum nec commodo.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20"/><path d="M7 7h10"/><path d="M9 12h6"/><path d="M10 17h4"/></svg>
        </div>
        <div class="feature-title">Inspiring Recipes</div>
        <div class="feature-sub">Etiam feugiat eleifend est, sed luctus odio tempor vitae. Vivamus maximus scelerisque ipsum nec commodo.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M8 12h8"/></svg>
        </div>
        <div class="feature-title">Salutary Meals</div>
        <div class="feature-sub">Etiam feugiat eleifend est, sed luctus odio tempor vitae. Vivamus maximus scelerisque ipsum nec commodo.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 21v-8l-5-5V5h18v3l-5 5v8"/><path d="M12 12v9"/></svg>
        </div>
        <div class="feature-title">Veteran Staff</div>
        <div class="feature-sub">Etiam feugiat eleifend est, sed luctus odio tempor vitae. Vivamus maximus scelerisque ipsum nec commodo.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12s4-8 8-8 8 8 8 8-4 8-8 8-8-8-8-8z"/></svg>
        </div>
        <div class="feature-title">Pristine Ingredients</div>
        <div class="feature-sub">Etiam feugiat eleifend est, sed luctus odio tempor vitae. Vivamus maximus scelerisque ipsum nec commodo.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="7" width="14" height="10" rx="2"/><path d="M9 7v-2h6v2"/></svg>
        </div>
        <div class="feature-title">Express Delivery</div>
        <div class="feature-sub">Etiam feugiat eleifend est, sed luctus odio tempor vitae. Vivamus maximus scelerisque ipsum nec commodo.</div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt chef">
  <div class="container">
    <div class="chef-wrap">
      <div class="chef-col">
        <div class="chef-item">
          <div class="chef-icon">🍽️</div>
          <div class="chef-title">Salubrious Snacks</div>
          <div class="chef-sub">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incid iduntus ut</div>
        </div>
        <div class="chef-item">
          <div class="chef-icon">🥤</div>
          <div class="chef-title">Healthy Drinks</div>
          <div class="chef-sub">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incid iduntus ut</div>
        </div>
        <div class="chef-item">
          <div class="chef-icon">☕</div>
          <div class="chef-title">Chocolate Desserts</div>
          <div class="chef-sub">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incid iduntus ut</div>
        </div>
      </div>
      <div class="chef-center">
        <img src="https://images.unsplash.com/photo-1528712306091-ed0763094c98?q=80&w=1200&auto=format&fit=crop" alt="Chef" />
      </div>
      <div class="chef-col">
        <div class="chef-item">
          <div class="chef-icon gold">🍷</div>
          <div class="chef-title">Hot Spirits</div>
          <div class="chef-sub">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incid iduntus ut</div>
        </div>
        <div class="chef-item">
          <div class="chef-icon gold">🥡</div>
          <div class="chef-title">Packaged Foods</div>
          <div class="chef-sub">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incid iduntus ut</div>
        </div>
        <div class="chef-item">
          <div class="chef-icon gold">💡</div>
          <div class="chef-title">Spicy Stuff</div>
          <div class="chef-sub">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incid iduntus ut</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section reviews">
  <div class="container">
    <div class="section-title" style="text-align:center;">Customer Reviews</div>
    <div class="reviews-grid">
      <?php foreach ($reviews as $r):
        $stars = max(1, min(5, (int) ($r['rating'] ?? 5))); ?>
        <div class="review-card">
          <div class="review-top">
            <div class="avatar-sm"><?php echo strtoupper(substr(h($r['name'] ?? 'U'), 0, 1)); ?></div>
            <div class="who">
              <div class="nm"><?php echo h($r['name'] ?? 'User'); ?></div>
              <div class="stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <span class="star <?php echo $i <= $stars ? 'fill' : ''; ?>">★</span>
                <?php endfor; ?>
              </div>
            </div>
          </div>
          <div class="review-body"><?php echo h($r['comment'] ?? ''); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
