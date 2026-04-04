<?php


session_start();

require_once __DIR__ . '/db_connect.php';

$homeProducts = db_fetch_all(
    'SELECT p.id, p.name, p.description, p.price, p.image_url, c.name AS category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     ORDER BY p.id DESC
     LIMIT 4'
);

$pageTitle = 'Maison Dorée — Artisan Bakery & Café';

require_once __DIR__ . '/header.php';
?>

<section class="hero" aria-label="Welcome to Maison Dorée">

    <!-- Background SVG grain + gradient layers -->
    <div class="hero__bg" aria-hidden="true"></div>

    <!-- Decorative floating rings -->
    <div class="hero__ring hero__ring--1" aria-hidden="true"></div>
    <div class="hero__ring hero__ring--2" aria-hidden="true"></div>

    <div class="container position-relative z-1">
        <div class="row align-items-center min-vh-hero">

            <!-- Text column -->
            <div class="col-12 col-lg-6 hero__content">
                <span class="hero__eyebrow animate-fade-up" style="--delay:.1s">
                    Est. 2009 · Paris, France
                </span>

                <h1 class="hero__title animate-fade-up" style="--delay:.2s">
                    Where Every<br>
                    Morning <em>Begins</em><br>
                    With Butter
                </h1>

                <p class="hero__sub animate-fade-up" style="--delay:.35s">
                    Handcrafted pastries, slow-fermented breads, and single-origin
                    espresso — served in a space that smells like Sunday should.
                </p>

                <div class="hero__cta d-flex flex-wrap gap-3 animate-fade-up" style="--delay:.5s">
                    <!-- FIX 1: menu.php → products.php -->
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-grid me-2" aria-hidden="true"></i>Browse Menu
                    </a>
                    <!-- FIX 2: reservations.php → reservation.php -->
                    <a href="reservation.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>Book a Table
                    </a>
                </div>

                <!-- Trust badges -->
                <div class="hero__badges d-flex flex-wrap gap-3 mt-4 animate-fade-up" style="--delay:.65s"
                     aria-label="Awards and certifications">
                    <span class="hero__badge">
                        <i class="bi bi-award" aria-hidden="true"></i> Best Bakery 2023
                    </span>
                    <span class="hero__badge">
                        <i class="bi bi-leaf" aria-hidden="true"></i> Organic Certified
                    </span>
                    <span class="hero__badge">
                        <i class="bi bi-heart" aria-hidden="true"></i> 500+ Reviews ★ 4.9
                    </span>
                </div>
            </div>

            <!-- Visual column (decorative illustration) -->
            <div class="col-12 col-lg-6 d-none d-lg-flex justify-content-center align-items-center">
                <div class="hero__illustration animate-fade-in" style="--delay:.3s" aria-hidden="true">
                    <!-- SVG artisan illustration -->
                    <svg viewBox="0 0 520 520" fill="none" xmlns="http://www.w3.org/2000/svg"
                         width="460" height="460" role="img" aria-label="Decorative bakery illustration">

                        <!-- Plate / base circle -->
                        <ellipse cx="260" cy="430" rx="180" ry="22" fill="rgba(200,147,90,0.15)"/>

                        <!-- Bread loaf -->
                        <path d="M130 310 Q150 240 260 230 Q370 240 390 310 Q400 360 260 375 Q120 360 130 310Z"
                              fill="#C8935A" opacity="0.9"/>
                        <path d="M160 300 Q180 260 260 255 Q340 260 360 300 Q370 340 260 350 Q150 340 160 300Z"
                              fill="#9C6B3C" opacity="0.6"/>
                        <!-- Bread score lines -->
                        <path d="M200 270 Q230 290 200 320" stroke="#5C3317" stroke-width="2.5" stroke-linecap="round" opacity="0.5"/>
                        <path d="M260 262 Q290 282 260 312" stroke="#5C3317" stroke-width="2.5" stroke-linecap="round" opacity="0.5"/>
                        <path d="M320 270 Q350 290 320 320" stroke="#5C3317" stroke-width="2.5" stroke-linecap="round" opacity="0.5"/>

                        <!-- Croissant (top left) -->
                        <path d="M80 200 Q60 160 100 150 Q130 145 140 170 Q160 140 190 155 Q210 165 200 195 Q170 185 155 200 Q140 215 120 210 Q95 215 80 200Z"
                              fill="#C8935A"/>
                        <path d="M85 198 Q90 175 115 172 Q140 170 150 190" stroke="#9C6B3C" stroke-width="2" fill="none" opacity="0.7"/>

                        <!-- Tart / pastry (top right) -->
                        <circle cx="390" cy="165" r="52" fill="#EDE0C4" stroke="#C8935A" stroke-width="3"/>
                        <circle cx="390" cy="165" r="38" fill="#C8935A" opacity="0.7"/>
                        <circle cx="390" cy="165" r="24" fill="#9C6B3C" opacity="0.8"/>
                        <circle cx="390" cy="165" r="10" fill="#C8935A"/>
                        <!-- Tart lattice -->
                        <line x1="352" y1="165" x2="428" y2="165" stroke="#EDE0C4" stroke-width="1.5" opacity="0.5"/>
                        <line x1="390" y1="127" x2="390" y2="203" stroke="#EDE0C4" stroke-width="1.5" opacity="0.5"/>

                        <!-- Coffee cup (bottom right) -->
                        <rect x="350" y="355" width="70" height="55" rx="8" fill="#5C3317" opacity="0.9"/>
                        <rect x="356" y="361" width="58" height="43" rx="5" fill="#2C1A0E" opacity="0.8"/>
                        <!-- Coffee surface -->
                        <ellipse cx="385" cy="372" rx="26" ry="8" fill="#9C6B3C" opacity="0.6"/>
                        <!-- Latte art swirl -->
                        <path d="M372 372 Q385 365 398 372" stroke="#F5ECD7" stroke-width="1.5" fill="none" opacity="0.7"/>
                        <!-- Handle -->
                        <path d="M420 363 Q440 363 440 380 Q440 398 420 398" stroke="#5C3317" stroke-width="5" fill="none" stroke-linecap="round"/>
                        <!-- Saucer -->
                        <ellipse cx="385" cy="414" rx="46" ry="8" fill="#9C6B3C" opacity="0.4"/>
                        <ellipse cx="385" cy="412" rx="42" ry="6" fill="#C8935A" opacity="0.3"/>

                        <!-- Steam wisps (coffee) -->
                        <path d="M368 350 Q364 338 368 326" stroke="#F5ECD7" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.3"/>
                        <path d="M385 346 Q381 334 385 322" stroke="#F5ECD7" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.25"/>
                        <path d="M402 350 Q398 338 402 326" stroke="#F5ECD7" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.3"/>

                        <!-- Wheat sprigs (decorative) -->
                        <g opacity="0.35" transform="translate(100 370) rotate(-30)">
                            <line x1="0" y1="0" x2="0" y2="-60" stroke="#C8935A" stroke-width="2"/>
                            <ellipse cx="-6" cy="-30" rx="5" ry="10" fill="#C8935A" transform="rotate(-20 -6 -30)"/>
                            <ellipse cx="6"  cy="-40" rx="5" ry="10" fill="#C8935A" transform="rotate(20 6 -40)"/>
                            <ellipse cx="-5" cy="-52" rx="4" ry="8"  fill="#C8935A" transform="rotate(-15 -5 -52)"/>
                        </g>
                        <g opacity="0.3" transform="translate(430 390) rotate(20)">
                            <line x1="0" y1="0" x2="0" y2="-50" stroke="#C8935A" stroke-width="1.5"/>
                            <ellipse cx="-5" cy="-25" rx="4" ry="8" fill="#C8935A" transform="rotate(-20 -5 -25)"/>
                            <ellipse cx="5"  cy="-35" rx="4" ry="8" fill="#C8935A" transform="rotate(20 5 -35)"/>
                        </g>

                        <!-- Floating sparkle dots -->
                        <circle cx="170" cy="130" r="3" fill="#C8935A" opacity="0.5"/>
                        <circle cx="310" cy="110" r="2" fill="#EDE0C4" opacity="0.4"/>
                        <circle cx="460" cy="230" r="3" fill="#C8935A" opacity="0.35"/>
                        <circle cx="100" cy="270" r="2" fill="#EDE0C4" opacity="0.3"/>

                    </svg>
                </div>
            </div>

        </div><!-- /.row -->
    </div><!-- /.container -->
