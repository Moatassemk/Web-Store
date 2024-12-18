<?php
session_start();

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    // Update cart quantity
    if (isset($_POST['update_quantity'])) {
        foreach ($_POST['quantity'] as $productId => $quantity) {
            if ($quantity > 0) {
                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId]['quantity'] = $quantity;
                }
            } else {
                unset($_SESSION['cart'][$productId]);
            }
        }
        $_SESSION['success_message'] = "Cart updated successfully.";
        header("Location: ../views/cart.php");
        exit;
    }

    // Remove item from cart
    if (isset($_POST['remove_from_cart'])) {
        $productId = $_POST['remove_from_cart']; // Get the productId from the button's value
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['success_message'] = "Item removed from cart.";
        }
        header("Location: ../views/cart.php");
        exit;
    }

    // Add item to cart (assuming product details are fetched from a database)
    if (isset($_POST['add_to_cart'])) {
        $productId = $_POST['product_id'];
        $productName = $_POST['product_name'];
        $productPrice = $_POST['product_price'];
        $quantity = $_POST['quantity'];

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = [
                'name' => $productName,
                'price' => $productPrice,
                'quantity' => $quantity
            ];
        }
        $_SESSION['success_message'] = "Item added to cart.";
        header("Location: ../views/cart.php");
        exit;
    }
}
?>
