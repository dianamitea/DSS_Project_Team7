<?php


declare(strict_types=1);
session_start();

require_once __DIR__ . '/db_connect.php';

/* ── Auth guard ─────────────────────────────────────────────── */
if (empty($_SESSION['user_id'])) {
    $_SESSION['flash'] = [
        'type'    => 'warning',
        'message' => 'Please log in to make a reservation.',
    ];
    // ✅ FIX 1: redirect target matches the actual filename (reservation.php)
    header('Location: login.php?redirect=reservation.php');
    exit;
}

/* ── CSRF token ─────────────────────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ── Constants ──────────────────────────────────────────────── */
const MAX_GUESTS     = 20;
const MAX_DAYS_AHEAD = 90;
const SLOTS_PER_TIME = 4;

// Available time slots (24-h format stored, 12-h displayed)
$timeSlots = [
    '07:00', '07:30', '08:00', '08:30', '09:00', '09:30',
    '10:00', '10:30', '11:00', '11:30', '12:00', '12:30',
    '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
    '16:00', '16:30', '17:00', '17:30', '18:00', '18:30',
];

/* ── Helpers ────────────────────────────────────────────────── */
function fmt12(string $time24): string
{
    return date('g:i A', strtotime($time24));
}

function isSlotAvailable(string $date, string $time): bool
{
    $count = (int) db_fetch_one(
        "SELECT COUNT(*) AS cnt
           FROM reservations
          WHERE res_date = :d AND res_time = :t AND status != 'cancelled'",
        [':d' => $date, ':t' => $time]
    )['cnt'];
    return $count < SLOTS_PER_TIME;
}

/* ── Defaults ───────────────────────────────────────────────── */
$errors   = [];
$formData = [
    'res_date' => $_GET['date'] ?? '',
    'res_time' => '',
    'guests'   => 2,
    'notes'    => '',
];

/* ── Fetch user's upcoming reservations (sidebar) ───────────── */
$myReservations = db_fetch_all(
    "SELECT id, res_date, res_time, guests, status
       FROM reservations
      WHERE user_id = :uid
        AND res_date >= CURDATE()
        AND status != 'cancelled'
      ORDER BY res_date ASC, res_time ASC
      LIMIT 5",
    [':uid' => $_SESSION['user_id']]
);

