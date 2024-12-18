<?php
require '../config/db.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate order ID
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Check if order ID is a valid positive integer
if (!$orderId || $orderId <= 0) {
    // Invalid order ID format
    $_SESSION['error_message'] = "Invalid order ID.";
    header("Location: order_history.php");
    exit();
}

try {
    // Verify order existence and user ownership
    $stmtVerify = $conn->prepare("
        SELECT 
            o.id, 
            o.status, 
            o.total_price, 
            o.shipping_address,
            o.created_at
        FROM orders o
        WHERE o.id = :order_id AND o.user_id = :user_id
    ");
    $stmtVerify->execute([
        ':order_id' => $orderId,
        ':user_id' => $_SESSION['user_id']
    ]);
    $orderInfo = $stmtVerify->fetch(PDO::FETCH_ASSOC);

    // Check if order exists and belongs to the user
    if (!$orderInfo) {
        // Order not found or user doesn't have permission
        $_SESSION['error_message'] = "Order not found or you do not have permission to view this order.";
        header("Location: order_history.php");
        exit();
    }

    // Fetch order items
    $stmtItems = $conn->prepare("
        SELECT 
            oi.quantity, 
            oi.price, 
            p.name AS product_name,
            p.id AS product_id
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id
    ");
    $stmtItems->execute([':order_id' => $orderId]);
    $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Ensure order has items
    if (empty($orderItems)) {
        $_SESSION['error_message'] = "No items found for this order.";
        header("Location: order_history.php");
        exit();
    }

} catch(PDOException $e) {
    // Log the error
    error_log("Order details error: " . $e->getMessage());
    
    // Generic error message for user
    $_SESSION['error_message'] = "An error occurred while retrieving order details.";
    header("Location: order_history.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <?php 
        // Display any error messages
        if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']); 
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Order Details</h2>

                <!-- Order Info Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title m-0">Order #<?php echo htmlspecialchars($orderInfo['id']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Order Date:</strong> 
                                    <?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($orderInfo['created_at']))); ?>
                                </p>
                                <p><strong>Status:</strong> 
                                    <?php echo htmlspecialchars($orderInfo['status']); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total Amount:</strong> 
                                    $<?php echo number_format($orderInfo['total_price'], 2); ?>
                                </p>
                                <p><strong>Shipping Address:</strong> 
                                    <?php echo htmlspecialchars($orderInfo['shipping_address'] ?? 'No address provided'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items Table -->
                <h4 class="mt-4 mb-3">Items in this Order</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalItemsPrice = 0;
                            foreach ($orderItems as $item): 
                                $itemTotal = $item['price'] * $item['quantity'];
                                $totalItemsPrice += $itemTotal;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>$<?php echo number_format($itemTotal, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <a href="order_history.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Order History
                    </a>
                    <div class="text-end">
                        <strong>Order Total: $<?php echo number_format($totalItemsPrice, 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>