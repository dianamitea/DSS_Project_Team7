<?php
/**
 * products.php
 * ------------
 * Displays products filtered by ?category_id=N.
 * Also supports ?q= full-text search and ?sort= ordering.
 *
 * FIXES APPLIED (PHP logic only — HTML/CSS unchanged):
 *  1. require_once 'db_connect.php'  (not dbconnect.php)
 *  2. Removed AND p.is_available = 1 from all product queries
 *  3. 'Add to Cart' button now uses:
 *         <a href="addtocart.php?id=<?php echo $product['id']; ?>">
 *     matching the simple GET-based addtocart.php file
 */

session_start();
require_once 'db_connect.php';

/* ── Input sanitisation ─────────────────────────────────────── */
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$searchQuery = trim(strip_tags($_GET['q'] ?? ''));
$sortRaw     = $_GET['sort'] ?? 'default';
$page        = max(1, (int) ($_GET['page'] ?? 1));
$perPage     = 9;

// Allowed sort options (whitelist to prevent SQL injection)
$sortOptions = [
    'default'     => 'p.id ASC',
    'name_asc'    => 'p.name ASC',
    'name_desc'   => 'p.name DESC',
    'price_asc'   => 'p.price ASC',
    'price_desc'  => 'p.price DESC',
];
$sortCol = $sortOptions[$sortRaw] ?? $sortOptions['default'];

/* ── Fetch active category ──────────────────────────────────── */
$currentCategory = null;
if ($categoryId) {
    $currentCategory = db_fetch_one(
        'SELECT id, name FROM categories WHERE id = :id LIMIT 1',
        [':id' => $categoryId]
    );
    if (!$currentCategory) {
        header('Location: categories.php');
        exit;
    }
}

/* ── Fetch all categories for sidebar ───────────────────────── */
$allCategories = db_fetch_all(
    'SELECT
         c.id,
         c.name,
         COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY c.name ASC'
);

/* ── Build WHERE clause dynamically ─────────────────────────── */
$whereClauses = ['1=1'];
$params       = [];

if ($categoryId) {
    $whereClauses[] = 'p.category_id = :cid';
    $params[':cid'] = $categoryId;
}

if ($searchQuery !== '') {
    $whereClauses[] = '(p.name LIKE :q OR p.description LIKE :q)';
    $params[':q']   = '%' . $searchQuery . '%';
}

$whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

/* ── Count total results (for pagination) ───────────────────── */
$totalProducts = (int) db_fetch_one(
    "SELECT COUNT(*) AS cnt
       FROM products p
       JOIN categories c ON c.id = p.category_id
      $whereSQL",
    $params
)['cnt'];

