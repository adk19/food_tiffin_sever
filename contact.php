<?php
require_once __DIR__ . '/includes/init.php';
$page = 'Contact';
$active = 'contact';
$err = null;
$ok = false;

// Initialize form data
$formData = [
  'name' => '',
  'email' => '',
  'phone' => '',
  'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));
  $message = trim((string) ($_POST['message'] ?? ''));

  // Store form data for repopulation on error
  $formData = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'message' => $message
  ];

  if ($name !== '' && $email !== '' && $message !== '') {
    try {
      // Check if contact_messages table exists first
      $pdo = db();
      $tableCheck = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
      if (!$tableCheck->fetch()) {
        $err = 'Contact system not set up. Please contact administrator.';
      } else {
        // Get current user ID if logged in
        $currentUser = cust_current();
        $userId = $currentUser ? $currentUser['id'] : null;

        // Store message in database
        $stmt = db()->prepare('INSERT INTO contact_messages (name, email, phone, message, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $message, $userId, time()]);

        // Clear form data on success
        $formData = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
        $ok = true;
        redirect('contact.php?sent=1');
      }
    } catch (Exception $e) {
      // Show actual error for debugging (remove in production)
      $err = 'Failed to send message: ' . $e->getMessage();
    }
  } else {
    $err = 'Please fill all required fields';
  }
}

// Check if message was sent successfully
if (isset($_GET['sent']) && $_GET['sent'] == '1') {
  $ok = true;
  // Clear form data on success page
  $formData = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
}
include __DIR__ . '/includes/header.php';
?>
<style>
/* Contact Page Responsive Styles */
@media (max-width: 768px) {
  .contact-grid {
    display: block !important;
    gap: 0 !important;
  }
  
  .contact-card {
    margin-bottom: 30px !important;
    padding: 20px !important;
  }
  
  .contact-form {
    display: block !important;
  }
  
  .form-field {
    margin-bottom: 20px !important;
    width: 100% !important;
  }
  
  .form-field.full {
    grid-column: unset !important;
  }
  
  .input, .textarea {
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 12px 16px !important;
    font-size: 16px !important; /* Prevents zoom on iOS */
  }
  
  .textarea {
    min-height: 120px !important;
    resize: vertical !important;
  }
  
  .btn {
    width: 100% !important;
    padding: 14px !important;
    font-size: 16px !important;
    margin-top: 10px !important;
  }
  
  .contact-info {
    margin-top: 0 !important;
  }
  
  .contact-card.mini {
    padding: 20px !important;
    margin-bottom: 20px !important;
  }
  
  .info-row {
    padding: 12px 0 !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    text-align: left !important;
  }
  
  .info-ic {
    margin-bottom: 8px !important;
    font-size: 20px !important;
  }
  
  .contact-map img {
    width: 100% !important;
    height: 200px !important;
    object-fit: cover !important;
    border-radius: 8px !important;
  }
  
  .section-title {
    font-size: 24px !important;
    text-align: center !important;
    margin-bottom: 30px !important;
  }
  
  .page-hero h1 {
    font-size: 32px !important;
  }
  
  .crumbs {
    font-size: 14px !important;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 0 15px !important;
  }
  
  .contact-card {
    padding: 15px !important;
    margin: 0 -5px 20px -5px !important;
  }
  
  .input, .textarea {
    padding: 10px 12px !important;
    font-size: 16px !important;
  }
  
  .btn {
    padding: 12px !important;
  }
  
  .page-hero h1 {
    font-size: 28px !important;
  }
  
  .section-title {
    font-size: 20px !important;
  }
  
  .info-row div {
    font-size: 14px !important;
    line-height: 1.4 !important;
  }
}

/* Tablet Styles */
@media (min-width: 769px) and (max-width: 1024px) {
  .contact-grid {
    grid-template-columns: 1fr !important;
    gap: 30px !important;
  }
  
  .contact-form {
    grid-template-columns: 1fr 1fr !important;
    gap: 20px !important;
  }
  
  .form-field.full {
    grid-column: 1 / -1 !important;
  }
  
  .contact-info {
    order: -1 !important;
  }
}

/* Desktop Enhancements */
@media (min-width: 1025px) {
  .contact-grid {
    display: grid !important;
    grid-template-columns: 2fr 1fr !important;
    gap: 40px !important;
    align-items: start !important;
  }
  
  .contact-form {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 20px !important;
  }
  
  .form-field.full {
    grid-column: 1 / -1 !important;
  }
  
  .form-actions {
    grid-column: 1 / -1 !important;
    text-align: right !important;
  }
  
  .btn {
    width: auto !important;
    min-width: 150px !important;
  }
}

