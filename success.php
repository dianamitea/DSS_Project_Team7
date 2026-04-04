<?php


declare(strict_types=1);
session_start();

/* ── Normalise both possible session keys ───────────────────── */
$ctx = null;

if (!empty($_SESSION['success_context'])) {
    $ctx = $_SESSION['success_context'];
    unset($_SESSION['success_context']);
}

// Also accept checkout.php's session key (order_confirm)
if ($ctx === null && !empty($_SESSION['order_confirm'])) {
    $raw = $_SESSION['order_confirm'];
    unset($_SESSION['order_confirm']);
    $ctx = [
        'type'       => 'order',
        'order_id'   => $raw['order_id']   ?? null,
        'name'       => $raw['name']        ?? 'Guest',
        'email'      => $raw['email']       ?? '',
        'total'      => $raw['total']       ?? 0,
        'item_count' => $raw['item_count']  ?? 0,
        'pickup'     => $raw['pickup']      ?? 'pickup',
    ];
}

if ($ctx === null) {
    header('Location: index.php');
    exit;
}

$type = $ctx['type'] ?? 'order';

/* ── Shared values ──────────────────────────────────────────── */
$name = htmlspecialchars($ctx['name'] ?? 'Guest', ENT_QUOTES);

/* ── Type-specific values ───────────────────────────────────── */
if ($type === 'reservation') {
    $resId   = (int)   ($ctx['reservation_id'] ?? 0);
    $resDate = $ctx['res_date'] ?? '';
    $resTime = $ctx['res_time'] ?? '';
    $guests  = (int)   ($ctx['guests'] ?? 1);

    // Format for display
    $dateFormatted = $resDate
        ? (new \DateTimeImmutable($resDate))->format('l, j F Y')
        : '';
    $timeFormatted = $resTime
        ? date('g:i A', strtotime($resTime))
        : '';

    $pageTitle   = 'Reservation Confirmed — Maison Dorée';
    $heroTitle   = 'Table Reserved!';
    $heroScript  = 'See you soon';
    $icon        = 'bi-calendar-heart';
    $accentColor = '#9C6B3C';   // latte brown

    $headline    = "Your table is booked, {$name}!";
    $subline     = "We can't wait to welcome you. Here's a summary of your reservation.";

    $summaryItems = array_filter([
        $resId        ? ['label' => 'Booking #',  'value' => "#{$resId}",      'icon' => 'bi-hash']         : null,
        $dateFormatted? ['label' => 'Date',        'value' => $dateFormatted,   'icon' => 'bi-calendar3']    : null,
        $timeFormatted? ['label' => 'Time',        'value' => $timeFormatted,   'icon' => 'bi-clock']        : null,
                        ['label' => 'Guests',      'value' => "{$guests} guest" . ($guests > 1 ? 's' : ''), 'icon' => 'bi-people'],
    ]);

    $infoBoxIcon = 'bi-cup-hot';
    $infoBoxBody = "A confirmation has been sent to your email. If you need to cancel or reschedule, please contact us at least 2 hours before your booking time.";

    $ctaPrimary      = ['href' => 'index.php',        'label' => 'Back to Home',  'icon' => 'bi-house'];
    $ctaSecondary    = ['href' => 'categories.php',   'label' => 'Browse Menu',   'icon' => 'bi-grid'];
    $ctaTertiary     = ['href' => 'reservation.php',  'label' => 'Book Another',  'icon' => 'bi-calendar-plus'];

} else {
    // type = 'order'
    $orderId    = (int)   ($ctx['order_id']   ?? 0);
    $email      = htmlspecialchars($ctx['email']      ?? '', ENT_QUOTES);
    $total      = (float) ($ctx['total']      ?? 0);
    $itemCount  = (int)   ($ctx['item_count'] ?? 0);
    $pickup     = $ctx['pickup'] ?? 'pickup';
    $isDelivery = $pickup === 'delivery';

    $pageTitle   = 'Order Confirmed — Maison Dorée';
    $heroTitle   = 'Order Placed!';
    $heroScript  = 'Thank you';
    $icon        = 'bi-bag-check';
    $accentColor = '#5C3317';   // coffee brown

    $headline    = "We've got your order, {$name}!";
    $subline     = $isDelivery
        ? "Your order is being prepared and will be on its way soon."
        : "Your order is being prepared. It'll be ready for pickup shortly!";

    $summaryItems = array_filter([
        $orderId   ? ['label' => 'Order #',     'value' => "#{$orderId}",          'icon' => 'bi-hash']          : null,
        $itemCount ? ['label' => 'Items',       'value' => "{$itemCount} item" . ($itemCount > 1 ? 's' : ''), 'icon' => 'bi-bag'] : null,
                     ['label' => 'Total',       'value' => "€" . number_format($total, 2),  'icon' => 'bi-currency-euro'],
                     ['label' => 'Method',      'value' => $isDelivery ? 'Home Delivery' : 'In-Store Pickup', 'icon' => $isDelivery ? 'bi-truck' : 'bi-shop'],
    ]);

    $eta         = $isDelivery ? '30–45 minutes' : '15–20 minutes';
    $infoBoxIcon = $isDelivery ? 'bi-truck' : 'bi-shop';
    $infoBoxBody = $isDelivery
        ? "Estimated delivery time: <strong>{$eta}</strong>. Our team will contact you if needed."
        : "Ready in approximately <strong>{$eta}</strong>. Come pick up at 12 Rue du Four, Paris 75001.";

    $ctaPrimary   = ['href' => 'index.php',       'label' => 'Back to Home',  'icon' => 'bi-house'];
    // FIX: 'Order More' now points to products.php
    $ctaSecondary = ['href' => 'products.php',    'label' => 'Order More',    'icon' => 'bi-grid'];
    // FIX: 'Book a Table' now points to reservation.php
    $ctaTertiary  = ['href' => 'reservation.php', 'label' => 'Book a Table',  'icon' => 'bi-calendar-check'];
}

