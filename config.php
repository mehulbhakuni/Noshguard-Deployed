<?php
// Your existing database connection
$host = "sql12.freesqldatabase.com"; // Example, use your actual host
$user = "sql12776018";           // Example, use your actual user
$password = "BBVZweFA6r";        // Example, use your actual password
$db = "sql12776018";             // Example, use your actual database

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    // For production, you might want to log this error and show a generic message
    // error_log("Database Connection Failed: " . $conn->connect_error);
    die("Sorry, we're having some technical difficulties (DB). Please try again later.");
}

// Define Base URL for easier asset linking and redirects
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host_name = $_SERVER['HTTP_HOST'];
    // Determine the script path more robustly
    $script_path = '';
    if (!empty($_SERVER['SCRIPT_NAME'])) {
        $script_path = dirname($_SERVER['SCRIPT_NAME']);
        // Ensure it's not just a single slash if in root, or handle subdirectories
        $script_path = rtrim($script_path, '/\\'); // Remove trailing slash
        if ($script_path === '' || $script_path === DIRECTORY_SEPARATOR) {
            $script_path = ''; // If in root, path is empty
        }
    }
    define('BASE_URL', $protocol . $host_name . $script_path . '/');
}


// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- MISTRAL API CONFIGURATION AND HELPERS ---

if (!defined('MISTRAL_API_KEY_PHP')) { // Using a distinct name
    // User's provided Mistral API Key
    define('MISTRAL_API_KEY_PHP', 'aCHLMXr7v1Ojf2lniUNkjftHGnzxLoDp'); 
}

if (!defined('MISTRAL_API_CHAT_URL')) {
    define('MISTRAL_API_CHAT_URL', 'https://api.mistral.ai/v1/chat/completions');
}
if (!defined('MISTRAL_MODEL_PHP')) { // Using a distinct name
    define('MISTRAL_MODEL_PHP', 'mistral-small-latest'); // Or your preferred model
}

// Function to send JSON response
if (!function_exists('send_json_response_php')) { // Using a distinct name
    function send_json_response_php($data, $statusCode = 200) {
        http_response_code($statusCode);
        if (!headers_sent()) {
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *'); 
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); 
            header('Access-Control-Allow-Headers: Content-Type, Authorization'); 
        }
        echo json_encode($data);
        exit; 
    }
}

// Handle OPTIONS request for CORS preflight
// This is useful if your JavaScript ever makes requests that trigger a preflight.
// For same-origin requests (scan.php calling api_ocr.php on the same domain),
// preflight is usually not needed unless custom headers are involved.
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    send_json_response_php(['message' => 'CORS preflight OK'], 200);
}


// Function to make cURL requests to Mistral API
if (!function_exists('call_mistral_api_php')) { // Using a distinct name
    function call_mistral_api_php($payloadJson) {
        if (!defined('MISTRAL_API_KEY_PHP') || empty(MISTRAL_API_KEY_PHP)) {
            error_log("Mistral API Key not defined or is empty in config.php");
            return ['error' => 'API key not configured on the server.', 'httpcode' => 500, 'data' => null];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, MISTRAL_API_CHAT_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . MISTRAL_API_KEY_PHP,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); 

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error_num = curl_errno($ch);
        $curl_error_msg = curl_error($ch);
        curl_close($ch);

        if ($curl_error_num) {
            error_log("PHP cURL Error to Mistral API ($curl_error_num): $curl_error_msg. URL: " . MISTRAL_API_CHAT_URL);
            return ['error' => "Failed to communicate with the AI service (cURL: $curl_error_msg)", 'httpcode' => 0, 'data' => null];
        }

        $responseData = json_decode($response, true);
        $json_decode_error = json_last_error();

        if ($httpcode >= 400 || $json_decode_error !== JSON_ERROR_NONE) {
             error_log("Mistral API Error (PHP) ($httpcode). URL: " . MISTRAL_API_CHAT_URL . ". Payload: " . $payloadJson . ". Response: " . $response . ". JSON Decode Error: " . json_last_error_msg());
             
             $apiErrorMsg = 'An error occurred with the AI service or the response was invalid.';
             if ($json_decode_error !== JSON_ERROR_NONE) {
                 $apiErrorMsg = 'Invalid response format from AI service.';
             } elseif (isset($responseData['message'])) { // General message
                 $apiErrorMsg = $responseData['message'];
             } elseif (isset($responseData['error']['message'])) { // Mistral-like error structure
                 $apiErrorMsg = $responseData['error']['message'];
             }
             
             return [
                'error' => "AI Service Error ($httpcode): " . $apiErrorMsg,
                'httpcode' => $httpcode,
                'data' => $responseData ?: ['raw_response' => $response] 
            ];
        }
        return ['error' => null, 'httpcode' => $httpcode, 'data' => $responseData];
    }
}
?>
