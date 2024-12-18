<?php
require '../config/db.php'; // Database connection

// Ensure the user is logged in and has admin rights
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get the new status and order ID from the form submission
$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

// Validate the inputs
if (!$orderId || !in_array($status, ['pending', 'completed', 'shipped', 'cancelled'])) {
    $_SESSION['error_message'] = "Invalid status or order ID.";
    header("Location: manage_orders.php");
    exit();
}

try {
    // Update the order status in the database
    $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
    $stmt->execute([
        ':status' => $status,
        ':order_id' => $orderId
    ]);

    $_SESSION['success_message'] = "Order status updated successfully.";
} catch (PDOException $e) {
    // Handle any database errors
    $_SESSION['error_message'] = "An error occurred while updating the order status.";
    error_log("Error updating order status: " . $e->getMessage());
}

header("Location: manage_orders.php"); // Redirect back to the orders page
exit();