</section>

<div class="marquee-strip" aria-hidden="true">
    <div class="marquee-track">
        <?php
        $items = [
            '🥐 Butter Croissant',
            '☕ Single Origin Espresso',
            '🍞 Sourdough Loaf',
            '🍋 Lemon Tart',
            '🥐 Almond Croissant',
            '🎂 Celebration Cakes',
            '🍩 Seasonal Specials',
            '☕ Cold Brew',
        ];
        // Duplicate for seamless loop
        $all = array_merge($items, $items, $items);
        foreach ($all as $item): ?>
            <span class="marquee-item"><?= htmlspecialchars($item) ?></span>
            <span class="marquee-sep" aria-hidden="true">·</span>
        <?php endforeach; ?>
    </div>
</div>

<section class="section section-milk" aria-labelledby="features-heading">
    <div class="container">

        <div class="text-center mb-5">
            <span class="label-script">Why choose us</span>
            <h2 class="section-title" id="features-heading">The Maison Dorée Promise</h2>
            <p class="section-subtitle mx-auto">
                From grain to glazed, every step is guided by craft, patience, and a deep
                respect for ingredients.
            </p>
        </div>

        <div class="row g-4">
            <?php
            $features = [
                [
                    'icon'  => 'bi-sunrise',
                    'title' => 'Baked Fresh Daily',
                    'body'  => 'Our ovens run from 4 am. By the time you arrive, everything is warm, golden, and at peak flavour.',
                ],
                [
                    'icon'  => 'bi-flower1',
                    'title' => 'Heritage Ingredients',
                    'body'  => 'Stone-milled organic flours, AOC butters, and single-origin chocolate — sourced from farmers we know by name.',
                ],
                [
                    'icon'  => 'bi-hourglass-split',
                    'title' => 'Slow Fermentation',
                    'body'  => 'Our sourdoughs ferment for 48–72 hours. No shortcuts. Just wild yeast, time, and the right temperature.',
                ],
                [
                    'icon'  => 'bi-cup-hot',
                    'title' => 'Specialty Coffee',
                    'body'  => 'Rotating seasonal espresso blends, brewed on La Marzocco machines by our certified barista team.',
                ],
            ];
            foreach ($features as $f): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-card__icon" aria-hidden="true">
                            <i class="bi <?= $f['icon'] ?>"></i>
                        </div>
                        <h3 class="feature-card__title"><?= htmlspecialchars($f['title']) ?></h3>
                        <p class="feature-card__body"><?= htmlspecialchars($f['body']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>


<section class="section section-cream" aria-labelledby="teaser-heading">
    <div class="container">

        <div class="row align-items-end mb-5">
            <div class="col">
                <span class="label-script">Fresh from the oven</span>
                <h2 class="section-title" id="teaser-heading">Today's Favourites</h2>
            </div>
            <div class="col-auto">
                <!-- FIX 3: menu.php → products.php -->
                <a href="products.php" class="btn btn-ghost">
                    Full Menu <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <?php
            if (!$homeProducts) {
                $homeProducts = [
                    [
                        'id'           => 0,
                        'name'         => 'Butter Croissant',
                        'category_name'=> 'Pastries',
                        'price'        => '2.80',
                        'description'  => 'Flaky, golden layers with rich European butter.',
                        'image_url'    => '',
                    ],
                    [
                        'id'           => 0,
                        'name'         => 'Lemon Tart',
                        'category_name'=> 'Cakes & Tarts',
                        'price'        => '4.80',
                        'description'  => 'Silky citrus curd in a buttery shortcrust shell.',
                        'image_url'    => '',
                    ],
                    [
                        'id'           => 0,
                        'name'         => 'Sourdough Loaf',
                        'category_name'=> 'Breads',
                        'price'        => '6.00',
                        'description'  => 'Slow-fermented 72-hour levain with open crumb.',
                        'image_url'    => '',
                    ],
                    [
                        'id'           => 0,
                        'name'         => 'Flat White',
                        'category_name'=> 'Hot Drinks',
                        'price'        => '4.00',
                        'description'  => 'Double ristretto with velvety steamed micro-foam.',
                        'image_url'    => '',
                    ],
                ];
            }
            
            // Batch products into slides (3 per slide)
            $itemsPerSlide = 3;
            $slides = array_chunk($homeProducts, $itemsPerSlide);
        ?>

        <div id="homeProductCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6000">
            <div class="carousel-indicators">
                <?php foreach ($slides as $slideIndex => $slide): ?>
                    <button
                        type="button"
                        data-bs-target="#homeProductCarousel"
                        data-bs-slide-to="<?= $slideIndex ?>"
                        class="<?= $slideIndex === 0 ? 'active' : '' ?>"
                        aria-current="<?= $slideIndex === 0 ? 'true' : 'false' ?>"
                        aria-label="Slide <?= $slideIndex + 1 ?>">
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="carousel-inner">
                <?php foreach ($slides as $slideIndex => $slide): ?>
                    <div class="carousel-item <?= $slideIndex === 0 ? 'active' : '' ?>">
                        <div class="row g-4 carousel-row">
                            <?php foreach ($slide as $item):
                                $price = number_format((float) ($item['price'] ?? 0), 2);
                                $thumbStyle = !empty($item['image_url'])
                                    ? 'background-image:url(images/' . htmlspecialchars($item['image_url'], ENT_QUOTES) . ');'
                                    : 'background-color:#C8935A;';
                                $itemLink = !empty($item['id'])
                                    ? 'product_details.php?id=' . (int) $item['id']
                                    : 'products.php';
                            ?>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <article class="card product-teaser-card carousel-card" aria-label="<?= htmlspecialchars($item['name']) ?>">

                                        <div class="product-teaser-card__thumb" style="<?= $thumbStyle ?>" aria-hidden="true">
                                            <?php if (empty($item['image_url'])): ?>
                                                <i class="bi bi-cup-hot product-teaser-card__icon"></i>
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-body">
                                            <p class="product-teaser-card__cat"><?= htmlspecialchars($item['category_name'] ?? '') ?></p>
                                            <h3 class="card-title"><?= htmlspecialchars($item['name']) ?></h3>
                                            <p class="card-text mb-3"><?= htmlspecialchars(mb_strimwidth($item['description'] ?? '', 0, 100, '…')) ?></p>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="card-price">€<?= $price ?></span>
                                                <a href="<?= htmlspecialchars($itemLink, ENT_QUOTES) ?>" class="btn btn-primary btn-sm">
                                                    View
                                                </a>
                                            </div>
                                        </div>

                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($slides) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#homeProductCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#homeProductCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            <?php endif; ?>
        </div>

    </div>
</section>


<section class="section-espresso py-5" aria-labelledby="cta-heading">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-12 col-lg-7">
                <span class="label-script" style="color:var(--cafe-caramel)">Reserve your spot</span>
                <h2 class="section-title text-parchment" id="cta-heading" style="color:var(--cafe-parchment);">
                    Join Us for Breakfast <em style="color:var(--cafe-caramel); font-style:italic;">or Brunch</em>
                </h2>
                <p class="lead" style="color:rgba(245,236,215,.6); max-width:52ch;">
                    Tables fill fast on weekends. Book yours in advance and wake up to something special.
                </p>
            </div>
            <div class="col-12 col-lg-5 d-flex gap-3 justify-content-lg-end flex-wrap">
                <!-- FIX 5: reservations.php → reservation.php -->
                <a href="reservation.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>Make a Reservation
                </a>
                <!-- FIX 6: menu.php → products.php -->
                <a href="products.php" class="btn btn-outline-light btn-lg">
                    View Menu
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Page-specific styles -->
<style>
/* ── Hero ─────────────────────────────────────── */
.hero {
    background-color: var(--cafe-espresso);
    padding: 6rem 0 5rem;
    overflow: hidden;
    position: relative;
}
.min-vh-hero { min-height: 72vh; }

.hero__bg {
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 60% 80% at 65% 40%, rgba(200,147,90,.15) 0%, transparent 65%),
        radial-gradient(ellipse 40% 50% at 20% 80%, rgba(92,51,23,.3) 0%, transparent 60%);
    pointer-events: none;
}
.hero__ring {
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(200,147,90,.12);
    pointer-events: none;
}
.hero__ring--1 { width:600px; height:600px; top:-100px; right:-150px; }
.hero__ring--2 { width:900px; height:900px; top:-250px; right:-350px; }

