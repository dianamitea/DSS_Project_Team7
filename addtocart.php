<?php

session_start();

require_once 'db_connect.php';

// ── Ensure session cart exists ──────────────────────────────
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ── Determine action and redirect target ────────────────────
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'products.php';
$action   = $_GET['action'] ?? $_POST['action'] ?? 'add';
$method   = $_SERVER['REQUEST_METHOD'];

// ── GET-based: add item ─────────────────────────────────────
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id < 1) {
        header("Location: {$redirect}");
        exit();
    }

    // Verify product exists
    $product = db_fetch_one(
        'SELECT id, name FROM products WHERE id = :id LIMIT 1',
        [':id' => $id]
    );

    if (!$product) {
        header("Location: {$redirect}");
        exit();
    }

    // Add to cart
    $current = $_SESSION['cart'][$id] ?? 0;
    $_SESSION['cart'][$id] = min($current + 1, 99);

    header("Location: {$redirect}");
    exit();
}

// ── POST-based: add/update/remove ───────────────────────────
if ($method === 'POST') {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || $csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('CSRF token invalid.');
    }

    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

    if ($product_id < 1) {
        header("Location: {$redirect}");
        exit();
    }

    // ── Remove item ─────────────────────────────────────────
    if ($action === 'remove') {
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
        header("Location: {$redirect}");
        exit();
    }

    // ── Update quantity ─────────────────────────────────────
    if ($action === 'update') {
        $qty = isset($_POST['qty']) ? (int) $_POST['qty'] : 0;

        if ($qty <= 0) {
            // Remove if qty is 0 or negative
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
            }
        } else {
            // Cap at 99
            $_SESSION['cart'][$product_id] = min($qty, 99);
        }

        header("Location: {$redirect}");
        exit();
    }

    // ── Add item (POST variant) ─────────────────────────────
    if ($action === 'add') {
        // Verify product exists
        $product = db_fetch_one(
            'SELECT id, name FROM products WHERE id = :id LIMIT 1',
            [':id' => $product_id]
        );

        if (!$product) {
            header("Location: {$redirect}");
            exit();
        }

        // Add to cart
        $current = $_SESSION['cart'][$product_id] ?? 0;
        $_SESSION['cart'][$product_id] = min($current + 1, 99);

        header("Location: {$redirect}");
        exit();
    }

    // Unknown action, redirect
    header("Location: {$redirect}");
    exit();
}

// Fallback
header("Location: {$redirect}");
exit();
