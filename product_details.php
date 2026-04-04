<?php
/**
 * product_details.php
 * -------------------
 * Single-product detail page.
 *
 * GET ?id=N  → fetch the product and render the detail view.
 * Missing or invalid ID → redirect to products.php.
 *
 * Schema used (no is_available column):
 *   products : id, category_id, name, description, price, image_url
 *   categories: id, name
 */

session_start();
require_once __DIR__ . '/db_connect.php';

/* ── Validate the ID from the URL ───────────────────────────── */
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$productId) {
    header('Location: products.php');
    exit;
}

/* ── Fetch the single product (with its category name) ──────── */
$product = db_fetch_one(
    'SELECT
         p.id,
         p.name,
         p.description,
         p.price,
         p.image_url,
         c.id   AS category_id,
         c.name AS category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
    WHERE p.id = :id
    LIMIT 1',
    [':id' => $productId]
);

/* Product not found → back to menu */
if (!$product) {
    header('Location: products.php');
    exit;
}

/* ── Derived display values ─────────────────────────────────── */
$price  = number_format((float) $product['price'], 2);

$imgSrc = !empty($product['image_url'])
    ? 'images/' . htmlspecialchars($product['image_url'], ENT_QUOTES)
    : 'images/placeholder.jpg';

/* Swatch colour (deterministic, same palette as products.php) */
$swatchColors = ['#C8935A','#9C6B3C','#5C3317','#EDE0C4','#C2796A','#6B7A3C','#4A7A8A'];
$swatch = $swatchColors[$product['id'] % count($swatchColors)];

$pageTitle = htmlspecialchars($product['name']) . ' — Maison Dorée';

require_once __DIR__ . '/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════
     PAGE HERO / BREADCRUMB
════════════════════════════════════════════════════════════ -->
<section class="pd-hero" aria-label="Product breadcrumb">
    <div class="container">
        <nav aria-label="Breadcrumb" class="pd-breadcrumb">
            <a href="index.php">Home</a>
            <span aria-hidden="true">›</span>
            <a href="products.php">Menu</a>
            <?php if (!empty($product['category_name'])): ?>
                <span aria-hidden="true">›</span>
                <a href="products.php?category_id=<?= (int) $product['category_id'] ?>">
                    <?= htmlspecialchars($product['category_name']) ?>
                </a>
            <?php endif; ?>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?= htmlspecialchars($product['name']) ?></span>
        </nav>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     MAIN: IMAGE (left) + DETAILS (right)
