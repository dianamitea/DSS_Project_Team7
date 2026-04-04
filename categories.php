<?php


session_start();
require_once __DIR__ . '/db_connect.php';

$categories = db_fetch_all(
    'SELECT
         c.id,
         c.name,
         COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p
            ON p.category_id  = c.id
           AND p.is_available = 1
     GROUP BY c.id, c.name
     ORDER BY c.name ASC'
);

$categoryMeta = [
    'pastries'      => [
        'icon'    => 'bi-sun',
        'color'   => '#C8935A',
        'bg'      => '#FDF3E7',
        'tagline' => 'Flaky, golden, irresistible',
    ],
    'cakes & tarts' => [
        'icon'    => 'bi-gift',
        'color'   => '#C2796A',
        'bg'      => '#FAF0EE',
        'tagline' => 'Celebration-worthy every day',
    ],
    'breads'        => [
        'icon'    => 'bi-layers',
        'color'   => '#9C6B3C',
        'bg'      => '#F5ECD7',
        'tagline' => 'Slow-fermented, deeply flavoured',
    ],
    'hot drinks'    => [
        'icon'    => 'bi-cup-hot',
        'color'   => '#5C3317',
        'bg'      => '#EDE0C4',
        'tagline' => 'From espresso to chai',
    ],
    'cold drinks'   => [
        'icon'    => 'bi-droplet',
        'color'   => '#4A7A8A',
        'bg'      => '#E8F3F6',
        'tagline' => 'Cool, refreshing, crafted',
    ],
    'savoury bites' => [
        'icon'    => 'bi-grid-3x3-gap',
        'color'   => '#6B7A3C',
        'bg'      => '#EEF2E4',
        'tagline' => 'Snacks worth savouring',
    ],
];

$fallbackMeta = [
    'icon'    => 'bi-bag',
    'color'   => '#9C6B3C',
    'bg'      => '#F5ECD7',
    'tagline' => 'Freshly made daily',
];

function getCategoryMeta(string $name, array $map, array $fallback): array
{
    return $map[strtolower(trim($name))] ?? $fallback;
}

/* ── Page metadata ──────────────────────────────────────────── */
$pageTitle    = 'Menu Categories — Maison Dorée';
$totalProducts = array_sum(array_column($categories, 'product_count'));

require_once __DIR__ . '/header.php';
?>


<section class="cat-hero" aria-labelledby="cat-hero-heading">
    <div class="cat-hero__bg" aria-hidden="true"></div>
    <div class="container position-relative z-1">
        <div class="row align-items-end">
            <div class="col-12 col-lg-7">
                <nav aria-label="Breadcrumb" class="cat-breadcrumb mb-3">
                    <a href="index.php">Home</a>
                    <span aria-hidden="true">›</span>
                    <span aria-current="page">Menu</span>
                </nav>
                <span class="label-script">What we make</span>
                <h1 class="cat-hero__title" id="cat-hero-heading">
                    Our Full <em>Menu</em>
                </h1>
                <p class="cat-hero__sub">
                    <?= count($categories) ?> categories ·
                    <?= $totalProducts ?> items available today
                </p>
            </div>
            <div class="col-12 col-lg-5 d-flex justify-content-lg-end mt-4 mt-lg-0">
                <!-- Quick search -->
                <form
                    class="cat-search"
                    action="products.php"
                    method="GET"
                    role="search"
                    aria-label="Search products">
                    <label for="q" class="visually-hidden">Search products</label>
                    <div class="input-group cat-search__group">
                        <span class="input-group-text cat-search__icon" aria-hidden="true">
                            <i class="bi bi-search"></i>
                        </span>
                        <input
                            type="search"
                            id="q"
                            name="q"
                            class="form-control cat-search__input"
                            placeholder="Search our menu…"
                            autocomplete="off">
                        <button type="submit" class="btn btn-primary cat-search__btn">
                            Go
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>


