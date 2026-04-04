<?php
/**
 * addtocart.php
 * -------------
 * Gets ?id= from the URL, adds it to $_SESSION['cart'], then
 * redirects back to products.php.
 *
 * Called via:  <a href="addtocart.php?id=<?php echo $product['id']; ?>">
 */

session_start();

require_once 'db_connect.php';

// ── Get & validate the product ID from the URL ──────────────
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id < 1) {
    // No valid ID supplied — go back to the product listing
    header('Location: products.php');
    exit();
}

// ── Verify the product actually exists in the DB ────────────
$product = db_fetch_one(
    'SELECT id, name FROM products WHERE id = :id LIMIT 1',
    [':id' => $id]
);

if (!$product) {
    // Product not found — go back to the product listing
    header('Location: products.php');
    exit();
}

// ── Add to session cart ─────────────────────────────────────
// Cart is a simple array:  [ product_id => quantity, ... ]
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Increment quantity (cap at 99)
$current = $_SESSION['cart'][$id] ?? 0;
$_SESSION['cart'][$id] = min($current + 1, 99);

// ── Redirect back to the product listing ────────────────────
header('Location: products.php');
exit();