/* ── Handle POST ────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF */
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please refresh the page and try again.';
    } else {

        $resDate = trim($_POST['res_date'] ?? '');
        $resTime = trim($_POST['res_time'] ?? '');
        $guests  = (int) ($_POST['guests'] ?? 0);
        $notes   = trim(strip_tags($_POST['notes'] ?? ''));

        $formData['res_date'] = $resDate;
        $formData['res_time'] = $resTime;
        $formData['guests']   = $guests;
        $formData['notes']    = $notes;

        /* ── Validate date ──────────────────────────────────── */
        if ($resDate === '') {
            $errors['res_date'] = 'Please select a date.';
        } else {
            $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $resDate);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $resDate) {
                $errors['res_date'] = 'Invalid date format.';
            } else {
                $today   = new \DateTimeImmutable('today');
                $maxDate = $today->modify('+' . MAX_DAYS_AHEAD . ' days');
                if ($dateObj < $today) {
                    $errors['res_date'] = 'Reservations cannot be made for past dates.';
                } elseif ($dateObj > $maxDate) {
                    $errors['res_date'] = 'Reservations can only be made up to ' . MAX_DAYS_AHEAD . ' days in advance.';
                }
            }
        }

        /* ── Validate time ──────────────────────────────────── */
        if ($resTime === '') {
            $errors['res_time'] = 'Please select a time slot.';
        } elseif (!in_array($resTime, $timeSlots, true)) {
            $errors['res_time'] = 'Invalid time slot selected.';
        }

        /* ── Validate guests ────────────────────────────────── */
        if ($guests < 1 || $guests > MAX_GUESTS) {
            $errors['guests'] = 'Please select between 1 and ' . MAX_GUESTS . ' guests.';
        }

        /* ── Check slot availability ────────────────────────── */
        if (empty($errors['res_date']) && empty($errors['res_time'])) {
            if (!isSlotAvailable($resDate, $resTime)) {
                $errors['res_time'] = 'This time slot is fully booked. Please choose another time.';
            }
        }

        /* ── Check duplicate reservation ────────────────────── */
        if (empty($errors)) {
            $duplicate = db_fetch_one(
                "SELECT id FROM reservations
                  WHERE user_id = :uid
                    AND res_date = :d
                    AND res_time = :t
                    AND status != 'cancelled'
                  LIMIT 1",
                [
                    ':uid' => $_SESSION['user_id'],
                    ':d'   => $resDate,
                    ':t'   => $resTime,
                ]
            );
            if ($duplicate) {
                $errors[] = 'You already have a reservation at this date and time.';
            }
        }

        /* ── Insert using db()->prepare()->execute() ────────── */
        if (empty($errors)) {
            try {
                // ✅ FIX 2: uses db()->prepare()->execute() as requested.
                // $_SESSION['user_id'] identifies which logged-in user owns the row.
                $stmt = db()->prepare(
                    'INSERT INTO reservations (user_id, res_date, res_time, guests, notes)
                     VALUES (:uid, :date, :time, :guests, :notes)'
                );
                $stmt->execute([
                    ':uid'    => (int) $_SESSION['user_id'],  // ← from session
                    ':date'   => $resDate,
                    ':time'   => $resTime,
                    ':guests' => $guests,
                    ':notes'  => $notes !== '' ? $notes : null,
                ]);

                // Retrieve the new row's ID for the confirmation page
                $reservationId = (int) db()->lastInsertId();

                // Pass confirmation data to success.php via session
                $_SESSION['success_context'] = [
                    'type'           => 'reservation',
                    'reservation_id' => $reservationId,
                    'name'           => $_SESSION['username'] ?? 'Guest',
                    'res_date'       => $resDate,
                    'res_time'       => $resTime,
                    'guests'         => $guests,
                ];

                header('Location: success.php');
                exit;

            } catch (\PDOException $e) {
                error_log('[reservation] Insert failed: ' . $e->getMessage());
                $errors[] = 'Could not save your reservation. Please try again.';
            }
        }
    }
}

/* ── Pre-compute min/max date strings for the input ─────────── */
$minDate = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+' . MAX_DAYS_AHEAD . ' days'));

$pageTitle = 'Book a Table — Maison Dorée';
require_once __DIR__ . '/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════
     PAGE HERO
