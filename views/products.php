<?php
require_once('../config/db.php');

// Get the current page number, default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Number of products per page
$offset = ($page - 1) * $limit;

// Get the search term, if any
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Build the query with filtering and pagination
$sql = "SELECT * FROM products WHERE name LIKE :search";
if ($category) {
    $sql .= " AND category = :category";
}
$sql .= " LIMIT :limit OFFSET :offset";

// Prepare the query
$stmt = $conn->prepare($sql);

// Bind the parameters
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR); // Bind the search term with wildcards
if ($category) {
    $stmt->bindValue(':category', $category, PDO::PARAM_STR); // Bind the category
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); // Bind the limit
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT); // Bind the offset

// Execute the query
$stmt->execute();

// Fetch the products
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the total number of products for pagination
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE name LIKE :search");
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$stmt->execute();
$totalProducts = $stmt->fetchColumn();

$totalPages = ceil($totalProducts / $limit);

// Include the header and navbar
require_once('../includes/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Products</h2>

        <!-- Search Bar -->
        <form method="GET" action="products.php" class="mb-4">
            <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary mt-3">Search</button>
        </form>

        <!-- Category Filter -->
        <form method="GET" action="products.php" class="mb-4">
            <select name="category" class="form-control">
                <option value="">All Categories</option>
                <option value="Electronics" <?php echo ($category == 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                <option value="Clothing" <?php echo ($category == 'Clothing') ? 'selected' : ''; ?>>Clothing</option>
                <option value="Books" <?php echo ($category == 'Books') ? 'selected' : ''; ?>>Books</option>
                <!-- Add more categories as needed -->
            </select>
            <button type="submit" class="btn btn-primary mt-3">Filter</button>
        </form>

        <!-- Product Grid -->
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <img src="../uploads/<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text">$<?php echo number_format($product['price'], 2); ?></p>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Product</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="products.php?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>&category=<?php echo htmlspecialchars($category); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
