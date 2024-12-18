<?php
require_once '../config/db.php'; // Correct path to the DB

// Fetch the user ID from the query string
if (!isset($_GET['id'])) {
    die("User ID not provided");
}

$userId = $_GET['id'];

// Delete the user from the database
$stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);

// Redirect after deletion
header("Location: user_list.php");
exit();
?>
