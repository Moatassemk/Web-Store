<?php
session_start();
require('../config/db.php');  // Correct path to db.php

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$orderStmt = $conn->prepare("SELECT orders.*, shipping.shipping_name, shipping.shipping_address, shipping.shipping_method, shipping.shipping_cost, shipping.shipping_status 
                             FROM orders 
                             LEFT JOIN shipping ON orders.id = shipping.order_id 
                             WHERE orders.user_id = :user_id 
                             ORDER BY orders.order_date DESC LIMIT 5");
$orderStmt->execute([':user_id' => $userId]);
$orders = $orderStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include('../includes/navbar.php'); ?>  <!-- Correct path to navbar.php in includes folder -->

    <div class="container mt-5">
        <h1>Your Orders</h1>

        <div class="list-group">
            <?php foreach ($orders as $order): ?>
                <div class="list-group-item">
                    <h5 class="mb-1">Order ID: <?php echo htmlspecialchars($order['id']); ?></h5>
                    <p class="mb-1">Total: $<?php echo number_format($order['total'], 2); ?></p>
                    <p class="mb-1">Status: <?php echo ucfirst($order['status']); ?></p>
                    <p class="mb-1">Shipping Name: <?php echo htmlspecialchars($order['shipping_name']); ?></p>
                    <p class="mb-1">Shipping Address: <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    <p class="mb-1">Shipping Method: <?php echo htmlspecialchars($order['shipping_method']); ?></p>
                    <p class="mb-1">Shipping Cost: $<?php echo number_format($order['shipping_cost'], 2); ?></p>
                    <p class="mb-1">Shipping Status: <?php echo ucfirst($order['shipping_status']); ?></p>
                    <small>Order Date: <?php echo htmlspecialchars($order['order_date']); ?></small>
                    <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-link">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>  <!-- Correct path to footer.php in includes folder -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
