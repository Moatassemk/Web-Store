<?php
session_start();
require('../config/db.php');  // Correct path to db.php

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];  // Store user's name in the session
        
        // Redirect based on role
        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");  // Redirect to admin dashboard if admin
        } else {
            header("Location: user_dashboard.php");  // Redirect to user dashboard if user
        }
        exit();
    } else {
        $error = "Invalid credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include('../includes/navbar.php'); ?>  <!-- Correct path to navbar.php in includes folder -->

    <div class="container mt-5">
        <h2>Login</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="mt-3">
            <p>Don't have an account? <a href="register.php" class="btn btn-link">Register here</a></p> <!-- Register link -->
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>  <!-- Correct path to footer.php in includes folder -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
