<?php
// Function to generate a new CSRF token if it doesn't exist
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a random CSRF token
    }
}

// Function to validate the CSRF token
function validateCsrfToken($token) {
    // Compare the provided token with the session token
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
