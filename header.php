<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cart item count (stored as array of product IDs → qty in session)
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
}

// Current page detection for active nav link
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Maison Dorée — Artisan bakery & café. Freshly baked every morning.">
    <title><?= htmlspecialchars($pageTitle ?? 'Maison Dorée — Artisan Bakery & Café') ?></title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <!-- Google Fonts: Cormorant Garamond + DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">

    <!-- Custom theme -->
    <link rel="stylesheet" href="style.css">
</head>
<body>


<nav class="navbar navbar-expand-lg sticky-top" id="mainNav" aria-label="Main navigation">
    <div class="container">

        <!-- Brand / Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="navbar-brand-icon">
                <!-- SVG croissant icon -->
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M4 22C6 14 12 6 24 4C22 12 16 20 4 22Z" fill="#C8935A" opacity="0.9"/>
                    <path d="M24 22C22 14 16 6 4 4C6 12 12 20 24 22Z" fill="#9C6B3C" opacity="0.7"/>
                    <circle cx="14" cy="13" r="3" fill="#F5ECD7" opacity="0.6"/>
                </svg>
            </span>
            Maison <span>Dorée</span>
        </a>

        <!-- Mobile toggler -->
        <button
            class="navbar-toggler border-0 p-2"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMain"
            aria-controls="navbarMain"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto gap-lg-1">

                <!-- Home → index.php ✅ -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>"
                       href="index.php"
                       aria-current="<?= $currentPage === 'index.php' ? 'page' : 'false' ?>">
                        Home
                    </a>
                </li>

                <!-- Menu → products.php ✅ FIX 2: was 'menu.php' -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>"
                       href="products.php"
                       aria-current="<?= $currentPage === 'products.php' ? 'page' : 'false' ?>">
                        Menu
                    </a>
                </li>

                <!-- Reservations → reservation.php ✅ FIX 3: was 'reservations.php' -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'reservation.php' ? 'active' : '' ?>"
                       href="reservation.php"
                       aria-current="<?= $currentPage === 'reservation.php' ? 'page' : 'false' ?>">
                        Reservations
                    </a>
                </li>

                <!-- Cart → cart.php ✅ -->
                <li class="nav-item">
                    <a class="nav-link cart-link <?= $currentPage === 'cart.php' ? 'active' : '' ?>"
                       href="cart.php"
                       aria-label="Shopping cart, <?= $cartCount ?> items"
                       aria-current="<?= $currentPage === 'cart.php' ? 'page' : 'false' ?>">
                        <i class="bi bi-basket2" aria-hidden="true"></i>
                        Cart
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

            </ul>

            <!-- Auth buttons -->
            <div class="navbar-auth d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <!-- Logged-in state -->
                    <span class="navbar-greeting d-none d-lg-flex align-items-center gap-1">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?>
                    </span>
                    <a href="my_account.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-person me-1" aria-hidden="true"></i>My Account
                    </a>
                    <a href="my_orders.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-list-check me-1" aria-hidden="true"></i>My Orders
                    </a>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Logout
                    </a>
                <?php else: ?>
                    <!-- Login → login.php ✅ -->
                    <a href="login.php"
                       class="btn btn-outline-light btn-sm <?= $currentPage === 'login.php' ? 'active' : '' ?>">
                        <i class="bi bi-person me-1" aria-hidden="true"></i>Login
                    </a>
                    <!-- Register → register.php ✅ -->
                    <a href="register.php"
                       class="btn btn-secondary btn-sm <?= $currentPage === 'register.php' ? 'active' : '' ?>">
                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Register
                    </a>
                <?php endif; ?>
            </div>

        </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
</nav><!-- /#mainNav -->


<?php if (!empty($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div
        class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show flash-banner"
        role="alert"
        aria-live="assertive">
        <div class="container d-flex align-items-center gap-2">
            <?php
            $icons = [
                'success' => 'bi-check-circle-fill',
                'danger'  => 'bi-exclamation-triangle-fill',
                'warning' => 'bi-exclamation-circle-fill',
                'info'    => 'bi-info-circle-fill',
            ];
            $icon = $icons[$flash['type']] ?? 'bi-info-circle-fill';
            ?>
            <i class="bi <?= $icon ?> flex-shrink-0" aria-hidden="true"></i>
            <span><?= htmlspecialchars($flash['message']) ?></span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<main id="main-content">

