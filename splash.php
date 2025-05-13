<?php
// config.php should be included first to start session and define BASE_URL
include_once("config.php");

// Determine redirect URL based on login status
if (isset($_SESSION["user"]["id"])) {
    $redirect_url = BASE_URL . "dashboard.php";
} else {
    $redirect_url = BASE_URL . "login.php";
}

$page_title = 'Welcome to NoshGuard';
// Use a more branded gradient for the splash screen
$body_class = 'splash-screen-body flex items-center justify-center min-h-screen bg-gradient-to-br from-fresh-green/20 via-soft-green to-white';

// No header/footer needed for a pure splash screen
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <style>
        body { font-family: 'Nunito', sans-serif; overflow: hidden; /* Hide scrollbars for splash */ }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .fade-in-splash { animation: fadeIn 1s ease-in-out forwards; }
        .logo-splash { animation-delay: 0.2s; }
        .title-splash { animation-delay: 0.5s; }
        .subtitle-splash { animation-delay: 0.8s; }
    </style>
</head>
<body class="<?= htmlspecialchars($body_class); ?>">

    <div class="text-center p-4">
        <div class="mb-6 opacity-0 fade-in-splash logo-splash">
             <img src="<?= BASE_URL ?>images/logo.jpg" alt="NoshGuard Logo" class="w-28 h-28 sm:w-32 sm:h-32 mx-auto object-contain">
        </div>
        <h1 class="text-3xl sm:text-4xl font-poppins font-bold text-fresh-green mb-3 opacity-0 fade-in-splash title-splash">NoshGuard</h1>
        <p class="text-lg sm:text-xl font-nunito text-charcoal opacity-0 fade-in-splash subtitle-splash">
            Smart Scans. Safe Choices.
        </p>
    </div>

    <script>
        setTimeout(() => {
            window.location.href = '<?php echo $redirect_url; ?>';
        }, 2500); // 2.5-second delay
    </script>
</body>
</html>