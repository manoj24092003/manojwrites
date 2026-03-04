
<?php


 ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once("../config/connection.php");
session_start();

// ===================================================
// =========== REUSABLE FUNCTIONS (FOR ALL) ==========
// ===================================================

// This one function handles image uploads for both books and poems.
function Image_upload($img){
    $tmp_loc = $img['tmp_name'];
    $new_name = random_int(11111, 99999) . $img['name'];
    $new_loc = UPLOAD_SRC . $new_name;

    if (!move_uploaded_file($tmp_loc, $new_loc)) {
        header("location:adminpanel.php?alert=img_upload_failed");
        exit;
    } else {
        return $new_name;
    }
}

// This one function handles removing images for both books and poems.
function image_remove($img){
    if (file_exists(UPLOAD_SRC . $img) && !is_dir(UPLOAD_SRC . $img)) {
        unlink(UPLOAD_SRC . $img);
    }
}


// ===================================================
// ================= BOOKS LOGIC =====================
// ===================================================

// ======== SAVE NEW BOOK ========
if (isset($_POST['save'])) {
    $query = "INSERT INTO `books`(`title`, `genre`, `description`, `cover_image`, `status`) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        $imgpath = Image_upload($_FILES['cover_image']);
        mysqli_stmt_bind_param($stmt, "sssss", $_POST['title'], $_POST['genre'], $_POST['description'], $imgpath, $_POST['status']);
        if(mysqli_stmt_execute($stmt)){
            header("location: adminpanel.php?success=added");
        } else {
            header("location: adminpanel.php?alert=add_failed");
        }
        mysqli_stmt_close($stmt);
    }
}

// ======== UPDATE EXISTING BOOK ========
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $genre = $_POST['genre'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $old_img = $_POST['oldimg'];
    $new_img = '';

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['size'] > 0) {
        image_remove($old_img);
        $new_img = Image_upload($_FILES['cover_image']);
    } else {
        $new_img = $old_img;
    }

    $query = "UPDATE `books` SET `title`=?, `genre`=?, `description`=?, `cover_image`=?, `status`=? WHERE `id`=?";
    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssi", $title, $genre, $description, $new_img, $status, $id);
        if (mysqli_stmt_execute($stmt)) {
            header("location:adminpanel.php?success=updated");
        } else {
            header("location:adminpanel.php?alert=update_failed");
        }
        mysqli_stmt_close($stmt);
    }
}

// ======== DELETE BOOK ========
if (isset($_GET['rem']) && $_GET['rem'] > 0) {
    $id_to_delete = $_GET['rem'];
    $query_select = "SELECT `cover_image` FROM `books` WHERE `id`=?";
    $stmt_select = mysqli_prepare($con, $query_select);
    mysqli_stmt_bind_param($stmt_select, "i", $id_to_delete);
    mysqli_stmt_execute($stmt_select);
    $result = mysqli_stmt_get_result($stmt_select);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        image_remove($row['cover_image']);
    }
    mysqli_stmt_close($stmt_select);

    $query_delete = "DELETE FROM `books` WHERE `id`=?";
    $stmt_delete = mysqli_prepare($con, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, "i", $id_to_delete);
    if (mysqli_stmt_execute($stmt_delete)) {
        header("location:adminpanel.php?success=removed");
    } else {
        header("location:adminpanel.php?alert=remove_failed");
    }
    mysqli_stmt_close($stmt_delete);
}


// ===================================================
// ================= POEMS LOGIC =====================
// ===================================================

// ======== SAVE NEW POEM ========
if (isset($_POST['psave'])) {
    $query = "INSERT INTO `poems`(`p_title`, `p_theme`, `p_lines`, `p_image`, `p_pub_year`) VALUES (?, ?, ?, ?, ?)";
    $pstmt = mysqli_prepare($con, $query);
    if ($pstmt) {
        $pimgpath = Image_upload($_FILES['p_image']);
        mysqli_stmt_bind_param($pstmt, "sssss", $_POST['p_title'], $_POST['p_theme'], $_POST['p_lines'], $pimgpath, $_POST['p_pub_year']);
        if(mysqli_stmt_execute($pstmt)){
            header("location: adminpanel.php?success=poem_added#addpoem");
        } else {
            header("location: adminpanel.php?alert=poem_add_failed#addpoem");
        }
        mysqli_stmt_close($pstmt);
    } else {
        header("location: adminpanel.php?alert=prepare_failed");
    }
}