/* Form Focus States */
.input:focus, .textarea:focus {
  outline: none !important;
  border-color: #4CAF50 !important;
  box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1) !important;
}

/* Error and Success Messages Responsive */
@media (max-width: 768px) {
  .section > .container > div[style*="background: rgba(239, 68, 68"],
  .section > .container > div[style*="background: rgba(34, 197, 94"] {
    margin: 0 -15px 20px -15px !important;
    border-radius: 0 !important;
    padding: 15px 20px !important;
  }
}

/* Success message animation */
.success-message {
  animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Form submission state */
.form-submitting {
  opacity: 0.7;
  pointer-events: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Auto-hide success message after 5 seconds
  const successMessage = document.querySelector('div[style*="background: rgba(34, 197, 94"]');
  if (successMessage) {
    successMessage.classList.add('success-message');
    setTimeout(function() {
      successMessage.style.transition = 'opacity 0.5s ease-out';
      successMessage.style.opacity = '0';
      setTimeout(function() {
        successMessage.style.display = 'none';
      }, 500);
    }, 5000);
  }

  // Prevent double form submission
  const contactForm = document.querySelector('.contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn.disabled) {
        e.preventDefault();
        return false;
      }
      
      // Disable submit button and show loading state
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Sending...';
      this.classList.add('form-submitting');
      
      // Re-enable after 3 seconds in case of error
      setTimeout(function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Send Message';
        contactForm.classList.remove('form-submitting');
      }, 3000);
    });
  }

  // Clear URL parameters after showing success message
  if (window.location.search.includes('sent=1')) {
    setTimeout(function() {
      const url = new URL(window.location);
      url.searchParams.delete('sent');
      window.history.replaceState({}, document.title, url.pathname);
    }, 1000);
  }
});
</script>

<section class="page-hero" style="--hero:url('https://images.unsplash.com/photo-1492724441997-5dc865305da7?q=80&w=1600&auto=format&fit=crop');">
  <div class="hero-overlay">
    <h1>Contact Us</h1>
    <div class="crumbs"><a href="index.php">Home</a> › <span>Contact</span></div>
  </div>
</section>
<section class="section">
  <div class="container">
    <div class="section-title">We'd love to hear from you</div>
    <?php if ($err): ?><div style="position:static; margin-bottom:10px; padding: 12px 16px; border-radius: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #ef4444; font-weight: 500;"><?php echo h($err); ?></div><?php endif; ?>
    <?php if ($ok): ?><div style="position:static; margin-bottom:10px; padding: 12px 16px; border-radius: 8px; background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #22c55e; font-weight: 500;">✅ Thank you! Your message has been sent successfully. We'll get back to you soon.</div><?php endif; ?>

    <div class="contact-grid">
      <div class="contact-card">
        <form method="post" class="contact-form" novalidate>
          <input type="hidden" name="action" value="send" />
          <div class="form-field">
            <label for="name" class="label">Your Name</label>
            <input id="name" class="input" name="name" value="<?php echo h($formData['name']); ?>" placeholder="e.g., Rahul Sharma" required />
          </div>
          <div class="form-field">
            <label for="email" class="label">Email</label>
            <input id="email" class="input" name="email" type="email" value="<?php echo h($formData['email']); ?>" placeholder="you@example.com" required />
          </div>
          <div class="form-field">
            <label for="phone" class="label">Phone (Optional)</label>
            <input id="phone" class="input" name="phone" type="tel" value="<?php echo h($formData['phone']); ?>" placeholder="e.g., +91 98765 43210" />
          </div>
          <div class="form-field full">
            <label for="message" class="label">Message</label>
            <textarea id="message" class="textarea" name="message" rows="10" placeholder="Write your message..." required><?php echo h($formData['message']); ?></textarea>
          </div>
          <div class="form-actions">
            <button class="btn" type="submit">Send Message</button>
          </div>
        </form>
      </div>

      <aside class="contact-info">
        <div class="contact-card mini">
          <div class="info-row"><span class="info-ic">📍</span><div>123, Food Street, Ahmedabad, Gujarat</div></div>
          <div class="info-row"><span class="info-ic">📞</span><div>+91 98765 43210</div></div>
          <div class="info-row"><span class="info-ic">⏰</span><div>Mon–Sat: 9:00 AM – 9:00 PM</div></div>
          <div class="info-row"><span class="info-ic">✉️</span><div>support@tiffin.example</div></div>
        </div>
        <div class="contact-map">
          <img src="https://images.unsplash.com/photo-1529070538774-1843cb3265df?q=80&w=1200&auto=format&fit=crop" alt="Office location" />
        </div>
      </aside>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
