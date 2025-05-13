<?php
include("config.php"); // Session is started in config.php

// If user is already logged in, redirect to dashboard
if (isset($_SESSION["user"])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$registration_error = "";

$allDiseases = [
  "Diabetes", "Hypertension", "Heart Disease", "Celiac Disease", "Lactose Intolerance",
  "Kidney Disease", "High Cholesterol", "Gluten Sensitivity", "Obesity", "Asthma",
  "Acid Reflux", "Migraine", "Eczema", "Irritable Bowel Syndrome", "Fatty Liver",
  "Peanut Allergy", "Shellfish Allergy", "Soy Allergy", "Wheat Allergy", "Milk Allergy"
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $selected_diseases = isset($_POST["diseases"]) && is_array($_POST["diseases"]) ? $_POST["diseases"] : [];

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $registration_error = "All fields except conditions are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $registration_error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
         $registration_error = "Password must be at least 6 characters long.";
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $registration_error = "An account with this email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $disease_json = json_encode($selected_diseases);

                $stmt_insert = $conn->prepare("INSERT INTO users (name, email, password, diseases) VALUES (?, ?, ?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("ssss", $name, $email, $hashed_password, $disease_json);

                    if ($stmt_insert->execute()) {
                        header("Location: " . BASE_URL . "login.php?registration=success");
                        exit();
                    } else {
                        $registration_error = "Registration failed. Please try again.";
                        error_log("Registration insert failed: " . $stmt_insert->error . " | SQL: INSERT INTO users (name, email, password, diseases) VALUES ($name, $email, HASHED_PWD, $disease_json)");
                    }
                    $stmt_insert->close();
                } else {
                     $registration_error = "Database error during registration. Please try again later.";
                     error_log("Register prepare failed: " . $conn->error);
                }
            }
            $stmt_check->close();
        } else {
             $registration_error = "Database error checking email. Please try again later.";
             error_log("Email check prepare failed: " . $conn->error);
        }
    }
}

$pageTitle = "Create Account";
include("header.php");
?>

<div class="flex items-center justify-center py-8 sm:py-12">
    <div class="max-w-2xl w-full bg-white p-6 sm:p-8 rounded-xl shadow-card space-y-6">
        <div class="text-center">
            <img src="<?= BASE_URL ?>images/logo.jpg" alt="NoshGuard Logo" class="mx-auto mb-4 w-20 h-20 sm:w-24 sm:h-24 object-contain">
            <h2 class="font-poppins text-2xl sm:text-3xl font-bold text-charcoal">Create Your Account</h2>
        </div>

         <?php if (!empty($registration_error)): ?>
            <div class="bg-red-100 border border-danger-red text-danger-red px-4 py-3 rounded-lg relative" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($registration_error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>register.php" class="space-y-4">
            <div>
                <label for="register-name" class="block font-nunito text-sm font-medium text-charcoal mb-1">Full Name</label>
                <input type="text" id="register-name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm"
                       placeholder="Your Name">
            </div>
            <div>
                <label for="register-email" class="block font-nunito text-sm font-medium text-charcoal mb-1">Email</label>
                <input type="email" id="register-email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm"
                       placeholder="you@example.com">
            </div>
            <div>
                <label for="register-password" class="block font-nunito text-sm font-medium text-charcoal mb-1">Password (min 6 characters)</label>
                <input type="password" id="register-password" name="password" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm"
                       placeholder="Create a password">
            </div>
             <div>
                <label for="register-confirm-password" class="block font-nunito text-sm font-medium text-charcoal mb-1">Confirm Password</label>
                <input type="password" id="register-confirm-password" name="confirm_password" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm"
                       placeholder="Confirm your password">
            </div>

            <div class="pt-2">
                 <label class="block font-nunito text-base font-medium text-charcoal mb-3 text-center">Select Your Health Conditions (Optional)</label>
                 <div class="max-h-48 overflow-y-auto p-3 sm:p-4 border rounded-lg bg-light-beige/50">
                      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-6 sm:gap-x-8 gap-y-3">
                          <?php foreach ($allDiseases as $disease):
                              $selectedPOSTDiseases = $_POST['diseases'] ?? [];
                              $checked = is_array($selectedPOSTDiseases) && in_array($disease, $selectedPOSTDiseases) ? 'checked' : '';
                          ?>
                              <div class="flex items-center">
                                  <input id="disease-<?= strtolower(str_replace(' ', '-', $disease)) ?>" name="diseases[]" type="checkbox" value="<?= htmlspecialchars($disease) ?>" <?= $checked ?>
                                         class="custom-checkbox h-4 w-4 text-fresh-green border-gray-300 rounded focus:ring-fresh-green cursor-pointer">
                                  <label for="disease-<?= strtolower(str_replace(' ', '-', $disease)) ?>" class="ml-2 block text-sm font-nunito text-gray-700 cursor-pointer"><?= htmlspecialchars($disease) ?></label>
                              </div>
                          <?php endforeach; ?>
                      </div>
                 </div>
            </div>

            <button type="submit"
                    class="w-full bg-fresh-green text-white font-nunito font-semibold py-3 px-4 rounded-lg hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition duration-200 shadow-md hover:shadow-lg mt-6 text-base">
                Register
            </button>
        </form>
        <p class="text-center font-nunito text-sm text-gray-600 pt-2">
            Already have an account?
            <a href="<?= BASE_URL ?>login.php" class="font-medium text-fresh-green hover:underline">Login</a>
        </p>
    </div>
</div>

<?php include("footer.php"); ?>