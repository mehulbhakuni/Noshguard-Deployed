<?php
require_once 'config.php'; // Includes MISTRAL_API_KEY_PHP, MISTRAL_MODEL_PHP, call_mistral_api_php, send_json_response_php

// This script expects a POST request with a JSON body: {"ocr_text": "...", "user_diseases": ["..."]}

// The OPTIONS check is now in config.php, which is included first.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response_php(["error" => "Invalid request method. Only POST is allowed."], 405);
}

try {
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
        send_json_response_php(["error" => "Invalid JSON data provided: " . json_last_error_msg()], 400);
    }

    $ocrText = $data["ocr_text"] ?? ""; 
    $userDiseases = $data["user_diseases"] ?? [];

    if (!is_array($userDiseases)) {
        send_json_response_php(["error" => "'user_diseases' must be an array."], 400);
    }
    // Allow empty ocrText to be sent to the AI; the prompt can handle it.

    $diseasesString = !empty($userDiseases) ? implode(", ", array_map('htmlspecialchars', $userDiseases)) : "no specific medical conditions stated";

    $promptTextContent = <<<PROMPT
You are NoshGuard, a health-conscious food ingredient analyzer.
User's conditions: {$diseasesString}.
Scanned ingredients:
---
{$ocrText}
---
Analyze potential risks based *only* on the ingredients and conditions. If risks exist, explain simply. If none are obvious, state that. Conclude with a general suitability remark (e.g., "likely suitable," "caution advised," "may not be suitable").
**CRITICAL: Always state: 'This is not medical advice. Consult a doctor or nutritionist for personalized guidance.'**
Be concise (under 100 words). Do not use markdown formatting.
PROMPT;

    $messagesForAnalysis = [
        ["role" => "user", "content" => $promptTextContent]
    ];

    $payload = [
        "model"       => MISTRAL_MODEL_PHP, // From config.php
        "messages"    => $messagesForAnalysis,
        "temperature" => 0.2
    ];
    $payloadJson = json_encode($payload);

    $apiResult = call_mistral_api_php($payloadJson); // Function from config.php

    if ($apiResult['error']) {
        send_json_response_php(["error" => $apiResult['error']], $apiResult['httpcode'] ?: 500);
    }

    $analysisResponseData = $apiResult['data'];

    if (!isset($analysisResponseData['choices'][0]['message']['content'])) {
        error_log("Mistral Analyze API response (PHP) missing expected content: " . json_encode($analysisResponseData));
        send_json_response_php(["error" => "Analysis failed or received an invalid response structure from AI."], 500);
    }

    $advice = trim($analysisResponseData['choices'][0]['message']['content']);
    send_json_response_php(["advice" => $advice], 200);

} catch (Exception $e) {
    error_log("Unexpected PHP Analyze API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    send_json_response_php(["error" => "An unexpected error occurred during analysis: " . $e->getMessage()], 500);
}
?>
