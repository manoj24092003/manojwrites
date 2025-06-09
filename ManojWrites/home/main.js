




document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.querySelector('.menu-btn');
    const closeBtn = document.querySelector('.close-btn');
    const navItems = document.querySelector('.nav-items');
    const navAll = document.querySelector('.nav-all');
    
    // Initially hide the close button
    closeBtn.style.display = 'none';
    
    // Toggle menu function
    function toggleMenu() {
        if (navAll.style.display === 'flex' || navAll.style.display === '') {
            navAll.style.display = 'none';
            menuBtn.style.display = 'block';
            closeBtn.style.display = 'none';
        } else {
            navAll.style.display = 'flex';
            menuBtn.style.display = 'none';
            closeBtn.style.display = 'block';
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
    window.addEventListener('resize', function() {
        if (window.innerWidth > 750) {
            navAll.style.display = 'flex';
            menuBtn.style.display = 'none';
            closeBtn.style.display = 'none';
        } else {
            if (navAll.style.display !== 'none') {
                navAll.style.display = 'none';
                menuBtn.style.display = 'block';
                closeBtn.style.display = 'none';
            }
        }
    });
    
    // Initialize based on screen size
    if (window.innerWidth <= 750) {
        navAll.style.display = 'none';
    }

});



   




//--------------------fortoggle animation---------------------------//

let checkbox = document.querySelector('input[type="checkbox"]');
checkbox.addEventListener('change', function () {
    document.documentElement.classList.toggle('switch');
    
    console.log("checked");
});



// const checkbox=document.querySelector('switch');

// console.log(checkbox)

// checkbox.addEventListener('change', () => {

//     if(checkbox.checked){

       
        
//         console.log("toggle-btn checked")
//         document.documentElement.classList.toggle('switch');
//     }
//     else{

        

//         console.log("toggle-btn unchecked")
        
//     }


// });






//for dark mode




