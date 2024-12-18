<?php
// Start the session
session_start();

// Include the database connection and security functions
require_once '../config/db.php';
require_once '../includes/security_functions.php';

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Ensure the user is logged in

// Use session cart instead of database cart
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Check if the cart is empty
if (empty($cartItems)) {
    $_SESSION['error_message'] = "Your cart is empty.";
    header("Location: cart.php");
    exit();
}

// Shipping cost calculation function
function calculateShippingCost($method, $total) {
    switch ($method) {
        case 'Standard':
            return 5.00;
        case 'Express':
            return 15.00;
        case 'Next Day':
            return 25.00;
        default:
            return 0;
    }
}

// Handle form submission for the checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Token Validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    // Sanitize and validate inputs
    $shipping_name = filter_input(INPUT_POST, 'shipping_name', FILTER_SANITIZE_STRING);
    $shipping_address = filter_input(INPUT_POST, 'shipping_address', FILTER_SANITIZE_STRING);
    $shipping_city = filter_input(INPUT_POST, 'shipping_city', FILTER_SANITIZE_STRING);
    $shipping_zip = filter_input(INPUT_POST, 'shipping_zip', FILTER_SANITIZE_STRING);
    $shipping_country = filter_input(INPUT_POST, 'shipping_country', FILTER_SANITIZE_STRING);
    $shipping_method = filter_input(INPUT_POST, 'shipping_method', FILTER_SANITIZE_STRING);
    $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);

    // Validate required fields
    $errors = [];
    if (empty($shipping_name)) $errors[] = "Shipping Name is required";
    if (empty($shipping_address)) $errors[] = "Shipping Address is required";
    if (empty($shipping_city)) $errors[] = "Shipping City is required";
    if (empty($shipping_zip)) $errors[] = "Shipping ZIP is required";
    if (empty($shipping_country)) $errors[] = "Shipping Country is required";
    if (empty($shipping_method)) $errors[] = "Shipping Method is required";
    if (empty($phone_number)) $errors[] = "Phone Number is required";

    // If no errors, proceed with order
    if (empty($errors)) {
        try {
            // Start a database transaction
            $conn->beginTransaction();

            // Calculate total and shipping cost
            $total = 0;
            foreach ($cartItems as $productId => $item) {
                $total += $item['price'] * $item['quantity'];
            }
            $shipping_cost = calculateShippingCost($shipping_method, $total);
            $final_total = $total + $shipping_cost;

            // Insert order into the 'orders' table
            $stmt = $conn->prepare("INSERT INTO orders (user_id, status, total_amount, shipping_cost) VALUES (:user_id, 'Pending', :total_amount, :shipping_cost)");
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':total_amount', $final_total);
            $stmt->bindParam(':shipping_cost', $shipping_cost);
            $stmt->execute();

            // Get the inserted order ID
            $orderId = $conn->lastInsertId();

            // Insert shipping details into the 'shipping' table
            $stmt = $conn->prepare("INSERT INTO shipping (order_id, shipping_name, shipping_address, shipping_city, shipping_zip, shipping_country, shipping_method, shipping_status, phone_number) 
                                    VALUES (:order_id, :shipping_name, :shipping_address, :shipping_city, :shipping_zip, :shipping_country, :shipping_method, 'Pending', :phone_number)");
            $stmt->bindParam(':order_id', $orderId);
            $stmt->bindParam(':shipping_name', $shipping_name);
            $stmt->bindParam(':shipping_address', $shipping_address);
            $stmt->bindParam(':shipping_city', $shipping_city);
            $stmt->bindParam(':shipping_zip', $shipping_zip);
            $stmt->bindParam(':shipping_country', $shipping_country);
            $stmt->bindParam(':shipping_method', $shipping_method);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->execute();

            // Loop through cart items and add them to the order details
            foreach ($cartItems as $productId => $item) {
                // Validate inventory before processing order
                $stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = :product_id");
                $stmt->bindParam(':product_id', $productId);
                $stmt->execute();
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product['stock'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: " . $item['name']);
                }

                // Insert the order details for each product in the cart
                $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");
                $stmt->bindParam(':order_id', $orderId);
                $stmt->bindParam(':product_id', $productId);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':price', $item['price']);
                $stmt->execute();

                // Update product inventory
                $stmt = $conn->prepare("UPDATE products SET stock = stock - :quantity WHERE product_id = :product_id");
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':product_id', $productId);
                $stmt->execute();
            }

            // Commit the transaction
            $conn->commit();

            // Clear the cart after successful checkout
            unset($_SESSION['cart']);

            // Redirect to the order confirmation page
            $_SESSION['success_message'] = "Order placed successfully!";
            header("Location: order_confirmation.php?id=$orderId");
            exit;

        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $conn->rollBack();
            $_SESSION['error_message'] = "Order processing failed: " . $e->getMessage();
            header("Location: checkout.php");
            exit;
        }
    } else {
        // Store errors in session to display on the page
        $_SESSION['form_errors'] = $errors;
    }
}

