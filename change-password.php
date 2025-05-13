<?php
include("config.php"); // Session is started in config.php

if (!isset($_SESSION["user"]["id"])) {
    http_response_code(403);
    echo "Error: Unauthorized access.";
    exit();
}

$user_id = $_SESSION["user"]["id"];
$old_password = $_POST["old_password"] ?? "";
$new_password = $_POST["new_password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";

if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
     http_response_code(400);
     echo "Error: All password fields are required.";
     exit();
}

if ($new_password !== $confirm_password) {
     http_response_code(400);
    echo "Error: New passwords do not match.";
    exit();
}

if (strlen($new_password) < 6) {
     http_response_code(400);
     echo "Error: New password must be at least 6 characters long.";
     exit();
}
if ($new_password === $old_password) {
    http_response_code(400);
    echo "Error: New password cannot be the same as the old password.";
    exit();
}


$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
if (!$stmt) {
     http_response_code(500);
     error_log("Change pwd (select) prepare failed: " . $conn->error);
     echo "Error: Database error (Could not verify current password).";
     exit();
}

$stmt->bind_param("i", $user_id);
if(!$stmt->execute()){
    http_response_code(500);
    error_log("Change pwd (select) execute failed: " . $stmt->error);
    echo "Error: Database query error (Could not verify current password).";
    $stmt->close();
    exit();
}
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($old_password, $user["password"])) {
     http_response_code(400); // Use 400 or 401 depending on preference
    echo "Error: Current password provided is incorrect.";
    exit();
}

$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
if ($new_hash === false) {
    http_response_code(500);
    error_log("Password hashing failed for user ID: " . $user_id);
    echo "Error: Could not process new password due to a server error.";
    exit();
}

$update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
 if (!$update_stmt) {
     http_response_code(500);
     error_log("Change pwd (update) prepare failed: " . $conn->error);
     echo "Error: Database error during password update.";
     exit();
}

$update_stmt->bind_param("si", $new_hash, $user_id);

if ($update_stmt->execute()) {
    // Password changed successfully
    // Optionally, could destroy other sessions for this user for security
    echo "success"; // Signal success to AJAX
    exit();
} else {
    http_response_code(500);
     error_log("Change pwd (update) execute failed: " . $update_stmt->error);
    echo "Error: Failed to save the new password.";
    exit();
}
$update_stmt->close();
?>