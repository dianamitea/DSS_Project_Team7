<?php

// session_start() MUST be first — before any output or logic
session_start();

// Unset just the cart key, leaving the rest of the session intact
unset($_SESSION['cart']);

// Redirect back to cart (which will now show the empty state)
header('Location: cart.php');
exit;
