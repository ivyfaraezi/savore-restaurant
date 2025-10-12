<?php
session_start();
$isLoggedIn = false;
$customerData = null;

if (isset($_SESSION['customer_id'])) {
  $isLoggedIn = true;
  $customerData = [
    'id' => $_SESSION['customer_id'],
    'name' => $_SESSION['customer_name'],
    'email' => $_SESSION['customer_email'],
    'mobile' => $_SESSION['customer_mobile']
  ];
} elseif (isset($_COOKIE['remember_customer'])) {
  $cookieData = json_decode($_COOKIE['remember_customer'], true);
  if ($cookieData && isset($cookieData['email'])) {
    $_SESSION['customer_id'] = $cookieData['id'];
    $_SESSION['customer_name'] = $cookieData['name'];
    $_SESSION['customer_email'] = $cookieData['email'];
    $_SESSION['customer_mobile'] = $cookieData['mobile'];

    $isLoggedIn = true;
    $customerData = $cookieData;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Savoré Restaurant</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="assets/style.css" />
</head>

<body>
  <!-- Sign In Modal -->
  <div id="signin-modal" class="modal">
    <div class="modal-content">
      <span id="close-modal">&times;</span>
      <h2 id="modal-title">Savoré</h2>
      <form id="signin-form" method="POST" action="auth/login.php">
        <div class="field-row">
          <input type="text" name="email" id="signin-email" placeholder="Email" />
          <div class="field-error" id="signin-email-error"></div>
        </div>
        <div class="field-row">
          <div class="password-input-wrapper">
            <input type="password" name="password" id="signin-password" placeholder="Password" />
            <i class="fa fa-eye-slash toggle-password" id="toggle-signin-password"></i>
          </div>
          <div class="field-error" id="signin-password-error"></div>
        </div>
        <div class="remember-me-row">
          <input type="checkbox" id="remember-me" name="remember_me" checked />
          <label for="remember-me">Remember Me</label>
        </div>
        <div class="form-actions">
          <button type="submit" id="signin-submit-btn">
            <span class="btn-text" style="align-items:center;display:flex;">Login</span>
            <span class="btn-spinner" style="display:none;align-items:center;">
              <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
            </span>
          </button>
          <a href="" id="forgot-password-link">Forgot Password?</a>
          <a href="" id="sign-up-link">Don't have an account? Sign Up</a>
        </div>
        <div class="message-container" id="signin-message" style="margin-top:8px;"></div>
      </form>
    </div>
  </div>

  <!-- Sign Up Modal -->
  <div id="signup-modal" class="modal">
    <div class="modal-content">
      <span id="close-signup-modal">&times;</span>
      <h2 id="signup-modal-title">Sign Up</h2>

      <!-- Registration Form -->
      <form id="signup-form" class="signup-step" data-step="1">
        <div class="field-row">
          <input type="text" id="signup-name" placeholder="Name" />
          <div class="field-error" id="signup-name-error"></div>
        </div>
        <div class="field-row">
          <input type="email" id="signup-email" placeholder="Email" />
          <div class="field-error" id="signup-email-error"></div>
        </div>
        <div class="field-row">
          <input type="tel" id="signup-mobile" placeholder="Mobile Number (11 digits)" maxlength="11" pattern="[0-9]{11}" />
          <div class="field-error" id="signup-mobile-error"></div>
        </div>
        <div class="field-row">
          <div class="password-input-wrapper">
            <input type="password" id="signup-password" placeholder="Password" />
            <i class="fa fa-eye-slash toggle-password" id="toggle-signup-password"></i>
          </div>
          <div class="field-error" id="signup-password-error"></div>
        </div>
        <div class="field-row">
          <div class="password-input-wrapper">
            <input type="password" id="signup-confirm-password" placeholder="Confirm Password" />
            <i class="fa fa-eye-slash toggle-password" id="toggle-signup-confirm-password"></i>
          </div>
          <div class="field-error" id="signup-cpassword-error"></div>
        </div>
        <button type="submit" id="register-btn">Send Verification Code</button>
        <a href="" id="sign-in-link">Already have an account? Sign In</a>
      </form>

      <!-- OTP Verification Form -->
      <div id="otp-verification" class="signup-step" data-step="2" style="display: none;">
        <div class="otp-container">
          <h3>Verify Your Email</h3>
          <p>We've sent a 6-digit verification code to:</p>
          <p><strong id="verification-email"></strong></p>

          <form id="otp-form">
            <div class="otp-input-container">
              <input type="text" id="otp-input" placeholder="Enter 6-digit OTP" maxlength="6" required />
            </div>
            <button type="submit" id="verify-otp-btn">Verify Email</button>
          </form>

          <div class="otp-actions">
            <p>Didn't receive the code?</p>
            <button type="button" id="resend-otp-btn" class="resend-btn">Resend Code</button>
            <button type="button" id="change-email-btn" class="change-email-btn">Change Email</button>
          </div>

          <div id="otp-timer" class="otp-timer"></div>
        </div>
      </div>

      <!-- Success Message -->
      <div id="verification-success" class="signup-step" data-step="3" style="display: none;">
        <div class="success-container">
          <div class="success-icon">✓</div>
          <h3>Email Verified Successfully!</h3>
          <p>Welcome to Savoré Restaurant, <span id="welcome-name"></span>!</p>
          <p>Your account has been created and verified. You can now sign in and enjoy our services.</p>
          <button type="button" id="go-to-signin-btn" class="success-btn">Sign In Now</button>
        </div>
      </div>

      <!-- Loading Indicator -->
      <div id="loading-indicator" style="display: none;">
        <div class="loading-spinner"></div>
        <p>Processing...</p>
      </div>

      <!-- Message Display -->
      <div id="signup-message" class="message-container"></div>
    </div>
  </div>

  <!-- Reserve Table Modal -->
  <div id="reserve-modal" class="modal">
    <div class="modal-content">
      <span id="close-reserve-modal">&times;</span>
      <h2>Reserve a Table</h2>
      <form id="reserve-form">
        <div class="field-row">
          <input type="text" id="reserve-name" placeholder="Your Name" readonly />
          <div class="field-error" id="reserve-name-error"></div>
        </div>
        <div class="field-row">
          <input type="email" id="reserve-email" placeholder="Email" readonly />
          <div class="field-error" id="reserve-email-error"></div>
        </div>
        <div class="field-row">
          <input type="tel" id="reserve-phone" placeholder="Mobile Number (11 digits)" maxlength="11" pattern="[0-9]{11}" readonly />
          <div class="field-error" id="reserve-phone-error"></div>
        </div>

        <!-- Custom Date Picker -->
        <div class="custom-date-picker">
          <input type="text" id="reserve-date-display" placeholder="Select Date" readonly />
          <input type="hidden" id="reserve-date" name="date" />
          <div class="field-error" id="reserve-date-error"></div>
          <div class="date-picker-calendar" id="reserve-date-picker">
            <div class="calendar-header">
              <button type="button" class="calendar-nav" id="reserve-prev-month">&lt;</button>
              <span class="calendar-month-year" id="reserve-calendar-month-year"></span>
              <button type="button" class="calendar-nav" id="reserve-next-month">&gt;</button>
            </div>
            <div class="calendar-weekdays">
              <div class="calendar-weekday">Sun</div>
              <div class="calendar-weekday">Mon</div>
              <div class="calendar-weekday">Tue</div>
              <div class="calendar-weekday">Wed</div>
              <div class="calendar-weekday">Thu</div>
              <div class="calendar-weekday">Fri</div>
              <div class="calendar-weekday">Sat</div>
            </div>
            <div class="calendar-days" id="reserve-calendar-days">
              <!-- Days will be populated by JavaScript -->
            </div>
          </div>
        </div>

        <!-- Custom Time Picker -->
        <div class="custom-time-picker">
          <input type="text" id="reserve-time-display" placeholder="Select Time" readonly />
          <input type="hidden" id="reserve-time" name="time" />
          <div class="field-error" id="reserve-time-error"></div>
          <div class="time-picker-dropdown" id="reserve-time-picker">
            <div class="time-picker-scroll" id="reserve-time-list">
              <!-- Time slots will be populated by JavaScript -->
            </div>
          </div>
        </div>

        <div class="field-row">
          <input type="number" id="reserve-guests" placeholder="Number of Guests" min="1" max="20" />
          <div class="field-error" id="reserve-guests-error"></div>
        </div>
        <button type="submit">Reserve Table</button>
      </form>
      <div id="reserve-message" style="display: none;"></div>
    </div>
  </div>

  <!-- Change Password Modal -->
  <div id="change-password-modal" class="modal">
    <div class="modal-content">
      <span id="close-change-password-modal">&times;</span>
      <h2>Change Password</h2>
      <form id="change-password-form">
        <input type="password" id="current-password" placeholder="Current Password" required />
        <input type="password" id="new-password" placeholder="New Password" required />
        <input type="password" id="confirm-new-password" placeholder="Confirm New Password" required />
        <button type="submit">Change Password</button>
      </form>
      <div id="change-password-message" class="message-container"></div>
    </div>
  </div>

  <!-- Forgot Password Modal -->
  <div id="forgot-password-modal" class="modal">
    <div class="modal-content">
      <span id="close-forgot-password-modal">&times;</span>
      <h2>Forgot Password</h2>
      <p>Enter your email address and we'll send you a link to reset your password.</p>
      <form id="forgot-password-form">
        <div class="field-row">
          <input type="email" id="forgot-password-email" placeholder="Enter your email" />
          <div class="field-error" id="forgot-password-email-error"></div>
        </div>
        <button type="submit">Send Reset Link</button>
        <a href="" id="back-to-signin-link">Back to Sign In</a>
      </form>
      <div id="forgot-password-message" class="message-container"></div>
    </div>
  </div>

  <!-- Cart Modal -->
  <div id="cart-modal" class="modal">
    <div class="modal-content cart-modal-content">
      <div class="cart-header">
        <h2><i class="fa fa-shopping-cart"></i> Your Cart</h2>
        <span id="close-cart-modal">&times;</span>
      </div>
      <div class="cart-body">
        <div id="cart-items" class="cart-items">
          <div class="empty-cart">
            <i class="fa fa-shopping-cart empty-cart-icon"></i>
            <p>Your cart is empty</p>
            <p class="empty-cart-subtitle">Add some delicious items to get started!</p>
          </div>
        </div>
      </div>
      <div class="cart-footer">
        <div class="cart-total">
          <div class="total-row">
            <span>Subtotal:</span>
            <span id="cart-subtotal">৳0.00</span>
          </div>
          <div class="total-row">
            <span>Tax (10%):</span>
            <span id="cart-tax">৳0.00</span>
          </div>
          <div class="total-row final-total">
            <strong>Total: <span id="cart-total">৳0.00</span></strong>
          </div>
        </div>
        <button id="place-order-btn" class="place-order-btn" disabled>
          <i class="fa fa-credit-card"></i> Place Order
        </button>
      </div>
    </div>
  </div>

  <!-- Floating Orders Toggle Button -->
  <div id="floating-orders-btn" class="floating-orders-btn" style="display: <?php echo $isLoggedIn ? 'flex' : 'none'; ?>;">
    <i class="fa fa-receipt"></i>
  </div>

  <!-- Floating Reservations Toggle Button -->
  <div id="floating-reservations-btn" class="floating-reservations-btn" style="display: <?php echo $isLoggedIn ? 'flex' : 'none'; ?>;">
    <i class="fa fa-calendar-check"></i>
  </div>

  <!-- Order Status Sidebar -->
  <div id="order-sidebar" class="order-sidebar" style="display: none;">
    <div class="sidebar-header">
      <h3><i class="fa fa-receipt"></i> My Orders</h3>
      <button id="toggle-sidebar" class="sidebar-toggle">
        <i class="fa fa-chevron-left"></i>
      </button>
    </div>
    <div class="sidebar-content">
      <div id="sidebar-loading" class="sidebar-loading" style="display: none;">
        <div class="loading-spinner-small"></div>
        <p>Loading orders...</p>
      </div>
      <div id="orders-list" class="orders-list">
        <!-- User orders will be loaded here -->
      </div>
      <div id="no-orders" class="no-orders" style="display: none;">
        <i class="fa fa-shopping-cart"></i>
        <p>No orders yet</p>
        <small>Your orders will appear here</small>
      </div>
    </div>
  </div>

  <!-- Reservations Sidebar -->
  <div id="reservation-sidebar" class="reservation-sidebar" style="display: none;">
    <div class="sidebar-header">
      <h3><i class="fa fa-calendar-check"></i> My Reservations</h3>
      <button id="toggle-reservation-sidebar" class="sidebar-toggle">
        <i class="fa fa-chevron-right"></i>
      </button>
    </div>
    <div class="sidebar-content">
      <div id="reservation-sidebar-loading" class="sidebar-loading" style="display: none;">
        <div class="loading-spinner-small"></div>
        <p>Loading reservations...</p>
      </div>
      <div id="reservations-list" class="reservations-list">
        <!-- User reservations will be loaded here -->
      </div>
      <div id="no-reservations" class="no-reservations" style="display: none;">
        <i class="fa fa-calendar-times"></i>
        <p>No reservations yet</p>
        <small>Your reservations will appear here</small>
      </div>
    </div>
  </div>

  <header>
    <nav>
      <div class="navbar-container">
        <div class="logo">
          <a href="#home" class="logo-link">
            <h2 class="logo-title">Savoré</h2>
          </a>
        </div>
        <div class="nav-links-inline">
          <a id="aboutBtn" href="#about" class="button">About</a>
          <a href="#menu" class="button">Menu</a>
          <a href="#contact" class="button">Contact</a>
          <a href="#review" class="button">Review</a>
          <div class="search-container">
            <input type="text" class="search-bar" placeholder="Search..." />
            <div class="search-dropdown" id="search-dropdown"></div>
          </div>
          <button class="cart-btn">
            <i class="fa fa-shopping-cart" aria-hidden="true"></i>Cart
          </button>
          <button class="signin-btn" style="display: <?php echo $isLoggedIn ? 'none' : 'inline-block'; ?>;">
            <i class="fa fa-sign-in-alt" aria-hidden="true"></i>Sign In
          </button>
          <span class="user-icon" style="display: <?php echo $isLoggedIn ? 'inline-block' : 'none'; ?>; position: relative; cursor: pointer">
            <i class="fa-solid fa-user"></i>
            <div class="user-dropdown">
              <div class="dropdown-row" style="display: none;">
                <strong>Username:</strong>
                <span id="dropdown-username"><?php echo $isLoggedIn ? htmlspecialchars($customerData['name']) : ''; ?></span>
              </div>
              <div class="dropdown-row">
                <strong>Name:</strong> <span id="dropdown-name"><?php echo $isLoggedIn ? htmlspecialchars($customerData['name']) : ''; ?></span>
              </div>
              <div class="dropdown-row">
                <strong>Email:</strong> <span id="dropdown-email"><?php echo $isLoggedIn ? htmlspecialchars($customerData['email']) : ''; ?></span>
              </div>
              <div class="dropdown-row">
                <strong>Mobile No:</strong>
                <span id="dropdown-mobile"><?php echo $isLoggedIn ? htmlspecialchars($customerData['mobile']) : ''; ?></span>
              </div>
              <button id="change-password-btn">Change Password</button>
              <button id="logout-btn">Logout</button>
            </div>
          </span>
        </div>
      </div>
    </nav>
  </header>
  <main>
    <!-- Hero Section -->
    <section id="home" class="home">
      <div class="home-overlay">
        <h1>Welcome to Savoré</h1>
        <p>Experience the finest dining with us.</p>
        <button class="reserve-btn">Reserve a Table</button>
      </div>
      <img src="assets/image/hero1.png" alt="Hero Image" class="hero-img" />
    </section>

    <!-- About Section -->
    <section id="about" class="about about-modern">
      <div class="about-img-container">
        <img src="assets/image/about.png" alt="Chef preparing food" class="about-img" />
      </div>
      <div class="about-text-modern">
        <h2>About Us</h2>
        <p>
          We are an independent, family-run restaurant located in the heart of
          the city. At Savoré, we are passionate about fresh ingredients,
          creative flavors and a welcoming atmosphere. Our chefs craft each
          dish with care, ensuring every meal is memorable. Whether you’re
          celebrating a special occasion or enjoying a casual dinner, our
          attentive staff will make you feel right at home. Join us and
          discover why Savoré is the perfect place for food lovers and
          families alike!
        </p>
      </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="menu menu-grid">
      <div class="menu-header">
        <h2>Menu</h2>
        <div class="menu-filter">
          <select id="menu-type-filter" class="menu-type-dropdown">
            <option value="all">All Items</option>
            <!-- Menu types will be loaded dynamically -->
          </select>
        </div>
      </div>
      <div id="menu-loading" class="menu-loading" style="display: block;">
        <div class="loading-spinner"></div>
        <p>Loading delicious menu items...</p>
      </div>
      <div id="menu-error" class="menu-error" style="display: none;">
        <p>Unable to load menu items. Please try again later.</p>
        <button id="retry-menu-btn" class="retry-btn">Retry</button>
      </div>
      <div id="menu-items" class="menu-items" style="display: none;">
        <!-- Menu items will be loaded dynamically -->
      </div>
    </section>

    <!-- Contact Section -->

    <section id="contact" class="contact contact-modern">
      <div class="contact-header">
        <h2>Contact Us</h2>
      </div>
      <div class="contact-content">
        <div class="contact-form-modern">
          <h3>Write Us A Message</h3>
          <form id="contact-form">
            <div class="form-row">
              <input type="text" id="contact-name" name="name" placeholder="Your Name" required />
              <input type="email" id="contact-email" name="email" placeholder="Email Address" required />
            </div>
            <div class="form-row">
              <input type="number" id="contact-phone" name="phone" placeholder="Mobile Number" required />
              <input type="text" id="contact-subject" name="subject" placeholder="Subject" required />
            </div>
            <textarea id="contact-message" name="message" placeholder="Write A Message" style="resize: none;" required></textarea>
            <button type="submit" class="send-btn">Send A Message</button>
          </form>
          <div id="contact-form-message" class="message-container" style="display: none;"></div>
        </div>
        <div class="contact-info-modern">
          <div class="info-block">
            <h4>Address</h4>
            <p>123 Savoré Street, Dhaka, Bangladesh</p>
          </div>
          <div class="info-block">
            <h4>Call Us</h4>
            <p>+880-1992346336</p>
            <p>+880-1857048383</p>
          </div>
          <div class="info-block">
            <h4>Email</h4>
            <p>savore.2006@gmail.com</p>
          </div>
        </div>
      </div>
      <div class="map-container-modern">
        <iframe src="https://www.google.com/maps?q=23.8221,90.4274&output=embed" width="100%" height="300"
          allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </section>

    <!-- Review Section -->
    <section id="review" class="reviews">
      <h2>Customer Reviews</h2>
      <form id="review-form">
        <input type="text" id="review-name" name="name" placeholder="Your Name" required maxlength="50" />
        <textarea id="review-message" name="message" placeholder="Your Review" rows="10" required style="resize: none;"></textarea>
        <input type="hidden" id="review-stars" name="stars" value="0" />
        <label for="review-stars">Rating</label>
        <div class="star-rating">
          <span class="star" data-value="1"><i class="fa fa-star fa-regular"></i></span>
          <span class="star" data-value="2"><i class="fa fa-star fa-regular"></i></span>
          <span class="star" data-value="3"><i class="fa fa-star fa-regular"></i></span>
          <span class="star" data-value="4"><i class="fa fa-star fa-regular"></i></span>
          <span class="star" data-value="5"><i class="fa fa-star fa-regular"></i></span>
        </div>
        <button type="submit">Submit Review</button>
      </form>
      <div id="review-message-display" class="message-container" style="display: none;"></div>
      <div id="reviews-list"></div>
    </section>
  </main>
  <footer>
    <div class="footer-content">
      <p>&copy; 2025 Savoré Restaurant. All rights reserved.</p>
      <div class="social-icons">
        <a href="https://facebook.com" class="social-link" aria-label="Facebook">
          <i class="fa-brands fa-facebook" aria-hidden="true"></i>
        </a>
        <a href="https://twitter.com" class="social-link" aria-label="Twitter">
          <i class="fa-brands fa-twitter" aria-hidden="true"></i>
        </a>
        <a href="https://instagram.com" class="social-link" aria-label="Instagram">
          <i class="fa-brands fa-instagram" aria-hidden="true"></i>
        </a>
        <a href="https://youtube.com" class="social-link" aria-label="YouTube">
          <i class="fa-brands fa-youtube" aria-hidden="true"></i>
        </a>
      </div>
    </div>
  </footer>

  <script>
    window.customerSession = <?php echo json_encode([
                                'isLoggedIn' => $isLoggedIn,
                                'customer' => $customerData
                              ]); ?>;
  </script>
  <script src="assets/scripts/main.js"></script>
</body>

</html>