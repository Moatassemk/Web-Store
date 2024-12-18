<?php
require_once '../config/db.php'; // Correct path to the DB
session_start();

// Ensure the user is an admin
if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all products
$stmt = $conn->prepare("SELECT * FROM products");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle product deletion
if (isset($_GET['delete_id'])) {
    $productId = $_GET['delete_id'];

    try {
        // Delete product from the database
        $stmtDelete = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmtDelete->bindParam(':id', $productId);
        $stmtDelete->execute();

        $success_message = "Product deleted successfully!";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle product status update (active/inactive)
if (isset($_GET['update_status_id']) && isset($_GET['status'])) {
    $productId = $_GET['update_status_id'];
    $newStatus = $_GET['status'];

    // Validate the status value before updating (to prevent invalid data)
    if (in_array($newStatus, ['active', 'inactive'])) {
        try {
            // Update product status
            $stmtStatus = $conn->prepare("UPDATE products SET status = :status WHERE id = :id");
            $stmtStatus->bindParam(':status', $newStatus);
            $stmtStatus->bindParam(':id', $productId);
            $stmtStatus->execute();

            $success_message = "Product status updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Invalid status provided!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h2>Manage Products</h2>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Product Table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['id']); ?></td>
                        <td><?= htmlspecialchars($product['name']); ?></td>
                        <td>$<?= number_format($product['price'], 2); ?></td>
                        <td>
                            <?php if ($product['status'] == 'active'): ?>
                                Active
                            <?php else: ?>
                                Inactive
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Delete Button -->
                            <a href="?delete_id=<?= $product['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>

                            <!-- Change Status Button -->
                            <?php if ($product['status'] == 'active'): ?>
                                <a href="?update_status_id=<?= $product['id']; ?>&status=inactive" class="btn btn-warning">Deactivate</a>
                            <?php else: ?>
                                <a href="?update_status_id=<?= $product['id']; ?>&status=active" class="btn btn-success">Activate</a>
                            <?php endif; ?>
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