// Prepare cart items for display
$processedCartItems = [];
$total = 0;
foreach ($cartItems as $productId => $item) {
    $itemTotal = $item['price'] * $item['quantity'];
    $total += $itemTotal;
    $processedCartItems[] = [
        'product_id' => $productId,
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'total' => $itemTotal
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .progress { height: 25px; }
        .progress-bar { width: 60%; }
        .order-summary {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .order-summary h5 { margin-top: 0; }
        .error-message { color: red; }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <!-- Error Messages -->
        <?php if (isset($_SESSION['form_errors'])): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($_SESSION['form_errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['form_errors']); ?>
        <?php endif; ?>

        <h2>Checkout</h2>

        <!-- Progress Bar -->
        <div class="progress mb-4">
            <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">Step 2/3</div>
        </div>

        <!-- Checkout Form -->
        <form method="POST" id="checkoutForm" onsubmit="return validateForm()">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Shipping Information -->
            <h3>Shipping Information</h3>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="shipping_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="shipping_name" name="shipping_name" required 
                           pattern="[A-Za-z\s]+" title="Letters and spaces only" 
                           aria-describedby="nameHelp">
                    <small id="nameHelp" class="form-text text-muted">Enter your full name as it appears on identification</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone_number" name="phone_number" required 
                           pattern="[0-9]{10}" title="10 digit phone number" 
                           aria-describedby="phoneHelp">
                    <small id="phoneHelp" class="form-text text-muted">10 digit phone number without spaces or dashes</small>
                </div>
            </div>

            <div class="mb-3">
                <label for="shipping_address" class="form-label">Shipping Address</label>
                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="shipping_city" class="form-label">City</label>
                    <input type="text" class="form-control" id="shipping_city" name="shipping_city" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="shipping_zip" class="form-label">ZIP/Postal Code</label>
                    <input type="text" class="form-control" id="shipping_zip" name="shipping_zip" required 
                           pattern="\d{5}(-\d{4})?" title="5 digit ZIP code">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="shipping_country" class="form-label">Country</label>
                    <select class="form-control" id="shipping_country" name="shipping_country" required>
                        <option value="">Select Country</option>
                        <option value="USA">United States</option>
                        <option value="Canada">Canada</option>
                        <option value="UK">United Kingdom</option>
                        <option value="Australia">Australia</option>
                        <option value="Germany">Germany</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="shipping_method" class="form-label">Shipping Method</label>
                    <select class="form-control" id="shipping_method" name="shipping_method" required>
                        <option value="">Select Shipping Method</option>
                        <option value="Standard">Standard Shipping (5-7 business days)</option>
                        <option value="Express">Express Shipping (2-3 business days)</option>
                        <option value="Next Day">Next Day Delivery</option>
                    </select>
                </div>
            </div>

            <!-- Cart Information -->
            <h3 class="mt-4">Order Details</h3>
            <div class="table-responsive">
                <table class="table" id="cartTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processedCartItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']); ?></td>
                                <td>$<?= number_format($item['price'], 2); ?></td>
                                <td><?= $item['quantity']; ?></td>
                                <td>$<?= number_format($item['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Order Summary -->
            <div class="order-summary mt-4">
                <h5>Order Summary</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Subtotal:</strong> $<?= number_format($total, 2); ?></p>
                        <p><strong>Tax (10%):</strong> $<?= number_format($total * 0.1, 2); ?></p>
                        <p><strong id="shippingDisplay">Shipping Cost:</strong> 
                            <span id="shippingCostSummary">$0.00</span>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h4><strong>Total:</strong> 
                            <span id="finalTotal">$<?= number_format($total * 1.1, 2); ?></span>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg" id="placeOrderButton">
                    Complete Order
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update shipping cost and final total based on selected shipping method
            const shippingMethodSelect = document.getElementById('shipping_method');
            const shippingCostDisplay = document.getElementById('shippingCostSummary');
            const finalTotalDisplay = document.getElementById('finalTotal');
            const placeOrderButton = document.getElementById('placeOrderButton');

            // Function to update shipping cost and final total
            function updateShippingAndTotal() {
                const shippingMethod = shippingMethodSelect.value;
                let shippingCost = 0;
                let subtotal = <?= json_encode($total); ?>;
                
                if (shippingMethod === 'Standard') {
                    shippingCost = 5.00;
                } else if (shippingMethod === 'Express') {
                    shippingCost = 15.00;
                } else if (shippingMethod === 'Next Day') {
                    shippingCost = 25.00;
                }

                const tax = subtotal * 0.1;
                const finalTotal = subtotal + shippingCost + tax;

                // Display updated values
                shippingCostDisplay.textContent = `$${shippingCost.toFixed(2)}`;
                finalTotalDisplay.textContent = `$${finalTotal.toFixed(2)}`;
            }

            // Listen for changes in shipping method and update the summary
            shippingMethodSelect.addEventListener('change', updateShippingAndTotal);

            // Initialize the form with the default shipping method's cost
            updateShippingAndTotal();
        });

        // Optional: Validate form on submit
        function validateForm(event) {
            const shippingName = document.getElementById('shipping_name').value.trim();
            const phoneNumber = document.getElementById('phone_number').value.trim();
            const shippingAddress = document.getElementById('shipping_address').value.trim();
            const shippingCity = document.getElementById('shipping_city').value.trim();
            const shippingZip = document.getElementById('shipping_zip').value.trim();
            const shippingCountry = document.getElementById('shipping_country').value.trim();
            const shippingMethod = document.getElementById('shipping_method').value.trim();

            if (!shippingName || !phoneNumber || !shippingAddress || !shippingCity || !shippingZip || !shippingCountry || !shippingMethod) {
                alert("All fields are required!");
                return false;  // Prevent form submission if validation fails
            }

            // If validation passes, allow the form to submit
            return true;
        }

    </script>
</body>
</html>