$totalPages = max(1, (int) ceil($totalProducts / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

/* ── Fetch products ─────────────────────────────────────────── */
// FIX: removed AND p.is_available = 1 — column does not exist in this schema
$products = db_fetch_all(
    "SELECT
         p.id,
         p.name,
         p.description,
         p.price,
         p.image_url,
         c.name AS category_name,
         c.id   AS category_id
     FROM products p
     JOIN categories c ON c.id = p.category_id
    $whereSQL
     ORDER BY $sortCol
     LIMIT :lim OFFSET :off",
    array_merge($params, [':lim' => $perPage, ':off' => $offset])
);

/* ── URL builder helper ─────────────────────────────────────── */
function buildUrl(array $overrides = []): string
{
    $base = [
        'category_id' => $_GET['category_id'] ?? null,
        'q'           => $_GET['q']           ?? null,
        'sort'        => $_GET['sort']         ?? null,
        'page'        => $_GET['page']         ?? null,
    ];
    $merged = array_filter(array_merge($base, $overrides), fn($v) => $v !== null && $v !== '');
    return 'products.php?' . http_build_query($merged);
}

/* ── Page meta ───────────────────────────────────────────────── */
$pageTitle = $currentCategory
    ? htmlspecialchars($currentCategory['name']) . ' — Maison Dorée'
    : 'All Products — Maison Dorée';

require_once __DIR__ . '/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════
     PAGE HERO / BREADCRUMB BAR
════════════════════════════════════════════════════════════ -->
<section class="prod-hero" aria-labelledby="prod-hero-heading">
    <div class="prod-hero__bg" aria-hidden="true"></div>
    <div class="container position-relative z-1">

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="prod-breadcrumb mb-3">
            <a href="index.php">Home</a>
            <span aria-hidden="true">›</span>
            <a href="categories.php">Menu</a>
            <?php if ($currentCategory): ?>
                <span aria-hidden="true">›</span>
                <span aria-current="page"><?= htmlspecialchars($currentCategory['name']) ?></span>
            <?php else: ?>
                <span aria-hidden="true">›</span>
                <span aria-current="page">All Products</span>
            <?php endif; ?>
        </nav>

        <div class="row align-items-end g-3">
            <div class="col">
                <span class="label-script">
                    <?= $currentCategory ? htmlspecialchars($currentCategory['name']) : 'Everything' ?>
                </span>
                <h1 class="prod-hero__title" id="prod-hero-heading">
                    <?php if ($searchQuery !== ''): ?>
                        Results for "<em><?= htmlspecialchars($searchQuery) ?></em>"
                    <?php elseif ($currentCategory): ?>
                        <?= htmlspecialchars($currentCategory['name']) ?>
                    <?php else: ?>
                        All <em>Products</em>
                    <?php endif; ?>
                </h1>
                <p class="prod-hero__meta" aria-live="polite">
                    <?= $totalProducts ?> item<?= $totalProducts !== 1 ? 's' : '' ?>
                    <?= $totalPages > 1 ? "· page {$page} of {$totalPages}" : '' ?>
                </p>
            </div>

            <!-- Sort control -->
            <div class="col-auto">
                <form method="GET" action="products.php" class="d-flex align-items-center gap-2"
                      aria-label="Sort products">
                    <?php if ($categoryId): ?>
                        <input type="hidden" name="category_id" value="<?= (int) $categoryId ?>">
                    <?php endif; ?>
                    <?php if ($searchQuery !== ''): ?>
                        <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                    <?php endif; ?>
                    <label for="sort" class="prod-sort__label visually-hidden">Sort by</label>
                    <select
                        id="sort"
                        name="sort"
                        class="form-select prod-sort__select"
                        aria-label="Sort products"
                        onchange="this.form.submit()">
                        <option value="default"    <?= $sortRaw === 'default'    ? 'selected' : '' ?>>Sort: Default</option>
                        <option value="price_asc"  <?= $sortRaw === 'price_asc'  ? 'selected' : '' ?>>Price: Low → High</option>
                        <option value="price_desc" <?= $sortRaw === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
                        <option value="name_asc"   <?= $sortRaw === 'name_asc'   ? 'selected' : '' ?>>Name: A → Z</option>
                        <option value="name_desc"  <?= $sortRaw === 'name_desc'  ? 'selected' : '' ?>>Name: Z → A</option>
                    </select>
                    <noscript>
                        <button type="submit" class="btn btn-primary btn-sm">Go</button>
                    </noscript>
                </form>
            </div>
        </div><!-- /.row -->

    </div><!-- /.container -->
</section>

<!-- ═══════════════════════════════════════════════════════════
     MAIN CONTENT: SIDEBAR + PRODUCT GRID
════════════════════════════════════════════════════════════ -->
<div class="container prod-layout py-5">
    <div class="row g-5">

        <!-- ── SIDEBAR ─────────────────────────────────────── -->
        <aside class="col-12 col-lg-3" aria-label="Category filter">

            <!-- Search form -->
            <div class="prod-sidebar-block mb-4">
                <h2 class="prod-sidebar-block__title">Search</h2>
                <form method="GET" action="products.php" role="search" aria-label="Search products">
                    <?php if ($categoryId): ?>
                        <input type="hidden" name="category_id" value="<?= (int) $categoryId ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <input
                            type="search"
                            name="q"
                            class="form-control prod-sidebar__search"
                            placeholder="Search menu…"
                            value="<?= htmlspecialchars($searchQuery) ?>"
                            aria-label="Search products">
                        <button type="submit" class="btn btn-primary" aria-label="Search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Category list -->
            <div class="prod-sidebar-block">
                <h2 class="prod-sidebar-block__title">Categories</h2>
                <nav aria-label="Filter by category">
                    <ul class="prod-cat-list" role="list">

                        <!-- "All" option -->
                        <li role="listitem">
                            <a
                                href="products.php<?= $searchQuery ? '?q=' . urlencode($searchQuery) : '' ?>"
                                class="prod-cat-list__item <?= !$categoryId ? 'prod-cat-list__item--active' : '' ?>"
                                aria-current="<?= !$categoryId ? 'page' : 'false' ?>">
                                <span class="prod-cat-list__name">All Products</span>
                                <span class="prod-cat-list__count">
                                    <?= array_sum(array_column($allCategories, 'product_count')) ?>
                                </span>
                            </a>
                        </li>

                        <?php foreach ($allCategories as $cat): ?>
                            <li role="listitem">
                                <a
                                    href="<?= buildUrl(['category_id' => $cat['id'], 'page' => null]) ?>"
                                    class="prod-cat-list__item <?= (int)($currentCategory['id'] ?? 0) === (int)$cat['id'] ? 'prod-cat-list__item--active' : '' ?>"
                                    aria-current="<?= (int)($currentCategory['id'] ?? 0) === (int)$cat['id'] ? 'page' : 'false' ?>">
                                    <span class="prod-cat-list__name">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </span>
                                    <span class="prod-cat-list__count">
                                        <?= (int) $cat['product_count'] ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div><!-- /.prod-sidebar-block -->

            <!-- Help block -->
            <div class="prod-sidebar-block mt-4">
                <h2 class="prod-sidebar-block__title">Need Help?</h2>
                <div class="prod-sidebar-help">
                    <i class="bi bi-chat-heart prod-sidebar-help__icon" aria-hidden="true"></i>
                    <p class="prod-sidebar-help__text">
                        Can't find what you're looking for? Our team would love to help.
                    </p>
                    <a href="mailto:bonjour@maisondoree.fr" class="btn btn-ghost btn-sm w-100">
                        <i class="bi bi-envelope me-1" aria-hidden="true"></i> Contact Us
                    </a>
                </div>
            </div>

        </aside><!-- /aside -->

        <!-- ── PRODUCT GRID ────────────────────────────────── -->
        <main class="col-12 col-lg-9" id="products-grid" aria-label="Products">

            <?php if (empty($products)): ?>
                <!-- Empty / no-results state -->
                <div class="prod-empty" role="status" aria-live="polite">
                    <i class="bi bi-bag-x prod-empty__icon" aria-hidden="true"></i>
                    <h2 class="prod-empty__title">
                        <?= $searchQuery !== ''
                            ? 'No results for "' . htmlspecialchars($searchQuery) . '"'
                            : 'No products available' ?>
                    </h2>
                    <p class="prod-empty__body">
                        <?= $searchQuery !== ''
                            ? 'Try a different search term, or browse a category below.'
                            : 'Check back soon — our bakers are hard at work!' ?>
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap mt-3">
                        <a href="categories.php" class="btn btn-primary">Browse All Categories</a>
                        <?php if ($searchQuery !== '' || $categoryId): ?>
                            <a href="products.php" class="btn btn-ghost">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>

                <div class="row g-4" id="productGrid">
                    <?php foreach ($products as $index => $product):
                        $price = number_format((float)$product['price'], 2);

                        $imgSrc = !empty($product['image_url'])
                            ? 'images/' . htmlspecialchars($product['image_url'], ENT_QUOTES)
                            : 'images/placeholder.jpg';

                        $swatchColors = ['#C8935A','#9C6B3C','#5C3317','#EDE0C4','#C2796A','#6B7A3C','#4A7A8A'];
                        $swatch = $swatchColors[$product['id'] % count($swatchColors)];
                    ?>
                        <div
                            class="col-12 col-sm-6 col-xl-4"
                            style="--p-delay:<?= $index * 0.06 ?>s">

                            <article
                                class="prod-card"
                                aria-label="<?= htmlspecialchars($product['name']) ?>, €<?= $price ?>">

                                <!-- Thumbnail -->
                                <a
                                    href="product_details.php?id=<?= (int) $product['id'] ?>"
                                    class="prod-card__thumb-link"
                                    tabindex="-1"
                                    aria-hidden="true">

                                    <img
                                        src="<?= $imgSrc ?>"
                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                        class="prod-card__img card-img-top"
                                        loading="lazy"
                                        width="400"
                                        height="200"
                                        onerror="this.onerror=null; this.src='images/placeholder.jpg';">

                                    <!-- Category ribbon -->
                                    <span class="prod-card__category-ribbon">
                                        <?= htmlspecialchars($product['category_name']) ?>
                                    </span>
                                </a>

                                <!-- Body -->
                                <div class="prod-card__body">

                                    <h3 class="prod-card__name">
                                        <a
                                            href="product_details.php?id=<?= (int) $product['id'] ?>"
                                            class="prod-card__name-link">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h3>

                                    <?php if (!empty($product['description'])): ?>
                                        <p class="prod-card__desc">
                                            <?= htmlspecialchars(
                                                mb_strimwidth($product['description'], 0, 90, '…')
                                            ) ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Card footer -->
                                    <div class="prod-card__footer">

                                        <span class="prod-card__price" aria-label="Price: €<?= $price ?>">
                                            €<?= $price ?>
                                        </span>

                                        <div class="d-flex gap-2 align-items-center">
                                            <a
                                                href="product_details.php?id=<?= (int) $product['id'] ?>"
                                                class="btn btn-outline-secondary btn-sm prod-card__cta"
                                                aria-label="View details for <?= htmlspecialchars($product['name']) ?>">
                                                Details
                                            </a>
                                            <!-- FIX: Add to Cart uses addtocart.php?id= (GET-based, no form/POST needed) -->
                                            <a
                                                href="addtocart.php?id=<?= (int) $product['id'] ?>"
                                                class="btn btn-primary btn-sm prod-card__cta"
                                                aria-label="Add <?= htmlspecialchars($product['name']) ?> to cart">
                                                <i class="bi bi-basket-plus me-1" aria-hidden="true"></i>Add
                                            </a>
                                        </div>
                                    </div><!-- /.prod-card__footer -->

                                </div><!-- /.prod-card__body -->

                            </article><!-- /.prod-card -->
                        </div>
                    <?php endforeach; ?>
                </div><!-- /#productGrid -->

                <!-- ── PAGINATION ──────────────────────────── -->
                <?php if ($totalPages > 1): ?>
                    <nav
                        class="prod-pagination mt-5"
                        aria-label="Product pages">
                        <ul class="pagination justify-content-center">

                            <!-- Prev -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a
                                    class="page-link"
                                    href="<?= buildUrl(['page' => $page - 1]) ?>"
                                    aria-label="Previous page"
                                    <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                </a>
                            </li>

                            <?php
                            $showPages = [];
                            for ($i = 1; $i <= $totalPages; $i++) {
                                if ($i === 1 || $i === $totalPages || abs($i - $page) <= 1) {
                                    $showPages[] = $i;
                                }
                            }
                            $showPages = array_unique($showPages);
                            sort($showPages);
                            $prev = null;
                            foreach ($showPages as $pg):
                                if ($prev !== null && $pg - $prev > 1): ?>
                                    <li class="page-item disabled" aria-hidden="true">
                                        <span class="page-link">…</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item <?= $pg === $page ? 'active' : '' ?>"
                                    <?= $pg === $page ? 'aria-current="page"' : '' ?>>
                                    <a class="page-link" href="<?= buildUrl(['page' => $pg]) ?>"
                                       aria-label="Page <?= $pg ?>">
                                        <?= $pg ?>
                                    </a>
                                </li>
                                <?php $prev = $pg;
                            endforeach; ?>

                            <!-- Next -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a
                                    class="page-link"
                                    href="<?= buildUrl(['page' => $page + 1]) ?>"
                                    aria-label="Next page"
                                    <?= $page >= $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>

                        </ul>

                        <p class="prod-pagination__info text-center" aria-live="polite">
                            Showing
                            <?= ($offset + 1) ?>–<?= min($offset + $perPage, $totalProducts) ?>
                            of <?= $totalProducts ?> products
                        </p>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>

        </main><!-- /main#products-grid -->

    </div><!-- /.row -->
</div><!-- /.container.prod-layout -->

<!-- ═══════════════════════════════════════════════════════════
     PAGE STYLES  (unchanged)
════════════════════════════════════════════════════════════ -->
<style>
/* ── Hero bar ─────────────────────────────────────────────── */
.prod-hero {
    background-color: var(--cafe-espresso);
    padding: 2.5rem 0 2rem;
    position: relative;
    overflow: hidden;
}
.prod-hero__bg {
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 50% 120% at 90% 50%, rgba(200,147,90,.12) 0%, transparent 60%);
    pointer-events: none;
}
.prod-breadcrumb {
    display: flex; align-items: center; gap: .4rem;
    font-size: .77rem; letter-spacing: .07em; text-transform: uppercase;
}
.prod-breadcrumb a { color: rgba(245,236,215,.45); text-decoration: none; }
.prod-breadcrumb a:hover { color: var(--cafe-caramel); }
.prod-breadcrumb span { color: rgba(245,236,215,.25); }
.prod-breadcrumb [aria-current] { color: var(--cafe-caramel); }

.prod-hero__title {
    font-family: var(--font-display);
    font-size: clamp(2rem, 4.5vw, 3rem);
    font-weight: 300;
    color: var(--cafe-parchment);
    line-height: 1.1;
    margin: .2rem 0 .4rem;
}
.prod-hero__title em { font-style: italic; color: var(--cafe-caramel); }
.prod-hero__meta {
    font-size: .82rem; letter-spacing: .07em; text-transform: uppercase;
    color: rgba(245,236,215,.4); margin: 0;
}

/* Sort select */
.prod-sort__select {
    background-color: rgba(255,255,255,.08);
    border-color: rgba(200,147,90,.3);
    color: var(--cafe-parchment);
    font-size: .82rem;
    padding: .45rem .9rem;
    border-radius: .4rem;
    min-width: 180px;
}
.prod-sort__select option { background: var(--cafe-espresso); color: var(--cafe-parchment); }
.prod-sort__select:focus { box-shadow: 0 0 0 3px rgba(200,147,90,.25); border-color: var(--cafe-caramel); }

/* ── Sidebar ──────────────────────────────────────────────── */
.prod-sidebar-block {
    background: var(--cafe-white);
    border: 1px solid var(--cafe-border);
    border-radius: var(--bs-border-radius-lg);
    padding: 1.25rem 1.4rem;
}
.prod-sidebar-block__title {
    font-family: var(--font-body);
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--cafe-muted);
    margin: 0 0 1rem;
    padding-bottom: .6rem;
    border-bottom: 1px solid var(--cafe-border);
}
.prod-sidebar__search { font-size: .88rem; }

