<?php
include("config.php"); // Session is started in config.php

if (!isset($_SESSION["user"])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// Get user diseases from session for JS
$userDiseasesJson = $_SESSION["user"]["diseases"] ?? "[]";
try {
    $decoded = json_decode($userDiseasesJson);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) { // Ensure it decodes to an array
        $userDiseasesJson = "[]";
    } else {
        // Re-encode if it was valid to ensure consistent format for JS
        $userDiseasesJson = json_encode($decoded);
    }
} catch (Exception $e) {
    $userDiseasesJson = "[]";
}

// Check for scan error messages from other actions (e.g., scan save failure)
$scan_page_message = "";
$scan_page_message_type = "error"; // Default to error
if (isset($_SESSION['scan_error'])) {
    $scan_page_message = $_SESSION['scan_error'];
    unset($_SESSION['scan_error']);
} elseif (isset($_SESSION['scan_success'])) { // Optional: for success messages on this page
    $scan_page_message = $_SESSION['scan_success'];
    $scan_page_message_type = "success";
    unset($_SESSION['scan_success']);
}


$pageTitle = "Scan Product";
include("header.php");
?>

<div class="flex flex-col items-center space-y-6">

    <?php if (!empty($scan_page_message)): ?>
    <div class="w-full max-w-lg p-4 rounded-md <?= $scan_page_message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?>" role="alert">
        <p><?= htmlspecialchars($scan_page_message) ?></p>
    </div>
    <?php endif; ?>

    <h2 class="font-poppins text-2xl md:text-3xl font-bold text-charcoal text-center">Scan Product Ingredients</h2>
    <p class="font-nunito text-center text-gray-600 max-w-lg px-4">
        Use your camera to scan an ingredient list or barcode. Alternatively, upload an image.
    </p>

    <div class="w-full max-w-lg bg-white p-5 sm:p-6 rounded-xl shadow-card">

        <div id="cameraViewContainer" class="relative mb-4 hidden">
            <video id="videoElement" autoplay playsinline class="w-full h-auto rounded-lg border-2 border-gray-300 aspect-video bg-gray-900"></video>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-11/12 h-5/6 sm:w-4/5 sm:h-3/4 border-4 border-fresh-green/70 rounded-lg shadow-[0_0_0_9999px_rgba(0,0,0,0.5)] backdrop-blur-[2px]">
                </div>
            </div>
        </div>

        <div id="initialUploadView" class="text-center py-8">
            <button type="button" id="startCameraBtn" class="mb-4 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-fresh-green hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition duration-200">
                <i data-lucide="camera" class="w-5 h-5 mr-2"></i>Use Camera
            </button>
            <p class="font-nunito text-sm text-gray-500">Or, upload an image of the ingredients:</p>
            <input type="file" id="imageUploadInput" accept="image/*" class="hidden">
            <button type="button" onclick="document.getElementById('imageUploadInput').click()" class="mt-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-charcoal bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green">
                <i data-lucide="upload" class="w-4 h-4 mr-2"></i>Upload Image
            </button>
        </div>

        <button type="button" id="captureImageBtn" class="hidden w-full mt-4 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-alert-orange hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-alert-orange transition duration-200">
            <i data-lucide="aperture" class="w-5 h-5 mr-2"></i>Capture Image
        </button>
        <button type="button" id="stopCameraBtn" class="hidden w-full mt-2 inline-flex items-center justify-center px-6 py-2 border border-gray-300 text-sm font-medium rounded-lg text-charcoal bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition duration-200">
            <i data-lucide="x-circle" class="w-4 h-4 mr-2"></i>Close Camera
        </button>

        <canvas id="imageCanvas" class="hidden"></canvas>

        <div id="loadingIndicator" class="hidden text-center my-6 font-nunito text-gray-600">
            <svg class="animate-spin h-8 w-8 text-fresh-green mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p id="loadingText">Processing image...</p>
        </div>
        <div id="generalErrorMsg" class="hidden my-4 bg-red-100 border-l-4 border-danger-red text-danger-red p-4" role="alert">
            <p class="font-bold">Error</p>
            <p id="generalErrorText">An error occurred. Please try again.</p>
        </div>
    </div>

    <div id="scanResultArea" class="w-full max-w-lg bg-white p-5 sm:p-6 rounded-xl shadow-card hidden space-y-4">
        <h3 class="font-poppins text-xl font-semibold text-charcoal text-center">Scan Analysis</h3>
        <div>
            <h4 class="font-poppins text-base font-semibold text-charcoal mb-2 flex items-center"><i data-lucide="file-text" class="w-5 h-5 mr-2 text-fresh-green"></i>Extracted Text:</h4>
            <pre id="ocrTextResult" class="text-sm font-mono bg-gray-100 p-3 rounded border border-gray-200 whitespace-pre-wrap min-h-[60px] max-h-40 overflow-y-auto">Awaiting scan...</pre>
        </div>
        <div>
            <h4 class="font-poppins text-base font-semibold text-charcoal mb-2 flex items-center"><i data-lucide="brain" class="w-5 h-5 mr-2 text-fresh-green"></i>Health Advice:</h4>
            <pre id="aiAdviceResult" class="text-sm font-nunito bg-green-50 p-3 rounded border border-soft-green whitespace-pre-wrap min-h-[70px] max-h-60 overflow-y-auto leading-relaxed">Awaiting analysis...</pre>
        </div>

        <form method="POST" action="<?= BASE_URL ?>scan-save.php" id="scanResultForm" class="pt-4 text-center space-y-3">
            <input type="hidden" name="ocr_text" id="form_ocr_text">
            <input type="hidden" name="ai_advice" id="form_ai_advice">
            <input type="hidden" name="decision" id="form_user_decision">

            <p class="font-nunito text-sm text-gray-700 mb-3">Will you consume this product?</p>
            <div class="flex flex-col sm:flex-row justify-center sm:space-x-4 space-y-3 sm:space-y-0">
                <button type="button" id="consumeBtn" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-fresh-green hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition duration-200">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>Yes, Consume
                </button>
                <button type="button" id="avoidBtn" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-danger-red hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-red transition duration-200">
                    <i data-lucide="x-circle" class="w-5 h-5 mr-2"></i>No, Avoid
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const video = document.getElementById('videoElement');
    const canvas = document.getElementById('imageCanvas');
    const ocrTextEl = document.getElementById('ocrTextResult');
    const aiAdviceEl = document.getElementById('aiAdviceResult');
    const startCameraBtn = document.getElementById('startCameraBtn');
    const captureBtn = document.getElementById('captureImageBtn');
    const stopCamBtn = document.getElementById('stopCameraBtn');
    const imageUploadInput = document.getElementById('imageUploadInput');

    const cameraViewContainer = document.getElementById('cameraViewContainer');
    const initialUploadView = document.getElementById('initialUploadView');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const loadingText = document.getElementById('loadingText');
    const errorMsgDiv = document.getElementById('generalErrorMsg');
    const errorMsgText = document.getElementById('generalErrorText');
    const resultArea = document.getElementById('scanResultArea');

    const scanResultForm = document.getElementById('scanResultForm');
    const consumeBtn = document.getElementById('consumeBtn');
    const avoidBtn = document.getElementById('avoidBtn');

    let currentStream = null;
    const userDiseasesForJS = <?= $userDiseasesJson ?>; // From PHP

    // VVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV
    // --- THIS IS THE SECTION TO UPDATE ---
    // URLs now point to your PHP backend scripts on the same server
    const OCR_API_URL = 'api_ocr.php';     // Relative path to your PHP OCR script
    const ANALYZE_API_URL = 'api_analyze.php'; // Relative path to your PHP Analyze script
    // --- END OF UPDATED SECTION ---
    // ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


    function showLoading(text = 'Processing image...') {
        loadingText.textContent = text;
        loadingIndicator.style.display = 'block';
        resultArea.style.display = 'none';
        errorMsgDiv.style.display = 'none';
        initialUploadView.style.display = 'none';
        cameraViewContainer.style.display = 'none';
        captureBtn.style.display = 'none';
        stopCamBtn.style.display = 'none';
    }

    function hideLoading() {
        loadingIndicator.style.display = 'none';
    }
    
    function displayError(message = 'An error occurred. Please try again.') {
        hideLoading();
        errorMsgText.textContent = message;
        errorMsgDiv.style.display = 'block';
        resultArea.style.display = 'none';
        showInitialView(); 
    }

    function showInitialView() {
        stopCameraStream();
        initialUploadView.style.display = 'block';
        cameraViewContainer.style.display = 'none';
        captureBtn.style.display = 'none';
        stopCamBtn.style.display = 'none';
        resultArea.style.display = 'none';
        errorMsgDiv.style.display = 'none';
        hideLoading();
    }

    function showCameraView() {
        initialUploadView.style.display = 'none';
        cameraViewContainer.style.display = 'block';
        captureBtn.style.display = 'block';
        stopCamBtn.style.display = 'block';
        resultArea.style.display = 'none';
        errorMsgDiv.style.display = 'none';
        hideLoading();
    }

    function stopCameraStream() {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
        if (video) video.srcObject = null;
    }

    startCameraBtn.addEventListener('click', async () => {
        showLoading('Starting camera...');
        errorMsgDiv.style.display = 'none';
        resultArea.style.display = 'none';
        try {
            currentStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
            video.srcObject = currentStream;
            await video.play(); 
            showCameraView();
        } catch (err) {
            console.error("Error accessing rear camera:", err);
            try { 
                currentStream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = currentStream;
                await video.play();
                showCameraView();
            } catch (finalErr) {
                console.error("Error accessing any camera:", finalErr);
                displayError('Could not access camera. Please ensure permissions are granted and try again. Make sure you are using HTTPS.');
                showInitialView();
            }
        }
    });

    stopCamBtn.addEventListener('click', showInitialView);

    captureBtn.addEventListener('click', () => {
        if (!currentStream || video.readyState < video.HAVE_METADATA) { 
            displayError('Camera not ready or stream ended.');
            return;
        }
        showLoading('Capturing image...');
        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageDataUrl = canvas.toDataURL('image/jpeg', 0.9); 
        stopCameraStream(); 
        processImage(imageDataUrl);
    });

    imageUploadInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            showLoading('Reading uploaded image...');
            const reader = new FileReader();
            reader.onload = (e) => {
                processImage(e.target.result); 
            }
            reader.onerror = () => {
                displayError('Failed to read the uploaded file.');
            }
            reader.readAsDataURL(file);
        }
        event.target.value = null; 
    });

    async function processImage(base64ImageData) {
        try {
            showLoading('Extracting text from image...');
            ocrTextEl.textContent = "Extracting text..."; 
            aiAdviceEl.textContent = "Waiting for text extraction..."; 
            
            const ocrData = await fetchOCR(base64ImageData);
            
            if (ocrData && ocrData.ocr_text !== undefined) { 
                const extractedOcrText = ocrData.ocr_text;
                ocrTextEl.textContent = extractedOcrText || (ocrData.message || "No text extracted."); 
                document.getElementById('form_ocr_text').value = extractedOcrText; 

                showLoading('Analyzing ingredients for health advice...');
                aiAdviceEl.textContent = "Analyzing..."; 
                resultArea.style.display = 'block'; 

                const aiData = await fetchAI(extractedOcrText);
                if (!aiData || aiData.advice === undefined) { 
                    throw new Error(aiData?.error || 'AI analysis failed to return advice.');
                }
                aiAdviceEl.textContent = aiData.advice;
                document.getElementById('form_ai_advice').value = aiData.advice; 

            } else { 
                 throw new Error(ocrData?.error || 'OCR failed to return text or expected data. The image might be unclear or have no text.');
            }

            hideLoading(); 
            if (typeof lucide !== 'undefined') { 
                lucide.createIcons(); 
            }

        } catch (error) {
            console.error("Image processing failed:", error);
            displayError(`Processing failed: ${error.message}`); 
        }
    }

    function dataURLtoBlob(dataurl) {
        try {
            let arr = dataurl.split(','), mimeMatch = arr[0].match(/:(.*?);/);
            if (!mimeMatch || arr.length < 2) { 
                console.error("Invalid data URL for blob conversion"); 
                return null; 
            }
            let mime = mimeMatch[1],
                bstr = atob(arr[1]), 
                n = bstr.length, 
                u8arr = new Uint8Array(n);
            while(n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new Blob([u8arr], {type: mime});
        } catch (e) {
            console.error("Error in dataURLtoBlob:", e);
            return null;
        }
    }

    async function fetchOCR(base64Image) {
        const blob = dataURLtoBlob(base64Image);
        if (!blob) { throw new Error("Failed to convert image data for OCR."); }

        const formData = new FormData();
        formData.append('image', blob, 'scan_image.jpg'); 

        const response = await fetch(OCR_API_URL, { 
            method: 'POST',
            body: formData
        });

        const responseData = await response.json(); 
        if (!response.ok) {
            throw new Error(responseData.error || `OCR API Error (${response.status})`);
        }
        return responseData; 
    }

    async function fetchAI(textForAnalysis) {
        const response = await fetch(ANALYZE_API_URL, { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ocr_text: textForAnalysis, user_diseases: userDiseasesForJS })
        });
        const responseData = await response.json(); ao
        if (!response.ok) {
            throw new Error(responseData.error || `Analyze API Error (${response.status})`);
        }
        return responseData; 
    }

    function submitUserDecision(decisionChoice) {
        if (!document.getElementById('form_ocr_text').value && document.getElementById('ocrTextResult').textContent === "Awaiting scan...") {
             alert("Please wait for the scan and analysis to complete before saving your decision.");
             return;
        }
        document.getElementById('form_user_decision').value = decisionChoice;
        scanResultForm.submit(); 
    }

    consumeBtn.addEventListener('click', () => submitUserDecision('Yes'));
    avoidBtn.addEventListener('click', () => submitUserDecision('No'));

    window.addEventListener('beforeunload', stopCameraStream); 

    showInitialView();
    if (typeof lucide !== 'undefined') { 
       lucide.createIcons(); 
    }
});
</script>

<?php include("footer.php"); ?>
