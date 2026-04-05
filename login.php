<?php

session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db_connect.php';

/* ── Rate-limit helpers ─────────────────────────────────────── */
function checkRateLimit(): bool
{
    $key      = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? '');
    $attempts = $_SESSION[$key]['count'] ?? 0;
    $lockedAt = $_SESSION[$key]['locked_at'] ?? null;
    if ($lockedAt) {
        if (time() - $lockedAt < 900) return false;
        unset($_SESSION[$key]);
    }
    return true;
}

function recordFailedAttempt(): void
{
    $key = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? '');
    $_SESSION[$key]['count'] = ($_SESSION[$key]['count'] ?? 0) + 1;
    if ($_SESSION[$key]['count'] >= 5) {
        $_SESSION[$key]['locked_at'] = time();
    }
}

function clearAttempts(): void
{
    $key = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? '');
    unset($_SESSION[$key]);
}

function remainingLockoutMinutes(): int
{
    $key      = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? '');
    $lockedAt = $_SESSION[$key]['locked_at'] ?? null;
    if (!$lockedAt) return 0;
    return (int) ceil((900 - (time() - $lockedAt)) / 60);
}

/* ── Defaults ────────────────────────────────────────────────── */
$errors     = [];
$credential = '';

$redirect = filter_var(
    $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php',
    FILTER_SANITIZE_URL
);
if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+\.php$/', $redirect)) {
    $redirect = 'index.php';
}

/* ── Handle POST ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';

    } elseif (!checkRateLimit()) {
        $mins = remainingLockoutMinutes();
        $errors[] = "Too many failed attempts. Please wait {$mins} minute(s) before trying again.";

    } else {
        $credential = trim($_POST['credential'] ?? '');
        $password   = $_POST['password'] ?? '';

        if ($password   === '') $errors['password']   = 'Please enter your password.';

        if (empty($errors)) {
            $user = db_fetch_one(
                'SELECT id, username, email, password
                   FROM users
                  WHERE email = :cred OR username = :cred2
                  LIMIT 1',
                [':cred' => $credential, ':cred2' => $credential]
            );

            if ($user && password_verify($password, $user['password'])) {
                clearAttempts();
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];

                $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
                if (password_needs_rehash($user['password'], $algo)) {
                    $newHash = password_hash($password, $algo);
                    db()->prepare('UPDATE users SET password = :p WHERE id = :id')
                         ->execute([':p' => $newHash, ':id' => $user['id']]);
                }

                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Welcome back, ' . htmlspecialchars($user['username']) . '! ☕',
                ];

                header('Location: ' . $redirect);
                exit;
            } else {
                recordFailedAttempt();
                $errors[] = 'The email/username or password is incorrect. Please try again.';
                $attemptsLeft = 5 - ($_SESSION['login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? '')]['count'] ?? 0);
                if ($attemptsLeft > 0 && $attemptsLeft <= 3) {
                    $errors[] = "Warning: {$attemptsLeft} attempt(s) remaining before temporary lockout.";
                }
            }
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Sign In — Maison Dorée';
require_once __DIR__ . '/header.php';
?>

<section class="auth-section" aria-labelledby="login-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

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
                        <span class="label-script mt-3">Welcome back</span>
                        <h1 class="auth-card__title" id="login-heading">Sign In to Your Account</h1>
                        <p class="auth-card__sub">
                            New here?
                            <a href="register.php" class="auth-link">Create a free account</a>
                        </p>
                    </div>

                    <!-- Errors -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert" aria-live="assertive">
                            <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                            <?php if (count($errors) === 1): ?>
                                <?= htmlspecialchars(reset($errors)) ?>
                            <?php else: ?>
                                <ul class="mb-0 ps-3 mt-1">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= htmlspecialchars($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form
                        method="POST"
                        action="login.php<?= $redirect !== 'index.php' ? '?redirect=' . urlencode($redirect) : '' ?>"
                        novalidate
                        aria-label="Login form">

                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="redirect"
                               value="<?= htmlspecialchars($redirect) ?>">

                        <!-- Email or Username -->
                        <div class="mb-4">
                            <label for="credential" class="form-label">
                                Email or Username <span class="text-rose" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text auth-input-icon" aria-hidden="true">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input
                                    type="text"
                                    id="credential"
                                    name="credential"
                                    class="form-control <?= isset($errors['credential']) ? 'is-invalid' : '' ?>"
                                    value="<?= htmlspecialchars($credential) ?>"
                                    placeholder="you@example.com or username"
                                    required
                                    autocomplete="username"
                                    autofocus
                                    aria-describedby="credential-feedback"
                                    aria-required="true"
                                    aria-invalid="<?= isset($errors['credential']) ? 'true' : 'false' ?>">
                                <?php if (isset($errors['credential'])): ?>
                                    <div class="invalid-feedback" id="credential-feedback" role="alert">
                                        <?= htmlspecialchars($errors['credential']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <label for="password" class="form-label mb-0">
                                    Password <span class="text-rose" aria-hidden="true">*</span>
                                </label>
                                <a href="forgot-password.php" class="auth-link" style="font-size:.8rem;">
                                    Forgot password?
                                </a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text auth-input-icon" aria-hidden="true">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                    placeholder="Your password"
                                    required
                                    autocomplete="current-password"
                                    aria-describedby="password-feedback"
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
                                        <?= htmlspecialchars($errors['password']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Remember me -->
                        <div class="form-check mb-5 mt-3">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="remember"
                                name="remember"
                                value="1">
                            <label class="form-check-label" for="remember"
                                   style="font-size:.88rem; color:var(--cafe-muted); text-transform:none; letter-spacing:0; font-weight:400;">
                                Keep me signed in on this device
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg auth-submit">
                            <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>
                            Sign In
                        </button>

                    </form>

                    <!-- Divider -->
                    <div class="divider-ornament my-4">
                        <span>or</span>
                    </div>

                    <div class="text-center">
                        <p class="mb-0" style="font-size:.9rem; color:var(--cafe-muted);">
                            Don't have an account yet?
                        </p>
                        <a href="register.php" class="btn btn-ghost w-100 mt-2">
                            <i class="bi bi-person-plus me-2" aria-hidden="true"></i>
                            Create a Free Account
                        </a>
                    </div>

                </div><!-- /.auth-card -->

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/auth_styles.php'; ?>

<script>
/* Password visibility toggle */
document.querySelectorAll('.auth-toggle-pass').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        const icon  = btn.querySelector('i');
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
