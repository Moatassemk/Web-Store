<?php
session_start();
require('../config/db.php');  // Correct path to db.php

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

// Fetch last login time if available
$lastLogin = isset($user['last_login']) ? $user['last_login'] : 'Not available';

// Fetch the user's recent orders (optional)
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY order_date DESC LIMIT 5");
$orderStmt->execute([':user_id' => $userId]);
$orders = $orderStmt->fetchAll();

// Fetch user's wishlist items with product details
$wishlistStmt = $conn->prepare("SELECT products.id AS product_id, products.name AS product_name, products.image_url AS product_image, products.price 
                                FROM wishlist 
                                JOIN products ON wishlist.product_id = products.id 
                                WHERE wishlist.user_id = :user_id");
$wishlistStmt->execute([':user_id' => $userId]);
$wishlist = $wishlistStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-img {
            max-width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
        .order-card {
            margin-bottom: 10px;
        }
        .wishlist-img {
            max-height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include('../includes/navbar.php'); ?>  <!-- Correct path to navbar.php in includes folder -->

    <div class="container mt-5">
        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>

        <!-- Profile Section -->
        <div class="row mt-4">
            <div class="col-md-3">
                <?php if (isset($user['profile_picture']) && $user['profile_picture'] != ''): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-img">
                <?php else: ?>
                    <img src="https://via.placeholder.com/150" alt="Profile Picture" class="profile-img">
                <?php endif; ?>
            </div>
            <div class="col-md-9">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                <p><strong>Last Login:</strong> <?php echo htmlspecialchars($lastLogin); ?></p>

                <div class="mt-4">
                    <a href="user_orders.php" class="btn btn-secondary">View Orders</a>
                    <a href="user_profile.php" class="btn btn-secondary">Edit Profile</a>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>

        <!-- Recent Orders Section -->
        <div class="mt-5">
            <h3>Your Recent Orders</h3>
            <?php if (count($orders) > 0): ?>
                <div class="list-group">
                    <?php foreach ($orders as $order): ?>
                        <div class="list-group-item order-card">
                            <h5 class="mb-1">Order ID: <?php echo htmlspecialchars($order['id']); ?></h5>
                            <p class="mb-1">Total: $<?php echo number_format($order['total'], 2); ?></p>
                            <p class="mb-1">Status: <?php echo ucfirst($order['status']); ?></p>
                            <small>Order Date: <?php echo htmlspecialchars($order['order_date']); ?></small>
                            <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-link">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No recent orders to display.</p>
            <?php endif; ?>
        </div>

        <!-- Wishlist Section -->
        <div class="mt-5">
            <h3>Your Wishlist</h3>
            <?php if (count($wishlist) > 0): ?>
                <div class="row">
                    <?php foreach ($wishlist as $item): ?>
                        <div class="col-md-4">
                            <div class="card">
                                <img src="../uploads/<?php echo htmlspecialchars($item['product_image']); ?>" class="card-img-top wishlist-img" alt="Product Image">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['product_name']); ?></h5>
                                    <p class="card-text">$<?php echo number_format($item['price'], 2); ?></p>
                                    <a href="product.php?product_id=<?php echo $item['product_id']; ?>" class="btn btn-primary">View Product</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Your wishlist is empty.</p>
            <?php endif; ?>
        </div>

    </div>

    <?php include('../includes/footer.php'); ?>  <!-- Correct path to footer.php in includes folder -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