require_once __DIR__ . '/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════
     SUCCESS HERO
════════════════════════════════════════════════════════════ -->
<section class="suc-hero" aria-labelledby="suc-heading">
    <div class="suc-hero__bg" aria-hidden="true"></div>
    <div class="suc-hero__particles" aria-hidden="true">
        <?php for ($i = 0; $i < 18; $i++): ?>
            <span class="suc-particle" style="
                --x: <?= rand(5, 95) ?>%;
                --delay: <?= round(rand(0, 25) / 10, 1) ?>s;
                --dur: <?= round(rand(25, 50) / 10, 1) ?>s;
                --size: <?= rand(3, 8) ?>px;
                --opacity: <?= round(rand(2, 6) / 10, 1) ?>;
            " aria-hidden="true"></span>
        <?php endfor; ?>
    </div>
    <div class="container position-relative z-1 text-center">
        <span class="label-script suc-hero__script"><?= $heroScript ?></span>
        <h1 class="suc-hero__title" id="suc-heading"><?= $heroTitle ?></h1>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     CONFIRMATION CARD
════════════════════════════════════════════════════════════ -->
<section class="suc-section" aria-label="Confirmation details">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-7">

                <div class="suc-card">

                    <!-- Animated success seal -->
                    <div class="suc-seal" aria-hidden="true">
                        <div class="suc-seal__ring suc-seal__ring--1"></div>
                        <div class="suc-seal__ring suc-seal__ring--2"></div>
                        <div class="suc-seal__core" style="background:<?= $accentColor ?>;">
                            <i class="bi <?= $icon ?> suc-seal__icon"></i>
                        </div>
                        <!-- Animated checkmark overlay -->
                        <svg class="suc-check-svg" viewBox="0 0 52 52" aria-hidden="true">
                            <circle class="suc-check-svg__circle" cx="26" cy="26" r="25" fill="none"/>
                            <path   class="suc-check-svg__tick"   fill="none" d="M14 27l7 7 17-17"/>
                        </svg>
                    </div>

                    <h2 class="suc-card__headline"><?= $headline ?></h2>
                    <p class="suc-card__subline"><?= $subline ?></p>

                    <!-- Divider ornament -->
                    <div class="divider-ornament my-4">
                        <span>✦</span>
                    </div>

                    <!-- Summary grid -->
                    <dl class="suc-summary-grid" aria-label="Summary">
                        <?php foreach ($summaryItems as $item): ?>
                            <div class="suc-summary-item">
                                <dt class="suc-summary-item__label">
                                    <i class="bi <?= $item['icon'] ?>" aria-hidden="true"></i>
                                    <?= htmlspecialchars($item['label']) ?>
                                </dt>
                                <dd class="suc-summary-item__value">
                                    <?= htmlspecialchars($item['value']) ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>

                    <!-- Info box -->
                    <div class="suc-info-box" role="note" aria-label="What happens next">
                        <i class="bi <?= $infoBoxIcon ?> suc-info-box__icon" aria-hidden="true"
                           style="color:<?= $accentColor ?>;"></i>
                        <p class="suc-info-box__text"><?= $infoBoxBody ?></p>
                    </div>

                    <!-- CTA buttons -->
                    <div class="suc-ctas">
                        <a href="<?= $ctaPrimary['href'] ?>" class="btn btn-primary btn-lg suc-cta-primary">
                            <i class="bi <?= $ctaPrimary['icon'] ?> me-2" aria-hidden="true"></i>
                            <?= htmlspecialchars($ctaPrimary['label']) ?>
                        </a>
                        <div class="suc-ctas__secondary">
                            <a href="<?= $ctaSecondary['href'] ?>" class="btn btn-ghost">
                                <i class="bi <?= $ctaSecondary['icon'] ?> me-1" aria-hidden="true"></i>
                                <?= htmlspecialchars($ctaSecondary['label']) ?>
                            </a>
                            <a href="<?= $ctaTertiary['href'] ?>" class="btn btn-ghost">
                                <i class="bi <?= $ctaTertiary['icon'] ?> me-1" aria-hidden="true"></i>
                                <?= htmlspecialchars($ctaTertiary['label']) ?>
                            </a>
                        </div>
                    </div>

                </div><!-- /.suc-card -->

                <!-- Contact nudge -->
                <p class="suc-contact-nudge text-center mt-4">
                    Questions? Call us at
                    <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                    or email
                    <a href="mailto:bonjour@maisondoree.fr">bonjour@maisondoree.fr</a>
                </p>

            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     WHAT'S NEXT STRIP (type-specific)
