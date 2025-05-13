<?php
include("config.php"); // Session is started in config.php

// If user is already logged in, redirect to dashboard
if (isset($_SESSION["user"])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$login_error = "";
$display_message = ""; // For success/info messages

if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $display_message = "Registration successful! Please log in.";
}
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $display_message = "You have been logged out successfully.";
}
if (isset($_GET['session_expired']) && $_GET['session_expired'] === 'true') {
    $login_error = "Your session has expired. Please log in again.";
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $login_error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user["password"])) {
                session_regenerate_id(true); // Regenerate session ID on login
                $_SESSION["user"] = $user;
                header("Location: " . BASE_URL . "dashboard.php");
                exit();
            } else {
                $login_error = "Invalid email or password.";
            }
        } else {
            $login_error = "Database error. Please try again later.";
            error_log("Login prepare failed: " . $conn->error);
        }
    }
}

$pageTitle = "Login";
include("header.php");
?>

<div class="flex items-center justify-center min-h-[calc(100vh-220px)] py-8 sm:py-12">
    <div class="max-w-md w-full bg-white p-6 sm:p-8 rounded-xl shadow-card space-y-6">
        <div class="text-center">
            <img src="<?= BASE_URL ?>images/logo.jpg" alt="NoshGuard Logo" class="mx-auto mb-4 w-20 h-20 sm:w-24 sm:h-24 object-contain">
            <h2 class="font-poppins text-2xl sm:text-3xl font-bold text-charcoal">Welcome Back!</h2>
        </div>

        <?php if (!empty($display_message)): ?>
            <div class="bg-green-100 border-l-4 border-fresh-green text-fresh-green p-4" role="alert">
                <p class="font-bold">Success</p>
                <p><?= htmlspecialchars($display_message) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($login_error)): ?>
            <div class="bg-red-100 border-l-4 border-danger-red text-danger-red p-4" role="alert">
                <p class="font-bold">Error</p>
                <p><?= htmlspecialchars($login_error) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>login.php" class="space-y-4">
            <div>
                <label for="login-email" class="block font-nunito text-sm font-medium text-charcoal mb-1">Email</label>
                <input type="email" id="login-email" name="email" required autocomplete="email"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm"
                       placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div>
                <label for="login-password" class="block font-nunito text-sm font-medium text-charcoal mb-1">Password</label>
                <input type="password" id="login-password" name="password" required autocomplete="current-password"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-fresh-green focus:border-fresh-green transition duration-200 shadow-sm bg-gray-50 text-sm"
                       placeholder="••••••••">
            </div>
            <div class="text-right">
                <?php /* <a href="#" class="font-nunito text-sm text-fresh-green hover:underline">Forgot password?</a> */ ?>
            </div>
            <button type="submit"
                    class="w-full bg-fresh-green text-white font-nunito font-semibold py-3 px-4 rounded-lg hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fresh-green transition duration-200 shadow-md hover:shadow-lg text-base">
                Login
            </button>
        </form>
        <p class="text-center font-nunito text-sm text-gray-600 pt-2">
            Don't have an account?
            <a href="<?= BASE_URL ?>register.php" class="font-medium text-fresh-green hover:underline">Register now</a>
        </p>
    </div>
</div>

<?php include("footer.php"); ?>