<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($email)) {
        $message = 'Username and email are required.';
    } else {
        try {
            $stmt = db()->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
            $stmt->execute([$username, $email, $user_id]);
            
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->execute([$hashed, $user_id]);
            }
            
            $message = 'Account updated successfully!';
            $_SESSION['username'] = $username;
        } catch (Exception $e) {
            $message = 'Failed to update account.';
        }
    }
}

$stmt = db()->prepare('SELECT username, email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Cafe DSS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth_styles.php">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container" style="max-width: 600px; margin: 40px auto;">
        <h2>My Account</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">New Password (leave blank to keep current):</label>
                <input type="password" id="password" name="password" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Update Account</button>
        </form>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