/* Fade-up animation */
.animate-fade-up {
    opacity: 0;
    transform: translateY(22px);
    animation: fadeUp .7s ease forwards;
    animation-delay: var(--delay, 0s);
}
.animate-fade-in {
    opacity: 0;
    animation: fadeIn .9s ease forwards;
    animation-delay: var(--delay, 0s);
}
@keyframes fadeUp  { to { opacity:1; transform:translateY(0); } }
@keyframes fadeIn  { to { opacity:1; } }

.hero__illustration { filter: drop-shadow(0 20px 60px rgba(0,0,0,.35)); }

/* Trust badges */
.hero__badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .78rem;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: rgba(245,236,215,.6);
    border: 1px solid rgba(200,147,90,.25);
    padding: .3rem .8rem;
    border-radius: 2rem;
}
.hero__badge i { color: var(--cafe-caramel); }

/* ── Marquee strip ───────────────────────────── */
.marquee-strip {
    background-color: var(--cafe-coffee);
    overflow: hidden;
    padding: .6rem 0;
    border-top: 1px solid rgba(200,147,90,.3);
    border-bottom: 1px solid rgba(200,147,90,.3);
}
.marquee-track {
    display: flex;
    white-space: nowrap;
    animation: marquee 35s linear infinite;
}
.marquee-item {
    font-size: .8rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--cafe-parchment);
    padding: 0 .5rem;
}
.marquee-sep { color: var(--cafe-caramel); padding: 0 .25rem; }
@keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-33.333%); } }
@media (prefers-reduced-motion: reduce) { .marquee-track { animation: none; } }

