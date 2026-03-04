

<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

session_name('manual_login');   // name can be anything
session_start();

require ("../config/connection.php");

// PROCESS LOGIN BEFORE OUTPUT
if (isset($_POST['Signin'])) {

    $name = trim($_POST['Adminname']);
    $pass = $_POST['Adminpassword'];

    // 1️⃣ Prepare query (no SQL injection)
    $stmt = $con->prepare("
        SELECT Admin_Password 
        FROM admin_login 
        WHERE Admin_Name = ?
    ");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    // 2️⃣ Check if admin exists
    if ($stmt->num_rows === 1) {

        $stmt->bind_result($hashedPassword);
        $stmt->fetch();

        // 3️⃣ Verify hashed password
        if (password_verify($pass, $hashedPassword)) {

            session_regenerate_id(true); // security
            $_SESSION['AdminLoginId'] = $name;

            header("Location: adminpanel.php");
            exit();

        } else {
            $_SESSION['login_error'] = "Incorrect Username or Password";
        }

    } else {
        $_SESSION['login_error'] = "Incorrect Username or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <meta name="theme-color" content="#000000">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100..900&display=swap" rel="stylesheet">
    

    <link rel="stylesheet" href="./login.css?v=7">
    <link rel="stylesheet" href="../style.css?v=7">
</head>


<body>
    <header class="header" style="z-index:9999999;">
        <div class="logo" onclick="location.href='/index.php'"><i class="bi bi-tv-fill tv-logo"></i></div>

    <!-- SEARCH BAR -->
    <div class="search-box" id="search-box">
        <i class="bi bi-search search-icon"></i>
        <input type="search" id="searchInput" placeholder="Search..." autocomplete="off">
    </div>

    <div id="searchResults"></div>
        
        <div class="rcontainer">
<i class="bi bi-search search-icon rsearch"></i>


    <!-- Desktop navigation -->
    <nav class="nav-desktop" aria-label="Primary">
        <ul>
            <li>
                <a href="../index.php">
                    <i class="bi bi-house-fill"></i> HOME
                </a>
            </li>

            <li>
                <a href="../books/index.php">
                    <i class="bi bi-book-half"></i> BOOKS
                </a>
            </li>

            <li>
                <a href="../poems/index.php">
                    <i class="bi bi-vector-pen"></i> POEMS
                </a>
            </li>

            <li>
                <a href="./index.php">
                    <i class="bi bi-person-fill"></i> ADMIN
                </a>
            </li>
        </ul>
    </nav>

    

        <!-- Burger button for mobile -->
        <div>
            <button id="menuBtn"
                class="menu-btn"
                aria-label="Open menu"
                aria-expanded="false"
                aria-controls="mobileMenu">
                <span class="bar"></span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile menu -->
<nav id="mobileMenu"
    class="mobile-panel"
    aria-hidden="true"
    aria-label="Mobile primary">
             <a href="../index.php"><i class="bi bi-house-fill"></i> HOME</a>
  <a href="../books/index.php"><i class="bi bi-book-half"></i> BOOKS</a>
  <a href="../poems/index.php"><i class="bi bi-vector-pen"></i> POEMS</a>
  <a href="./index.php"><i class="bi bi-person-fill"></i> ADMIN</a>
</nav>
    
<!------------------------------------>
    

<main>
<!---------------START------------------->
        <div class="hero ">

            <div class="formbox">

                <form action="" method="post">
                    <span><h2>ADMIN LOGIN PANEL</h2></span>
                    <div class="form-group"><i class="bi bi-person-fill-lock"></i>
                        <input type="text" name="Adminname" placeholder="Admin Name" >
                    </div>
                    
                    <div class="form-group"><i class="bi bi-lock-fill"></i>
                        <input type="password" name="Adminpassword" placeholder="Admin Password">
                    </div>
                    
                    <div class="form-group signin">
                        <i class="bi bi-unlock-fill"></i>
                        <input type="submit" class="btn btn-primary" value="Sign In" name="Signin" >
                    </div>
                    <span class="recover"><a href="recover.php">Forgot Password?</a></span>
                </form>
            </div>
        </div>

<!---------------END--------------------->


<!-------------------------------->
        <div class="footer">
            <div class="copy">
                <p>&copy2025.</p>
            </div>

            <!--<div class="social">
            
            </div>-->
        </div>
        
    </main>
    <script src="login.js"></script>
    <script src="../home/main.js"></script>
    
    <!-- Toast (global) -->
    <div id="site-toast" role="status" aria-live="polite" aria-atomic="true"></div>
    <?php 
if (isset($_SESSION['login_error'])) {
    echo "<script>toast('{$_SESSION['login_error']}');</script>";
    unset($_SESSION['login_error']);
}
?>
    </body>
</html>