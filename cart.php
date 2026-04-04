<?php


declare(strict_types=1);
session_start();

require_once 'db_connect.php';

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


$cartSession = $_SESSION['cart'] ?? [];
$cartItems   = [];
$subtotal    = 0.0;

if (!empty($cartSession)) {
    $ids          = array_map('intval', array_keys($cartSession));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $rows = db_fetch_all(
        "SELECT p.id, p.name, p.description, p.price, p.image_url, c.name AS category_name
           FROM products p
           JOIN categories c ON c.id = p.category_id
          WHERE p.id IN ($placeholders)
          ORDER BY p.name ASC",
        $ids
    );

    foreach ($rows as $row) {
        $pid       = (int) $row['id'];
        $qty       = (int) ($cartSession[$pid] ?? 1);
        $lineTotal = (float) $row['price'] * $qty;
        $subtotal += $lineTotal;

        $cartItems[] = [
            'id'            => $pid,
            'name'          => $row['name'],
            'description'   => $row['description'],
            'price'         => (float) $row['price'],
            'image_url'     => $row['image_url'],
            'category_name' => $row['category_name'],
            'qty'           => $qty,
            'line_total'    => $lineTotal,
        ];
    }
}

$deliveryFee = ($subtotal >= 30 || $subtotal === 0.0) ? 0.0 : 3.50;
$orderTotal  = $subtotal + $deliveryFee;
$cartCount   = array_sum($cartSession);

$pageTitle = 'Your Cart — Maison Dorée';
require_once __DIR__ . '/header.php';
?>

