<?php
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_name('manual_login');   // MUST MATCH login page
session_start();
require ("../config/connection.php");
    if(!isset($_SESSION['AdminLoginId'])){
        header("location:index.php");
        exit();
    }
    
    // We only need one variable to track if we are in update mode.
    $update_mode = false; 
    $id = 0;
    $title = '';
    $genre = '';
    $description = '';
    $cover_image = '';
    $status = '';

    if(isset($_GET['edit'])){
        $update_mode = true; // Set our one variable to true
        $id = $_GET['edit'];

        $query = "SELECT * FROM `books` WHERE id=?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $title = $row['title'];
        $genre = $row['genre'];
        $description = $row['description'];
        $cover_image = $row['cover_image'];
        $status = $row['status'];
    }
    // -----------------------------------
     $pupdate_mode = false; 
    $p_id = 0;
    $p_title = '';
    $p_theme = '';
    $p_lines = '';
    $p_image = '';
    $p_pub_year = '';

if(isset($_GET['pedit'])){
    $pupdate_mode = true; 
    $p_id = $_GET['pedit'];

    $query = "SELECT * FROM `poems` WHERE p_id=?";
    $stmt = $con->prepare($query);

    // THE FIX IS HERE: Use the correct variable for the poem's ID
    $stmt->bind_param("i", $p_id);

    $stmt->execute();
    $result = $stmt->get_result();
    
    // It's also good practice to check if a result was actually found
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $p_title = $row['p_title'];
        $p_lines = $row['p_lines'];
        $p_theme = $row['p_theme'];
        $p_image = $row['p_image'];
        $p_pub_year = $row['p_pub_year'];
    }
}
// --------------------------------------------------
   // ------------edit-----------------------
     $cupdate_mode = false; 
    $c_id = '';
    $c_title = '';
    $c_number = '';
    $c_text = '';
    $book_title='';


