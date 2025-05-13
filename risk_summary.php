<?php
include("config.php"); // Session is started in config.php

if (!isset($_SESSION["user"])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$scan_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$scanData = null;
$pageTitle = "Risk Summary"; // Default

if ($scan_id) {
    $user_id = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT * FROM scans WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $scan_id, $user_id);
        if($stmt->execute()){
            $result = $stmt->get_result();
            $scanData = $result->fetch_assoc();
        } else {
            error_log("Risk Summary execute failed: " . $stmt->error);
        }
        $stmt->close();
    } else {
         error_log("Risk Summary prepare failed: " . $conn->error);
    }
}

if (!$scanData) {
    // If scan not found or no ID, redirect to history or show an error on dashboard
    $_SESSION['dashboard_error_msg'] = $scan_id ? "Could not find scan report #{$scan_id}." : "Scan report ID not provided.";
    header("Location: " . BASE_URL . "history.php"); // Or dashboard.php
    exit();
}

$pageTitle = "Risk Summary for Scan #" . htmlspecialchars($scanData['id']);

// Determine Safety Level based on keywords in AI advice
$adviceLower = strtolower($scanData['advice'] ?? '');
$safetyLevel = 'unknown'; // 'safe', 'caution', 'avoid'
$safetyText = 'Analysis Result';
$safetyColorClassBg = 'bg-gray-400'; // For progress bar background
$safetyTextColor = 'text-gray-500';
$safetyIcon = 'help-circle'; // Default Lucide icon

// More nuanced keyword matching
if (preg_match('/\b(unsafe|harmful|not recommended|high risk|avoid|not suitable)\b/i', $adviceLower)) {
    $safetyLevel = 'avoid';
    $safetyText = 'Potentially Harmful - Avoid';
    $safetyColorClassBg = 'bg-danger-red';
    $safetyIcon = 'shield-x';
    $safetyTextColor = 'text-danger-red';
} elseif (preg_match('/\b(caution|moderate risk|some concern|potential concern|careful)\b/i', $adviceLower)) {
    $safetyLevel = 'caution';
    $safetyText = 'Caution Advised';
    $safetyColorClassBg = 'bg-alert-orange';
    $safetyIcon = 'shield-alert';
    $safetyTextColor = 'text-alert-orange';
} elseif (preg_match('/\b(safe|suitable|no major concerns|low risk|generally suitable|seems okay)\b/i', $adviceLower)) {
    $safetyLevel = 'safe';
    $safetyText = 'Likely Safe / Suitable';
    $safetyColorClassBg = 'bg-fresh-green';
    $safetyIcon = 'shield-check';
    $safetyTextColor = 'text-fresh-green';
}

$safetyPercentage = match($safetyLevel) {
    'safe' => 90,
    'caution' => 50,
    'avoid' => 15,
    default => 30, // For 'unknown' or less clear cases
};


include("header.php");
?>

<div class="max-w-3xl mx-auto bg-white p-5 sm:p-8 rounded-xl shadow-card space-y-6">
    <div class="flex items-center justify-between mb-4">
         <h2 class="font-poppins text-xl sm:text-2xl font-bold text-charcoal flex-grow flex items-center">
            <i data-lucide="file-search-2" class="w-7 h-7 mr-2 text-fresh-green"></i>Product Risk Report
        </h2>
         <a href="<?= BASE_URL ?>history.php" title="Back to Scan History" class="text-gray-500 hover:text-fresh-green transition duration-200">
             <i data-lucide="x-circle" class="w-7 h-7"></i>
         </a>
    </div>

    <div class="p-4 sm:p-5 bg-light-beige/70 rounded-xl shadow-subtle">
         <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-2">
             <span class="font-nunito font-semibold text-base text-charcoal mb-1 sm:mb-0">Overall Safety Assessment:</span>
             <span class="font-nunito font-bold text-base flex items-center <?= $safetyTextColor ?>">
                  <i data-lucide="<?= $safetyIcon ?>" class="w-5 h-5 mr-1.5"></i>
                  <?= htmlspecialchars($safetyText) ?>
             </span>
         </div>
         <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700 overflow-hidden">
             <div class="<?= $safetyColorClassBg ?> h-3 rounded-full transition-all duration-500 ease-out" style="width: <?= $safetyPercentage ?>%"></div>
         </div>
         <p class="text-xs text-gray-500 mt-1 text-right">Assessment based on AI analysis</p>
     </div>


    <div class="space-y-5">
        <div>
            <h4 class="font-poppins text-lg font-semibold text-charcoal mb-2 flex items-center">
                 <i data-lucide="message-circle-heart" class="w-6 h-6 mr-2 text-fresh-green"></i>Health Advisor Analysis
            </h4>
            <div class="font-nunito text-sm text-gray-800 bg-green-50 p-3 sm:p-4 rounded-lg border border-green-200 whitespace-pre-wrap leading-relaxed"><?= nl2br(htmlspecialchars($scanData['advice'])) ?></div>
        </div>

        <div>
             <h4 class="font-poppins text-lg font-semibold text-charcoal mb-2 flex items-center">
                  <i data-lucide="list-checks" class="w-6 h-6 mr-2 text-fresh-green"></i>Detected Ingredients / Text
             </h4>
             <pre class="font-mono text-xs sm:text-sm text-gray-800 bg-gray-100 p-3 sm:p-4 rounded-lg border border-gray-200 whitespace-pre-wrap break-words"><?= htmlspecialchars($scanData['ingredients']) ?></pre>
        </div>
    </div>

     <?php if (isset($scanData['decision']) && strtolower($scanData['decision']) !== 'n/a' && !empty($scanData['decision']) ):
         $decisionText = htmlspecialchars(ucfirst($scanData['decision']));
         $decisionClass = '';
         $decisionIcon = 'help-circle';
         if (strtolower($scanData['decision']) === 'yes') {
             $decisionClass = 'text-fresh-green'; $decisionIcon = 'thumbs-up';
         } elseif (strtolower($scanData['decision']) === 'no') {
             $decisionClass = 'text-danger-red'; $decisionIcon = 'thumbs-down';
         }
     ?>
        <div class="pt-4 border-t border-gray-200 text-center">
             <p class="font-nunito text-sm text-gray-600">Your Decision Recorded:
                <span class="font-bold <?= $decisionClass ?> inline-flex items-center ml-1">
                    <i data-lucide="<?= $decisionIcon ?>" class="w-4 h-4 mr-1"></i><?= $decisionText ?>
                </span>
             </p>
             <p class="font-nunito text-xs text-gray-400">On: <?= htmlspecialchars(date("M d, Y H:i", strtotime($scanData['timestamp']))) ?></p>
        </div>
     <?php endif; ?>

     <div class="text-center mt-6 pt-4 border-t border-gray-100">
         <a href="<?= BASE_URL ?>alternatives.php?source_scan_id=<?= $scan_id ?>" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-fresh-green hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition duration-200">
              <i data-lucide="lightbulb" class="w-5 h-5 mr-2"></i>Find Better Choices
         </a>
     </div>
</div>

<?php include("footer.php"); ?>