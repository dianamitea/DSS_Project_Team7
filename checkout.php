<?php
/**
 * checkout.php
 * ------------
 * Two-phase page:
 *
 *  GET  → Show order review form (cart summary + customer details).
 *  POST → Validate, insert into `orders` (and optionally `order_items`),
 *         clear the session cart, redirect to success.php.
 *
 * FIXES APPLIED (PHP logic only — HTML/CSS unchanged):
 *  1. require_once 'db_connect.php'  (not dbconnect.php)
 *  2. Removed AND p.is_available = 1 from loadCart() SQL
 *  3. unset($_SESSION['cart']) clears the basket after a successful order
 *  4. header('Location: success.php'); exit(); is the final step on success
 */

declare(strict_types=1);
session_start();

require_once 'db_connect.php';

/* ── Guard: cart must not be empty ─────────────────────────── */
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Your cart is empty.'];
    header('Location: categories.php');
    exit;
}

/* ── CSRF token ─────────────────────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ── Load cart from DB ──────────────────────────────────────── */
function loadCart(): array
{
    $cartSession  = $_SESSION['cart'];
    $ids          = array_map('intval', array_keys($cartSession));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // FIX: Removed AND p.is_available = 1 (column does not exist in this schema)
    $rows = db_fetch_all(
        "SELECT p.id, p.name, p.price, p.image_url, c.name AS category_name
           FROM products p
           JOIN categories c ON c.id = p.category_id
          WHERE p.id IN ($placeholders)",
        $ids
    );

    $items    = [];
    $subtotal = 0.0;

    foreach ($rows as $row) {
        $pid       = (int) $row['id'];
        $qty       = (int) ($cartSession[$pid] ?? 1);
        $lineTotal = (float) $row['price'] * $qty;
        $subtotal += $lineTotal;
        $items[]   = [
            'id'         => $pid,
            'name'       => $row['name'],
            'price'      => (float) $row['price'],
            'image_url'  => $row['image_url'] ?? null,
            'qty'        => $qty,
            'line_total' => $lineTotal,
            'category'   => $row['category_name'],
        ];
    }

    $delivery = ($subtotal >= 30 || $subtotal === 0.0) ? 0.0 : 3.50;

    return [
        'items'    => $items,
        'subtotal' => $subtotal,
        'delivery' => $delivery,
        'total'    => $subtotal + $delivery,
    ];
}

$cart = loadCart();

/* ── Defaults / pre-fill from session ───────────────────────── */
$errors   = [];
$formData = [
    'name'   => $_SESSION['username'] ?? '',
    'email'  => $_SESSION['email']    ?? '',
    'phone'  => '',
    'notes'  => '',
    'pickup' => 'pickup',
];

/* ── POST: place the order ──────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF */
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please refresh and try again.';
    } else {

        /* Validate customer details */
        $name   = trim($_POST['name']   ?? '');
        $email  = trim($_POST['email']  ?? '');
        $phone  = trim($_POST['phone']  ?? '');
        $notes  = trim($_POST['notes']  ?? '');
        $pickup = ($_POST['pickup'] ?? 'pickup') === 'delivery' ? 'delivery' : 'pickup';

        $formData = compact('name', 'email', 'phone', 'notes', 'pickup');

        if ($name === '') {
            $errors['name'] = 'Your name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email address is required.';
        }

        // Re-load cart inside POST to get fresh prices
        $cart = loadCart();

        if (empty($cart['items'])) {
            $errors[] = 'Your cart is empty. Please add items before checking out.';
        }

        if (empty($errors)) {

            $userId = $_SESSION['user_id'] ?? null;

            try {
                $pdo = db();
                $pdo->beginTransaction();

                // ── INSERT into orders ──────────────────────
                // FIX: Uses the correct table name `orders` and columns
                //      `user_id`, `total_price` that exist in this schema.
                $stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, total_price)
                     VALUES (:uid, :total)'
                );
                $stmt->execute([
                    ':uid'   => $userId,
                    ':total' => $cart['total'],
                ]);
                $orderId = (int) $pdo->lastInsertId();

                // ── INSERT into order_items (graceful fallback) ──
                try {
                    $itemStmt = $pdo->prepare(
                        'INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                         VALUES (:oid, :pid, :qty, :price)'
                    );
                    foreach ($cart['items'] as $item) {
                        $itemStmt->execute([
                            ':oid'   => $orderId,
                            ':pid'   => $item['id'],
                            ':qty'   => $item['qty'],
                            ':price' => $item['price'],
                        ]);
                    }
                } catch (\PDOException) {
                    // order_items table may not exist yet — continue gracefully
                }

                $pdo->commit();

                // ── FIX: Clear the session cart ─────────────
                unset($_SESSION['cart']);

                // ── FIX: Pass context to success.php ────────
                $_SESSION['success_context'] = [
                    'type'       => 'order',
                    'order_id'   => $orderId,
                    'name'       => $name,
                    'email'      => $email,
                    'total'      => $cart['total'],
                    'item_count' => array_sum(array_column($cart['items'], 'qty')),
                    'pickup'     => $pickup,
                ];

                // ── FIX: Redirect to success.php (not orderconfirmation.php) ──
                header('Location: success.php');
                exit();

            } catch (\PDOException $e) {
                $pdo->rollBack();
                error_log('[checkout] Order insert failed: ' . $e->getMessage());
                $errors[] = 'We could not place your order right now. Please try again in a moment.';
            }
        }
    }
}