/* Category list */
.prod-cat-list {
    list-style: none;
    margin: 0; padding: 0;
    display: flex; flex-direction: column; gap: .15rem;
}
.prod-cat-list__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .5rem .75rem;
    border-radius: .4rem;
    font-size: .875rem;
    color: var(--cafe-charcoal);
    text-decoration: none;
    transition: background var(--transition-base), color var(--transition-base);
}
.prod-cat-list__item:hover {
    background: var(--cafe-parchment);
    color: var(--cafe-coffee);
    text-decoration: none;
}
.prod-cat-list__item--active {
    background: var(--cafe-coffee) !important;
    color: var(--cafe-parchment) !important;
    font-weight: 500;
}
.prod-cat-list__count {
    font-size: .72rem;
    background: var(--cafe-parchment);
    color: var(--cafe-muted);
    padding: .1rem .45rem;
    border-radius: 2rem;
    line-height: 1.5;
    min-width: 26px;
    text-align: center;
}
.prod-cat-list__item--active .prod-cat-list__count {
    background: rgba(255,255,255,.2);
    color: rgba(245,236,215,.8);
}

/* Help block */
.prod-sidebar-help { text-align: center; }
.prod-sidebar-help__icon {
    font-size: 1.75rem;
    color: var(--cafe-caramel);
    display: block;
    margin-bottom: .5rem;
}
.prod-sidebar-help__text {
    font-size: .83rem;
    color: var(--cafe-muted);
    line-height: 1.55;
    margin-bottom: .75rem;
}

