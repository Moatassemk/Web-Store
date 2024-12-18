<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start the session only if it's not already started
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="../public/index.php">WeSHOP</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="../views/products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>" href="../views/cart.php">Cart</a>
                </li>

                <!-- Admin and User dropdown -->
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $_SESSION['user_name']; // Display user's name ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="../views/admin_dashboard.php">Admin Dashboard</a></li>
                                <li><a class="dropdown-item" href="../views/manage_orders.php">Manage Orders</a></li> <!-- Added link for managing orders -->
                                <li><a class="dropdown-item" href="../views/manage_products.php">Manage Products</a></li> <!-- Added link for managing products -->
                            <?php else: ?>
                                <li><a class="dropdown-item" href="../views/user_dashboard.php">User Dashboard</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="../views/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="../views/login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