<section class="cart-hero" aria-labelledby="cart-heading">
    <div class="container">
        <nav aria-label="Breadcrumb" class="cart-breadcrumb mb-3">
            <a href="index.php">Home</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Cart</span>
        </nav>
        <div class="d-flex align-items-end justify-content-between gap-3 flex-wrap">
            <div>
                <span class="label-script">Ready to order?</span>
                <h1 class="cart-hero__title" id="cart-heading">
                    Your <em>Cart</em>
                </h1>
            </div>
            <?php if (!empty($cartItems)): ?>
                <p class="cart-hero__meta" aria-live="polite">
                    <?= $cartCount ?> item<?= $cartCount !== 1 ? 's' : '' ?> · €<?= number_format($orderTotal, 2) ?> total
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container cart-layout py-5">

    <?php if (empty($cartItems)): ?>
    
        <div class="cart-empty" role="status">
            <div class="cart-empty__icon-wrap" aria-hidden="true">
                <i class="bi bi-basket2"></i>
            </div>
            <h2 class="cart-empty__title">Your cart is empty</h2>
            <p class="cart-empty__body">
                Looks like you haven't added anything yet. Browse our fresh menu
                and find something delicious.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
                <!-- BUG 4 FIX: was categories.php → products.php -->
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-grid me-2" aria-hidden="true"></i>Browse Menu
                </a>
                <a href="index.php" class="btn btn-ghost btn-lg">Back to Home</a>
            </div>
        </div>

    <?php else: ?>
        <div class="row g-5 align-items-start">

          
            <div class="col-12 col-lg-8">

                <div class="cart-table-wrap d-none d-md-block">
                    <table class="table cart-table" aria-label="Cart items">
                        <thead>
                            <tr>
                                <th scope="col" style="width:45%">Product</th>
                                <th scope="col" class="text-center">Price</th>
                                <th scope="col" class="text-center" style="width:150px">Quantity</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col" class="text-center" style="width:60px">
                                    <span class="visually-hidden">Remove</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr class="cart-row" data-product-id="<?= $item['id'] ?>">

                                    <!-- Product info -->
                                    <td class="cart-row__product">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="cart-thumb" aria-hidden="true">
                                                <?php if (!empty($item['image_url'])): ?>
                                                    <img
                                                        src="images/<?= htmlspecialchars($item['image_url']) ?>"
                                                        alt=""
                                                        class="cart-thumb__img"
                                                        loading="lazy"
                                                        onerror="this.parentElement.classList.add('cart-thumb--placeholder'); this.remove();">
                                                <?php else: ?>
                                                    <div class="cart-thumb__placeholder">
                                                        <i class="bi bi-cup-hot"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="cart-row__cat"><?= htmlspecialchars($item['category_name']) ?></span>
                                                <p class="cart-row__name"><?= htmlspecialchars($item['name']) ?></p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Unit price -->
                                    <td class="cart-row__price text-center">
                                        €<?= number_format($item['price'], 2) ?>
                                    </td>

                                    <!-- Qty stepper -->
                                    <td class="text-center">
                                        <form
                                            method="POST"
                                            action="add_to_cart.php"
                                            class="qty-form"
                                            aria-label="Update quantity for <?= htmlspecialchars($item['name']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action"     value="update">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="redirect"   value="cart.php">

                                            <div class="qty-stepper" role="group" aria-label="Quantity">
                                                <button
                                                    type="button"
                                                    class="qty-btn qty-btn--minus"
                                                    aria-label="Decrease quantity"
                                                    data-action="dec">
                                                    <i class="bi bi-dash" aria-hidden="true"></i>
                                                </button>
                                                <input
                                                    type="number"
                                                    name="qty"
                                                    class="qty-input"
                                                    value="<?= $item['qty'] ?>"
                                                    min="0"
                                                    max="99"
                                                    aria-label="Quantity"
                                                    inputmode="numeric">
                                                <button
                                                    type="button"
                                                    class="qty-btn qty-btn--plus"
                                                    aria-label="Increase quantity"
                                                    data-action="inc">
                                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                                </button>
                                            </div>

                                            <button type="submit" class="cart-row__update-btn">
                                                Update
                                            </button>
                                        </form>
                                    </td>

                                    <!-- Line total -->
                                    <td class="cart-row__total text-end">
                                        €<?= number_format($item['line_total'], 2) ?>
                                    </td>

                                    <!-- Remove -->
                                    <td class="text-center">
                                        <form method="POST" action="addtocart.php">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action"     value="remove">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="redirect"   value="cart.php">
                                            <button
                                                type="submit"
                                                class="cart-row__remove"
                                                aria-label="Remove <?= htmlspecialchars($item['name']) ?> from cart"
                                                onclick="return confirm('Remove <?= addslashes(htmlspecialchars($item['name'])) ?> from your cart?')">
                                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile card list (shown below md) -->
                <div class="d-md-none cart-mobile-list" aria-label="Cart items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-mobile-card">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="cart-thumb cart-thumb--sm" aria-hidden="true">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="images/<?= htmlspecialchars($item['image_url']) ?>"
                                             alt="" class="cart-thumb__img" loading="lazy"
                                             onerror="this.parentElement.classList.add('cart-thumb--placeholder'); this.remove();">
                                    <?php else: ?>
                                        <div class="cart-thumb__placeholder">
                                            <i class="bi bi-cup-hot"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="cart-row__cat"><?= htmlspecialchars($item['category_name']) ?></span>
                                    <p class="cart-row__name mb-1"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="cart-row__price mb-2">€<?= number_format($item['price'], 2) ?> each</p>

                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <form method="POST" action="addtocart.php" class="qty-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action"     value="update">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="redirect"   value="cart.php">
                                            <div class="qty-stepper qty-stepper--sm" role="group" aria-label="Quantity">
                                                <button type="button" class="qty-btn qty-btn--minus" data-action="dec" aria-label="Decrease">
                                                    <i class="bi bi-dash" aria-hidden="true"></i>
                                                </button>
                                                <input type="number" name="qty" class="qty-input"
                                                       value="<?= $item['qty'] ?>" min="0" max="99"
                                                       aria-label="Quantity" inputmode="numeric">
                                                <button type="button" class="qty-btn qty-btn--plus" data-action="inc" aria-label="Increase">
                                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <button type="submit" class="cart-row__update-btn">Update</button>
                                        </form>

                                        <div class="text-end">
                                            <p class="cart-row__total mb-1">€<?= number_format($item['line_total'], 2) ?></p>
                                            <form method="POST" action="addtocart.php" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action"     value="remove">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <input type="hidden" name="redirect"   value="cart.php">
                                                <button type="submit" class="cart-row__remove"
                                                        aria-label="Remove <?= htmlspecialchars($item['name']) ?>"
                                                        onclick="return confirm('Remove this item?')">
                                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Actions row -->
                <div class="cart-actions d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
                    <a href="products.php" class="btn btn-ghost btn-sm">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Continue Shopping
                    </a>
                    <!-- BUG 1 FIX: form action → clearcart.php (simple session unset + redirect) -->
                    <form method="POST" action="clearcart.php">
                        <button
                            type="submit"
                            class="btn btn-ghost btn-sm text-rose"
                            onclick="return confirm('Clear your entire cart?')">
                            <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Clear Cart
                        </button>
                    </form>
                </div>

            </div><!-- /col cart table -->

            
            <div class="col-12 col-lg-4">
                <div class="cart-summary" aria-label="Order summary">

                    <h2 class="cart-summary__title">Order Summary</h2>

                    <table class="table cart-summary__table" aria-label="Price breakdown">
                        <tbody>
                            <tr>
                                <td>Subtotal <span class="text-muted-cafe">(<?= $cartCount ?> item<?= $cartCount !== 1 ? 's' : '' ?>)</span></td>
                                <td class="text-end">€<?= number_format($subtotal, 2) ?></td>
                            </tr>
                            <tr>
                                <td>
                                    Delivery
                                    <?php if ($deliveryFee === 0.0 && $subtotal > 0): ?>
                                        <span class="badge-cafe badge-new ms-1">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($deliveryFee > 0): ?>
                                        €<?= number_format($deliveryFee, 2) ?>
                                    <?php else: ?>
                                        <span class="text-success fw-semibold">€0.00</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="cart-summary__total-row">
                                <th scope="row">Total</th>
                                <th class="text-end" aria-live="polite">€<?= number_format($orderTotal, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>

                    <?php if ($deliveryFee > 0): ?>
                        <p class="cart-summary__delivery-note">
                            <i class="bi bi-truck me-1" aria-hidden="true"></i>
                            Add €<?= number_format(30 - $subtotal, 2) ?> more for free delivery.
                        </p>
                    <?php elseif ($subtotal > 0): ?>
                        <p class="cart-summary__delivery-note cart-summary__delivery-note--free">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                            You qualify for free delivery!
                        </p>
                    <?php endif; ?>

                    <a
                        href="checkout.php"
                        class="btn btn-primary w-100 btn-lg cart-summary__checkout">
                        <i class="bi bi-lock me-2" aria-hidden="true"></i>
                        Proceed to Checkout
                    </a>

                    <ul class="cart-trust" aria-label="Security and payment information">
                        <li><i class="bi bi-shield-check" aria-hidden="true"></i> Secure SSL checkout</li>
                        <li><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Free returns within 24 h</li>
                        <li><i class="bi bi-credit-card" aria-hidden="true"></i> Pay securely at pickup</li>
                    </ul>

                </div><!-- /.cart-summary -->
            </div>

        </div><!-- /.row -->
    <?php endif; ?>

</div><!-- /.container -->

<style>
/* ── Hero ──────────────────────────────────────────────────── */
.cart-hero {
    background: var(--cafe-espresso);
    padding: 2.5rem 0 2rem;
    border-bottom: 1px solid rgba(200,147,90,.2);
}
.cart-breadcrumb {
    display:flex; align-items:center; gap:.4rem;
    font-size:.77rem; letter-spacing:.07em; text-transform:uppercase;
}
.cart-breadcrumb a { color:rgba(245,236,215,.45); text-decoration:none; }
.cart-breadcrumb a:hover { color:var(--cafe-caramel); }
.cart-breadcrumb span { color:rgba(245,236,215,.25); }
.cart-breadcrumb [aria-current] { color:var(--cafe-caramel); }
.cart-hero__title {
    font-family:var(--font-display); font-size:clamp(2rem,4vw,2.8rem);
    font-weight:300; color:var(--cafe-parchment); line-height:1.1; margin:.2rem 0;
}
.cart-hero__title em { font-style:italic; color:var(--cafe-caramel); }
.cart-hero__meta {
    font-size:.85rem; letter-spacing:.06em; text-transform:uppercase;
    color:rgba(245,236,215,.45); margin:0;
}

.cart-layout { min-height:55vh; }

.cart-empty {
    text-align:center; padding:5rem 1rem; max-width:440px; margin:0 auto;
}
.cart-empty__icon-wrap {
    width:80px; height:80px; border-radius:50%;
    background:var(--cafe-parchment); border:1px solid var(--cafe-border);
    display:flex; align-items:center; justify-content:center;
    font-size:2.2rem; color:var(--cafe-latte);
    margin:0 auto 1.5rem;
}
.cart-empty__title {
    font-family:var(--font-display); font-size:1.8rem;
    color:var(--cafe-espresso); margin-bottom:.5rem;
}
.cart-empty__body { color:var(--cafe-muted); font-size:.95rem; max-width:36ch; margin:0 auto; }

.cart-table-wrap {
    border:1px solid var(--cafe-border);
    border-radius:var(--bs-border-radius-xl);
    overflow:hidden;
    box-shadow:var(--shadow-sm);
}
.cart-table { margin:0; }
.cart-table thead th {
    background:var(--cafe-parchment);
    font-size:.72rem; letter-spacing:.1em; text-transform:uppercase;
    color:var(--cafe-muted); font-weight:600;
    padding:.85rem 1.1rem;
    border-bottom:2px solid var(--cafe-border);
}
.cart-table tbody td { padding:1rem 1.1rem; border-color:var(--cafe-border); vertical-align:middle; }
.cart-row:last-child td { border-bottom:none; }
.cart-row { transition:background var(--transition-base); }
.cart-row:hover { background:var(--cafe-milk); }


.cart-thumb {
    width:64px; height:64px; border-radius:.5rem;
    overflow:hidden; flex-shrink:0;
    border:1px solid var(--cafe-border);
    background:var(--cafe-parchment);
}
.cart-thumb--sm { width:52px; height:52px; }
.cart-thumb__img { width:100%; height:100%; object-fit:cover; }
.cart-thumb__placeholder {
    width:100%; height:100%;
    display:flex; align-items:center; justify-content:center;
    font-size:1.4rem; color:var(--cafe-latte);
}


.cart-row__cat {
    font-size:.68rem; letter-spacing:.09em; text-transform:uppercase;
    color:var(--cafe-muted); display:block; margin-bottom:.15rem;
}
.cart-row__name {
    font-family:var(--font-display); font-size:1rem; font-weight:600;
    color:var(--cafe-espresso); margin:0; line-height:1.25;
}
.cart-row__price {
    font-size:.9rem; color:var(--cafe-muted); margin:0;
}
.cart-row__total {
    font-family:var(--font-display); font-size:1.05rem;
    font-weight:600; color:var(--cafe-coffee); white-space:nowrap;
}


.qty-stepper {
    display:inline-flex; align-items:center;
    border:1px solid var(--cafe-border);
    border-radius:.4rem; overflow:hidden;
    background:var(--cafe-white);
}
.qty-btn {
    background:none; border:none;
    width:30px; height:30px;
    display:flex; align-items:center; justify-content:center;
    color:var(--cafe-coffee); cursor:pointer;
    font-size:.9rem;
    transition:background var(--transition-base);
}
.qty-btn:hover { background:var(--cafe-parchment); }
.qty-input {
    width:38px; border:none; outline:none;
    text-align:center; font-size:.88rem;
    color:var(--cafe-charcoal); padding:0;
    background:transparent;
    -moz-appearance:textfield;
}
.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }

.qty-stepper--sm .qty-btn { width:26px; height:26px; font-size:.8rem; }
.qty-stepper--sm .qty-input { width:32px; font-size:.82rem; }

.cart-row__update-btn {
    position:absolute; opacity:0; pointer-events:none;
    width:1px; height:1px; overflow:hidden;
}


.cart-row__remove {
    background:none; border:none; padding:.3rem .4rem;
    color:var(--cafe-muted); border-radius:.35rem;
    cursor:pointer; font-size:1rem;
    transition:color var(--transition-base), background var(--transition-base);
}
.cart-row__remove:hover {
    color:var(--cafe-rose); background:var(--cafe-rose-light);
}


.cart-actions { padding-top:.75rem; border-top:1px dashed var(--cafe-border); }


.cart-mobile-list { display:flex; flex-direction:column; gap:.75rem; }
.cart-mobile-card {
    background:var(--cafe-white);
    border:1px solid var(--cafe-border);
    border-radius:var(--bs-border-radius-lg);
    padding:1rem;
    box-shadow:var(--shadow-sm);
}


.cart-summary {
    background:var(--cafe-white);
    border:1px solid var(--cafe-border);
    border-radius:var(--bs-border-radius-xl);
    padding:1.75rem;
    box-shadow:var(--shadow-md);
    position:sticky; top:90px;
}
.cart-summary__title {
    font-family:var(--font-display); font-size:1.4rem; font-weight:600;
    color:var(--cafe-espresso); margin-bottom:1.25rem;
    padding-bottom:.75rem; border-bottom:2px solid var(--cafe-border);
}
.cart-summary__table { font-size:.9rem; margin-bottom:.75rem; }
.cart-summary__table td, .cart-summary__table th {
    padding:.55rem 0; border-color:var(--cafe-border);
    color:var(--cafe-charcoal);
}
.cart-summary__table tbody tr:last-child td { border-bottom:1px solid var(--cafe-border); }
.cart-summary__total-row th {
    font-family:var(--font-display); font-size:1.15rem;
    color:var(--cafe-espresso); padding-top:.85rem; border:none;
}
.cart-summary__delivery-note {
    font-size:.8rem; color:var(--cafe-muted);
    margin-bottom:1rem;
}
.cart-summary__delivery-note--free { color:#5C7A55; }

.cart-summary__checkout { margin-bottom:1.25rem; font-size:.9rem; letter-spacing:.04em; }


.cart-trust {
    list-style:none; margin:0; padding:0;
    display:flex; flex-direction:column; gap:.4rem;
    border-top:1px solid var(--cafe-border); padding-top:1rem;
}
.cart-trust li {
    font-size:.78rem; color:var(--cafe-muted);
    display:flex; align-items:center; gap:.45rem;
}
.cart-trust li i { color:var(--cafe-caramel); }
</style>

<script>
document.querySelectorAll('.qty-stepper').forEach(stepper => {
    const input    = stepper.querySelector('.qty-input');
    const minusBtn = stepper.querySelector('[data-action="dec"]');
    const plusBtn  = stepper.querySelector('[data-action="inc"]');
    const form     = stepper.closest('form');

    if (!input || !form) return;

    minusBtn?.addEventListener('click', () => {
        const val = Math.max(0, parseInt(input.value, 10) - 1);
        input.value = val;
        if (val === 0 && confirm('Remove this item from your cart?')) {
            form.submit();
        } else if (val > 0) {
            form.submit();
        } else {
            input.value = 1;
        }
    });

    plusBtn?.addEventListener('click', () => {
        const val = Math.min(99, parseInt(input.value, 10) + 1);
        input.value = val;
        form.submit();
    });

    input.addEventListener('change', () => form.submit());
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
