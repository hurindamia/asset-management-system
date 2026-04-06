<?php
include "config/db.php";

$id = $_GET['id'] ?? 0;

if(!$id){
    die("❌ Invalid ID");
}

/* 🔹 1. GET USER ID */
$getUser = mysqli_query($conn, "SELECT user_id FROM assets WHERE asset_id='$id'");

if(!$getUser){
    die("Error fetching user: ".mysqli_error($conn));
}

$userData = mysqli_fetch_assoc($getUser);
$user_id = $userData['user_id'] ?? null;


/* 🔹 2. DELETE CHILD TABLES */
mysqli_query($conn, "DELETE FROM ram WHERE asset_id='$id'");
mysqli_query($conn, "DELETE FROM storage WHERE asset_id='$id'");
mysqli_query($conn, "DELETE FROM monitor WHERE asset_id='$id'");
mysqli_query($conn, "DELETE FROM software WHERE asset_id='$id'");
mysqli_query($conn, "DELETE FROM cpu WHERE asset_id='$id'");


/* 🔹 3. DELETE MAIN ASSET */
$deleteAsset = mysqli_query($conn, "DELETE FROM assets WHERE asset_id='$id'");

if(!$deleteAsset){
    die("Delete Error: ".mysqli_error($conn));
}


/* 🔹 4. DELETE USER IF NO MORE ASSETS */
if($user_id){

    $check = mysqli_query($conn, "SELECT * FROM assets WHERE user_id='$user_id'");

    if(mysqli_num_rows($check) == 0){
        mysqli_query($conn, "DELETE FROM users WHERE user_id='$user_id'");
    }
}


/* 🔹 DONE */
echo "<script>
alert('🗑️ Asset deleted successfully!');
window.location.href='asset_list.php';
</script>";
?>