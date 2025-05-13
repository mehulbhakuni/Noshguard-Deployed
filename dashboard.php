<?php
include("config.php"); // Session is started in config.php

if (!isset($_SESSION["user"])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION["user"]["id"];
$userName = $_SESSION["user"]["name"];
$userDiseasesJson = $_SESSION["user"]["diseases"] ?? "[]";
$userDiseases = json_decode($userDiseasesJson, true);
if (!is_array($userDiseases)) { // Ensure $userDiseases is an array for in_array checks
    $userDiseases = [];
}


// Fetch Scan Statistics
$stats = ["total" => 0, "consumed" => 0, "avoided" => 0];
$sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN LOWER(decision) = 'yes' THEN 1 ELSE 0 END) as consumed,
            SUM(CASE WHEN LOWER(decision) = 'no' THEN 1 ELSE 0 END) as avoided
        FROM scans
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fetched_stats = $result->fetch_assoc();
    if ($fetched_stats) {
        // Ensure keys exist and are numeric, default to 0 if null
        $stats["total"] = (int)($fetched_stats["total"] ?? 0);
        $stats["consumed"] = (int)($fetched_stats["consumed"] ?? 0);
        $stats["avoided"] = (int)($fetched_stats["avoided"] ?? 0);
    }
    $stmt->close();
} else {
    error_log("Dashboard stats prepare failed: " . $conn->error);
}

$allDiseases = [
  "Diabetes", "Hypertension", "Heart Disease", "Celiac Disease", "Lactose Intolerance",
  "Kidney Disease", "High Cholesterol", "Gluten Sensitivity", "Obesity", "Asthma",
  "Acid Reflux", "Migraine", "Eczema", "Irritable Bowel Syndrome", "Fatty Liver",
  "Peanut Allergy", "Shellfish Allergy", "Soy Allergy", "Wheat Allergy", "Milk Allergy"
];

// Check for dashboard messages from other actions (e.g., scan save)
$dashboard_message = "";
$dashboard_message_type = "success"; // can be 'success' or 'error'
if (isset($_SESSION['dashboard_msg'])) {
    $dashboard_message = $_SESSION['dashboard_msg'];
    unset($_SESSION['dashboard_msg']);
}
if (isset($_SESSION['dashboard_error_msg'])) {
    $dashboard_message = $_SESSION['dashboard_error_msg'];
    $dashboard_message_type = "error";
    unset($_SESSION['dashboard_error_msg']);
}


$pageTitle = "Dashboard";
include("header.php");
?>

<div class="space-y-8">

    <?php if (!empty($dashboard_message)): ?>
    <div class="p-4 rounded-md <?= $dashboard_message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?>" role="alert">
        <p><?= htmlspecialchars($dashboard_message) ?></p>
    </div>
    <?php endif; ?>

    <div class="mb-6">
        <h1 class="font-poppins text-2xl md:text-3xl font-bold text-charcoal">Hello, <?= htmlspecialchars($userName) ?>!</h1>
        <p class="font-nunito text-base text-gray-600">Ready to make smart food choices today?</p>
    </div>

     <div class="text-center my-8 md:my-12">
         <a href="<?= BASE_URL ?>scan.php" class="inline-block bg-fresh-green text-white rounded-full p-5 sm:p-6 shadow-lg hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition duration-200 transform hover:scale-105">
             <i data-lucide="scan-barcode" class="w-8 h-8 sm:w-10 sm:h-10"></i>
         </a>
         <p class="font-nunito text-lg font-medium text-gray-700 mt-3">Start New Scan</p>
     </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-card">
            <h3 class="font-poppins text-xl font-semibold text-charcoal mb-4 flex items-center">
                 <i data-lucide="bar-chart-3" class="w-6 h-6 mr-2 text-fresh-green"></i>
                 Your Scan Trends
            </h3>
            <div class="space-y-3 text-lg font-nunito">
                <p><strong>Total Scans:</strong> <span class="text-fresh-green font-bold"><?= $stats["total"] ?></span></p>
                <p><strong>Items Consumed:</strong> <span class="text-green-600 font-bold"><?= $stats["consumed"] ?></span></p>
                <p><strong>Items Avoided:</strong> <span class="text-danger-red font-bold"><?= $stats["avoided"] ?></span></p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-card flex flex-col items-center justify-center">
            <h3 class="font-poppins text-xl font-semibold text-charcoal mb-4 flex items-center">
                 <i data-lucide="pie-chart" class="w-6 h-6 mr-2 text-fresh-green"></i>
                 Trends Visualization
            </h3>
             <?php if ($stats['total'] > 0 && ($stats['consumed'] > 0 || $stats['avoided'] > 0) ): ?>
                 <div class="w-full max-w-xs h-56 sm:h-64">
                     <canvas id="trendChart"></canvas>
                 </div>
             <?php else: ?>
                <p class="text-gray-500 font-nunito mt-8 text-center">No scan decisions recorded yet to visualize.</p>
             <?php endif; ?>
        </div>
    </div>

     <div class="text-center mt-8 md:mt-10">
         <a href="<?= BASE_URL ?>history.php" class="text-fresh-green hover:underline font-nunito font-medium inline-flex items-center">
            View Full Scan History <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
        </a>
     </div>

