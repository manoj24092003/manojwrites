const button1=document.getElementById('addbooks');
const button2=document.getElementById('addchap');
const button3=document.getElementById('addpoem');

const class1=document.querySelectorAll('.addbooks');
const class2=document.querySelectorAll('.addchap');
const class3=document.querySelectorAll('.addpoem');


button1.addEventListener("click",Showbooks);

function Showbooks(){
    class1.forEach(element => {
        element.style.display='block';
    });
    class2.forEach(element => {
        element.style.display='none';
    });
    class3.forEach(element => {
        element.style.display='none';
    });
}

button2.addEventListener("click",Showchap);

function Showchap(){
    class1.forEach(element => {
        element.style.display='none';
    });
    class2.forEach(element => {
        element.style.display='block';
    });
    class3.forEach(element => {
        element.style.display='none';
    });
}


button3.addEventListener("click",Showpoem);

function Showpoem(){
    class1.forEach(element => {
        element.style.display='none';
    });
    class2.forEach(element => {
        element.style.display='none';
    });
    class3.forEach(element => {
        element.style.display='block';
    });
}

// ------------------------------------------



  


        $(document).ready(function() {
        // Create a URLSearchParams object from the current URL's query string
        const urlParams = new URLSearchParams(window.location.search);

        // Check if the URL has the 'pedit' parameter for editing a poem
        if (urlParams.has('pedit')) {
            // If it does, find the 'Poems' button and click it
            $('#addpoem').click();
        } 
        // Also check for the 'edit' parameter for editing a book
        else if (urlParams.has('edit')) {
            // If it does, click the 'Books' button to show that section
             $('#addbooks').click();
        }
        // -----
        if (urlParams.has('cedit')) {
            // If it does, find the 'Poems' button and click it
            $('#addchap').click();
        } 
       




        
        // This part handles the redirect from crud.php after saving/updating
        if (window.location.hash) {
            const hash = window.location.hash; // e.g., #addpoem
            $(hash).click();
        }
    });



//-------------------------------------
document.addEventListener("click", e => {

  // OPEN MODAL
  if (e.target.closest("#openSyncModal")) {
    if (document.getElementById("syncModal")) {
      document.getElementById("syncModal").style.display = "flex";
      return;
    }

    fetch("sync_modal.php")
      .then(r => r.text())
      .then(html => {
        document.body.insertAdjacentHTML("beforeend", html);
      });
  }

  // CLOSE MODAL
  if (e.target.closest("#closeSyncModal") ||
      e.target.closest("#closeSyncModalFooter")) {
    document.getElementById("syncModal").style.display = "none";
  }

  // RUN SYNC
  if (e.target.closest("#runSyncBtn")) {

    const task = document.getElementById("syncTask").value;
    const slug = document.getElementById("syncSlug").value;
    const log  = document.getElementById("syncLog");

    if (!task || !slug) {
      alert("Select task and series");
      return;
    }

    log.textContent = "Starting...\n";

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "sync.php");
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onprogress = () => {
      log.textContent = xhr.responseText;
      log.scrollTop = log.scrollHeight;
    };

    xhr.send(
  `task=${task}&slug=${slug}&key=7e3c9aF1D8QkZ2mT6J0xV5LrU8pH9Ayb`
);
  }

});






