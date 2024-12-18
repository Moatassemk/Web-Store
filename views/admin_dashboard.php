<?php
require_once '../config/db.php'; // Correct path to the DB

// Fetch total number of products
$stmtProducts = $conn->prepare("SELECT COUNT(*) FROM products");
$stmtProducts->execute();
$totalProducts = $stmtProducts->fetchColumn();

// Fetch total number of orders
$stmtOrders = $conn->prepare("SELECT COUNT(*) FROM orders");
$stmtOrders->execute();
$totalOrders = $stmtOrders->fetchColumn();

// Fetch total number of users
$stmtUsers = $conn->prepare("SELECT COUNT(*) FROM users");
$stmtUsers->execute();
$totalUsers = $stmtUsers->fetchColumn();

// Fetch top customers (users who have spent the most)
$stmtTopCustomers = $conn->prepare("
    SELECT u.id, u.name, SUM(o.total_price) as total_spent
    FROM users u
    JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
");
$stmtTopCustomers->execute();
$topCustomers = $stmtTopCustomers->fetchAll(PDO::FETCH_ASSOC);

// Handle order status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$query = "SELECT id, user_id, order_date, total_price, status FROM orders";
if ($statusFilter) {
    $query .= " WHERE status = :status";
}
$query .= " ORDER BY order_date DESC LIMIT 5";

$stmtRecentOrders = $conn->prepare($query);
if ($statusFilter) {
    $stmtRecentOrders->bindParam(':status', $statusFilter);
}
$stmtRecentOrders->execute();
$recentOrders = $stmtRecentOrders->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1>Admin Dashboard</h1>

        <!-- Dashboard Overview -->
        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Products</h5>
                        <p class="card-text"><?= number_format($totalProducts); ?> products</p>
                        <a href="products.php" class="btn btn-light">View Products</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <p class="card-text"><?= number_format($totalOrders); ?> orders</p>
                        <a href="order_history.php" class="btn btn-light">View Orders</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text"><?= number_format($totalUsers); ?> users</p>
                        <a href="user_list.php" class="btn btn-light">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Product Button -->
        <div class="mt-4">
            <a href="add_product.php" class="btn btn-primary">Add New Product</a>
        </div>
        
        <!-- Manage Products Button -->
        <div class="mt-4">
            <a href="manage_products.php" class="btn btn-primary">Manage Products</a>
        </div>


        <!-- Filter Orders by Status -->
        <form method="GET">
            <div class="mb-3">
                <label for="statusFilter" class="form-label">Filter by Status</label>
                <select class="form-select" id="statusFilter" name="status">
                    <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="shipped" <?php echo isset($_GET['status']) && $_GET['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <!-- Recent Orders -->
        <h3 class="mt-4">Recent Orders</h3>
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
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['id']); ?></td>
                        <td><?= htmlspecialchars($order['user_id']); ?></td>
                        <td><?= htmlspecialchars($order['order_date']); ?></td>
                        <td>$<?= number_format($order['total_price'], 2); ?></td>
                        <td><?= ucfirst($order['status']); ?></td>
                        <td>
                            <a href="order_details.php?id=<?= $order['id']; ?>" class="btn btn-info">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Top Customers -->
        <div class="card mt-5">
            <div class="card-header">Top Customers</div>
            <div class="card-body">
                <ul>
                    <?php foreach ($topCustomers as $customer): ?>
                        <li><?= htmlspecialchars($customer['name']); ?> - $<?= number_format($customer['total_spent'], 2); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Sales Analytics -->
        <div class="card mt-5">
            <div class="card-header">Sales Analytics</div>
            <div class="card-body">
                <canvas id="salesChart" width="400" height="200"></canvas>
                <script>
                    const ctx = document.getElementById('salesChart').getContext('2d');
                    const salesChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['January', 'February', 'March', 'April'], // Example months
                            datasets: [{
                                label: 'Total Sales',
                                data: [1200, 1500, 1000, 1800], // Example sales data
                                borderColor: 'rgba(75, 192, 192, 1)',
                                fill: false
                            }]
                        }
                    });
                </script>
            </div>
        </div>

    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
