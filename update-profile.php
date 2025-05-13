<?php
include("config.php"); // Session is started in config.php

if (!isset($_SESSION["user"]["id"])) {
    http_response_code(403); // Forbidden
    echo 'Error: Unauthorized access.';
    exit();
}

$user_id = $_SESSION["user"]["id"];
$new_name = trim($_POST["name"] ?? '');
$diseases = isset($_POST["diseases"]) && is_array($_POST["diseases"]) ? $_POST["diseases"] : [];
$disease_json = json_encode($diseases); // Always store as JSON, even if empty array "[]"

if (empty($new_name)) {
    http_response_code(400); // Bad Request
    echo 'Error: Name cannot be empty.';
    exit();
}
if (strlen($new_name) > 100) { // Example validation
    http_response_code(400);
    echo 'Error: Name is too long (max 100 characters).';
    exit();
}

$stmt = $conn->prepare("UPDATE users SET name = ?, diseases = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    error_log("Update profile prepare failed: " . $conn->error);
    echo 'Error: Database error preparing update.';
    exit();
}

$stmt->bind_param("ssi", $new_name, $disease_json, $user_id);

if ($stmt->execute()) {
    $_SESSION["user"]["name"] = $new_name;
    $_SESSION["user"]["diseases"] = $disease_json;

    http_response_code(200);
    echo "Profile updated successfully!"; // JS on dashboard.php will handle reload
    exit();
} else {
    http_response_code(500);
    error_log("Update profile execute failed: " . $stmt->error);
    echo 'Error: Failed to update profile in the database.';
    exit();
}

$stmt->close();
// $conn->close(); // Generally not needed if script ends, PHP handles it.
?>