<section class="section section-cream" aria-labelledby="categories-grid-label">
    <h2 class="visually-hidden" id="categories-grid-label">Browse by Category</h2>
    <div class="container">

        <?php if (empty($categories)): ?>
            <!-- Empty state -->
            <div class="cat-empty" role="status">
                <i class="bi bi-basket cat-empty__icon" aria-hidden="true"></i>
                <h2 class="cat-empty__title">No categories yet</h2>
                <p class="cat-empty__body">
                    We're still setting up the menu. Check back very soon!
                </p>
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="bi bi-house me-2" aria-hidden="true"></i>Back to Home
                </a>
            </div>

        <?php else: ?>
            <div class="row g-4" id="categoriesGrid">
                <?php foreach ($categories as $index => $cat):
                    $meta = getCategoryMeta($cat['name'], $categoryMeta, $fallbackMeta);
                    $count = (int) $cat['product_count'];
                ?>
                    <div
                        class="col-12 col-sm-6 col-lg-4"
                        style="--card-delay: <?= $index * 0.07 ?>s">

                        <a
                            href="products.php?category_id=<?= (int) $cat['id'] ?>"
                            class="cat-card"
                            aria-label="<?= htmlspecialchars($cat['name']) ?>, <?= $count ?> item<?= $count !== 1 ? 's' : '' ?> available">

                            <!-- Colour swatch header -->
                            <div class="cat-card__swatch"
                                 style="background-color:<?= htmlspecialchars($meta['bg']) ?>;">

                                <!-- Decorative ring -->
                                <div class="cat-card__ring" aria-hidden="true"
                                     style="border-color:<?= htmlspecialchars($meta['color']) ?>20;"></div>

                                <!-- Icon bubble -->
                                <div class="cat-card__icon-wrap"
                                     style="background-color:<?= htmlspecialchars($meta['color']) ?>1A;
                                            border-color:<?= htmlspecialchars($meta['color']) ?>35;">
                                    <i class="bi <?= htmlspecialchars($meta['icon']) ?> cat-card__icon"
                                       style="color:<?= htmlspecialchars($meta['color']) ?>;"
                                       aria-hidden="true"></i>
                                </div>

                                <!-- Product count pill -->
                                <span class="cat-card__count"
                                      style="background-color:<?= htmlspecialchars($meta['color']) ?>;
                                             color:#fff;">
                                    <?= $count ?>
                                    <?= $count === 1 ? 'item' : 'items' ?>
                                </span>

                            </div><!-- /.cat-card__swatch -->

                            <!-- Text body -->
                            <div class="cat-card__body">
                                <h3 class="cat-card__name">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </h3>
                                <p class="cat-card__tagline">
                                    <?= htmlspecialchars($meta['tagline']) ?>
                                </p>
                                <span class="cat-card__cta"
                                      style="color:<?= htmlspecialchars($meta['color']) ?>;">
                                    Browse <?= htmlspecialchars($cat['name']) ?>
                                    <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                </span>
                            </div>

                        </a><!-- /.cat-card -->
                    </div>
                <?php endforeach; ?>
            </div><!-- /#categoriesGrid -->

        <?php endif; ?>

    </div><!-- /.container -->
</section>


<section class="specials-banner" aria-label="Daily specials">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-auto d-none d-md-flex">
                <div class="specials-banner__icon-wrap" aria-hidden="true">
                    <i class="bi bi-stars"></i>
                </div>
            </div>
            <div class="col">
                <span class="label-script" style="color:var(--cafe-caramel)">Limited availability</span>
                <h2 class="specials-banner__title">Seasonal Specials Change Every Week</h2>
                <p class="specials-banner__sub">
                    Ask our team about today's off-menu creations — made in small batches from
                    whatever's freshest at the market.
                </p>
            </div>
            <div class="col-12 col-md-auto">
                <a href="reservations.php" class="btn btn-primary">
                    <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>Book a Table
                </a>
            </div>
        </div>
    </div>
</section>


<style>

.cat-hero {
    background-color: var(--cafe-espresso);
    padding: 3.5rem 0 3rem;
    position: relative;
    overflow: hidden;
}
.cat-hero__bg {
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 55% 90% at 80% 50%, rgba(200,147,90,.13) 0%, transparent 65%),
        radial-gradient(ellipse 30% 60% at 5%  50%, rgba(92,51,23,.25)   0%, transparent 55%);
    pointer-events: none;
}
.cat-breadcrumb {
    display: flex; align-items: center; gap: .45rem;
    font-size: .78rem; letter-spacing: .07em; text-transform: uppercase;
}
.cat-breadcrumb a { color: rgba(245,236,215,.5); text-decoration: none; }
.cat-breadcrumb a:hover { color: var(--cafe-caramel); }
.cat-breadcrumb span { color: rgba(245,236,215,.3); }
.cat-breadcrumb [aria-current] { color: var(--cafe-caramel); }

.cat-hero__title {
    font-family: var(--font-display);
    font-size: clamp(2.4rem, 5vw, 3.6rem);
    font-weight: 300;
    color: var(--cafe-parchment);
    line-height: 1.1;
    margin: .25rem 0 .6rem;
}
.cat-hero__title em { font-style: italic; color: var(--cafe-caramel); }
.cat-hero__sub {
    font-size: .9rem; letter-spacing: .05em;
    color: rgba(245,236,215,.45);
    text-transform: uppercase;
    margin: 0;
}

