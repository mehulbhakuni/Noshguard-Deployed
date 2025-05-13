document.addEventListener('DOMContentLoaded', function() {

    // --- Mobile Menu Toggle ---
    const menuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const openIcon = menuButton ? menuButton.querySelector('svg.block') : null; // Adjust selector if needed
    const closeIcon = menuButton ? menuButton.querySelector('svg.hidden') : null; // Adjust selector if needed

    if (menuButton && mobileMenu && openIcon && closeIcon) {
        menuButton.addEventListener('click', () => {
            const isExpanded = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', !isExpanded);
            mobileMenu.classList.toggle('hidden');
            openIcon.classList.toggle('hidden');
            openIcon.classList.toggle('block');
            closeIcon.classList.toggle('hidden');
            closeIcon.classList.toggle('block');
        });
    }

    // --- Initialize Lucide Icons ---
    // Ensure icons are rendered. Can be done here or in footer.
    try {
        if (typeof lucide !== 'undefined') {
             lucide.createIcons();
        }
    } catch (e) {
        console.error("Lucide icon creation failed in script.js:", e);
    }

    // Add any other theme-specific, non-AJAX, non-scan JS here
    // e.g., smooth scrolling, simple animations not handled by Tailwind

});