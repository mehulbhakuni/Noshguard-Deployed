<?php
// config.php should have already started the session
include_once("config.php"); // Ensures BASE_URL and session are available

$isLoggedIn = isset($_SESSION["user"]);
$home_url = $isLoggedIn ? BASE_URL . 'dashboard.php' : BASE_URL . 'login.php';

if (!isset($pageTitle)) {
    $pageTitle = "NoshGuard";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - NoshGuard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'soft-green': '#E6F4EA',
                        'fresh-green': '#5DB075',
                        'light-beige': '#FFF8E7',
                        'alert-orange': '#FFA726',
                        'charcoal': '#333333',
                        'danger-red': '#FF6B6B',
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                        nunito: ['Nunito', 'sans-serif'],
                    },
                     boxShadow: {
                        'subtle': '0 2px 4px rgba(0, 0, 0, 0.05)',
                        'card': '0 4px 10px rgba(0, 0, 0, 0.08)',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Nunito', sans-serif; color: #333333; }
        .nav-logo { max-height: 40px; /* Adjust as needed */ }
        /* Custom checkbox style for health conditions */
        .custom-checkbox:checked {
            background-color: #5DB075; /* fresh-green */
            border-color: #5DB075; /* fresh-green */
        }
        .custom-checkbox:focus {
            ring: #5DB075; /* fresh-green */
        }
    </style>
</head>
<body class="bg-soft-green min-h-screen flex flex-col">

    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-shrink-0 flex items-center">
                     <a href="<?= $home_url ?>" class="flex items-center">
                        <img src="<?= BASE_URL ?>images/logo.jpg" alt="NoshGuard Logo" class="nav-logo h-10 w-auto mr-2">
                        <span class="font-poppins font-bold text-xl text-fresh-green">NoshGuard</span>
                     </a>
                </div>

                <div class="hidden md:flex items-center space-x-4">
                     <?php if ($isLoggedIn): ?>
                        <a href="<?= BASE_URL ?>dashboard.php" class="text-charcoal hover:text-fresh-green px-3 py-2 rounded-md text-sm font-medium font-nunito">Dashboard</a>
                        <a href="<?= BASE_URL ?>scan.php" class="text-charcoal hover:text-fresh-green px-3 py-2 rounded-md text-sm font-medium font-nunito">Scan</a>
                        <a href="<?= BASE_URL ?>history.php" class="text-charcoal hover:text-fresh-green px-3 py-2 rounded-md text-sm font-medium font-nunito">History</a>
                        <a href="<?=BASE_URL ?>update-profile.php" id="profile-link-desktop" class="text-charcoal hover:text-fresh-green px-3 py-2 rounded-md text-sm font-medium font-nunito">Profile</a>
                        <a href="<?= BASE_URL ?>logout.php" class="text-charcoal hover:text-fresh-green px-3 py-2 rounded-md text-sm font-medium font-nunito">Logout</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>login.php" class="text-charcoal hover:text-fresh-green px-3 py-2 rounded-md text-sm font-medium font-nunito">Login</a>
                        <a href="<?= BASE_URL ?>register.php" class="text-charcoal hover:text-fresh-green px-3 py-2 rounded-md text-sm font-medium font-nunito">Register</a>
                    <?php endif; ?>
                </div>

                 <div class="-mr-2 flex md:hidden">
                     <button type="button" id="mobile-menu-button" class="bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-fresh-green hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-fresh-green" aria-controls="mobile-menu" aria-expanded="false">
                         <span class="sr-only">Open main menu</span>
                         <i data-lucide="menu" class="block h-6 w-6" id="menu-icon-open"></i>
                         <i data-lucide="x" class="hidden h-6 w-6" id="menu-icon-close"></i>
                     </button>
                 </div>
            </div>
        </div>

        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                 <?php if ($isLoggedIn): ?>
                    <a href="<?= BASE_URL ?>dashboard.php" class="text-charcoal hover:bg-soft-green hover:text-fresh-green block px-3 py-2 rounded-md text-base font-medium font-nunito">Dashboard</a>
                    <a href="<?= BASE_URL ?>scan.php" class="text-charcoal hover:bg-soft-green hover:text-fresh-green block px-3 py-2 rounded-md text-base font-medium font-nunito">Scan</a>
                    <a href="<?= BASE_URL ?>history.php" class="text-charcoal hover:bg-soft-green hover:text-fresh-green block px-3 py-2 rounded-md text-base font-medium font-nunito">History</a>
                    <a href="#" id="profile-link-mobile" class="text-charcoal hover:bg-soft-green hover:text-fresh-green block px-3 py-2 rounded-md text-base font-medium font-nunito">Profile</a>
                    <a href="<?= BASE_URL ?>logout.php" class="text-charcoal hover:bg-soft-green hover:text-fresh-green block px-3 py-2 rounded-md text-base font-medium font-nunito">Logout</a>
                 <?php else: ?>
                     <a href="<?= BASE_URL ?>login.php" class="text-charcoal hover:bg-soft-green hover:text-fresh-green block px-3 py-2 rounded-md text-base font-medium font-nunito">Login</a>
                     <a href="<?= BASE_URL ?>register.php" class="text-charcoal hover:bg-soft-green hover:text-fresh-green block px-3 py-2 rounded-md text-base font-medium font-nunito">Register</a>
                 <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-6">