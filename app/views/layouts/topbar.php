<?php
// Khởi động session nếu chưa có (để lấy thông tin user)
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Xác định file hiện tại để tô sáng menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<header class="topbar" role="banner">
  <div class="nav-left">
    <div class="logo">
      <a href="index.php">
        <img src="assets/images/logo.png" alt="DreamBoard Logo">
      </a>
    </div>

    <nav aria-label="Main navigation" style="display:flex;gap:6px;margin-left:18px;">
      <a class="nav-item <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="index.php">Home</a>
      <a class="nav-item <?= ($current_page == 'vision.php') ? 'active' : '' ?>" href="vision.php">Dream Canvas</a>
      <a class="nav-item <?= ($current_page == 'journal.php') ? 'active' : '' ?>" href="journal.php">Journey Log</a>
      <a class="nav-item <?= ($current_page == 'future.php') ? 'active' : '' ?>" href="future.php">Future Letter</a>
    </nav>
  </div>

  <div class="nav-right">
    <?php if (isset($_SESSION['user_id'])): ?>


      <img class="user-avatar" src="<?php
      echo isset($_SESSION['avatar']) && $_SESSION['avatar']
        ? $_SESSION['avatar']
        : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=C6A7FF&color=ffffff&size=50&bold=true&rounded=true';
      ?>">
      <span class="user-name">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
      <a href="logout.php" class="btn-ghost" style="text-decoration: none; display: flex; align-items: center; gap: 5px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M15 3h4a2 2 0 0 1 2-2v14" stroke="#5b5b66" stroke-width="1.6" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        Logout
      </a>


    <?php else: ?>

      <a href="login.php" class="btn-ghost" style="text-decoration: none; display: flex; align-items: center; gap: 5px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M15 3h4a2 2 0 0 1 2-2v14" stroke="#5b5b66" stroke-width="1.6" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        Login
      </a>

      <a href="register.php" class="btn-ghost btn-sign"
        style="text-decoration: none; display: flex; align-items: center; gap: 5px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
          <path d="M12 5v14" stroke="white" stroke-width="2" stroke-linecap="round" />
          <path d="M5 12h14" stroke="white" stroke-width="2" stroke-linecap="round" />
        </svg>
        Sign up
      </a>

    <?php endif; ?>
  </div>
</header>

<link rel="stylesheet" href="assets/css/topbar.css">