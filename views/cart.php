<?php
session_start(); // Start session at the very beginning

// Check if the cart is empty
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include('../includes/navbar.php'); ?>

    <div class="container mt-5">
        <h2>Your Shopping Cart</h2>

        <?php if (!empty($cart)): ?>
            <form method="POST" action="../controllers/cart_controller.php">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="cart-items">
                    <?php foreach ($cart as $productId => $item): ?>
                        <div class="cart-item mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div class="col-md-2">
                                    <!-- Quantity Input -->
                                    <input type="number" name="quantity[<?php echo $productId; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="form-control" style="width: 60px;">
                                </div>
                                <div class="col-md-2">
                                    <!-- Remove Item Button -->
                                    <button type="submit" name="remove_from_cart" value="<?php echo $productId; ?>" class="btn btn-danger">Remove</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Submit Button for Updating Cart -->
                    <button type="submit" name="update_quantity" class="btn btn-primary">Update Cart</button>
                </div>
            </form>
        <?php else: ?>
            <p>Your cart is empty!</p>
        <?php endif; ?>
        
        <hr>

        <!-- Display Total Price -->
        <?php
        $totalPrice = 0;
        foreach ($cart as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }
        ?>
        <h3>Total: $<?php echo number_format($totalPrice, 2); ?></h3>

        <!-- Proceed to Checkout Button -->
        <a href="../views/checkout.php" class="btn btn-success mt-3">Proceed to Checkout</a>
    </div>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