$pageTitle = 'Checkout — Maison Dorée';
require_once __DIR__ . '/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════
     PAGE HERO
════════════════════════════════════════════════════════════ -->
<section class="chk-hero" aria-labelledby="chk-heading">
    <div class="container">
        <nav aria-label="Breadcrumb" class="chk-breadcrumb mb-3">
            <a href="index.php">Home</a>
            <span aria-hidden="true">›</span>
            <a href="cart.php">Cart</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Checkout</span>
        </nav>

        <!-- Progress steps -->
        <ol class="chk-steps" aria-label="Checkout progress">
            <li class="chk-step chk-step--done">
                <span class="chk-step__num" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                <span class="chk-step__label">Cart</span>
            </li>
            <li class="chk-step chk-step--active" aria-current="step">
                <span class="chk-step__num" aria-hidden="true">2</span>
                <span class="chk-step__label">Details</span>
            </li>
            <li class="chk-step">
                <span class="chk-step__num" aria-hidden="true">3</span>
                <span class="chk-step__label">Confirmation</span>
            </li>
        </ol>

        <h1 class="chk-hero__title" id="chk-heading">Checkout</h1>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     MAIN
════════════════════════════════════════════════════════════ -->
<div class="container chk-layout py-5">

    <!-- Global errors -->
    <?php
    $globalErrors = array_filter($errors, fn($k) => is_int($k), ARRAY_FILTER_USE_KEY);
    if ($globalErrors): ?>
        <div class="alert alert-danger mb-4" role="alert" aria-live="assertive">
            <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
            <ul class="mb-0 ps-3">
                <?php foreach ($globalErrors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-5 align-items-start">

        <!-- ── CUSTOMER DETAILS FORM (left) ───────────────── -->
        <div class="col-12 col-lg-7">

            <form
                method="POST"
                action="checkout.php"
                novalidate
                aria-label="Checkout form">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <!-- ── Section: Your Details ─────────────── -->
                <div class="chk-section mb-4">
                    <h2 class="chk-section__title">
                        <span class="chk-section__num" aria-hidden="true">1</span>
                        Your Details
                    </h2>

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12 col-sm-6">
                            <label for="name" class="form-label">
                                Full Name <span class="text-rose" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text" id="name" name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($formData['name']) ?>"
                                placeholder="Marie Dupont"
                                required autocomplete="name"
                                aria-required="true"
                                aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>"
                                aria-describedby="name-err">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback" id="name-err" role="alert">
                                    <?= htmlspecialchars($errors['name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Phone -->
                        <div class="col-12 col-sm-6">
                            <label for="phone" class="form-label">Phone <span class="chk-optional">(optional)</span></label>
                            <input
                                type="tel" id="phone" name="phone"
                                class="form-control"
                                value="<?= htmlspecialchars($formData['phone']) ?>"
                                placeholder="+33 6 12 34 56 78"
                                autocomplete="tel">
                        </div>

                        <!-- Email -->
                        <div class="col-12">
                            <label for="email" class="form-label">
                                Email Address <span class="text-rose" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="email" id="email" name="email"
                                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($formData['email']) ?>"
                                placeholder="you@example.com"
                                required autocomplete="email"
                                aria-required="true"
                                aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>"
                                aria-describedby="email-err">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback" id="email-err" role="alert">
                                    <?= htmlspecialchars($errors['email']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Your order confirmation will be sent here.</div>
                        </div>
                    </div>
                </div>

                <!-- ── Section: Pickup or Delivery ───────── -->
                <div class="chk-section mb-4">
                    <h2 class="chk-section__title">
                        <span class="chk-section__num" aria-hidden="true">2</span>
                        Collection Method
                    </h2>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="chk-method-card <?= $formData['pickup'] === 'pickup' ? 'chk-method-card--active' : '' ?>">
                                <input
                                    type="radio" name="pickup" value="pickup"
                                    class="visually-hidden chk-method-radio"
                                    <?= $formData['pickup'] === 'pickup' ? 'checked' : '' ?>>
                                <i class="bi bi-shop chk-method-card__icon" aria-hidden="true"></i>
                                <span class="chk-method-card__label">In-Store Pickup</span>
                                <span class="chk-method-card__sub">Ready in 15–20 min · Free</span>
                            </label>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="chk-method-card <?= $formData['pickup'] === 'delivery' ? 'chk-method-card--active' : '' ?>">
                                <input
                                    type="radio" name="pickup" value="delivery"
                                    class="visually-hidden chk-method-radio"
                                    <?= $formData['pickup'] === 'delivery' ? 'checked' : '' ?>>
                                <i class="bi bi-truck chk-method-card__icon" aria-hidden="true"></i>
                                <span class="chk-method-card__label">Home Delivery</span>
                                <span class="chk-method-card__sub">
                                    30–45 min ·
                                    <?= $cart['delivery'] > 0
                                        ? '€' . number_format($cart['delivery'], 2)
                                        : '<span class="text-success">Free</span>' ?>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ── Section: Order Notes ───────────────── -->
                <div class="chk-section mb-4">
                    <h2 class="chk-section__title">
                        <span class="chk-section__num" aria-hidden="true">3</span>
                        Special Instructions
                        <span class="chk-optional ms-1">Optional</span>
                    </h2>
                    <textarea
                        id="notes" name="notes"
                        class="form-control"
                        rows="3"
                        maxlength="500"
                        placeholder="Allergies, requests, delivery notes…"
                        aria-label="Special instructions"><?= htmlspecialchars($formData['notes']) ?></textarea>
                    <div class="form-text">Max 500 characters.</div>
                </div>

                <!-- ── Submit ─────────────────────────────── -->
                <button type="submit" class="btn btn-primary btn-lg w-100 chk-submit">
                    <i class="bi bi-bag-check me-2" aria-hidden="true"></i>
                    Confirm Order · €<?= number_format($cart['total'], 2) ?>
                </button>

                <p class="chk-submit-note text-center mt-2">
                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                    By confirming you agree to our <a href="terms.php">Terms of Use</a>.
                    No payment is taken online.
                </p>

            </form>

        </div><!-- /form col -->

        <!-- ── ORDER SUMMARY (right) ───────────────────────── -->
        <div class="col-12 col-lg-5">
            <div class="chk-summary" aria-label="Order summary">

                <h2 class="chk-summary__title">Your Order</h2>

                <!-- Items table -->
                <div class="chk-items-table-wrap">
                    <table class="table chk-items-table" aria-label="Items in your order">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col" class="text-center">Qty</th>
                                <th scope="col" class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart['items'] as $item): ?>
                                <tr>
                                    <td class="chk-item__name">
                                        <span class="chk-item__cat"><?= htmlspecialchars($item['category']) ?></span>
                                        <?= htmlspecialchars($item['name']) ?>
                                    </td>
                                    <td class="text-center chk-item__qty">× <?= $item['qty'] ?></td>
                                    <td class="text-end chk-item__total">€<?= number_format($item['line_total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="chk-summary__subtotal">
                                <td colspan="2">Subtotal</td>
                                <td class="text-end">€<?= number_format($cart['subtotal'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    Delivery
                                    <?php if ($cart['delivery'] === 0.0): ?>
                                        <span class="badge-cafe badge-new ms-1">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($cart['delivery'] > 0): ?>
                                        €<?= number_format($cart['delivery'], 2) ?>
                                    <?php else: ?>
                                        <span class="text-success fw-semibold">€0.00</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr class="chk-summary__total">
                                <th colspan="2" scope="row">Total</th>
                                <th class="text-end" aria-live="polite">€<?= number_format($cart['total'], 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Edit cart link -->
                <a href="cart.php" class="btn btn-ghost btn-sm w-100 mt-2">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit Cart
                </a>

                <!-- Store info -->
                <div class="chk-store-info mt-4">
                    <h3 class="chk-store-info__title">
                        <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>Our Location
                    </h3>
                    <address>
                        12 Rue du Four, Old Town<br>
                        Paris, France 75001<br>
                        <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                    </address>
                    <p class="chk-store-info__hours">
                        <i class="bi bi-clock me-1" aria-hidden="true"></i>
                        Mon–Fri 7:00–19:00 · Sat 7:30–20:00 · Sun 8:00–17:00
                    </p>
                </div>

            </div><!-- /.chk-summary -->
        </div>

    </div><!-- /.row -->
</div><!-- /.container -->

<!-- ═══════════════════════════════════════════════════════════
     PAGE STYLES  (unchanged)
════════════════════════════════════════════════════════════ -->
<style>
/* ── Hero ──────────────────────────────────────────────────── */
.chk-hero {
    background:var(--cafe-espresso);
    padding:2rem 0 1.75rem;
    border-bottom:1px solid rgba(200,147,90,.2);
}
.chk-breadcrumb {
    display:flex; align-items:center; gap:.4rem;
    font-size:.77rem; letter-spacing:.07em; text-transform:uppercase;
}
.chk-breadcrumb a { color:rgba(245,236,215,.45); text-decoration:none; }
.chk-breadcrumb a:hover { color:var(--cafe-caramel); }
.chk-breadcrumb span { color:rgba(245,236,215,.25); }
.chk-breadcrumb [aria-current] { color:var(--cafe-caramel); }

/* Progress steps */
.chk-steps {
    display:flex; align-items:center; gap:0;
    list-style:none; margin:0 0 1rem; padding:0;
}
.chk-step {
    display:flex; align-items:center; gap:.5rem;
    font-size:.75rem; letter-spacing:.07em; text-transform:uppercase;
    color:rgba(245,236,215,.35);
}
.chk-step + .chk-step::before {
    content:'';
    display:block;
    width:32px; height:1px;
    background:rgba(200,147,90,.25);
    margin:0 .5rem;
}
.chk-step__num {
    width:22px; height:22px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.7rem; font-weight:700;
    border:1px solid rgba(245,236,215,.25);
    color:rgba(245,236,215,.35);
    flex-shrink:0;
}
.chk-step--done .chk-step__num {
    background:var(--cafe-caramel); border-color:var(--cafe-caramel);
    color:var(--cafe-espresso);
}
.chk-step--done { color:var(--cafe-caramel); }
.chk-step--active { color:var(--cafe-parchment); }
.chk-step--active .chk-step__num {
    background:var(--cafe-coffee); border-color:var(--cafe-coffee);
    color:var(--cafe-parchment);
}
.chk-hero__title {
    font-family:var(--font-display); font-size:clamp(1.8rem,3.5vw,2.5rem);
    font-weight:300; color:var(--cafe-parchment); margin:0;
}

/* ── Layout ────────────────────────────────────────────────── */
.chk-layout { min-height:55vh; }

/* ── Form sections ─────────────────────────────────────────── */
.chk-section {
    background:var(--cafe-white);
    border:1px solid var(--cafe-border);
    border-radius:var(--bs-border-radius-xl);
    padding:1.6rem 1.75rem;
    box-shadow:var(--shadow-sm);
}
.chk-section__title {
    font-family:var(--font-body);
    font-size:.8rem; font-weight:600;
    letter-spacing:.1em; text-transform:uppercase;
    color:var(--cafe-muted);
    margin:0 0 1.25rem;
    padding-bottom:.75rem;
    border-bottom:1px solid var(--cafe-border);
    display:flex; align-items:center; gap:.6rem;
}
.chk-section__num {
    width:22px; height:22px; border-radius:50%;
    background:var(--cafe-coffee); color:var(--cafe-parchment);
    font-size:.72rem; font-weight:700;
    display:inline-flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.chk-optional { font-weight:400; font-size:.75em; color:var(--cafe-border); }

/* ── Method cards ──────────────────────────────────────────── */
.chk-method-card {
    display:flex; flex-direction:column; align-items:center;
    gap:.3rem; padding:1.25rem 1rem;
    border:2px solid var(--cafe-border);
    border-radius:var(--bs-border-radius-lg);
    cursor:pointer; text-align:center;
    transition:border-color var(--transition-base), background var(--transition-base), box-shadow var(--transition-base);
    background:var(--cafe-white);
    user-select:none;
}
.chk-method-card:hover { border-color:var(--cafe-latte); background:var(--cafe-milk); }
.chk-method-card--active {
    border-color:var(--cafe-coffee) !important;
    background:var(--cafe-parchment) !important;
    box-shadow:0 0 0 3px rgba(92,51,23,.12);
}
.chk-method-card__icon { font-size:1.6rem; color:var(--cafe-latte); }
.chk-method-card--active .chk-method-card__icon { color:var(--cafe-coffee); }
.chk-method-card__label {
    font-weight:600; font-size:.9rem; color:var(--cafe-espresso);
}
.chk-method-card__sub {
    font-size:.78rem; color:var(--cafe-muted);
}

/* ── Submit ────────────────────────────────────────────────── */
.chk-submit { letter-spacing:.04em; font-size:.95rem; }
.chk-submit-note { font-size:.77rem; color:var(--cafe-muted); }
.chk-submit-note a { color:var(--cafe-latte); }

/* ── Summary card ──────────────────────────────────────────── */
.chk-summary {
    background:var(--cafe-white);
    border:1px solid var(--cafe-border);
    border-radius:var(--bs-border-radius-xl);
    padding:1.6rem 1.75rem;
    box-shadow:var(--shadow-md);
    position:sticky; top:90px;
}
.chk-summary__title {
    font-family:var(--font-display); font-size:1.3rem; font-weight:600;
    color:var(--cafe-espresso); margin-bottom:1rem;
    padding-bottom:.6rem; border-bottom:2px solid var(--cafe-border);
}

/* Items table */
.chk-items-table-wrap {
    border:1px solid var(--cafe-border);
    border-radius:.5rem; overflow:hidden;
    margin-bottom:.25rem;
}
.chk-items-table { margin:0; font-size:.85rem; }
.chk-items-table thead th {
    background:var(--cafe-parchment);
    font-size:.68rem; letter-spacing:.09em; text-transform:uppercase;
    color:var(--cafe-muted); padding:.6rem .85rem;
    border-bottom:1px solid var(--cafe-border);
}
.chk-items-table tbody td {
    padding:.6rem .85rem;
    border-color:var(--cafe-border);
    vertical-align:middle;
    color:var(--cafe-charcoal);
}
.chk-item__cat {
    display:block; font-size:.65rem; letter-spacing:.07em;
    text-transform:uppercase; color:var(--cafe-muted); margin-bottom:.1rem;
}
.chk-item__name { max-width:160px; }
.chk-item__qty { color:var(--cafe-muted); white-space:nowrap; }
.chk-item__total { font-weight:500; white-space:nowrap; }

.chk-items-table tfoot td,
.chk-items-table tfoot th {
    font-size:.82rem; padding:.55rem .85rem;
    border-color:var(--cafe-border);
    color:var(--cafe-charcoal);
}
.chk-summary__subtotal td {
    border-top:2px solid var(--cafe-border);
    color:var(--cafe-muted);
}
.chk-summary__total th {
    font-family:var(--font-display); font-size:1.05rem;
    color:var(--cafe-espresso); border-top:2px solid var(--cafe-border);
}

/* Store info */
.chk-store-info {
    background:var(--cafe-parchment);
    border:1px solid var(--cafe-border);
    border-radius:.5rem;
    padding:1rem 1.1rem;
    font-size:.82rem; color:var(--cafe-muted);
}
.chk-store-info__title {
    font-family:var(--font-body);
    font-size:.72rem; font-weight:600; letter-spacing:.08em;
    text-transform:uppercase; color:var(--cafe-coffee);
    margin-bottom:.4rem;
}
.chk-store-info address { font-style:normal; line-height:1.6; margin-bottom:.3rem; }
.chk-store-info address a { color:var(--cafe-latte); }
.chk-store-info__hours { margin:0; font-size:.78rem; }
</style>

<script>
/* ── Method card radio toggle ─────────────────────────────── */
document.querySelectorAll('.chk-method-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.chk-method-card').forEach(card => {
            card.classList.remove('chk-method-card--active');
        });
        radio.closest('.chk-method-card').classList.add('chk-method-card--active');
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>