<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MotoRent — <?= ucfirst($page_title ?? $current_page) ?></title>
<link rel="stylesheet" href="/motorent/assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="brand">🏍 MotoRent</div>
      <div class="sub">Fleet Management</div>
    </div>
    <nav>
      <span class="nav-section">Main</span>
      <a href="/motorent/index.php" class="nav-item <?= $current_page==='index'?'active':'' ?>">
        <span class="nav-icon">◈</span> Dashboard
      </a>
      <a href="/motorent/pages/bookings.php" class="nav-item <?= $current_page==='bookings'?'active':'' ?>">
        <span class="nav-icon">＋</span> New Booking
      </a>
      <a href="/motorent/pages/fleet.php" class="nav-item <?= $current_page==='fleet'?'active':'' ?>">
        <span class="nav-icon">◎</span> Fleet
      </a>
      <a href="/motorent/pages/rentals.php" class="nav-item <?= $current_page==='rentals'?'active':'' ?>">
        <span class="nav-icon">◷</span> Rentals
      </a>
      <span class="nav-section">Manage</span>
      <a href="/motorent/pages/customers.php" class="nav-item <?= $current_page==='customers'?'active':'' ?>">
        <span class="nav-icon">◉</span> Customers
      </a>
      <a href="/motorent/pages/payments.php" class="nav-item <?= $current_page==='payments'?'active':'' ?>">
        <span class="nav-icon">₱</span> Payments
      </a>
      <a href="/motorent/pages/maintenance.php" class="nav-item <?= $current_page==='maintenance'?'active':'' ?>">
        <span class="nav-icon">◫</span> Maintenance
      </a>
      <?php if(isAdmin()): ?>
      <a href="/motorent/pages/staff.php" class="nav-item <?= $current_page==='staff'?'active':'' ?>">
        <span class="nav-icon">★</span> Staff
      </a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <div class="staff-info">
        <div class="staff-avatar"><?= strtoupper(substr(currentStaff(),0,2)) ?></div>
        <div>
          <div class="staff-name"><?= htmlspecialchars(currentStaff()) ?></div>
          <div class="staff-role"><?= ucfirst(currentRole()) ?></div>
        </div>
      </div>
      <a href="/motorent/logout.php" class="btn-logout">Logout</a>
    </div>
  </aside>
  <main class="main-content">
