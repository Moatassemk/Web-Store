<?php
session_start();
require('../config/db.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Add product to wishlist
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $productId = $_GET['add'];

    // Check if the product exists
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if ($product) {
        // Check if the product is already in the wishlist
        $checkStmt = $conn->prepare("SELECT * FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
        $checkStmt->execute([':user_id' => $userId, ':product_id' => $productId]);

        if (!$checkStmt->fetch()) {
            // Add to wishlist
            $insertStmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)");
            $insertStmt->execute([':user_id' => $userId, ':product_id' => $productId]);
        }
    }
    header("Location: wishlist.php");
    exit();
}

// Remove product from wishlist
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $productId = $_GET['remove'];
    $deleteStmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
    $deleteStmt->execute([':user_id' => $userId, ':product_id' => $productId]);
    header("Location: wishlist.php");
    exit();
}

// Fetch wishlist products
$stmt = $conn->prepare("SELECT p.* FROM products p INNER JOIN wishlist w ON p.id = w.product_id WHERE w.user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - PlatformName</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include('../includes/navbar.php'); ?>

    <div class="container mt-5">
        <h2>My Wishlist</h2>
        <?php if (count($wishlistItems) > 0): ?>
            <div class="row">
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="card-text">$<?php echo number_format($item['price'], 2); ?></p>
                                <a href="wishlist.php?remove=<?php echo $item['id']; ?>" class="btn btn-danger">Remove</a>
                                <a href="product.php?id=<?php echo $item['id']; ?>" class="btn btn-primary">View Product</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No items in your wishlist.</p>
        <?php endif; ?>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