════════════════════════════════════════════════════════════ -->
<section class="suc-next section-parchment section-sm" aria-labelledby="suc-next-heading">
    <div class="container">
        <h2 class="section-title text-center mb-4" id="suc-next-heading" style="font-size:1.6rem;">
            While You Wait…
        </h2>
        <div class="row g-4 justify-content-center">
            <?php
            if ($type === 'reservation') {
                $nexts = [
                    ['icon' => 'bi-grid',     'title' => 'Explore the Menu',   'body' => "Take a peek at what we'll be serving. Our menu changes with the seasons.",         'href' => 'categories.php',    'cta' => 'See Menu'],
                    ['icon' => 'bi-phone',    'title' => 'Save Our Number',    'body' => "Need to update your reservation? Give us a call — we're always happy to help.",    'href' => 'tel:+33123456789',  'cta' => 'Call Us'],
                    ['icon' => 'bi-instagram','title' => 'Follow the Journey', 'body' => "Behind-the-scenes baking, seasonal specials, and daily shots of what's in the oven.", 'href' => '#',              'cta' => '@MaisonDoree'],
                ];
            } else {
                $nexts = [
                    // FIX: 'Book a Table' card points to reservation.php
                    ['icon' => 'bi-calendar-check', 'title' => 'Book a Table',  'body' => 'Loved your order? Come in and enjoy the full café experience in person.',       'href' => 'reservation.php',  'cta' => 'Reserve Now'],
                    // FIX: 'Browse More' card points to products.php
                    ['icon' => 'bi-grid',            'title' => 'Browse More',   'body' => 'Discover our full menu — pastries, breads, hot drinks, and seasonal specials.', 'href' => 'products.php',     'cta' => 'See Menu'],
                    ['icon' => 'bi-share',           'title' => 'Share the Love','body' => 'Enjoying Maison Dorée? Tell a friend — good bread is meant to be shared.',     'href' => '#',                'cta' => 'Share'],
                ];
            }
            foreach ($nexts as $n): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="suc-next-card">
                        <div class="suc-next-card__icon" aria-hidden="true">
                            <i class="bi <?= $n['icon'] ?>"></i>
                        </div>
                        <h3 class="suc-next-card__title"><?= htmlspecialchars($n['title']) ?></h3>
                        <p class="suc-next-card__body"><?= htmlspecialchars($n['body']) ?></p>
                        <a href="<?= $n['href'] ?>" class="suc-next-card__link">
                            <?= htmlspecialchars($n['cta']) ?>
                            <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     PAGE STYLES