/* ── Product Cards ────────────────────────────────────────── */
.prod-card {
    background: var(--cafe-white);
    border: 1px solid var(--cafe-border);
    border-radius: var(--bs-border-radius-xl);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
    box-shadow: var(--shadow-sm);
    transition:
        box-shadow   var(--transition-base),
        transform    var(--transition-base),
        border-color var(--transition-base);

    opacity: 0;
    transform: translateY(16px);
    animation: prodReveal .5s ease forwards;
    animation-delay: var(--p-delay, 0s);
}
@keyframes prodReveal { to { opacity:1; transform:translateY(0); } }

.prod-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-4px);
    border-color: rgba(200,147,90,.5);
}

.prod-card__thumb-link {
    display: block;
    position: relative;
    height: 200px;
    overflow: hidden;
    text-decoration: none;
    background: var(--cafe-parchment);
}

.prod-card__img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
    transition: transform var(--transition-slow);
}
.prod-card:hover .prod-card__img { transform: scale(1.05); }

.prod-card__placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
}
.prod-card__placeholder-icon {
    font-size: 3rem;
    color: rgba(255,255,255,.3);
}

.prod-card__category-ribbon {
    position: absolute;
    top: .7rem; left: .7rem;
    font-size: .68rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    background: rgba(44,26,14,.65);
    color: var(--cafe-parchment);
    backdrop-filter: blur(4px);
    padding: .2rem .6rem;
    border-radius: 2rem;
    pointer-events: none;
}

