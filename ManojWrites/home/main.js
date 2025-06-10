




document.addEventListener('DOMContentLoaded', function() {
    // Menu toggle functionality
    const menuBtn = document.querySelector('.menu-btn');
    const closeBtn = document.querySelector('.close-btn');
    const navAll = document.querySelector('.nav-all');
    
    // Check if elements exist before adding event listeners
    if (menuBtn && closeBtn && navAll) {
        // Initially hide the close button and set proper initial state
        closeBtn.style.display = 'none';
        
        // For mobile view, hide the nav items initially if screen is small
        if (window.innerWidth <= 750) {
            navAll.style.display = 'none';
            menuBtn.style.display = 'block';
        } else {
            navAll.style.display = 'flex';
            menuBtn.style.display = 'none';
        }
        
        // Toggle menu function
        function toggleMenu() {
            if (navAll.style.display === 'none' || !navAll.style.display) {
                navAll.style.display = 'flex';
                menuBtn.style.display = 'none';
                closeBtn.style.display = 'block';
            } else {
                navAll.style.display = 'none';
                menuBtn.style.display = 'block';
                closeBtn.style.display = 'none';
            }
        }
        
        // Event listeners
        menuBtn.addEventListener('click', toggleMenu);
        closeBtn.addEventListener('click', toggleMenu);
        
        // Close menu when a nav link is clicked (for mobile)
        const navLinks = document.querySelectorAll('.nav-link a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 750) {
                    navAll.style.display = 'none';
                    menuBtn.style.display = 'block';
                    closeBtn.style.display = 'none';
                }
            });
        });
        
        // Handle window resize
        function handleResize() {
            if (window.innerWidth > 750) {
                navAll.style.display = 'flex';
                menuBtn.style.display = 'none';
                closeBtn.style.display = 'none';
            } else {
                if (navAll.style.display === 'flex') {
                    navAll.style.display = 'none';
                    menuBtn.style.display = 'block';
                }
            }
        }
        
        window.addEventListener('resize', handleResize);
    }



});
   




//--------------------fortoggle animation---------------------------//

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get the toggle checkbox element
    const checkbox = document.querySelector('input[type="checkbox"]');
    
    // Only proceed if checkbox exists
    if (checkbox) {
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Set initial state based on preferences
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            checkbox.checked = true;
            document.documentElement.classList.add('switch');
        }
        
        // Add change event listener
        checkbox.addEventListener('change', function() {
            // Toggle the 'switch' class on the root element
            document.documentElement.classList.toggle('switch');
            
            // Save preference to localStorage
            localStorage.setItem('theme', this.checked ? 'dark' : 'light');
            
            // Optional: Log state for debugging
            console.log('Dark mode:', this.checked ? 'ON' : 'OFF');
        });
        
        // Optional: Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme')) { // Only auto-change if no manual preference set
                const newTheme = e.matches ? 'dark' : 'light';
                checkbox.checked = e.matches;
                document.documentElement.classList.toggle('switch', e.matches);
                console.log('System theme changed to:', newTheme);
            }
        });
    } else {
        console.warn('Theme toggle checkbox not found');
    }
});






//for dark mode