/* ── Feature cards ───────────────────────────── */
.feature-card {
    padding: 2rem 1.5rem;
    border-radius: var(--bs-border-radius-lg);
    background: var(--cafe-white);
    border: 1px solid var(--cafe-border);
    height: 100%;
    transition: box-shadow var(--transition-base), transform var(--transition-base);
}
.feature-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }

.feature-card__icon {
    width: 52px; height: 52px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 14px;
    background: var(--cafe-parchment);
    color: var(--cafe-coffee);
    font-size: 1.4rem;
    margin-bottom: 1.25rem;
    transition: background var(--transition-base), color var(--transition-base);
}
.feature-card:hover .feature-card__icon {
    background: var(--cafe-coffee); color: var(--cafe-parchment);
}
.feature-card__title {
    font-family: var(--font-display);
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--cafe-espresso);
    margin-bottom: .5rem;
}
.feature-card__body { font-size: .9rem; color: var(--cafe-muted); line-height:1.65; margin:0; }

/* ── Product teaser cards ────────────────────── */
.product-teaser-card__thumb {
    height: 220px;
    display: flex; align-items: center; justify-content: center;
    position: relative;
    overflow: hidden;
    background-size: cover;
    background-position: center;
}
.product-teaser-card__icon {
    font-size: 3rem;
    color: rgba(255,255,255,.25);
}
.product-teaser-card__cat {
    font-size: .72rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--cafe-muted);
    margin-bottom: .2rem;
}
.carousel-card {
    border: none;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    height: 100%;
    transition: box-shadow var(--transition-base), transform var(--transition-base);
}
.carousel-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}
.carousel-row {
    align-items: stretch;
}
.carousel .carousel-control-prev,
.carousel .carousel-control-next {
    filter: drop-shadow(0 0 2px rgba(0,0,0,.3));
}
.carousel-indicators [data-bs-target] {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    background-color: rgba(255,255,255,.7);
}
.carousel-indicators .active {
    background-color: var(--cafe-espresso);
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
