<?php
session_start(); // Start the session at the very beginning

// Include the database connection
require_once('../config/db.php');

// Include the header and navbar
require_once('../includes/navbar.php');

// Fetch featured products
$stmt = $conn->prepare("SELECT * FROM products LIMIT 10");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories dynamically for filters
$categoryStmt = $conn->prepare("SELECT DISTINCT category FROM products");
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Handle Recently Viewed Products
$recentlyViewed = isset($_SESSION['recently_viewed']) ? $_SESSION['recently_viewed'] : [];

// Add lazy-loading class for images
$lazyLoadClass = "lazy-load";

// Handle Search Query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : null;
if ($searchQuery) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE :search LIMIT 10");
    $stmt->bindValue(':search', '%' . $searchQuery . '%');
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle category filter
if (isset($_GET['category'])) {
    $category = $_GET['category'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = :category LIMIT 10");
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .lazy-load {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .lazy-load.loaded {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="jumbotron bg-primary text-white text-center py-5 mb-5">
        <h1 class="display-4">Welcome to Product Management</h1>
        <p class="lead">Explore our amazing products and enjoy great deals!</p>
        <a href="../views/products.php" class="btn btn-light btn-lg">Shop Now</a>
    </div>

    <!-- Search and Filter Section -->
    <div class="container mb-4">
        <div class="row">
            <div class="col-md-8">
                <form method="GET" action="index.php" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search for products..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit" class="btn btn-outline-primary">Search</button>
                </form>
            </div>
            <div class="col-md-4">
                <select id="categoryFilter" class="form-select" onchange="filterByCategory(this.value)">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo (isset($category) && $category == $_GET['category']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Featured Products Section -->
    <div class="container">
        <h2 class="mb-4">Featured Products</h2>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <img data-src="../uploads/<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top <?php echo $lazyLoadClass; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <?php if (isset($product['discount']) && $product['discount'] > 0): ?>
                                <p class="card-text text-success">
                                    <del>$<?php echo number_format($product['price'], 2); ?></del>
                                    <strong>$<?php echo number_format($product['price'] - ($product['price'] * $product['discount'] / 100), 2); ?></strong>
                                    <span class="badge bg-danger">-<?php echo $product['discount']; ?>%</span>
                                </p>
                            <?php else: ?>
                                <p class="card-text">$<?php echo number_format($product['price'], 2); ?></p>
                            <?php endif; ?>

                            <!-- Add to Cart Form -->
                            <form method="POST" action="../controllers/cart_controller.php" class="d-inline">
                                <!-- CSRF Token -->
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                <!-- Product Data -->
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                <input type="number" name="quantity" value="1" min="1" class="form-control mb-2" style="max-width: 80px; display: inline-block;">

                                <button type="submit" name="add_to_cart" class="btn btn-success">Quick Add to Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php
                // Add to Recently Viewed Products
                $_SESSION['recently_viewed'][$product['id']] = $product;
                ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recently Viewed Products -->
    <?php if (!empty($recentlyViewed)): ?>
        <div class="container mt-5">
            <h2>Recently Viewed</h2>
            <div class="row">
                <?php foreach (array_reverse($recentlyViewed) as $product): ?>
                    <div class="col-md-3">
                        <div class="card mb-3">
                            <img src="../uploads/<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="card-body">
                                <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                <a href="../views/product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">View Product</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Customer Testimonials -->
    <div class="bg-light py-5 mt-5">
        <div class="container">
            <h2 class="text-center mb-4">What Our Customers Say</h2>
            <div class="row">
                <div class="col-md-4 text-center">
                    <blockquote class="blockquote">
                        <p>"Amazing service and fantastic products!"</p>
                        <footer class="blockquote-footer">John Doe</footer>
                    </blockquote>
                </div>
                <div class="col-md-4 text-center">
                    <blockquote class="blockquote">
                        <p>"I saved so much with their discounts. Highly recommend!"</p>
                        <footer class="blockquote-footer">Jane Smith</footer>
                    </blockquote>
                </div>
                <div class="col-md-4 text-center">
                    <blockquote class="blockquote">
                        <p>"Fast delivery and top-notch quality."</p>
                        <footer class="blockquote-footer">Mike Johnson</footer>
                    </blockquote>
                </div>
            </div>
        </div>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
    <!-- Call to Action Section -->
        <div class="jumbotron bg-dark text-white text-center py-5 mt-5">
            <h2>Join Our Community</h2>
            <p>Sign up today and get exclusive deals delivered to your inbox!</p>
            <a href="../views/register.php" class="btn btn-warning btn-lg">Register Now</a>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Lazy Load Images
        document.addEventListener('DOMContentLoaded', function () {
            const lazyImages = document.querySelectorAll('.lazy-load');
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            });
            lazyImages.forEach(img => observer.observe(img));
        });

        // Filter by Category
        function filterByCategory(category) {
            window.location.href = `index.php?category=${encodeURIComponent(category)}`;
        }
    </script>
</body>
</html>
