<?php
// Khởi động session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Xác định file hiện tại
$current_page = basename($_SERVER['PHP_SELF']);
?>

<header class="topbar" role="banner">
  <div class="nav-left">
    <div class="logo">
      <a href="index.php">
        <img src="assets/images/logo.png" alt="DreamBoard Logo">
      </a>
    </div>


    <nav id="main-nav" aria-label="Main navigation">
      <a class="nav-item <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="index.php">Home</a>
      <a class="nav-item <?= ($current_page == 'vision.php') ? 'active' : '' ?>" href="vision.php">Dream Canvas</a>
      <a class="nav-item <?= ($current_page == 'journal.php') ? 'active' : '' ?>" href="journal.php">Journey Log</a>
      <a class="nav-item <?= ($current_page == 'future.php') ? 'active' : '' ?>" href="future.php">Future Letter</a>
    </nav>
  </div>
  <div class="nav-right">
    <?php if (isset($_SESSION['user_id'])): ?>

      <div class="user-info">
        <img class="user-avatar" src="<?php
        echo isset($_SESSION['avatar']) && $_SESSION['avatar']
          ? $_SESSION['avatar']
          : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=C6A7FF&color=ffffff&size=50&bold=true&rounded=true';
        ?>">
        <span class="user-name">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
      </div>

      <a href="logout.php" class="btn-ghost logout-btn-desktop">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M15 3h4a2 2 0 0 1 2-2v14" stroke="#5b5b66" stroke-width="1.6" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        Logout
      </a>

    <?php else: ?>

      <div class="auth-buttons">
        <a href="login.php" class="btn-ghost">
          Login
        </a>
        <a href="register.php" class="btn-ghost btn-sign">
          Sign up
        </a>
      </div>

    <?php endif; ?>

    <button class="hamburger-btn" onclick="toggleMenu()">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5b5b66" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>
  </div>
</header>


<script>
  function toggleMenu() {
    const nav = document.getElementById('main-nav');
    nav.classList.toggle('open');
  }
</script>