<?php
require_once '../config/db.php'; // Correct path to the DB

// Ensure the user is logged in and has admin rights
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all orders
$stmt = $conn->prepare("SELECT id, user_id, order_date, total_price, status FROM orders ORDER BY order_date DESC");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1>Manage Orders</h1>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php elseif (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Orders Table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>User ID</th>
                    <th>Order Date</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['id']); ?></td>
                        <td><?= htmlspecialchars($order['user_id']); ?></td>
                        <td><?= htmlspecialchars($order['order_date']); ?></td>
                        <td>$<?= number_format($order['total_price'], 2); ?></td>
                        <td>
                            <form action="update_order_status.php" method="POST">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