.prod-card__body {
    padding: 1.1rem 1.25rem 1.3rem;
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: .35rem;
}
.prod-card__name { margin: 0; }
.prod-card__name-link {
    font-family: var(--font-display);
    font-size: 1.15rem;
    font-weight: 600;
    color: var(--cafe-espresso);
    text-decoration: none;
    line-height: 1.25;
    transition: color var(--transition-base);
}
.prod-card__name-link:hover { color: var(--cafe-coffee); }

.prod-card__desc {
    font-size: .845rem;
    color: var(--cafe-muted);
    line-height: 1.6;
    margin: 0;
    flex: 1;
}

.prod-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    margin-top: .6rem;
    padding-top: .75rem;
    border-top: 1px solid var(--cafe-border);
}
.prod-card__price {
    font-family: var(--font-display);
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--cafe-coffee);
    line-height: 1;
}
.prod-card__cta {
    font-size: .78rem;
    letter-spacing: .05em;
    white-space: nowrap;
    flex-shrink: 0;
}

/* ── Empty / no-results ───────────────────────────────────── */
.prod-empty {
    text-align: center;
    padding: 4rem 1rem;
    max-width: 420px;
    margin: 0 auto;
}
.prod-empty__icon {
    font-size: 3.5rem;
    color: var(--cafe-border);
    display: block;
    margin-bottom: 1rem;
}
.prod-empty__title {
    font-family: var(--font-display);
    font-size: 1.6rem;
    color: var(--cafe-espresso);
    margin-bottom: .5rem;
}
.prod-empty__body { color: var(--cafe-muted); font-size: .9rem; }

/* ── Pagination ───────────────────────────────────────────── */
.prod-pagination .page-link {
    border-color: var(--cafe-border);
    color: var(--cafe-coffee);
    border-radius: .4rem !important;
    margin: 0 .15rem;
    padding: .5rem .85rem;
    font-size: .88rem;
    transition: background var(--transition-base), color var(--transition-base);
}
.prod-pagination .page-link:hover {
    background: var(--cafe-parchment);
    border-color: var(--cafe-caramel);
    color: var(--cafe-coffee);
}
.prod-pagination .page-item.active .page-link {
    background: var(--cafe-coffee);
    border-color: var(--cafe-coffee);
    color: var(--cafe-parchment);
}
.prod-pagination .page-item.disabled .page-link {
    color: var(--cafe-border);
    background: transparent;
}
.prod-pagination__info {
    font-size: .8rem;
    color: var(--cafe-muted);
    margin-top: .75rem;
    letter-spacing: .03em;
}

/* ── Reduced motion ───────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
    .prod-card { animation: none; opacity: 1; transform: none; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>