/* Search */
.cat-search { width: 100%; max-width: 380px; }
.cat-search__group { border-radius: .5rem; overflow: hidden; box-shadow: var(--shadow-md); }
.cat-search__icon {
    background: var(--cafe-white); border-color: transparent;
    color: var(--cafe-muted); padding: 0 .85rem;
}
.cat-search__input {
    border-color: transparent; font-size: .9rem;
    background: var(--cafe-white);
}
.cat-search__input:focus { box-shadow: none; border-color: transparent; }
.cat-search__btn { border-radius: 0 !important; padding: .65rem 1.2rem; font-size: .82rem; }

.cat-card {
    display: flex;
    flex-direction: column;
    border-radius: var(--bs-border-radius-xl);
    border: 1px solid var(--cafe-border);
    background: var(--cafe-white);
    overflow: hidden;
    text-decoration: none !important;
    color: inherit;
    height: 100%;
    box-shadow: var(--shadow-sm);
    transition:
        box-shadow   var(--transition-base),
        transform    var(--transition-base),
        border-color var(--transition-base);


    opacity: 0;
    transform: translateY(18px);
    animation: cardReveal .55s ease forwards;
    animation-delay: var(--card-delay, 0s);
}
@keyframes cardReveal { to { opacity:1; transform:translateY(0); } }

.cat-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-5px);
    border-color: var(--cafe-caramel);
}

/* Colour swatch top */
.cat-card__swatch {
    position: relative;
    height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}


.cat-card__ring {
    position: absolute;
    width: 220px; height: 220px;
    border-radius: 50%;
    border: 40px solid;
    top: 50%; left: 50%;
    transform: translate(-40%, -40%);
    opacity: .5;
    transition: transform var(--transition-slow);
}
.cat-card:hover .cat-card__ring {
    transform: translate(-40%, -40%) scale(1.1);
}

/* Icon bubble */
.cat-card__icon-wrap {
    width: 72px; height: 72px;
    border-radius: 50%;
    border: 2px solid;
    display: flex; align-items: center; justify-content: center;
    position: relative; z-index: 1;
    transition: transform var(--transition-base), box-shadow var(--transition-base);
}
.cat-card:hover .cat-card__icon-wrap {
    transform: scale(1.1);
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
}
.cat-card__icon { font-size: 1.9rem; }

/* Count pill */
.cat-card__count {
    position: absolute;
    bottom: .75rem; right: .75rem;
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: .2rem .65rem;
    border-radius: 2rem;
    line-height: 1.4;
}

/* Card text body */
.cat-card__body {
    padding: 1.25rem 1.4rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: .25rem;
    flex: 1;
}
.cat-card__name {
    font-family: var(--font-display);
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--cafe-espresso);
    margin: 0;
    transition: color var(--transition-base);
}
.cat-card:hover .cat-card__name { color: var(--cafe-coffee); }
.cat-card__tagline {
    font-size: .85rem;
    color: var(--cafe-muted);
    margin: 0 0 .5rem;
    line-height: 1.5;
}
.cat-card__cta {
    font-size: .8rem;
    font-weight: 500;
    letter-spacing: .06em;
    text-transform: uppercase;
    margin-top: auto;
    display: inline-flex;
    align-items: center;
    transition: gap var(--transition-base);
}
.cat-card:hover .cat-card__cta { gap: .5rem; }


.cat-empty {
    text-align: center;
    padding: 5rem 1rem;
    max-width: 420px;
    margin: 0 auto;
}
.cat-empty__icon {
    font-size: 3.5rem;
    color: var(--cafe-border);
    display: block;
    margin-bottom: 1rem;
}
.cat-empty__title {
    font-family: var(--font-display);
    font-size: 1.75rem;
    color: var(--cafe-espresso);
    margin-bottom: .5rem;
}
.cat-empty__body { color: var(--cafe-muted); font-size: .95rem; }


.specials-banner {
    background-color: var(--cafe-espresso);
    padding: 2.5rem 0;
    border-top: 1px solid rgba(200,147,90,.2);
}
.specials-banner__icon-wrap {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: rgba(200,147,90,.15);
    border: 1px solid rgba(200,147,90,.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    color: var(--cafe-caramel);
    flex-shrink: 0;
}
.specials-banner__title {
    font-family: var(--font-display);
    font-size: 1.4rem;
    font-weight: 400;
    color: var(--cafe-parchment);
    margin: 0 0 .25rem;
}
.specials-banner__sub {
    font-size: .88rem;
    color: rgba(245,236,215,.5);
    margin: 0;
    max-width: 54ch;
}

@media (prefers-reduced-motion: reduce) {
    .cat-card { animation: none; opacity: 1; transform: none; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
