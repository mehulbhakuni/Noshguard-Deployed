<?php
require_once 'config.php'; // Includes MISTRAL_API_KEY_PHP, MISTRAL_MODEL_PHP, call_mistral_api_php, send_json_response_php

// This script expects a POST request with an 'image' file upload.

// The OPTIONS check is now in config.php, which is included first.
// If config.php is not included at the very top by some other means,
// you might need the OPTIONS check here too. But assuming config.php runs first.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response_php(["error" => "Invalid request method. Only POST is allowed."], 405);
}

try {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the server's maximum file size limit.",
            UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the form's maximum file size limit.",
            UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder on the server.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk on the server.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
        ];
        $errorCode = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
        $errorMessage = $uploadErrors[$errorCode] ?? "Unknown image upload error.";
        send_json_response_php(["error" => "Image upload error: " . $errorMessage], 400);
    }

    $file = $_FILES["image"];
    $fileTmpName = $file["tmp_name"];

    // Determine MIME type
    $fileMimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $fileMimeType = finfo_file($finfo, $fileTmpName);
            finfo_close($finfo);
        }
    }
    if (empty($fileMimeType) && !empty($file['type'])) { // Fallback to browser-sent type
        $fileMimeType = $file['type'];
    }
    if (empty($fileMimeType)) { // Absolute fallback
        $fileMimeType = 'application/octet-stream';
    }


    $imageData = file_get_contents($fileTmpName);
    if ($imageData === false) {
        send_json_response_php(["error" => "Could not read uploaded image file."], 500);
    }
    $encodedImageString = base64_encode($imageData);
    // Temporary file ($fileTmpName) is automatically cleaned up by PHP after script execution.

    $base64DataUrl = "data:" . $fileMimeType . ";base64," . $encodedImageString;

    $messagesForOcr = [
        [
            "role" => "user",
            "content" => [
                ["type" => "image_url", "image_url" => ["url" => $base64DataUrl]],
                ["type" => "text", "text" => "Extract all text from this image. Present it clearly and concisely."]
            ]
        ]
    ];

    $payload = [
        "model"    => MISTRAL_MODEL_PHP, // From config.php
        "messages" => $messagesForOcr
    ];
    $payloadJson = json_encode($payload);

    $apiResult = call_mistral_api_php($payloadJson); // Function from config.php

    if ($apiResult['error']) {
        send_json_response_php(["error" => $apiResult['error']], $apiResult['httpcode'] ?: 500);
    }

    $ocrResponseData = $apiResult['data'];

    if (!isset($ocrResponseData['choices'][0]['message']['content'])) {
        error_log("Mistral OCR API response (PHP) missing expected content: " . json_encode($ocrResponseData));
        send_json_response_php(["error" => "OCR could not extract text or received an invalid response structure from AI."], 400);
    }

    $extractedText = trim($ocrResponseData['choices'][0]['message']['content']);

    // Return 200 OK even if no text is found, but indicate it in the message
    if (empty($extractedText) && $extractedText !== "") { 
         send_json_response_php(["ocr_text" => "", "message" => "OCR process completed, but no text was extracted from the image."], 200);
    } else {
        send_json_response_php(["ocr_text" => $extractedText], 200);
    }

} catch (Exception $e) {
    error_log("Unexpected PHP OCR API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    send_json_response_php(["error" => "An unexpected error occurred during OCR processing: " . $e->getMessage()], 500);
}
?>
