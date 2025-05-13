</main>
    <footer class="bg-white mt-auto py-4 shadow-inner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm text-gray-500 font-nunito">
                &copy; <?= date("Y") ?> NoshGuard. All rights reserved.
            </p>
        </div>
    </footer>

    <?php /* <script src="<?= BASE_URL ?>js/script.js"></script> IF YOU HAVE A GLOBAL JS FILE */ ?>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
             try {
                 lucide.createIcons();
             } catch (e) {
                 console.error("Lucide icon creation failed: ", e);
             }

            // Mobile menu toggle
            const menuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const menuIconOpen = document.getElementById('menu-icon-open');
            const menuIconClose = document.getElementById('menu-icon-close');

            if (menuButton && mobileMenu && menuIconOpen && menuIconClose) { // Ensure all elements exist
                menuButton.addEventListener('click', () => {
                    const isExpanded = menuButton.getAttribute('aria-expanded') === 'true' || false;
                    menuButton.setAttribute('aria-expanded', !isExpanded);
                    mobileMenu.classList.toggle('hidden');
                    menuIconOpen.classList.toggle('hidden');
                    menuIconClose.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>