<?php
include("config.php"); // Session is started in config.php

if (!isset($_SESSION["user"])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION["user"]["id"];

$sort = strtolower(trim($_GET["sort"] ?? "latest"));
$filter = strtolower(trim($_GET["filter"] ?? "all"));

$validSorts = ["latest", "oldest"];
$validFilters = ["all", "yes", "no"];

if (!in_array($sort, $validSorts)) {
    $sort = "latest";
}
if (!in_array($filter, $validFilters)) {
    $filter = "all";
}

$query = "SELECT id, ingredients, advice, decision, timestamp FROM scans WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if ($filter === "yes" || $filter === "no") {
    $query .= " AND LOWER(decision) = ?";
    $params[] = $filter;
    $types .= "s";
}

$query .= " ORDER BY timestamp " . ($sort === "oldest" ? "ASC" : "DESC");

$stmt = $conn->prepare($query);
$scanHistory = [];

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if($stmt->execute()){
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $scanHistory[] = $row;
        }
    } else {
        error_log("History execute failed: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("History prepare failed: " . $conn->error);
}

$pageTitle = "Scan History";
include("header.php");
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
         <h2 class="font-poppins text-2xl md:text-3xl font-bold text-charcoal flex items-center">
            <i data-lucide="history" class="w-8 h-8 mr-2 text-fresh-green"></i>Scan History
        </h2>
         <a href="<?= BASE_URL ?>dashboard.php" title="Back to Dashboard" class="text-fresh-green hover:text-opacity-80 transition duration-200">
              <i data-lucide="arrow-left-circle" class="w-7 h-7"></i>
         </a>
    </div>


    <div class="bg-white p-4 rounded-lg shadow-sm flex flex-col sm:flex-row flex-wrap justify-center items-center gap-x-6 gap-y-3">
        <div class="flex items-center gap-2">
             <span class="font-nunito font-semibold text-sm text-gray-700">Sort by:</span>
             <a href="?sort=latest&filter=<?= htmlspecialchars($filter) ?>" class="font-nunito text-sm px-2 py-1 rounded <?= $sort === 'latest' ? 'text-white bg-fresh-green font-bold' : 'text-gray-600 hover:text-fresh-green hover:bg-soft-green' ?>">Latest</a>
             <a href="?sort=oldest&filter=<?= htmlspecialchars($filter) ?>" class="font-nunito text-sm px-2 py-1 rounded <?= $sort === 'oldest' ? 'text-white bg-fresh-green font-bold' : 'text-gray-600 hover:text-fresh-green hover:bg-soft-green' ?>">Oldest</a>
        </div>
         <div class="flex items-center gap-2">
             <span class="font-nunito font-semibold text-sm text-gray-700">Filter:</span>
             <a href="?sort=<?= htmlspecialchars($sort) ?>&filter=all" class="font-nunito text-sm px-2 py-1 rounded <?= $filter === 'all' ? 'text-white bg-fresh-green font-bold' : 'text-gray-600 hover:text-fresh-green hover:bg-soft-green' ?>">All</a>
             <a href="?sort=<?= htmlspecialchars($sort) ?>&filter=yes" class="font-nunito text-sm px-2 py-1 rounded <?= $filter === 'yes' ? 'text-white bg-fresh-green font-bold' : 'text-gray-600 hover:text-fresh-green hover:bg-soft-green' ?>">Consumed</a>
             <a href="?sort=<?= htmlspecialchars($sort) ?>&filter=no" class="font-nunito text-sm px-2 py-1 rounded <?= $filter === 'no' ? 'text-white bg-fresh-green font-bold' : 'text-gray-600 hover:text-fresh-green hover:bg-soft-green' ?>">Avoided</a>
        </div>
    </div>

    <div class="bg-white shadow-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
             <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-light-beige">
                    <tr>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-poppins font-bold text-charcoal uppercase tracking-wider">#</th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-poppins font-bold text-charcoal uppercase tracking-wider">Scanned Text</th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-poppins font-bold text-charcoal uppercase tracking-wider">Advice Given</th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-poppins font-bold text-charcoal uppercase tracking-wider">Your Decision</th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-poppins font-bold text-charcoal uppercase tracking-wider">Date & Time</th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-poppins font-bold text-charcoal uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($scanHistory)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 font-nunito">
                                No scan history found matching your criteria. <a href="<?= BASE_URL ?>scan.php" class="text-fresh-green hover:underline">Start a new scan!</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($scanHistory as $scan):
                            $decisionText = htmlspecialchars(ucfirst($scan['decision']));
                            $decisionClass = '';
                            if (strtolower($scan['decision']) === 'yes') {
                                $decisionClass = 'text-fresh-green bg-green-50';
                                $decisionIcon = 'thumbs-up';
                            } elseif (strtolower($scan['decision']) === 'no') {
                                $decisionClass = 'text-danger-red bg-red-50';
                                $decisionIcon = 'thumbs-down';
                            } else {
                                $decisionClass = 'text-gray-600 bg-gray-50';
                                $decisionIcon = 'help-circle';
                            }
                            $formattedTimestamp = date("M d, Y H:i", strtotime($scan['timestamp']));
                        ?>
                            <tr>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $i++ ?></td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-gray-700 font-nunito max-w-xs">
                                    <details>
                                         <summary class="cursor-pointer text-blue-600 hover:underline focus:outline-none">View Text</summary>
                                         <pre class="mt-2 text-xs bg-gray-100 p-2 rounded border whitespace-pre-wrap break-words"><?= htmlspecialchars($scan['ingredients']) ?></pre>
                                    </details>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-gray-700 font-nunito max-w-xs">
                                     <details>
                                         <summary class="cursor-pointer text-blue-600 hover:underline focus:outline-none">View Advice</summary>
                                         <pre class="mt-2 text-xs bg-green-50 p-2 rounded border whitespace-pre-wrap break-words"><?= htmlspecialchars($scan['advice']) ?></pre>
                                     </details>
                                </td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $decisionClass ?>">
                                        <i data-lucide="<?= $decisionIcon ?>" class="w-4 h-4 mr-1"></i>
                                        <?= $decisionText ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($formattedTimestamp) ?></td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="<?= BASE_URL ?>risk_summary.php?id=<?= $scan['id'] ?>" class="text-fresh-green hover:underline" title="View Full Report">
                                        <i data-lucide="file-text" class="w-5 h-5"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>