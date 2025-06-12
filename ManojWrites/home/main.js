



// ----------------------------------------
const burgerBtn = document.querySelector(".burger-menu");
const navAll = document.querySelector(".nav-all");
const navItems = document.querySelector(".nav-items");

// Function to check screen width
function isMobileView() {
    return window.innerWidth <= 750;
}

// Toggle nav visibility
function toggleNav() {
    if (isMobileView()) {
        navAll.style.display = navAll.style.display === 'none' ? 'block' : 'none';
    }
}

// Initialize - hide nav on mobile by default
if (isMobileView()) {
    navAll.style.display = 'none';
}

// Burger button click event
burgerBtn.addEventListener("click", () => {
    // Toggle burger animation
    burgerBtn.querySelectorAll("span").forEach((span) => span.classList.toggle("open"));
    
    // Toggle nav visibility
    toggleNav();
});

// Handle window resize
window.addEventListener('resize', () => {
    if (!isMobileView()) {
        // Always show nav on larger screens
        navAll.style.display = 'block';
    } else {
        // Hide nav on mobile if burger is not open
        if (!burgerBtn.querySelector(".rectangle-top").classList.contains("open")) {
            navAll.style.display = 'none';
        }
    }
});

// -------------------------------------------------------











//--------------------for toggle animation---------------------------//

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




