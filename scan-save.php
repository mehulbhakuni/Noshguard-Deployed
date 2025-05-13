<?php
include("config.php"); // Session is started in config.php

if (!isset($_SESSION["user"])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$ocr_text = trim($_POST["ocr_text"] ?? "");
$ai_advice = trim($_POST["ai_advice"] ?? "");
$decision = trim($_POST["decision"] ?? "");
$user_id = $_SESSION["user"]["id"] ?? 0;

if (empty($ocr_text)) {
    $_SESSION['scan_error'] = 'Scan failed or no text was detected. Please try again.';
    header("Location: " . BASE_URL . "scan.php");
    exit();
}
if (empty($ai_advice)) {
    $_SESSION['scan_error'] = 'AI analysis was not available. Please try again.';
    header("Location: " . BASE_URL . "scan.php");
    exit();
}
if (empty($decision) || !in_array(strtolower($decision), ['yes', 'no'])) {
     $_SESSION['scan_error'] = 'Invalid decision submitted. Please make a choice.';
     header("Location: " . BASE_URL . "scan.php");
    exit();
}
if ($user_id === 0) {
    $_SESSION['scan_error'] = 'User session error. Please log in again.';
    header("Location: " . BASE_URL . "login.php");
    exit();
}


// $risky_ingredients is not currently being determined or stored separately.
// For future enhancement, this could be extracted from $ai_advice or by another AI call.
$risky_ingredients_placeholder = "";

$stmt = $conn->prepare("INSERT INTO scans (user_id, ingredients, risky_ingredients, advice, decision, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");

if ($stmt) {
    $stmt->bind_param("issss", $user_id, $ocr_text, $risky_ingredients_placeholder, $ai_advice, $decision);

    if ($stmt->execute()) {
        $_SESSION['dashboard_msg'] = 'Scan and your decision have been saved successfully!';
        header("Location: " . BASE_URL . "dashboard.php");
        exit();
    } else {
        error_log("Database Error saving scan: " . $stmt->error . " | UID: $user_id");
        $_SESSION['scan_error'] = 'Failed to save scan results to the database. Please try again.';
        header("Location: " . BASE_URL . "scan.php");
        exit();
    }
     $stmt->close();
} else {
     error_log("Database Error preparing scan save statement: " . $conn->error);
     $_SESSION['scan_error'] = 'Database error. Failed to prepare for saving the scan.';
     header("Location: " . BASE_URL . "scan.php");
     exit();
}
?>