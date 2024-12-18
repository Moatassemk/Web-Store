<?php
session_start();
require '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}

// Get the product ID from the URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId > 0) {
    // Fetch product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo "Product not found!";
        exit();
    }
} else {
    echo "Invalid product ID!";
    exit();
}

// Handle "Add to Wishlist" action
if (isset($_POST['add_to_wishlist'])) {
    $userId = $_SESSION['user_id'];

    // Check if the product is already in the wishlist
    $stmt = $conn->prepare("SELECT * FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
    
    if ($stmt->rowCount() == 0) {
        // Insert the product into the wishlist
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)");
        $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);

        $wishlistMessage = "Product added to your wishlist!";
    } else {
        $wishlistMessage = "This product is already in your wishlist!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<?php include '../includes/navbar.php'; ?>

<div class="container mt-5">
    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
    <div class="row">
        <div class="col-md-6">
            <img src="../uploads/<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="col-md-6">
            <h3>$<?php echo number_format($product['price'], 2); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <!-- Add to Cart Button -->
            <a href="cart.php?add=<?php echo $product['id']; ?>" class="btn btn-primary">Add to Cart</a>

            <!-- Add to Wishlist Button -->
            <form method="POST" action="" class="mt-2">
                <button type="submit" name="add_to_wishlist" class="btn btn-success">Add to Wishlist</button>
            </form>

            <!-- Wishlist Message -->
            <?php if (isset($wishlistMessage)): ?>
                <div class="alert alert-info mt-3"><?php echo $wishlistMessage; ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
