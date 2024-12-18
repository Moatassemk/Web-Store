<?php
session_start();
require_once '../config/db.php'; // Correct path to DB

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id']; // Get user ID from session

// Fetch the user's data from the database
$stmt = $conn->prepare("SELECT id, name, email, role, registration_date FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If no user is found, show an error message
if (!$user) {
    echo "User not found!";
    exit();
}

// Handle profile update (optional)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the form was submitted
    $name = $_POST['name'];
    $email = $_POST['email'];

    // Update user data in the database
    $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':id' => $userId
    ]);

    // Redirect after updating profile
    header("Location: user_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1>User Profile - <?= htmlspecialchars($user['name']); ?></h1>

        <!-- Profile details -->
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <input type="text" class="form-control" id="role" value="<?= htmlspecialchars($user['role']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="registration_date" class="form-label">Registration Date</label>
                        <input type="text" class="form-control" id="registration_date" value="<?= date("F j, Y", strtotime($user['registration_date'])); ?>" disabled>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>

        <a href="user_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
