<?php
// Start the session
session_start();

// Include the database connection
require_once '../config/db.php';

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid order!";
    exit();
}

$order_id = $_GET['id']; // Get order ID from the URL

// Fetch order details from the 'orders' table
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id");
$stmt->bindParam(':order_id', $order_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// If the order is not found, display an error
if (!$order) {
    echo "Invalid order!";
    exit();
}

// Fetch order items from the 'order_details' table
$stmt = $conn->prepare("SELECT od.quantity, p.name, p.price FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = :order_id");
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" role="alert">
                <h4><?= $_SESSION['success_message']; ?></h4>
            </div>
            <?php unset($_SESSION['success_message']); // Clear the success message after displaying ?>
        <?php endif; ?>

        <h2>Order Confirmation</h2>

        <div class="alert alert-success" role="alert">
            <h4>Your order has been placed successfully!</h4>
            <p>Order ID: #<?= htmlspecialchars($order['id']); ?></p>
            <p>Status: <?= htmlspecialchars($order['status']); ?></p>
            <p>Name: <?= htmlspecialchars($order['name']); ?></p>
            <p>Address: <?= htmlspecialchars($order['address']); ?></p>
        </div>

        <h3>Order Details</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalAmount = 0;
                foreach ($orderItems as $item) {
                    $itemTotal = $item['price'] * $item['quantity'];
                    $totalAmount += $itemTotal;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']); ?></td>
                        <td>$<?= number_format($item['price'], 2); ?></td>
                        <td><?= htmlspecialchars($item['quantity']); ?></td>
                        <td>$<?= number_format($itemTotal, 2); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <h4>Total Amount: $<?= number_format($totalAmount, 2); ?></h4>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