════════════════════════════════════════════════════════════ -->
<style>
/* ── Hero ──────────────────────────────────────────────────── */
.suc-hero {
    background: var(--cafe-espresso);
    padding: 3.5rem 0 2.5rem;
    position: relative;
    overflow: hidden;
    text-align: center;
}
.suc-hero__bg {
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 60% 80% at 50% 60%, rgba(200,147,90,.18) 0%, transparent 65%);
    pointer-events: none;
}

/* Floating particles */
.suc-hero__particles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.suc-particle {
    position: absolute;
    left: var(--x);
    top: -10px;
    width: var(--size);
    height: var(--size);
    border-radius: 50%;
    background: var(--cafe-caramel);
    opacity: var(--opacity);
    animation: particleFall var(--dur) var(--delay) linear infinite;
}
@keyframes particleFall {
    0%   { transform: translateY(0)      rotate(0deg);   opacity: var(--opacity); }
    100% { transform: translateY(400px)  rotate(360deg); opacity: 0; }
}
@media (prefers-reduced-motion: reduce) { .suc-particle { animation: none; } }

.suc-hero__script {
    color: var(--cafe-caramel) !important;
    display: block; margin-bottom: .3rem;
}
.suc-hero__title {
    font-family: var(--font-display);
    font-size: clamp(2.4rem, 6vw, 4rem);
    font-weight: 300; color: var(--cafe-parchment);
    line-height: 1.1; margin: 0;
}

/* ── Section ───────────────────────────────────────────────── */
.suc-section { padding: 3rem 0 1rem; background: var(--cafe-cream); }

/* ── Card ──────────────────────────────────────────────────── */
.suc-card {
    background: var(--cafe-white);
    border: 1px solid var(--cafe-border);
    border-radius: var(--bs-border-radius-xl);
    box-shadow: var(--shadow-lg);
    padding: 2.75rem 2.5rem;
    text-align: center;
    position: relative;
    overflow: hidden;

    animation: cardSlideUp .6s cubic-bezier(.22,1,.36,1) forwards;
}
@keyframes cardSlideUp {
    from { opacity: 0; transform: translateY(28px); }
    to   { opacity: 1; transform: translateY(0); }
}
/* Top gradient accent line */
.suc-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, var(--cafe-coffee), var(--cafe-caramel), var(--cafe-coffee));
}

/* ── Seal / icon ───────────────────────────────────────────── */
.suc-seal {
    position: relative;
    width: 96px; height: 96px;
    margin: 0 auto 1.5rem;
}
.suc-seal__ring {
    position: absolute; inset: 0; border-radius: 50%;
    border: 1.5px solid var(--cafe-caramel);
    animation: sealPulse 2.5s ease-in-out infinite;
}
.suc-seal__ring--1 { opacity: .35; }
.suc-seal__ring--2 { inset: -10px; opacity: .2; animation-delay: .4s; }
@keyframes sealPulse {
    0%, 100% { transform: scale(1);    opacity: var(--o, .35); }
    50%       { transform: scale(1.06); opacity: calc(var(--o, .35) * 0.5); }
}
.suc-seal__core {
    position: absolute; inset: 8px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 24px rgba(0,0,0,.2);
}
.suc-seal__icon { font-size: 1.8rem; color: var(--cafe-parchment); }

/* SVG checkmark overlay */
.suc-check-svg {
    position: absolute; inset: 0;
    width: 96px; height: 96px;
    pointer-events: none;
}
.suc-check-svg__circle {
    stroke: var(--cafe-caramel); stroke-width: 1.5;
    stroke-dasharray: 166; stroke-dashoffset: 166;
    animation: dashC .7s .2s cubic-bezier(.65,0,.45,1) forwards;
}
.suc-check-svg__tick {
    stroke: var(--cafe-parchment); stroke-width: 3;
    stroke-linecap: round; stroke-linejoin: round;
    stroke-dasharray: 48; stroke-dashoffset: 48;
    animation: dashT .4s .9s cubic-bezier(.65,0,.45,1) forwards;
}
@keyframes dashC { to { stroke-dashoffset: 0; } }
@keyframes dashT { to { stroke-dashoffset: 0; } }

