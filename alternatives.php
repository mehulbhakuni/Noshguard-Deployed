<?php
include("config.php"); // Session is started in config.php

if (!isset($_SESSION["user"])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION["user"]["id"];
$source_scan_id = filter_input(INPUT_GET, 'source_scan_id', FILTER_VALIDATE_INT);
$scannedProductData = null;
$pageTitle = "Considering Alternatives"; // Default

if ($source_scan_id) {
    $stmt = $conn->prepare("SELECT ingredients, advice, decision FROM scans WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $source_scan_id, $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $scannedProductData = $result->fetch_assoc();
            if ($scannedProductData) {
                $pageTitle = "Guidance for Scan #" . htmlspecialchars($source_scan_id);
            }
        } else {
            error_log("Alternatives page: Failed to execute statement to fetch scan data: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Alternatives page: Failed to prepare statement to fetch scan data: " . $conn->error);
    }
}

if (!$scannedProductData) {
    $_SESSION['dashboard_error_msg'] = $source_scan_id ? "Could not retrieve original scan data (ID: {$source_scan_id}) to provide guidance." : "Original scan ID not provided for guidance.";
    header("Location: " . BASE_URL . "history.php"); // Or dashboard.php
    exit();
}

// --- Basic Keyword-Based Analysis of AI Advice (Example - Needs Refinement for Production) ---
$adviceLower = strtolower($scannedProductData['advice'] ?? '');
$potentialConcerns = [];
$suggestedFocusTips = [];
$keywordsToTips = [
    ['keywords' => ['sugar', 'sweet', 'fructose', 'syrup', 'sucrose', 'glucose', 'high in sugar'], 'concern' => 'Potentially High Sugar Content', 'tip' => "Look for products labeled 'no added sugar', 'unsweetened', or those naturally low in sugar. Consider fresh fruits or items sweetened with natural alternatives in moderation."],
    ['keywords' => ['sodium', 'salt', 'salty', 'high in sodium'], 'concern' => 'Potentially High Sodium/Salt', 'tip' => "Opt for 'low sodium' or 'reduced sodium' versions. Rinse canned foods. Focus on fresh, whole ingredients and control salt in home cooking."],
    ['keywords' => ['gluten', 'wheat', 'barley', 'rye', 'semolina', 'spelt'], 'concern' => 'Contains Gluten', 'tip' => "If you need to avoid gluten (e.g., Celiac disease, gluten sensitivity), choose certified 'gluten-free' products made from alternative grains like rice, corn, quinoa, buckwheat, or almond/coconut flour."],
    ['keywords' => ['fat', 'saturated fat', 'trans fat', 'hydrogenated oil', 'unhealthy fat'], 'concern' => 'Potentially Unhealthy Fats', 'tip' => "Prefer products with healthy unsaturated fats (from nuts, seeds, avocados, olive oil). Check labels to avoid trans fats and limit saturated fats."],
    ['keywords' => ['artificial color', 'artificial sweetener', 'preservative', 'additive'], 'concern' => 'Contains Artificial Additives', 'tip' => "Consider products with shorter ingredient lists and fewer artificial additives. Whole, unprocessed foods are often a better choice."],
    ['keywords' => ['allergen', 'allergic'], 'concern' => 'Potential Allergen Mentioned', 'tip' => "If you have allergies, always meticulously check ingredient lists for your specific allergens, even if the advice seems general."],
];

$userProfileDiseases = json_decode($_SESSION['user']['diseases'] ?? "[]", true);
if(!is_array($userProfileDiseases)) $userProfileDiseases = [];


foreach ($keywordsToTips as $item) {
    $concernMatched = false;
    foreach ($item['keywords'] as $keyword) {
        if (str_contains($adviceLower, $keyword)) {
            // Special handling for gluten if user has related conditions
            if ($keyword === 'gluten' || $keyword === 'wheat') {
                if (in_array("Celiac Disease", $userProfileDiseases) || in_array("Gluten Sensitivity", $userProfileDiseases)) {
                    if (!in_array($item['concern'], $potentialConcerns)) $potentialConcerns[] = $item['concern'];
                    $concernMatched = true;
                } else {
                    // It contains gluten, but user hasn't specified sensitivity. Still a point to note.
                    if (!in_array("Contains Gluten (Note: Check if relevant for you)", $potentialConcerns)) $potentialConcerns[] = "Contains Gluten (Note: Check if relevant for you)";
                     $concernMatched = true; // Add the tip anyway
                }
            } else {
                if (!in_array($item['concern'], $potentialConcerns)) $potentialConcerns[] = $item['concern'];
                $concernMatched = true;
            }
        }
    }
    if ($concernMatched && !in_array($item['tip'], $suggestedFocusTips)) {
        $suggestedFocusTips[] = $item['tip'];
    }
}

// Fallback if no specific keywords hit but advice wasn't "safe"
if (empty($potentialConcerns) && !(str_contains($adviceLower, 'safe') || str_contains($adviceLower, 'suitable') || str_contains($adviceLower, 'no major concerns'))) {
    $potentialConcerns[] = "General Caution Advised by AI";
    $suggestedFocusTips[] = "The AI advice suggests some level of caution. Review the full advice on the risk summary page. Focus on understanding why caution was advised and look for products that don't share those characteristics. When in doubt, choose whole, unprocessed foods or consult a nutritionist.";
} elseif (empty($potentialConcerns) && !empty($scannedProductData['advice'])) {
     $potentialConcerns[] = "AI analysis provided."; // Generic statement if no keywords matched but advice exists
     $suggestedFocusTips[] = "Review the full AI advice on the risk summary page to understand its assessment. Consider looking for products with simpler ingredient lists or those that align better with your dietary goals.";
}


include("header.php");
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
        <h2 class="font-poppins text-2xl md:text-3xl font-bold text-charcoal mb-2 sm:mb-0 flex items-center">
            <i data-lucide="clipboard-check" class="w-8 h-8 mr-2 text-fresh-green"></i>Guidance & Healthier Tips
        </h2>
        <a href="<?= BASE_URL ?>risk_summary.php?id=<?= $source_scan_id ?>" title="Back to Risk Report" class="text-sm text-fresh-green hover:underline inline-flex items-center">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i>Back to Full Report (Scan #<?= $source_scan_id ?>)
        </a>
    </div>

    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-card space-y-4">
        <h3 class="font-poppins text-xl font-semibold text-charcoal">Regarding the Scanned Product:</h3>
        <div class="font-nunito text-sm text-gray-700 bg-amber-50 p-3 rounded-lg border border-amber-300 max-h-40 overflow-y-auto">
            <p class="font-semibold text-amber-800 mb-1">Summary of AI Advice:</p>
            <?= nl2br(htmlspecialchars($scannedProductData['advice'])) ?>
        </div>

        <?php if (!empty($potentialConcerns)): ?>
            <p class="font-nunito text-gray-800 pt-2">
                <strong class="text-charcoal block mb-1 text-base">Key Considerations Identified:</strong>
                <ul class="list-disc list-inside ml-4 space-y-1 text-sm">
                    <?php foreach ($potentialConcerns as $concern): ?>
                        <li><?= htmlspecialchars($concern) ?></li>
                    <?php endforeach; ?>
                </ul>
            </p>
        <?php else: ?>
            <p class="font-nunito text-gray-600 pt-2 text-sm">No specific keyword-based concerns automatically highlighted from the advice for this summary. Please review the full AI advice above.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($suggestedFocusTips)): ?>
    <div class="bg-white p-5 sm:p-6 rounded-xl shadow-card space-y-4">
        <h3 class="font-poppins text-xl font-semibold text-charcoal flex items-center">
            <i data-lucide="sparkles" class="w-6 h-6 mr-2 text-alert-orange"></i>Tips for Making Healthier Choices:
        </h3>
        <ul class="font-nunito text-gray-700 space-y-3">
            <?php foreach ($suggestedFocusTips as $tip_id => $tip): ?>
                <li class="p-3.5 bg-green-50 rounded-lg border border-fresh-green/40 shadow-sm">
                    <div class="flex items-start">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-fresh-green mr-2 mt-0.5 shrink-0"></i>
                        <p class="text-sm leading-relaxed"><?= htmlspecialchars($tip) ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl shadow-card space-y-4 text-center">
        <h4 class="font-poppins text-lg font-semibold text-charcoal">Next Steps:</h4>
        <div class="flex flex-col sm:flex-row justify-center gap-3">
            <a href="<?= BASE_URL ?>scan.php" class="inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-fresh-green hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition-all duration-150 ease-in-out hover:scale-105">
                <i data-lucide="scan-line" class="w-5 h-5 mr-2"></i>Scan Another Product
            </a>
            <a href="<?= BASE_URL ?>history.php" class="inline-flex items-center justify-center px-5 py-2.5 border border-gray-300 text-base font-medium rounded-lg shadow-sm text-charcoal bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition-all duration-150 ease-in-out hover:scale-105">
                <i data-lucide="history" class="w-5 h-5 mr-2"></i>View Full Scan History
            </a>
        </div>
        <p class="text-xs text-gray-500 mt-5 leading-relaxed">
            NoshGuard aims to provide helpful information based on product scans and AI analysis. This information is not a substitute for professional medical or nutritional advice. Always consult with a healthcare professional or registered dietitian for personalized dietary guidance, especially if you have specific health conditions or allergies.
        </p>
    </div>
</div>

<?php include("footer.php"); ?>