════════════════════════════════════════════════════════════ -->
<section class="res-hero" aria-labelledby="res-heading">
    <div class="res-hero__bg" aria-hidden="true"></div>

    <!-- Decorative SVG illustration -->
    <div class="res-hero__illustration" aria-hidden="true">
        <svg viewBox="0 0 400 320" fill="none" xmlns="http://www.w3.org/2000/svg" width="400" height="320">
            <ellipse cx="200" cy="210" rx="160" ry="28" fill="#5C3317" opacity=".35"/>
            <rect x="55" y="170" width="290" height="42" rx="8" fill="#5C3317" opacity=".5"/>
            <rect x="90"  y="210" width="14" height="70" rx="4" fill="#3A1F0D" opacity=".4"/>
            <rect x="296" y="210" width="14" height="70" rx="4" fill="#3A1F0D" opacity=".4"/>
            <rect x="42" y="158" width="316" height="22" rx="6" fill="#EDE0C4" opacity=".18"/>
            <rect x="192" y="105" width="16" height="64" rx="3" fill="#F5ECD7" opacity=".85"/>
            <ellipse cx="200" cy="105" rx="8" ry="4" fill="#EDE0C4" opacity=".7"/>
            <path d="M200 98 Q197 90 200 82 Q203 90 200 98Z" fill="#C8935A" opacity=".9"/>
            <path d="M200 96 Q199 91 200 86 Q201 91 200 96Z" fill="#FDF3E7" opacity=".7"/>
            <ellipse cx="200" cy="90" rx="18" ry="16" fill="#C8935A" opacity=".08"/>
            <g transform="translate(130 120)">
                <path d="M20 0 Q28 18 24 32 L16 32 Q12 18 20 0Z" fill="#F5ECD7" opacity=".3"/>
                <rect x="19" y="32" width="2" height="22" fill="#F5ECD7" opacity=".3"/>
                <ellipse cx="20" cy="54" rx="8" ry="2.5" fill="#F5ECD7" opacity=".25"/>
                <path d="M14 22 Q20 28 26 22 Q24 32 16 32Z" fill="#C2796A" opacity=".5"/>
            </g>
            <g transform="translate(240 120)">
                <path d="M20 0 Q28 18 24 32 L16 32 Q12 18 20 0Z" fill="#F5ECD7" opacity=".3"/>
                <rect x="19" y="32" width="2" height="22" fill="#F5ECD7" opacity=".3"/>
                <ellipse cx="20" cy="54" rx="8" ry="2.5" fill="#F5ECD7" opacity=".25"/>
                <path d="M14 20 Q20 26 26 20 Q24 32 16 32Z" fill="#C2796A" opacity=".4"/>
            </g>
            <ellipse cx="120" cy="175" rx="42" ry="10" fill="#EDE0C4" opacity=".25"/>
            <ellipse cx="120" cy="173" rx="38" ry="9"  fill="#EDE0C4" opacity=".2"/>
            <path d="M100 172 Q112 158 132 165 Q138 172 124 175 Q110 178 100 172Z" fill="#C8935A" opacity=".55"/>
            <ellipse cx="278" cy="175" rx="42" ry="10" fill="#EDE0C4" opacity=".25"/>
            <ellipse cx="278" cy="173" rx="38" ry="9"  fill="#EDE0C4" opacity=".2"/>
            <path d="M260 172 Q272 157 290 164 Q298 172 282 176 Q266 179 260 172Z" fill="#9C6B3C" opacity=".5"/>
            <circle cx="80"  cy="100" r="2"   fill="#C8935A" opacity=".4"/>
            <circle cx="320" cy="80"  r="1.5" fill="#EDE0C4" opacity=".35"/>
            <circle cx="60"  cy="150" r="1.5" fill="#C8935A" opacity=".3"/>
            <circle cx="355" cy="140" r="2"   fill="#EDE0C4" opacity=".3"/>
        </svg>
    </div>

    <div class="container position-relative z-1">
        <nav aria-label="Breadcrumb" class="res-breadcrumb mb-3">
            <a href="index.php">Home</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Reservations</span>
        </nav>
        <span class="label-script">Reserve your seat</span>
        <h1 class="res-hero__title" id="res-heading">
            Book a <em>Table</em>
        </h1>
        <p class="res-hero__sub">
            Join us for breakfast, brunch, or an afternoon treat.
            Tables fill fast — secure yours now.
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     MAIN: FORM + SIDEBAR
════════════════════════════════════════════════════════════ -->
<div class="container res-layout py-5">
    <div class="row g-5 align-items-start">

        <!-- ── BOOKING FORM (left) ──────────────────────────── -->
        <div class="col-12 col-lg-7">

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

            <div class="res-form-card">

                <div class="res-form-card__header">
                    <i class="bi bi-calendar-heart res-form-card__header-icon" aria-hidden="true"></i>
                    <div>
                        <h2 class="res-form-card__title">New Reservation</h2>
                        <p class="res-form-card__sub">
                            Booking as
                            <strong><?= htmlspecialchars($_SESSION['username'] ?? 'you') ?></strong>
                        </p>
                    </div>
                </div>

                <!-- ✅ FIX 3: form action points to reservation.php (matches filename) -->
                <form
                    method="POST"
                    action="reservation.php"
                    novalidate
                    aria-label="Table reservation form"
                    id="reservationForm">

                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <!-- ── Date + Time ───────────────────────── -->
                    <div class="row g-4 mb-4">

                        <!-- Date -->
                        <div class="col-12 col-sm-6">
                            <label for="res_date" class="form-label">
                                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                                Date <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="date"
                                id="res_date"
                                name="res_date"
                                class="form-control <?= isset($errors['res_date']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($formData['res_date']) ?>"
                                min="<?= $minDate ?>"
                                max="<?= $maxDate ?>"
                                required
                                aria-required="true"
                                aria-describedby="res_date-err res_date-hint"
                                aria-invalid="<?= isset($errors['res_date']) ? 'true' : 'false' ?>">
                            <?php if (isset($errors['res_date'])): ?>
                                <div class="invalid-feedback" id="res_date-err" role="alert">
                                    <?= htmlspecialchars($errors['res_date']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text" id="res_date-hint">
                                Up to <?= MAX_DAYS_AHEAD ?> days in advance.
                            </div>
                        </div>

                        <!-- Time -->
                        <div class="col-12 col-sm-6">
                            <label for="res_time" class="form-label">
                                <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                Time Slot <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <select
                                id="res_time"
                                name="res_time"
                                class="form-select <?= isset($errors['res_time']) ? 'is-invalid' : '' ?>"
                                required
                                aria-required="true"
                                aria-describedby="res_time-err"
                                aria-invalid="<?= isset($errors['res_time']) ? 'true' : 'false' ?>">
                                <option value="" disabled
                                    <?= empty($formData['res_time']) ? 'selected' : '' ?>>
                                    Select a time…
                                </option>
                                <?php foreach ($timeSlots as $slot): ?>
                                    <option
                                        value="<?= $slot ?>"
                                        <?= $formData['res_time'] === $slot ? 'selected' : '' ?>>
                                        <?= fmt12($slot) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['res_time'])): ?>
                                <div class="invalid-feedback" id="res_time-err" role="alert">
                                    <?= htmlspecialchars($errors['res_time']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div><!-- /.row date+time -->

                    <!-- ── Guests ────────────────────────────── -->
                    <div class="mb-4">
                        <label class="form-label d-block">
                            <i class="bi bi-people me-1" aria-hidden="true"></i>
                            Number of Guests <span class="text-danger" aria-hidden="true">*</span>
                        </label>

                        <div
                            class="guest-picker"
                            role="group"
                            aria-label="Select number of guests"
                            id="guestPicker">
                            <?php for ($g = 1; $g <= 10; $g++): ?>
                                <button
                                    type="button"
                                    class="guest-btn <?= (int)$formData['guests'] === $g ? 'guest-btn--active' : '' ?>"
                                    data-guests="<?= $g ?>"
                                    aria-pressed="<?= (int)$formData['guests'] === $g ? 'true' : 'false' ?>"
                                    aria-label="<?= $g ?> guest<?= $g > 1 ? 's' : '' ?>">
                                    <?= $g ?>
                                </button>
                            <?php endfor; ?>
                            <button
                                type="button"
                                class="guest-btn guest-btn--more <?= (int)$formData['guests'] > 10 ? 'guest-btn--active' : '' ?>"
                                id="guestMoreBtn"
                                aria-label="More than 10 guests">
                                10+
                            </button>
                        </div>

                        <!-- Hidden input that carries the guest count to PHP -->
                        <input
                            type="hidden"
                            id="guests"
                            name="guests"
                            value="<?= (int)$formData['guests'] ?>">

                        <!-- Large-group fallback -->
                        <div
                            id="guestLargeWrap"
                            class="mt-3 <?= (int)$formData['guests'] > 10 ? '' : 'd-none' ?>">
                            <label for="guestLargeInput" class="form-label">
                                Exact number (11–<?= MAX_GUESTS ?>)
                            </label>
                            <input
                                type="number"
                                id="guestLargeInput"
                                class="form-control <?= isset($errors['guests']) ? 'is-invalid' : '' ?>"
                                min="11"
                                max="<?= MAX_GUESTS ?>"
                                value="<?= (int)$formData['guests'] > 10 ? (int)$formData['guests'] : '' ?>"
                                placeholder="e.g. 12"
                                aria-describedby="guests-err">
                            <?php if (isset($errors['guests'])): ?>
                                <div class="invalid-feedback" id="guests-err" role="alert">
                                    <?= htmlspecialchars($errors['guests']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">For groups over <?= MAX_GUESTS ?>, please call us.</div>
                        </div>
                    </div>

                    <!-- ── Special Requests ──────────────────── -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">
                            <i class="bi bi-chat-square-text me-1" aria-hidden="true"></i>
                            Special Requests
                            <span class="res-optional">(optional)</span>
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            class="form-control"
                            rows="3"
                            maxlength="500"
                            placeholder="Allergies, high chair, anniversary setup, window table…"
                            aria-label="Special requests"><?= htmlspecialchars($formData['notes']) ?></textarea>
                        <div class="form-text d-flex justify-content-between">
                            <span>Let us know how we can make your visit special.</span>
                            <span id="notesCount" aria-live="polite">0 / 500</span>
                        </div>
                    </div>

                    <!-- ── Live summary preview ──────────────── -->
                    <div class="res-preview" id="resPreview" aria-live="polite" aria-label="Booking summary">
                        <i class="bi bi-calendar-check res-preview__icon" aria-hidden="true"></i>
                        <div id="resPreviewText" class="res-preview__text">
                            Your booking details will appear here as you fill in the form.
                        </div>
                    </div>

                    <!-- ── Submit ────────────────────────────── -->
                    <button type="submit" class="btn btn-primary btn-lg w-100 res-submit mt-4">
                        <i class="bi bi-calendar-plus me-2" aria-hidden="true"></i>
                        Confirm Reservation
                    </button>

                    <p class="res-submit-note text-center mt-2">
                        A confirmation will be sent to
                        <strong><?= htmlspecialchars($_SESSION['email'] ?? 'your email') ?></strong>.
                    </p>

                </form>
            </div><!-- /.res-form-card -->

        </div><!-- /col form -->

        <!-- ── SIDEBAR (right) ─────────────────────────────── -->
        <aside class="col-12 col-lg-5" aria-label="Reservation information">

            <!-- Opening hours -->
            <div class="res-info-card mb-4">
                <h2 class="res-info-card__title">
                    <i class="bi bi-clock me-2" aria-hidden="true"></i>Opening Hours
                </h2>
                <table class="res-hours-table" aria-label="Opening hours">
                    <tbody>
                        <?php
                        $hours = [
                            'Monday – Friday' => '7:00 AM – 7:00 PM',
                            'Saturday'        => '7:30 AM – 8:00 PM',
                            'Sunday'          => '8:00 AM – 5:00 PM',
                        ];
                        $today = date('l');
                        foreach ($hours as $day => $time):
                            $isToday = str_contains($day, $today);
                        ?>
                            <tr <?= $isToday ? 'class="res-hours--today"' : '' ?>>
                                <td>
                                    <?= $day ?>
                                    <?php if ($isToday): ?>
                                        <span class="badge bg-warning text-dark ms-1"
                                              style="font-size:.62rem;">Today</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $time ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- User's upcoming reservations -->
            <?php if (!empty($myReservations)): ?>
                <div class="res-info-card mb-4">
                    <h2 class="res-info-card__title">
                        <i class="bi bi-bookmark-check me-2" aria-hidden="true"></i>
                        Your Upcoming Bookings
                    </h2>
                    <ul class="res-booking-list" role="list">
                        <?php foreach ($myReservations as $r): ?>
                            <li class="res-booking-item" role="listitem">
                                <div class="res-booking-item__date">
                                    <span class="res-booking-item__day">
                                        <?= date('d', strtotime($r['res_date'])) ?>
                                    </span>
                                    <span class="res-booking-item__month">
                                        <?= date('M', strtotime($r['res_date'])) ?>
                                    </span>
                                </div>
                                <div class="res-booking-item__info">
                                    <p class="res-booking-item__time">
                                        <?= fmt12($r['res_time']) ?>
                                        · <?= (int)$r['guests'] ?> guest<?= $r['guests'] > 1 ? 's' : '' ?>
                                    </p>
                                    <span class="res-booking-item__status res-booking-item__status--<?= $r['status'] ?>">
                                        <?= ucfirst($r['status']) ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Location -->
            <div class="res-info-card res-location-card">
                <h2 class="res-info-card__title">
                    <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Find Us
                </h2>
                <div class="res-map-placeholder" aria-label="Map placeholder">
                    <svg viewBox="0 0 320 160" fill="none" xmlns="http://www.w3.org/2000/svg"
                         width="100%" aria-hidden="true">
                        <rect x="0" y="0" width="320" height="160" fill="#EDE0C4" rx="8"/>
                        <line x1="0"   y1="55"  x2="320" y2="55"  stroke="#C8B89A" stroke-width="8"/>
                        <line x1="0"   y1="105" x2="320" y2="105" stroke="#C8B89A" stroke-width="8"/>
                        <line x1="80"  y1="0"   x2="80"  y2="160" stroke="#C8B89A" stroke-width="6"/>
                        <line x1="180" y1="0"   x2="180" y2="160" stroke="#C8B89A" stroke-width="6"/>
                        <line x1="260" y1="0"   x2="260" y2="160" stroke="#C8B89A" stroke-width="5"/>
                        <rect x="6"   y="6"   width="68" height="43" rx="4" fill="#D9C9AE" opacity=".6"/>
                        <rect x="86"  y="6"   width="88" height="43" rx="4" fill="#D9C9AE" opacity=".5"/>
                        <rect x="186" y="6"   width="68" height="43" rx="4" fill="#D9C9AE" opacity=".6"/>
                        <rect x="6"   y="61"  width="68" height="38" rx="4" fill="#D9C9AE" opacity=".5"/>
                        <rect x="86"  y="61"  width="88" height="38" rx="4" fill="#D9C9AE" opacity=".4"/>
                        <rect x="186" y="61"  width="68" height="38" rx="4" fill="#D9C9AE" opacity=".5"/>
                        <rect x="6"   y="111" width="68" height="43" rx="4" fill="#D9C9AE" opacity=".6"/>
                        <rect x="86"  y="111" width="88" height="43" rx="4" fill="#D9C9AE" opacity=".5"/>
                        <rect x="186" y="111" width="130" height="43" rx="4" fill="#D9C9AE" opacity=".4"/>
                        <circle cx="130" cy="80" r="14" fill="#5C3317"/>
                        <circle cx="130" cy="80" r="6"  fill="#F5ECD7"/>
                        <path d="M130 94 L130 105" stroke="#5C3317" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="130" cy="80" r="20" stroke="#C8935A" stroke-width="1.5" opacity=".4"/>
                        <circle cx="130" cy="80" r="28" stroke="#C8935A" stroke-width="1"   opacity=".2"/>
                    </svg>
                </div>
                <address class="res-address">
                    <p class="mb-1">
                        <i class="bi bi-signpost-2 me-1" aria-hidden="true"></i>
                        12 Rue du Four, Old Town<br>Paris, France 75001
                    </p>
                    <p class="mb-1">
                        <i class="bi bi-telephone me-1" aria-hidden="true"></i>
                        <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-envelope me-1" aria-hidden="true"></i>
                        <a href="mailto:bonjour@maisondoree.fr">bonjour@maisondoree.fr</a>
                    </p>
                </address>
            </div>

        </aside>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     PAGE STYLES  (unchanged from original)
════════════════════════════════════════════════════════════ -->
<style>
.res-hero {
    background-color: var(--cafe-espresso);
    padding: 3rem 0;
    position: relative;
    overflow: hidden;
    min-height: 280px;
    display: flex;
    align-items: center;
}
.res-hero__bg {
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 55% 80% at 75% 50%, rgba(200,147,90,.15) 0%, transparent 60%),
        radial-gradient(ellipse 30% 60% at 5%  60%, rgba(92,51,23,.3)   0%, transparent 55%);
    pointer-events: none;
}
.res-hero__illustration {
    position: absolute;
    right: 2rem; top: 50%;
    transform: translateY(-50%);
    opacity: .55;
    pointer-events: none;
    display: none;
}
@media (min-width: 992px) { .res-hero__illustration { display: block; } }
.res-breadcrumb {
    display: flex; align-items: center; gap: .4rem;
    font-size: .77rem; letter-spacing: .07em; text-transform: uppercase;
}
.res-breadcrumb a { color: rgba(245,236,215,.45); text-decoration: none; }
.res-breadcrumb a:hover { color: var(--cafe-caramel); }
.res-breadcrumb span { color: rgba(245,236,215,.25); }
.res-breadcrumb [aria-current] { color: var(--cafe-caramel); }
.res-hero__title {
    font-family: var(--font-display);
    font-size: clamp(2.2rem, 5vw, 3.4rem);
    font-weight: 300; color: var(--cafe-parchment);
    line-height: 1.1; margin: .25rem 0 .5rem;
}
.res-hero__title em { font-style: italic; color: var(--cafe-caramel); }
.res-hero__sub { font-size: .9rem; color: rgba(245,236,215,.5); max-width: 44ch; margin: 0; line-height: 1.65; }
.res-form-card {
    background: var(--cafe-white);
    border: 1px solid var(--cafe-border);
    border-radius: var(--bs-border-radius-xl);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}
.res-form-card__header {
    display: flex; align-items: center; gap: 1rem;
    background: var(--cafe-parchment);
    padding: 1.4rem 1.75rem;
    border-bottom: 1px solid var(--cafe-border);
}
.res-form-card__header-icon { font-size: 2rem; color: var(--cafe-coffee); flex-shrink: 0; }
.res-form-card__title {
    font-family: var(--font-display); font-size: 1.35rem;
    font-weight: 600; color: var(--cafe-espresso); margin: 0 0 .1rem;
}
.res-form-card__sub { font-size: .83rem; color: var(--cafe-muted); margin: 0; }
.res-form-card form { padding: 1.75rem; }
.guest-picker { display: flex; flex-wrap: wrap; gap: .5rem; }
.guest-btn {
    width: 42px; height: 42px; border-radius: 50%;
    border: 1.5px solid var(--cafe-border);
    background: var(--cafe-white); color: var(--cafe-charcoal);
    font-size: .88rem; font-weight: 500; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s, border-color .2s, color .2s, box-shadow .2s, transform .2s;
}
.guest-btn:hover { border-color: var(--cafe-latte); background: var(--cafe-parchment); transform: translateY(-1px); }
.guest-btn--active {
    background: var(--cafe-coffee) !important;
    border-color: var(--cafe-coffee) !important;
    color: var(--cafe-parchment) !important;
    box-shadow: 0 3px 10px rgba(92,51,23,.3);
}
.guest-btn--more { width: auto; padding: 0 .85rem; border-radius: 21px; font-size: .8rem; }
.res-preview {
    display: flex; align-items: flex-start; gap: .85rem;
    background: var(--cafe-parchment);
    border: 1px solid var(--cafe-border);
    border-left: 3px solid var(--cafe-caramel);
    border-radius: .5rem;
    padding: 1rem 1.1rem;
    font-size: .875rem; color: var(--cafe-charcoal);
    line-height: 1.6;
    transition: border-color .2s;
}
.res-preview.res-preview--ready { border-left-color: var(--cafe-coffee); }
.res-preview__icon { font-size: 1.3rem; color: var(--cafe-caramel); flex-shrink: 0; margin-top: .05rem; }
.res-preview__text { flex: 1; }
.res-submit { letter-spacing: .04em; }
.res-submit-note { font-size: .78rem; color: var(--cafe-muted); }
.res-optional { font-weight: 400; font-size: .78em; color: var(--cafe-border); margin-left: .2rem; }
.res-info-card {
    background: var(--cafe-white);
    border: 1px solid var(--cafe-border);
    border-radius: var(--bs-border-radius-xl);
    padding: 1.4rem 1.6rem;
    box-shadow: var(--shadow-sm);
}
.res-info-card__title {
    font-family: var(--font-body);
    font-size: .72rem; font-weight: 600;
    letter-spacing: .1em; text-transform: uppercase;
    color: var(--cafe-muted); margin: 0 0 1rem;
    padding-bottom: .65rem;
    border-bottom: 1px solid var(--cafe-border);
    display: flex; align-items: center;
}
.res-hours-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.res-hours-table td { padding: .4rem 0; color: var(--cafe-charcoal); border-bottom: 1px dashed var(--cafe-border); }
.res-hours-table tr:last-child td { border-bottom: none; }
.res-hours-table td:last-child { text-align: right; color: var(--cafe-muted); }
.res-hours--today td { color: var(--cafe-coffee); font-weight: 600; }
.res-hours--today td:last-child { color: var(--cafe-latte); }
.res-booking-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .65rem; }
.res-booking-item {
    display: flex; align-items: center; gap: .85rem;
    padding: .65rem .75rem;
    background: var(--cafe-milk);
    border: 1px solid var(--cafe-border);
    border-radius: .5rem;
}
.res-booking-item__date {
    width: 40px; height: 40px; border-radius: .4rem;
    background: var(--cafe-coffee); color: var(--cafe-parchment);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    flex-shrink: 0; line-height: 1;
}
.res-booking-item__day   { font-size: 1rem; font-weight: 700; }
.res-booking-item__month { font-size: .6rem; text-transform: uppercase; letter-spacing: .05em; opacity: .75; }
.res-booking-item__info  { flex: 1; }
.res-booking-item__time  { font-size: .83rem; color: var(--cafe-charcoal); margin: 0 0 .2rem; }
.res-booking-item__status {
    font-size: .65rem; letter-spacing: .07em; text-transform: uppercase;
    font-weight: 600; padding: .1rem .5rem; border-radius: 2rem;
}
.res-booking-item__status--pending   { background: #FDF3E7; color: #9C6B3C; border: 1px solid #E8C98A; }
.res-booking-item__status--confirmed { background: #EAF2E8; color: #3A5E35; border: 1px solid #B7D4B2; }
.res-map-placeholder { border-radius: .5rem; overflow: hidden; margin-bottom: 1rem; border: 1px solid var(--cafe-border); }
.res-address { font-style: normal; font-size: .85rem; color: var(--cafe-muted); line-height: 1.7; }
.res-address a { color: var(--cafe-latte); }
.res-address a:hover { color: var(--cafe-coffee); }
</style>

<script>
/* ── Guest picker ───────────────────────────────────────────── */
const guestInput     = document.getElementById('guests');
const guestPicker    = document.getElementById('guestPicker');
const guestMoreBtn   = document.getElementById('guestMoreBtn');
const guestLargeWrap  = document.getElementById('guestLargeWrap');
const guestLargeInput = document.getElementById('guestLargeInput');

guestPicker.querySelectorAll('.guest-btn:not(.guest-btn--more)').forEach(btn => {
    btn.addEventListener('click', () => {
        setGuests(parseInt(btn.dataset.guests, 10));
        guestLargeWrap.classList.add('d-none');
    });
});

guestMoreBtn.addEventListener('click', () => {
    setActiveBtn(null);
    guestMoreBtn.classList.add('guest-btn--active');
    guestMoreBtn.setAttribute('aria-pressed', 'true');
    guestLargeWrap.classList.remove('d-none');
    guestLargeInput.focus();
    guestInput.value = guestLargeInput.value || 11;
    updatePreview();
});

guestLargeInput?.addEventListener('input', () => {
    guestInput.value = guestLargeInput.value;
    updatePreview();
});

function setGuests(n) {
    guestInput.value = n;
    setActiveBtn(n);
    updatePreview();
}

function setActiveBtn(n) {
    guestPicker.querySelectorAll('.guest-btn').forEach(b => {
        const match = parseInt(b.dataset.guests, 10) === n;
        b.classList.toggle('guest-btn--active', match);
        b.setAttribute('aria-pressed', match ? 'true' : 'false');
    });
}

/* ── Live booking preview ───────────────────────────────────── */
const dateInput   = document.getElementById('res_date');
const timeSelect  = document.getElementById('res_time');
const previewBox  = document.getElementById('resPreview');
const previewText = document.getElementById('resPreviewText');

function fmt12js(t) {
    if (!t) return '';
    const [h, m] = t.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    return `${h % 12 || 12}:${String(m).padStart(2,'0')} ${ampm}`;
}

function updatePreview() {
    const d = dateInput?.value;
    const t = timeSelect?.value;
    const g = guestInput?.value;

    if (!d && !t) {
        previewText.textContent = 'Your booking details will appear here as you fill in the form.';
        previewBox.classList.remove('res-preview--ready');
        return;
    }

    const parts = [];
    if (d) {
        const dateObj = new Date(d + 'T12:00:00');
        parts.push(dateObj.toLocaleDateString('en-GB', { weekday:'long', day:'numeric', month:'long', year:'numeric' }));
    }
    if (t) parts.push('at ' + fmt12js(t));
    if (g && parseInt(g, 10) > 0) parts.push('for ' + g + ' guest' + (parseInt(g, 10) > 1 ? 's' : ''));

    previewText.innerHTML = '🗓️ <strong>' + parts.join(' ') + '</strong>';
    previewBox.classList.add('res-preview--ready');
}

dateInput?.addEventListener('change', updatePreview);
timeSelect?.addEventListener('change', updatePreview);
updatePreview();

/* ── Notes character counter ────────────────────────────────── */
const notesArea  = document.getElementById('notes');
const notesCount = document.getElementById('notesCount');
notesArea?.addEventListener('input', () => {
    notesCount.textContent = notesArea.value.length + ' / 500';
});
if (notesArea) notesCount.textContent = notesArea.value.length + ' / 500';
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