// ======== UPDATE EXISTING POEM ========
if (isset($_POST['pupdate'])) {
    $p_id = $_POST['p_id'];
    $p_title = $_POST['p_title'];
    $p_theme = $_POST['p_theme'];
    $p_lines = $_POST['p_lines'];
    $p_pub_year = $_POST['p_pub_year'];
    $p_old_img = $_POST['poldimg'];
    $p_new_img = '';

    if (isset($_FILES['p_image']) && $_FILES['p_image']['size'] > 0) {
        image_remove($p_old_img);
        $p_new_img = Image_upload($_FILES['p_image']);
    } else {
        $p_new_img = $p_old_img;
    }

    $query = "UPDATE `poems` SET `p_title`=?, `p_theme`=?, `p_lines`=?, `p_image`=?, `p_pub_year`=? WHERE `p_id`=?";
    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssi", $p_title, $p_theme, $p_lines, $p_new_img, $p_pub_year, $p_id);
        if (mysqli_stmt_execute($stmt)) {
            header("location:adminpanel.php?success=poem_updated#addpoem");
        } else {
            header("location:adminpanel.php?alert=poem_update_failed#addpoem");
        }
        mysqli_stmt_close($stmt);
    }
}

// ======== DELETE POEM ========
if (isset($_GET['prem']) && $_GET['prem'] > 0) {
    $id_to_delete = $_GET['prem'];
    $query_select = "SELECT `p_image` FROM `poems` WHERE `p_id`=?";
    $stmt_select = mysqli_prepare($con, $query_select);
    mysqli_stmt_bind_param($stmt_select, "i", $id_to_delete);
    mysqli_stmt_execute($stmt_select);
    $result = mysqli_stmt_get_result($stmt_select);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        image_remove($row['p_image']);
    }
    mysqli_stmt_close($stmt_select);

    $query_delete = "DELETE FROM `poems` WHERE `p_id`=?";
    $stmt_delete = mysqli_prepare($con, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, "i", $id_to_delete);
    if (mysqli_stmt_execute($stmt_delete)) {
        header("location:adminpanel.php?success=poem_removed#addpoem");
    } else {
        header("location:adminpanel.php?alert=poem_remove_failed#addpoem");
    }
    mysqli_stmt_close($stmt_delete);
}



// ===================================================
// ================= CHAPTERS LOGIC =====================
// ===================================================
// ======== SAVE NEW CHAPTER ========
if (isset($_POST['csave'])) {
    
    // This is the ID of the book you chose from the dropdown in the form.
    // This is the MOST IMPORTANT variable.
    $book_id = $_POST['book_id']; 

    $word_count = str_word_count($_POST['c_text']);

    // This query correctly includes `book_id` and does NOT include `c_id`.
    $query = "INSERT INTO `chapters`(`book_id`, `c_number`, `c_title`, `c_text`, `c_word_count`) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        
        // We bind the `$book_id` that you selected from the form.
        mysqli_stmt_bind_param($stmt, "iissi", 
            $book_id, 
            $_POST['c_number'], 
            $_POST['c_title'], 
            $_POST['c_text'], 
            $word_count
        );

        if(mysqli_stmt_execute($stmt)){
            header("location: adminpanel.php?success=chapter_added#addchap");
        } else {
            header("location: adminpanel.php?alert=chapter_add_failed#addchap");
        }
        mysqli_stmt_close($stmt);
    }
}

// ======== UPDATE EXISTING CHAPTER ========
if (isset($_POST['cupdate'])) {
    $c_id = $_POST['c_id'];
    $c_number = $_POST['c_number'];
    $c_title = $_POST['c_title'];
    $c_text = $_POST['c_text'];
    
    $word_count = str_word_count($c_text);

    $query = "UPDATE `chapters` SET `c_id`=?, `c_number`=?, `c_title`=?, `c_text`=?, `c_word_count`=? WHERE `c_id`=?";
    
    $stmt = mysqli_prepare($con, $query);
    if ($stmt) {
        // FIX: The types and variables now match the query perfectly (5 for SET, 1 for WHERE)
        mysqli_stmt_bind_param($stmt, "iissii", $c_id, $c_number, $c_title, $c_text, $word_count, $c_id);
        
        if (mysqli_stmt_execute($stmt)) {
            header("location:adminpanel.php?success=chapter_updated#addchap");
        } else {
            header("location:adminpanel.php?alert=chapter_update_failed#addchap");
        }
        mysqli_stmt_close($stmt);
    }
}

// ======== DELETE CHAPTER ========
if (isset($_GET['crem'])) {
    $code_to_delete = $_GET['crem'];
    $query_delete = "DELETE FROM `chapters` WHERE `c_id`=?";
    $stmt_delete = mysqli_prepare($con, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, "s", $code_to_delete);
    if (mysqli_stmt_execute($stmt_delete)) {
        header("location:adminpanel.php?success=chapter_removed#addchap");
    } else {
        header("location:adminpanel.php?alert=chapter_remove_failed#addchap");
    }
    mysqli_stmt_close($stmt_delete);
}
?>