/* ── Text ──────────────────────────────────────────────────── */
.suc-card__headline {
    font-family: var(--font-display);
    font-size: clamp(1.6rem, 3.5vw, 2.1rem);
    font-weight: 400; color: var(--cafe-espresso); margin-bottom: .5rem;
}
.suc-card__subline {
    font-size: .95rem; color: var(--cafe-muted); max-width: 48ch; margin: 0 auto;
}

/* ── Summary grid ──────────────────────────────────────────── */
.suc-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: .75rem; margin: 0;
    text-align: left;
}
.suc-summary-item {
    background: var(--cafe-parchment);
    border: 1px solid var(--cafe-border);
    border-radius: .5rem;
    padding: .75rem .9rem;
}
.suc-summary-item__label {
    display: flex; align-items: center; gap: .35rem;
    font-size: .68rem; letter-spacing: .09em; text-transform: uppercase;
    color: var(--cafe-muted); font-weight: 600; margin-bottom: .25rem;
}
.suc-summary-item__label i { color: var(--cafe-caramel); }
.suc-summary-item__value {
    font-family: var(--font-display); font-size: 1.05rem; font-weight: 600;
    color: var(--cafe-espresso); margin: 0;
}

/* ── Info box ──────────────────────────────────────────────── */
.suc-info-box {
    display: flex; align-items: flex-start; gap: .85rem;
    background: var(--cafe-milk);
    border: 1px solid var(--cafe-border);
    border-radius: .5rem;
    padding: 1rem 1.2rem;
    text-align: left;
}
.suc-info-box__icon { font-size: 1.4rem; flex-shrink: 0; margin-top: .1rem; }
.suc-info-box__text { font-size: .875rem; color: var(--cafe-charcoal); line-height: 1.65; margin: 0; }

/* ── CTA buttons ───────────────────────────────────────────── */
.suc-ctas { display: flex; flex-direction: column; align-items: center; gap: .75rem; }
.suc-cta-primary { min-width: 220px; letter-spacing: .04em; }
.suc-ctas__secondary { display: flex; gap: .6rem; flex-wrap: wrap; justify-content: center; }

/* ── Contact nudge ─────────────────────────────────────────── */
.suc-contact-nudge { font-size: .83rem; color: var(--cafe-muted); }
.suc-contact-nudge a { color: var(--cafe-latte); }
.suc-contact-nudge a:hover { color: var(--cafe-coffee); }

/* ── "What's Next" strip ───────────────────────────────────── */
.suc-next { padding: 3.5rem 0 4rem; }
.suc-next-card {
    background: var(--cafe-white);
    border: 1px solid var(--cafe-border);
    border-radius: var(--bs-border-radius-xl);
    padding: 1.6rem 1.5rem;
    height: 100%;
    transition: box-shadow var(--transition-base), transform var(--transition-base);
}
.suc-next-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }
.suc-next-card__icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: var(--cafe-parchment); border: 1px solid var(--cafe-border);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: var(--cafe-coffee);
    margin-bottom: 1rem;
    transition: background var(--transition-base), color var(--transition-base);
}
.suc-next-card:hover .suc-next-card__icon { background: var(--cafe-coffee); color: var(--cafe-parchment); }
.suc-next-card__title {
    font-family: var(--font-display); font-size: 1.15rem; font-weight: 600;
    color: var(--cafe-espresso); margin-bottom: .35rem;
}
.suc-next-card__body {
    font-size: .85rem; color: var(--cafe-muted); line-height: 1.6; margin-bottom: 1rem;
}
.suc-next-card__link {
    font-size: .8rem; font-weight: 500;
    letter-spacing: .06em; text-transform: uppercase;
    color: var(--cafe-coffee); text-decoration: none;
    display: inline-flex; align-items: center;
    transition: gap var(--transition-base);
    gap: .25rem;
}
.suc-next-card__link:hover { color: var(--cafe-latte); gap: .5rem; text-decoration: none; }

/* ── Responsive ────────────────────────────────────────────── */
@media (max-width: 575.98px) {
    .suc-card { padding: 2rem 1.25rem; }
    .suc-ctas__secondary { flex-direction: column; width: 100%; }
    .suc-ctas__secondary .btn { width: 100%; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
