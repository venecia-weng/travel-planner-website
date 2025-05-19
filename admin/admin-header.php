<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
  header('Location: ../admin-login.php');
  exit;
}

// Get current page for nav highlighting
$active_page = isset($active_page) ? $active_page : '';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="RoundTours Admin Panel">
  <meta name="author" content="RoundTours">
  <title><?php echo isset($page_title) ? $page_title : 'Admin Panel - RoundTours'; ?></title>
  <!-- Favicon icon -->
  <link rel="icon" type="image/png" sizes="80x80" href="../assets/images/favicon.png">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin CSS -->
  <link href="assets/css/admin.css" rel="stylesheet">
  <!-- Any additional CSS -->
  <?php if (isset($additional_css)): ?>
    <?php echo $additional_css; ?>
  <?php endif; ?>
</head>

<body>
  <!-- Top Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm mb-4">
    <div class="container-fluid px-4">
      <!-- Logo/Brand -->
      <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
        <img src="../assets/images/logo.png" alt="RoundTours" height="40" class="me-2">
        <span class="fw-bold text-primary d-none d-md-inline">Admin Panel</span>
      </a>

      <!-- Toggle Button for Mobile -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Navigation Links -->
      <div class="collapse navbar-collapse" id="adminNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $active_page === 'dashboard' ? 'active fw-bold' : ''; ?>" href="dashboard.php">
              <i class="bi bi-speedometer2 me-1 text-primary"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $active_page === 'users' ? 'active fw-bold' : ''; ?>" href="users.php">
              <i class="bi bi-people me-1 text-primary"></i> Users
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $active_page === 'trips' ? 'active fw-bold' : ''; ?>" href="trips.php">
              <i class="bi bi-map me-1 text-primary"></i> Trips
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $active_page === 'bookings' ? 'active fw-bold' : ''; ?>" href="bookings-view.php">
              <i class="bi bi-calendar-check me-1 text-primary"></i> Bookings
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $active_page === 'blogs' ? 'active fw-bold' : ''; ?>" href="blog-list.php">
              <i class="bi bi-newspaper me-1 text-primary"></i> Blogs
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $active_page === 'hotels' ? 'active fw-bold' : ''; ?>" href="hotels-view.php">
              <i class="bi bi-building me-1 text-primary"></i> Hotels
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $active_page === 'rooms' ? 'active fw-bold' : ''; ?>" href="rooms-view.php">
              <i class="bi bi-hospital me-1 text-primary"></i> Rooms
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $active_page === 'flights' ? 'active fw-bold' : ''; ?>" href="flights-view.php">
              <i class="bi bi-airplane me-1 text-primary"></i> Flights
            </a>
          </li>
          <!-- Security Management Menu Items -->
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $active_page == 'security' ? 'active fw-bold' : ''; ?>" href="security-dashboard.php">
              <i class="bi bi-shield-lock me-1 text-primary"></i> Security
            </a>
          </li>
        </ul>

        <!-- User Account Dropdown -->
        <div class="dropdown">
          <a class="btn btn-light dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-2 text-primary"></i>
            <span class="me-1"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li><a class="dropdown-item" href="admin-profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
          </ul>
        </div>


      </div>
    </div>
  </nav>
  <!-- Page Content -->
  <div class="admin-page-content">
    <div class="container-fluid px-4 py-4">
      <!-- Main content goes here -->