════════════════════════════════════════════════════════════ -->
<section class="pd-section" aria-labelledby="pd-product-name">
    <div class="container">
        <div class="row g-5 align-items-start">

            <!-- ── LEFT: Product image ──────────────────────── -->
            <div class="col-12 col-lg-5">
                <div class="pd-image-wrap">
                    <img
                        src="<?= $imgSrc ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        class="pd-image"
                        width="640"
                        height="480"
                        onerror="this.onerror=null; this.src='images/placeholder.jpg';">

                    <!-- Swatch shown while image loads / if missing -->
                    <div class="pd-image-swatch"
                         style="background-color:<?= $swatch ?>;"
                         aria-hidden="true">
                        <i class="bi bi-cup-hot pd-image-swatch__icon"></i>
                    </div>

                    <!-- Category badge overlaid on image -->
                    <?php if (!empty($product['category_name'])): ?>
                        <span class="pd-image-badge">
                            <?= htmlspecialchars($product['category_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── RIGHT: Details panel ─────────────────────── -->
            <div class="col-12 col-lg-7">
                <div class="pd-details">

                    <!-- Script label -->
                    <span class="label-script">From our kitchen</span>

                    <!-- Product name -->
                    <h1 class="pd-details__name" id="pd-product-name">
                        <?= htmlspecialchars($product['name']) ?>
                    </h1>

                    <!-- Price -->
                    <p class="pd-details__price" aria-label="Price: €<?= $price ?>">
                        €<?= $price ?>
                    </p>

                    <!-- Divider -->
                    <hr class="pd-divider">

                    <!-- Description -->
                    <?php if (!empty($product['description'])): ?>
                        <div class="pd-details__desc">
                            <h2 class="pd-details__desc-label">About this item</h2>
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>
                    <?php else: ?>
                        <p class="pd-details__desc-empty">
                            No description available for this product yet.
                        </p>
                    <?php endif; ?>

                    <!-- Trust pills -->
                    <ul class="pd-trust" aria-label="Product highlights">
                        <li><i class="bi bi-sunrise" aria-hidden="true"></i> Baked fresh daily</li>
                        <li><i class="bi bi-flower1" aria-hidden="true"></i> Heritage ingredients</li>
                        <li><i class="bi bi-shield-check" aria-hidden="true"></i> Quality guaranteed</li>
                    </ul>

                    <!-- ── CTA buttons ───────────────────────── -->
                    <div class="pd-actions">

                        <!-- Add to Cart — GET link matches your addtocart.php -->
                        <a
                            href="addtocart.php?id=<?= (int) $product['id'] ?>"
                            class="btn btn-primary btn-lg pd-actions__cart"
                            aria-label="Add <?= htmlspecialchars($product['name']) ?> to cart">
                            <i class="bi bi-basket-plus me-2" aria-hidden="true"></i>
                            Add to Cart
                        </a>

                        <!-- Back to menu -->
                        <a
                            href="products.php<?= !empty($product['category_id']) ? '?category_id=' . (int)$product['category_id'] : '' ?>"
                            class="btn btn-ghost pd-actions__back">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                            Back to Menu
                        </a>

                    </div><!-- /.pd-actions -->

                </div><!-- /.pd-details -->
            </div>

        </div><!-- /.row -->
    </div><!-- /.container -->
</section>

<!-- ═══════════════════════════════════════════════════════════
     PAGE STYLES
════════════════════════════════════════════════════════════ -->
<style>
/* ── Breadcrumb hero bar ──────────────────────────────────── */
.pd-hero {
    background: var(--cafe-espresso);
    padding: 1.4rem 0;
    border-bottom: 1px solid rgba(200,147,90,.2);
}
.pd-breadcrumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem;
    font-size: .77rem;
    letter-spacing: .07em;
    text-transform: uppercase;
    margin: 0;
}
.pd-breadcrumb a {
    color: rgba(245,236,215,.45);
    text-decoration: none;
    transition: color .2s;
}
.pd-breadcrumb a:hover   { color: var(--cafe-caramel); }
.pd-breadcrumb span      { color: rgba(245,236,215,.25); }
.pd-breadcrumb [aria-current] { color: var(--cafe-caramel); }

/* ── Main section ─────────────────────────────────────────── */
.pd-section {
    padding: 3.5rem 0 5rem;
    background: var(--cafe-cream);
}

/* ── Image panel ──────────────────────────────────────────── */
.pd-image-wrap {
    position: relative;
    border-radius: var(--bs-border-radius-xl, 1.25rem);
    overflow: hidden;
    box-shadow: 0 12px 48px rgba(44,26,14,.18);
    aspect-ratio: 4 / 3;   /* keeps the panel proportional on all screens */
    background: var(--cafe-parchment);
}

/* Swatch is always rendered; the real <img> sits on top */
.pd-image-swatch {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.pd-image-swatch__icon {
    font-size: 5rem;
    color: rgba(255,255,255,.2);
}

/* Real product image covers the swatch */
.pd-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .45s cubic-bezier(.4,0,.2,1);
}
.pd-image-wrap:hover .pd-image { transform: scale(1.04); }

/* Category badge on image */
.pd-image-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    font-size: .68rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    background: rgba(44,26,14,.65);
    color: var(--cafe-parchment);
    backdrop-filter: blur(4px);
    padding: .25rem .7rem;
    border-radius: 2rem;
}

/* ── Details panel ────────────────────────────────────────── */
.pd-details {
    padding-top: .5rem;
}
.pd-details__name {
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: clamp(2rem, 4vw, 2.8rem);
    font-weight: 400;
    color: var(--cafe-espresso);
    line-height: 1.1;
    margin: .2rem 0 .75rem;
}
.pd-details__price {
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: 2rem;
    font-weight: 600;
    color: var(--cafe-coffee);
    margin: 0 0 1.25rem;
    line-height: 1;
}

.pd-divider {
    border-color: var(--cafe-border);
    opacity: 1;
    margin: 1.25rem 0 1.5rem;
}

.pd-details__desc-label {
    font-family: var(--font-body, 'DM Sans', sans-serif);
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--cafe-muted);
    margin: 0 0 .6rem;
}
.pd-details__desc {
    font-size: .95rem;
    color: var(--cafe-charcoal);
    line-height: 1.75;
    margin-bottom: 1.5rem;
}
.pd-details__desc-empty {
    font-size: .9rem;
    color: var(--cafe-muted);
    font-style: italic;
    margin-bottom: 1.5rem;
}

/* Trust pills */
.pd-trust {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem .75rem;
}
.pd-trust li {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .8rem;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--cafe-muted);
    background: var(--cafe-parchment);
    border: 1px solid var(--cafe-border);
    padding: .3rem .85rem;
    border-radius: 2rem;
}
.pd-trust li i { color: var(--cafe-caramel); }

/* CTA buttons */
.pd-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    align-items: center;
}
.pd-actions__cart {
    font-size: .9rem;
    letter-spacing: .05em;
    padding: .8rem 2rem;
    border-radius: .5rem;
    transition: transform .2s, box-shadow .2s;
}
.pd-actions__cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(92,51,23,.3);
}
.pd-actions__back {
    font-size: .82rem;
    letter-spacing: .05em;
    color: var(--cafe-muted);
    border: 1px solid var(--cafe-border);
    padding: .75rem 1.25rem;
    border-radius: .5rem;
    text-decoration: none;
    transition: background .2s, color .2s, border-color .2s;
}
.pd-actions__back:hover {
    background: var(--cafe-parchment);
    color: var(--cafe-coffee);
    border-color: var(--cafe-caramel);
}

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 575.98px) {
    .pd-actions { flex-direction: column; }
    .pd-actions__cart,
    .pd-actions__back { width: 100%; text-align: center; justify-content: center; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>