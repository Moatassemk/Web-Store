<?php
require_once '../config/db.php'; // Correct path to the DB

// Fetch all users
$stmtUsers = $conn->prepare("SELECT id, name, email, registration_date FROM users");
$stmtUsers->execute();
$users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Pagination settings
$limit = 10; // Number of users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get the total number of users for pagination
$stmtCount = $conn->prepare("SELECT COUNT(*) FROM users");
$stmtCount->execute();
$totalUsers = $stmtCount->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Fetch users with pagination
$stmtPaginated = $conn->prepare("SELECT id, name, email, registration_date FROM users LIMIT :limit OFFSET :offset");
$stmtPaginated->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmtPaginated->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmtPaginated->execute();
$users = $stmtPaginated->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1>User List</h1>

        <!-- User Table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id']; ?></td>
                            <td><?= htmlspecialchars($user['name']); ?></td>
                            <td><?= htmlspecialchars($user['email']); ?></td>
                            <td><?= date("F j, Y", strtotime($user['registration_date'])); ?></td>
                            <td>
                                <a href="user_profile.php?id=<?= $user['id']; ?>" class="btn btn-info btn-sm">View</a>
                                <a href="edit_user.php?id=<?= $user['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_user.php?id=<?= $user['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <li class="page-item <?= $page == 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page == $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
