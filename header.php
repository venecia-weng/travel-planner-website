<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['currency'])) {
    $_SESSION['currency'] = 'SGD';
}
$current_currency = $_SESSION['currency'];

$favorites_count = 0;
$profile_image = null;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['user_id'])) {
    require_once 'includes/db_connect.php';
    $user_id = $_SESSION['user_id'];

    $fav_stmt =  $conn->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
    $fav_stmt->bind_param("i", $user_id);
    $fav_stmt->execute();
    $fav_result = $fav_stmt->get_result();
    if ($fav_result->num_rows > 0) {
        $favorites_count = $fav_result->fetch_assoc()['count'];
    }

    $stmt = $conn->prepare("SELECT profile_image, first_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $profile_image = $user['profile_image'];
        $user_first_name = $user['first_name'];
    }
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($page_title) ? $page_title : 'RoundTours - Tours and Travel Landing Page'; ?></title>
    <link rel="icon" type="image/png" sizes="80x80" href="assets/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        .header {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar {
            padding-top: 15px !important;
            padding-bottom: 15px !important;
        }

        .navbar-brand img {
            height: 50px;
            width: auto;
        }

        .navbar-nav .nav-link {
            padding: 15px 18px !important;
            font-size: 16px;
            font-weight: 500;
        }

        .profile-pic-nav {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .currency-dropdown .nav-link {
            font-weight: 500;
            padding: 15px 15px !important;
        }

        .cart-icon {
            font-size: 22px;
            position: relative;
            color: #333;
            display: inline-block;
            margin-top: 3px;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 10px;
            width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 0;
            display: inline-block;
        }

        .user-profile-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
            font-size: 20px;
        }

        .user-dropdown-menu {
            min-width: 200px;
            margin-top: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .user-dropdown-menu .dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .user-dropdown-menu .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .user-dropdown-menu .dropdown-item i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .user-dropdown-menu .dropdown-divider {
            margin: 0.5rem 0;
        }

        .user-dropdown-menu .badge {
            margin-left: auto;
        }

        .user-name {
            font-weight: 500;
            color: #333;
        }

        .navbar-nav.page-menu.mb-3.mb-lg-0 {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
<div class="pagewrap">
    <div class="head-wrapper">
        <header class="header theme-bg-white">
            <div class="container">
                <nav class="navbar navbar-expand-lg py-3 py-lg-0 px-0">
                    <a class="navbar-brand" href="index.php">
                        <img src="assets/images/logo.png" alt="Brand Logo" class="img-fluid">
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav me-auto page-menu" id="nav">
                            <?php
                            $menu_items = [
                                ['link' => 'about-us.php', 'text' => 'About'],
                                ['link' => 'destinations.php', 'text' => 'Destinations'],
                                ['link' => 'flights.php', 'text' => 'Flights'],
                                ['link' => 'hotels.php', 'text' => 'Hotels'],
                                ['link' => 'itinerary.php', 'text' => 'Itinerary'],
                                ['link' => 'blogs.php', 'text' => 'Blogs']
                            ];
                            foreach ($menu_items as $index => $item) {
                                $is_active = (basename($_SERVER['PHP_SELF']) == $item['link']) ? 'active' : '';
                                $padding_class = $index === 0 ? 'pe-5 ps-0 ps-lg-5' : 'pe-5';
                                echo '<li class="nav-item"><a class="nav-link ' . $padding_class . ' ' . $is_active . '" href="' . $item['link'] . '">' . $item['text'] . '</a></li>';
                            }
                            ?>
                        </ul>

                        <ul class="navbar-nav page-menu mb-3 mb-lg-0">
                            <!-- Currency -->
                            <li class="nav-item dropdown currency-dropdown">
                                <a href="#" class="nav-link dropdown-toggle" id="currencyDropdown" data-bs-toggle="dropdown">
                                    <?php echo $current_currency; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencyDropdown">
                                    <?php
                                    $currencies = ['SGD', 'USD', 'EUR', 'THB'];
                                    foreach ($currencies as $currency) {
                                        $active_class = ($currency == $current_currency) ? 'active' : '';
                                        echo '<li><a class="dropdown-item ' . $active_class . '" href="change_currency.php?currency=' . $currency . '">' . $currency . '</a></li>';
                                    }
                                    ?>
                                </ul>
                            </li>

                            <!-- Cart -->
                            <li class="nav-item me-2">
                                <a href="cart.php" class="nav-link position-relative">
                                    <i class="bi bi-cart cart-icon"></i>
                                    <span class="cart-badge cart-count">0</span>
                                </a>
                            </li>

                            <!-- User -->
                            <li class="nav-item dropdown my-auto">
                                <a href="#" class="nav-link dropdown-toggle p-0 user" id="userDropdown" data-bs-toggle="dropdown" aria-label="User Profile Dropdown">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                                            <?php if (!empty($user_first_name)): ?>
                                                <span class="user-name d-none d-md-inline">Welcome, <?php echo htmlspecialchars($user_first_name); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($profile_image)): ?>
                                                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-pic-nav">
                                            <?php else: ?>
                                                <div class="user-profile-icon theme-bg-primary"><i class="bi bi-person-fill"></i></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="user-profile-icon theme-bg-primary"><i class="bi bi-person-fill"></i></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu sub-menu" aria-labelledby="userDropdown">
                                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i>My Profile</a></li>
                                        <li><a class="dropdown-item" href="my-trips.php"><i class="bi bi-briefcase"></i>My Trips</a></li>
                                        <li><a class="dropdown-item" href="favorites.php"><i class="bi bi-heart"></i>Favorites
                                            <?php if ($favorites_count > 0): ?>
                                                <span class="badge bg-primary ms-1"><?php echo $favorites_count; ?></span>
                                            <?php endif; ?>
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a></li>
                                    <?php else: ?>
                                        <li><a class="dropdown-item" href="login.php"><i class="bi bi-box-arrow-in-right"></i>Sign in</a></li>
                                        <li><a class="dropdown-item" href="register.php"><i class="bi bi-person-plus"></i>Register</a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
            <script>
                function updateCartCount() {
                    try {
                        const cart = JSON.parse(localStorage.getItem('cart')) || [];
                        document.querySelectorAll('.cart-count').forEach(el => {
                            el.textContent = cart.length;
                            el.style.display = 'inline-block';
                        });
                    } catch (e) {
                        document.querySelectorAll('.cart-count').forEach(el => {
                            el.textContent = '0';
                            el.style.display = 'inline-block';
                        });
                    }
                }
                document.addEventListener('DOMContentLoaded', updateCartCount);
                window.addEventListener('storage', updateCartCount);
            </script>
        </header>
    </div>
</div>
</body>
</html>