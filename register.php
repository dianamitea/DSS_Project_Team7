<?php

/**
 * register.php
 * ------------
 * New-user registration form.
 *   • Validates all inputs server-side
 *   • Hashes passwords with PASSWORD_ARGON2ID (falls back to BCRYPT)
 *   • Stores the new user in `cafe_db`.`users`
 *   • Redirects to login.php with a flash message on success
 */

session_start();

// Already logged in → redirect home
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db_connect.php';

/* ── Helpers ─────────────────────────────────────────────── */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/* ── Defaults ────────────────────────────────────────────── */
$errors   = [];
$formData = ['username' => '', 'email' => ''];

/* ── Handle POST ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF token check
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {

        $username  = trim($_POST['username']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']        ?? '';
        $password2 = $_POST['password_confirm'] ?? '';

        // Preserve typed values for re-display
        $formData['username'] = sanitize($username);
        $formData['email']    = sanitize($email);

        // ── Validate username ──
        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif (strlen($username) < 3 || strlen($username) > 60) {
            $errors['username'] = 'Username must be between 3 and 60 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9._\-]+$/', $username)) {
            $errors['username'] = 'Username may only contain letters, numbers, dots, hyphens, and underscores.';
        }

        // ── Validate email ──
        if ($email === '') {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        // ── Validate password ──
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number.';
        }

        // ── Confirm password ──
        if ($password2 === '') {
            $errors['password_confirm'] = 'Please confirm your password.';
        } elseif ($password !== $password2) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        // ── Check for duplicates (only if basics pass) ──
        if (empty($errors)) {
            $existing = db_fetch_one(
                'SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1',
                [':email' => $email, ':username' => $username]
            );

            if ($existing) {
                // Don't reveal which field is taken for security; show both
                $errors[] = 'That username or email is already registered. Please <a href="login.php" class="alert-link">log in</a> instead.';
            }
        }

        // ── Insert ──
        if (empty($errors)) {
            $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $hash = password_hash($password, $algo);

            try {
                db_insert_user($username, $email, $hash);

                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => '🎉 Account created! Welcome to Maison Dorée. Please log in.',
                ];

                header('Location: login.php');
                exit;

            } catch (\PDOException $e) {
                error_log('[register] DB error: ' . $e->getMessage());
                $errors[] = 'Something went wrong on our end. Please try again in a moment.';
            }
        }
    }
}

// Generate CSRF token for the form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Create Account — Maison Dorée';
require_once __DIR__ . '/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════
     REGISTER PAGE
════════════════════════════════════════════════════════════ -->
<section class="auth-section" aria-labelledby="register-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

                <!-- Card -->
                <div class="auth-card">

                    <!-- Header -->
                    <div class="auth-card__header text-center">
                        <a href="index.php" class="auth-card__logo" aria-label="Back to home">
                            <svg width="36" height="36" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M4 22C6 14 12 6 24 4C22 12 16 20 4 22Z" fill="#C8935A" opacity="0.9"/>
                                <path d="M24 22C22 14 16 6 4 4C6 12 12 20 24 22Z" fill="#9C6B3C" opacity="0.7"/>
                                <circle cx="14" cy="13" r="3" fill="#F5ECD7" opacity="0.6"/>
                            </svg>
                        </a>
                        <span class="label-script mt-3">Join us</span>
                        <h1 class="auth-card__title" id="register-heading">Create Your Account</h1>
                        <p class="auth-card__sub">
                            Already a member?
                            <a href="login.php" class="auth-link">Sign in here</a>
                        </p>
                    </div>

                    <!-- Global errors -->
                    <?php
                    $globalErrors = array_filter($errors, fn($k) => is_int($k), ARRAY_FILTER_USE_KEY);
                    if ($globalErrors): ?>
                        <div class="alert alert-danger" role="alert" aria-live="assertive">
                            <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($globalErrors as $err): ?>
                                    <li><?= $err /* already escaped or intentionally contains link */ ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form
                        method="POST"
                        action="register.php"
                        novalidate
                        autocomplete="off"
                        aria-label="Registration form">

                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <!-- Username -->
                        <div class="mb-4">
                            <label for="username" class="form-label">
                                Username <span class="text-rose" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group <?= isset($errors['username']) ? 'is-invalid-group' : '' ?>">
                                <span class="input-group-text auth-input-icon" aria-hidden="true">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                    value="<?= $formData['username'] ?>"
                                    placeholder="e.g. marie_dupont"
                                    required
                                    minlength="3"
                                    maxlength="60"
                                    autocomplete="username"
                                    aria-describedby="username-feedback username-hint"
                                    aria-required="true"
                                    aria-invalid="<?= isset($errors['username']) ? 'true' : 'false' ?>">
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback" id="username-feedback" role="alert">
                                        <?= sanitize($errors['username']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div id="username-hint" class="form-text">3–60 characters. Letters, numbers, dots, hyphens, underscores.</div>
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label">
                                Email Address <span class="text-rose" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text auth-input-icon" aria-hidden="true">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                    value="<?= $formData['email'] ?>"
                                    placeholder="you@example.com"
                                    required
                                    autocomplete="email"
                                    aria-describedby="email-feedback"
                                    aria-required="true"
                                    aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback" id="email-feedback" role="alert">
                                        <?= sanitize($errors['email']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                Password <span class="text-rose" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text auth-input-icon" aria-hidden="true">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                    placeholder="Min. 8 characters"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                    aria-describedby="password-feedback password-hint"
                                    aria-required="true"
                                    aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>">
                                <button
                                    type="button"
                                    class="input-group-text auth-toggle-pass"
                                    aria-label="Toggle password visibility"
                                    data-target="password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback" id="password-feedback" role="alert">
                                        <?= sanitize($errors['password']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Password strength indicators -->
                            <div id="password-hint" class="password-hints mt-2" aria-live="polite">
                                <span class="hint" data-rule="length">
                                    <i class="bi bi-circle" aria-hidden="true"></i> 8+ characters
                                </span>
                                <span class="hint" data-rule="upper">
                                    <i class="bi bi-circle" aria-hidden="true"></i> Uppercase letter
                                </span>
                                <span class="hint" data-rule="number">
                                    <i class="bi bi-circle" aria-hidden="true"></i> Number
                                </span>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-5">
                            <label for="password_confirm" class="form-label">
                                Confirm Password <span class="text-rose" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text auth-input-icon" aria-hidden="true">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input
                                    type="password"
                                    id="password_confirm"
                                    name="password_confirm"
                                    class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                                    placeholder="Re-enter your password"
                                    required
                                    autocomplete="new-password"
                                    aria-describedby="confirm-feedback"
                                    aria-required="true"
                                    aria-invalid="<?= isset($errors['password_confirm']) ? 'true' : 'false' ?>">
                                <button
                                    type="button"
                                    class="input-group-text auth-toggle-pass"
                                    aria-label="Toggle confirm password visibility"
                                    data-target="password_confirm">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <?php if (isset($errors['password_confirm'])): ?>
                                    <div class="invalid-feedback" id="confirm-feedback" role="alert">
                                        <?= sanitize($errors['password_confirm']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg auth-submit">
                            <i class="bi bi-person-plus me-2" aria-hidden="true"></i>
                            Create Account
                        </button>

                        <p class="auth-terms text-center mt-3">
                            By registering you agree to our
                            <a href="terms.php">Terms of Use</a> and
                            <a href="privacy.php">Privacy Policy</a>.
                        </p>

                    </form>
                </div><!-- /.auth-card -->

            </div>
        </div>
    </div>
</section>

<!-- Auth page styles + register-specific JS -->
<?php require_once __DIR__ . '/auth_styles.php'; ?>

<script>
/* Password visibility toggle */
document.querySelectorAll('.auth-toggle-pass').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        const icon  = btn.querySelector('i');
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
            btn.setAttribute('aria-label', 'Hide password');
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
            btn.setAttribute('aria-label', 'Show password');
        }
    });
});

/* Live password strength hints */
const pwInput = document.getElementById('password');
const hints   = {
    length : document.querySelector('.hint[data-rule="length"]'),
    upper  : document.querySelector('.hint[data-rule="upper"]'),
    number : document.querySelector('.hint[data-rule="number"]'),
};

function setHint(el, pass) {
    if (!el) return;
    const rules = {
        length : v => v.length >= 8,
        upper  : v => /[A-Z]/.test(v),
        number : v => /[0-9]/.test(v),
    };
    const rule = el.dataset.rule;
    const ok   = rules[rule]?.(pass) ?? false;
    el.classList.toggle('hint--ok', ok);
    el.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
}

if (pwInput) {
    pwInput.addEventListener('input', () => {
        const v = pwInput.value;
        Object.keys(hints).forEach(k => setHint(hints[k], v));
    });
}

/* Live confirm-match indicator */
const pw2 = document.getElementById('password_confirm');
if (pwInput && pw2) {
    const check = () => {
        const match = pwInput.value === pw2.value && pw2.value !== '';
        pw2.classList.toggle('is-valid',   match);
        pw2.classList.toggle('is-invalid', !match && pw2.value !== '');
    };
    pw2.addEventListener('input', check);
    pwInput.addEventListener('input', check);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>