</div>

<div id="profileModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 overflow-y-auto h-full w-full z-50 hidden flex items-center justify-center p-4 transition-opacity duration-300 ease-in-out opacity-0" tabindex="-1">
  <div class="relative mx-auto p-5 sm:p-6 border w-full max-w-2xl shadow-xl rounded-xl bg-white transform transition-all duration-300 ease-in-out scale-95">
    <div class="text-center">
      <h3 class="text-xl sm:text-2xl leading-6 font-bold font-poppins text-charcoal mb-6 flex items-center justify-center">
        <i data-lucide="user-cog" class="w-7 h-7 mr-2 text-fresh-green"></i>Edit Profile
      </h3>
      <form id="profileForm" class="space-y-4 text-left">
        <div>
            <label for="profile-name" class="block font-nunito text-sm font-medium text-charcoal mb-1">Full Name</label>
            <input type="text" id="profile-name" name="name" required value="<?= htmlspecialchars($userName) ?>"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm">
        </div>

        <div>
            <label class="block font-nunito text-base font-medium text-charcoal mb-3">Update Health Conditions:</label>
             <div class="max-h-48 overflow-y-auto p-3 sm:p-4 border rounded-lg bg-light-beige/50">
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-6 sm:gap-x-8 gap-y-3">
                       <?php foreach ($allDiseases as $disease):
                           $isChecked = in_array($disease, $userDiseases);
                           $checkedAttr = $isChecked ? 'checked' : '';
                       ?>
                           <div class="flex items-center">
                               <input id="modal-disease-<?= strtolower(str_replace(' ', '-', $disease)) ?>" name="diseases[]" type="checkbox" value="<?= htmlspecialchars($disease) ?>" <?= $checkedAttr ?>
                                      class="custom-checkbox h-4 w-4 text-fresh-green border-gray-300 rounded focus:ring-fresh-green cursor-pointer">
                               <label for="modal-disease-<?= strtolower(str_replace(' ', '-', $disease)) ?>" class="ml-2 block text-sm font-nunito text-gray-700 cursor-pointer"><?= htmlspecialchars($disease) ?></label>
                           </div>
                       <?php endforeach; ?>
                   </div>
             </div>
        </div>

        <div id="profileMsg" class="text-sm font-medium h-5 mt-2 text-center"></div>

        <div class="mt-6 flex flex-col sm:flex-row justify-center items-center gap-3">
          <button type="submit" id="saveProfileBtn" class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg border border-transparent shadow-sm px-6 py-2.5 bg-fresh-green text-base font-medium text-white hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition duration-200">
            <i data-lucide="save" class="w-5 h-5 mr-2"></i>Save Changes
          </button>
           <button type="button" id="changePasswordBtnProfile" class="w-full sm:w-auto mt-3 sm:mt-0 inline-flex items-center justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-2.5 bg-yellow-400 text-base font-medium text-gray-700 hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition duration-200">
            <i data-lucide="key-round" class="w-5 h-5 mr-2"></i>Change Password
          </button>
          <button type="button" id="closeProfileModalBtn" class="w-full sm:w-auto mt-3 sm:mt-0 inline-flex items-center justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="passwordModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 overflow-y-auto h-full w-full z-50 hidden flex items-center justify-center p-4 transition-opacity duration-300 ease-in-out opacity-0" tabindex="-1">
  <div class="relative mx-auto p-5 sm:p-6 border w-full max-w-md shadow-xl rounded-xl bg-white transform transition-all duration-300 ease-in-out scale-95">
     <div class="text-center">
         <h3 class="text-xl sm:text-2xl leading-6 font-bold font-poppins text-charcoal mb-6 flex items-center justify-center">
            <i data-lucide="lock-keyhole" class="w-7 h-7 mr-2 text-fresh-green"></i>Change Password
        </h3>
         <form id="passwordForm" class="space-y-4 text-left">
              <div>
                  <label for="old_password" class="block font-nunito text-sm font-medium text-charcoal mb-1">Current Password</label>
                  <input type="password" id="old_password" name="old_password" required autocomplete="current-password"
                         class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm">
              </div>
              <div>
                  <label for="new_password" class="block font-nunito text-sm font-medium text-charcoal mb-1">New Password (min 6 characters)</label>
                  <input type="password" id="new_password" name="new_password" required autocomplete="new-password"
                         class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm">
              </div>
               <div>
                  <label for="confirm_password" class="block font-nunito text-sm font-medium text-charcoal mb-1">Confirm New Password</label>
                  <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
                         class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm">
              </div>

             <div id="passwordMsg" class="text-sm font-medium h-5 mt-2 text-center"></div>

             <div class="mt-6 flex flex-col sm:flex-row justify-center items-center gap-3">
                 <button type="submit" id="updatePasswordBtn" class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg border border-transparent shadow-sm px-6 py-2.5 bg-fresh-green text-base font-medium text-white hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition duration-200">
                    <i data-lucide="check-check" class="w-5 h-5 mr-2"></i>Update Password
                 </button>
                  <button type="button" id="closePasswordModalBtn" class="w-full sm:w-auto mt-3 sm:mt-0 inline-flex items-center justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                    Cancel
                 </button>
             </div>
         </form>
     </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Re-initialize Lucide icons after dynamic content or if footer script runs too early
    lucide.createIcons();

    const trendChartCanvas = document.getElementById('trendChart');
    const consumedCount = <?= $stats['consumed'] ?>;
    const avoidedCount = <?= $stats['avoided'] ?>;
    const totalScansForChart = consumedCount + avoidedCount;

    if (trendChartCanvas && totalScansForChart > 0) {
        const ctx = trendChartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Consumed', 'Avoided'],
                datasets: [{
                    label: 'Scan Decisions',
                    data: [consumedCount, avoidedCount],
                    backgroundColor: ['#5DB075', '#FF6B6B'], // fresh-green, danger-red
                    borderColor: ['#FFFFFF', '#FFFFFF'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                         position: 'bottom',
                         labels: { font: { family: 'Nunito', size: 14 }, padding: 20 }
                    },
                    tooltip: {
                         bodyFont: { family: 'Nunito' },
                         titleFont: { family: 'Poppins' },
                         callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // Modal Handling
    const profileModal = document.getElementById("profileModal");
    const passwordModal = document.getElementById("passwordModal");
    const profileLinkDesktop = document.getElementById("profile-link-desktop");
    const profileLinkMobile = document.getElementById("profile-link-mobile"); // from header.php
    const closeProfileModalBtn = document.getElementById("closeProfileModalBtn");
    const changePasswordBtnProfile = document.getElementById("changePasswordBtnProfile"); // Button within profile modal
    const closePasswordModalBtn = document.getElementById("closePasswordModalBtn");

    const profileForm = document.getElementById("profileForm");
    const passwordForm = document.getElementById("passwordForm");
    const profileMsg = document.getElementById("profileMsg");
    const passwordMsg = document.getElementById("passwordMsg");
    const saveProfileButton = document.getElementById('saveProfileBtn');
    const updatePasswordButton = document.getElementById('updatePasswordBtn');


    function openModal(modal) {
        if (!modal) return;
        modal.classList.remove("hidden", "opacity-0", "scale-95");
        modal.classList.add("opacity-100", "scale-100");
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden'; // Prevent background scroll
        lucide.createIcons(); // Re-render icons if modal contains them
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.add("opacity-0", "scale-95");
        modal.classList.remove("opacity-100", "scale-100");
        setTimeout(() => { // Allow animation to complete
            modal.classList.add("hidden");
            modal.setAttribute('aria-hidden', 'true');
        }, 300);
        document.body.style.overflow = ''; // Restore background scroll
    }

    if (profileLinkDesktop) profileLinkDesktop.addEventListener("click", (e) => { e.preventDefault(); openModal(profileModal); });
    if (profileLinkMobile) profileLinkMobile.addEventListener("click", (e) => { e.preventDefault(); openModal(profileModal); });
    if (closeProfileModalBtn) closeProfileModalBtn.addEventListener("click", () => closeModal(profileModal));

    if (changePasswordBtnProfile) changePasswordBtnProfile.addEventListener("click", () => {
        closeModal(profileModal);
        passwordMsg.textContent = "";
        if(passwordForm) passwordForm.reset();
        openModal(passwordModal);
    });
    if (closePasswordModalBtn) closePasswordModalBtn.addEventListener("click", () => closeModal(passwordModal));

    // Close modals by clicking on the backdrop or pressing Escape key
    [profileModal, passwordModal].forEach(modal => {
        if(modal) {
            modal.addEventListener("click", function(event) {
                if (event.target === modal) { // Clicked on backdrop
                    closeModal(modal);
                }
            });
        }
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            if (!profileModal.classList.contains('hidden')) closeModal(profileModal);
            if (!passwordModal.classList.contains('hidden')) closeModal(passwordModal);
        }
    });


    if (profileForm) {
        profileForm.addEventListener("submit", function (e) {
            e.preventDefault();
            if(saveProfileButton) saveProfileButton.disabled = true;
            profileMsg.textContent = "Saving...";
            profileMsg.className = "text-sm font-medium h-5 mt-2 text-center text-gray-500";
            const formData = new FormData(this);

            fetch("<?= BASE_URL ?>update-profile.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text().then(text => ({ ok: response.ok, status: response.status, text })))
            .then(data => {
                if (data.ok) {
                    profileMsg.textContent = "✅ Profile updated successfully!";
                    profileMsg.className = "text-sm font-medium h-5 mt-2 text-center text-fresh-green";
                    setTimeout(() => {
                         window.location.reload(); // Reload to reflect changes everywhere
                    }, 1500);
                } else {
                    throw new Error(data.text || `HTTP error! status: ${data.status}`);
                }
            })
            .catch((error) => {
                console.error("Profile Update Error:", error);
                profileMsg.textContent = `❌ ${error.message.replace('Error: ', '')}`; // Show backend error
                profileMsg.className = "text-sm font-medium h-5 mt-2 text-center text-danger-red";
            }).finally(() => {
                if(saveProfileButton) saveProfileButton.disabled = false;
            });
        });
    }

    if (passwordForm) {
        passwordForm.addEventListener("submit", function (e) {
            e.preventDefault();
            if(updatePasswordButton) updatePasswordButton.disabled = true;
            passwordMsg.textContent = "Updating...";
            passwordMsg.className = "text-sm font-medium h-5 mt-2 text-center text-gray-500";
            const formData = new FormData(this);

            fetch("<?= BASE_URL ?>change-password.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text().then(text => ({ ok: response.ok, status: response.status, text })))
            .then(data => {
                if (data.ok && data.text.trim().toLowerCase() === "success") {
                    passwordMsg.textContent = "✅ Password updated successfully!";
                    passwordMsg.className = "text-sm font-medium h-5 mt-2 text-center text-fresh-green";
                    setTimeout(() => {
                        closeModal(passwordModal);
                        passwordForm.reset();
                    }, 2000);
                } else {
                    // Use the error text from PHP if available and not just a generic HTTP error
                     throw new Error(data.text.replace('Error: ', '') || `Update failed. Status: ${data.status}`);
                }
            })
            .catch((error) => {
                console.error("Password Change Error:", error);
                passwordMsg.textContent = `❌ ${error.message}`;
                passwordMsg.className = "text-sm font-medium h-5 mt-2 text-center text-danger-red";
            }).finally(() => {
                 if(updatePasswordButton) updatePasswordButton.disabled = false;
            });
        });
    }
});
</script>

<?php include("footer.php"); ?>