<?php
session_start();
require('../config/db.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch order history
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute([':user_id' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - PlatformName</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include('../includes/navbar.php'); ?>

    <div class="container mt-5">
        <h2>Your Order History</h2>
        <?php if (count($orders) > 0): ?>
            <div class="list-group">
                <?php foreach ($orders as $order): ?>
                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="list-group-item list-group-item-action">
                        Order ID: <?php echo $order['id']; ?> | Total: $<?php echo number_format($order['total_price'], 2); ?> | Date: <?php echo date('Y-m-d', strtotime($order['created_at'])); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>You haven't placed any orders yet.</p>
        <?php endif; ?>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