if(isset($_GET['cedit'])){
    $cupdate_mode = true; 
    $c_code_to_edit = $_GET['cedit'];
    



    $query  = "SELECT * FROM `chapters` WHERE `c_id` = ?";



    $stmt = $con->prepare($query);

    $stmt->bind_param("i", $c_code_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // It's also good practice to check if a result was actually found
    if ($result->num_rows == 1) {

        $row = $result->fetch_assoc();
            $c_id = $row['c_id'];
            $c_number = $row['c_number'];
            $c_title = $row['c_title'];
            $c_text = $row['c_text'];
            $c_book_id = $row['book_id']; // <-- Add this

    }
}

?>

<!---------------------------->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#000"> 
    <title>Admin Panel</title>
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">    
<link rel="preconnect" href="https://fonts.googleapis.com">    
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>    
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&display=swap" rel="stylesheet">    
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">    
<link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">    


    <link rel="stylesheet" href="adminpanel.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
        <header class="header">                  
        <nav class="nav">
        
            <div class="optional">
                <button id="openSyncModal" class="btn btn-primary feedback-btn">
  <i class="bi bi-arrow-repeat"></i>Sync
                </button>
               
                <a href="./feedback.php" class="feedback-btn">Feedbacks</a>
                <form method="post">
                    <button class="logout" name="Logout">Logout</button>
                </form>
            </div>
            
        </nav>  
               
    </header>
            

    <!------------>
            
<!-------START------------->
<main>
    <div class="home">
<div class="hero">
    
    <div class="session"><p>ADMIN PANEL - <?php echo $_SESSION['AdminLoginId']?></p></div>
<div class="controlbar">
    
    <div class="controlbox">
        
        <button class="controlbtn addbooksbtn"  id="addbooks" >Books</button>
        <button class="controlbtn addchapbtn" id="addchap">Chapters</button>
        <button class="controlbtn addpoembtn" id="addpoem">Poems</button>
    </div>
    

</div>


 <!------------------------>
        <div class="rightcontainer addbooks"  >
            <div class="upper">
                
                    <div class="box">
                       
                        
                        <div class="formbox" >


                            <div class="title">Add Books</div>
                            <form action="crud.php" method="post" enctype="multipart/form-data" >
                               


                                <input type="hidden" name="id" value="<?=$id;?>" >

                                <div class="form-group">
                                    <span class="details" >Name</span>
                                    <input type="text" name="title" value="<?=$title;?>" placeholder="Enter Title" required>
                                </div>
                                <div class="form-group">
                                    <span class="details" >Genre</span>
                                    <input type="text" name="genre" value="<?= $genre;?>" placeholder="Genre" required >
                                </div>
                                <div class="form-group">
                                    <span class="details" >Description</span>
                                    <input type="text"  name="description" value="<?=$description;?>" placeholder="Enter Description" required >
                                </div>
                                
                                <div class="form-group">
                                    <span class="details" >Status</span>
                                    <input type="text" name="status" value="<?= $status;?>" placeholder="Enter Status" required >
                                </div>
                                <div class="form-group">
                                <?php if($update_mode == true): ?>
                                    <button type="submit" name="update" class="savebtn">Update</button>
                                <?php else: ?>
                                    <button type="submit" name="save" class="savebtn">Save</button>
                                <?php endif; ?>
                            </div>
                            </form>
                        </div>
                    </div>
            </div>


<!-- ------------------------- -->
            <div class="lower">
                <div class="booksection">
                    <h2>Lists of Books</h2> <br>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>TITLE</th>
                                <th>GENRE</th>
                                <th>DESCRIPTION</th>
                                <th>COVER IMAGE</th>
                                <th>CREATED AT</th>
                                <th>UPDATED AT</th>
                                <th>STATUS</th>
                                <th colspan="2" >ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="tbody" >

                        <?php
                            // require ("../config/connection.php");
                            $sql="select * from books";
                            $result=mysqli_query($con,$sql);
                            $fetch_src=FETCH_SRC;

                           
                            while($row =$result->fetch_assoc()): ?>
                               
                                    <tr>
                                        <td><?php echo $row['id']; ?></td> 
                                        <td><?php echo $row['title']; ?></td>
                                        <td><?php echo $row['genre']; ?></td>
                                        <td><?php echo $row['description']; ?></td>
                                        <td><img src="<?php echo $fetch_src . $row['cover_image']; ?>" width="50px" alt=""></td>
                                        <td><?php echo $row['created_at']; ?></td>
                                        <td><?php echo $row['updated_at']; ?></td>
                                        <td><?php echo $row['status']; ?></td>
                                        <td class="action" >
                                           <a href="adminpanel.php?edit=<?= $row['id']; ?>" class="editbtn">Edit</a>                                             
                                            <button onclick="confirm_book_delete(<?php echo $row['id']; ?>)" type="button" class="deletebtn">Delete</button>
                                            
                                        </td>  
                                    </tr>
                                <?php endwhile; ?>
                        </tbody>
                    </table>
                    <script>
                        function confirm_book_delete(id){
                            if(confirm("Are you sure , you want to delete this item?")){
                                window.location.href="crud.php?rem="+id;
                            }
                        }
                    </script>

                </div>
            </div>
        </div>
<!-- -------------add chap----------------- -->
        <div class="rightcontainer addchap">
            <div class="upper">
                
                    <div class="box">
                        
                        <div class="formbox" >
                            <div class="title">Add Chapters</div>
                            <form action="crud.php" method="post" enctype="multipart/form-data" >
                                <div class="forminnbox">

                                <input type="hidden" name="c_id" value="<?=$c_id;?>" >
                                <div class="chapdetails">
                                    <div class="form-group chaptext">
                                    <span class="details " >Chapter Lines</span>  
                                    <textarea cols="50" rows="9" name="c_text" placeholder="Enter Lines" required><?= htmlspecialchars($c_text); ?></textarea>
                                </div>
                                </div>
                                <!-- ------------- -->
                               
                                <div class="chapright">

                                <div class="form-group">
                                    <span class="details">Book</span>
                                    <select name="book_id" required>
                                        <option value="">-- Select a Book --</option>
                                        <?php
                                            $books_res = mysqli_query($con, "SELECT `id`, `title` FROM `books` ORDER BY `title` ASC");
                                            while($book_row = mysqli_fetch_assoc($books_res)){
                                                $selected = ($book_row['id'] == $c_book_id) ? 'selected' : '';
                                                echo "<option value='{$book_row['id']}' {$selected}>" . htmlspecialchars($book_row['title']) . "</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                                    
                                    <div class="form-group">
                                    <span class="details" >Chapter Number</span>
                                    <input type="text" name="c_number" value="<?= $c_number;?>" placeholder="e.g. 1" required>
                                </div>

                                    <div class="form-group">
                                    <span class="details" >Chapter Name</span>
                                    <input type="text" name="c_title" value="<?= $c_title;?>" placeholder="Enter Title" required>
                                </div>
                                
                                
                                <div class="form-group">
                                    
                                    <?php if($cupdate_mode == true): ?>
                                        <button type="submit" name="cupdate" class="savebtn">Update</button>
                                    <?php else: ?>
                                        <button type="submit" name="csave" class="savebtn">Save</button>
                                    <?php endif; ?>
                                </div>
                                </div>
                                </div>
                            </form>
                        </div>
                    </div>
                
            </div>

<!-- ------------------------- -->
            <div class="lower">
                <div class="booksection">
                    <h2>Lists of Chapters</h2> <br>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>C_ID</th>
                                <th>BOOK_NAME</th>
                                <th>C_NUMBER</th>
                                <th>C_TITLE</th>
                                <th>C_WORD_COUNT</th>

                                
                                <th colspan="2" >ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="tbody" >

                        <?php
                            $sql = "SELECT c.*, b.title AS book_title 
                    FROM `chapters` AS c 
                    INNER JOIN `books` AS b ON c.book_id = b.id ";

                            $result=mysqli_query($con,$sql);
                            $fetch_psrc=FETCH_SRC;

                           
                            while($row =$result->fetch_assoc()): ?>
                               
                                    <tr>
                                        <td><?php echo $row['c_id']; ?></td>
                                        <td><?php echo $row['book_title']; ?></td>
                                        <td><?php echo $row['c_number']; ?></td>
                                        <td><?php echo $row['c_title']; ?></td>
                                        <td><?php echo $row['c_word_count']; ?></td>

                                        <td class="action">
                                            <a href="adminpanel.php?cedit=<?= $row['c_id']; ?>#addchap" class="editbtn">Edit</a>
                                            
                                            <button onclick="confirm_crem('<?= $row['c_id']; ?>')" type="button" class="deletebtn">Delete</button>
                                        </td>   
                                    </tr>
                                <?php endwhile; ?>
                        </tbody>
                    </table>
                    <script>
                        function confirm_crem(c_id){
                            if(confirm("Are you sure , you want to delete the chapter?")){
                                window.location.href="crud.php?crem="+c_id;
                            }
                        }
                    </script>
                </div>
            </div>
        </div>
<!-- -----------------add-poem------------------------------- -->
        <div class="rightcontainer addpoem">
            <div class="upper">
                
                    <div class="box">
                        
                        <div class="formbox" >
                            <div class="title">Add Poems</div>
                            <form action="crud.php" method="post" enctype="multipart/form-data" >
                                <div class="forminnbox">

                                <input type="hidden" name="p_id" value="<?=$p_id;?>" >
                                <div class="chapdetails">
                                    <div class="form-group chaptext">
                                    <span class="details " >Poem Lines</span>  
                                    <textarea cols="50" rows="9" name="p_lines" placeholder="Enter Lines" required><?= htmlspecialchars($p_lines); ?></textarea>
                                </div>
                                </div>

                                <div class="chapright">
                                    <div class="form-group">
                                    <span class="details" >Poem Name</span>
                                    <input type="text" name="p_title" value="<?= $p_title;?>" placeholder="Enter Title" required>
                                </div>
                                <div class="form-group">
                                    <span class="details" >Theme</span>
                                    <input type="text" name="p_theme" value="<?= $p_theme;?>" placeholder="Genre" required >
                                </div>
                                
                               
                                <div class="form-group">
                                    <span class="details" >Publication Year</span>
                                    <input type="text" name="p_pub_year" value="<?= $p_pub_year;?>" placeholder="Enter Publication year" required >
                                </div>
                                <div class="form-group">
                                    
                                    <?php if($pupdate_mode == true): ?>
                                        <button type="submit" name="pupdate" class="savebtn">Update</button>
                                    <?php else: ?>
                                        <button type="submit" name="psave" class="savebtn">Save</button>
                                    <?php endif; ?>
                                </div>
                                </div>
                                </div>
                            </form>
                        </div>
                    </div>
                
            </div>

<!-- ------------------------- -->
            <div class="lower">
                <div class="booksection">
                    <h2>Lists of Poems</h2> <br>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>P_ID</th>
                                <th>P_TITLE</th>
                                <th>P_THEME</th>
                                <th>P_IMAGE</th>
                                <th>P_PUB_YEAR</th>
                                <th colspan="2" >ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="tbody" >

                        <?php
                            $sql="select * from poems";
                            $result=mysqli_query($con,$sql);
                            $fetch_psrc=FETCH_SRC;

                           
                            while($row =$result->fetch_assoc()): ?>
                               
                                    <tr>
                                        <td><?php echo $row['p_id']; ?></td>
                                        <td><?php echo $row['p_title']; ?></td>
                                        <td><?php echo $row['p_theme']; ?></td>
                                        <td><img src="<?php echo $fetch_src . $row['p_image']; ?>" width="50px" alt=""></td>
                                        <td><?php echo $row['p_pub_year']; ?></td>
                                        <td class="action" >
                                           <a href="adminpanel.php?pedit=<?= $row['p_id']; ?>" class="editbtn">Edit</a>                                             
                                            <button onclick="confirm_poem_delete(<?php echo $row['p_id']; ?>)" type="button" class="deletebtn">Delete</button>
                                            
                                        </td>   
                                    </tr>
                                <?php endwhile; ?>
                        </tbody>
                    </table>
                    <script>
                        function confirm_poem_delete(p_id){
                            if(confirm("Are you sure , you want to delete the poem?")){
                                window.location.href="crud.php?prem="+p_id;
                            }
                        }
                    </script>
                </div>
            </div>
        </div>
<!-- -----------------------end-------------------- -->
</div>
</div>
<!-- script -->
 <script src="adminpanel.js" ></script>
    <!--------------->
 <?php
if (isset($_POST['Logout'])) {
    session_name('manual_login');
    session_start();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
    </main>
</body>
</html>