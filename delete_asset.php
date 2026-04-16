<?php
include "config/db.php";

$id = (int)($_GET['id'] ?? 0);
if($id <= 0){
    die("Invalid ID");
}

$getUser = mysqli_query($conn, "SELECT user_id FROM assets WHERE asset_id='{$id}'");
if(!$getUser){
    die("Error fetching user: ".mysqli_error($conn));
}

$userData = mysqli_fetch_assoc($getUser);
$userId = (int)($userData['user_id'] ?? 0);

mysqli_query($conn, "DELETE FROM ram WHERE asset_id='{$id}'");
mysqli_query($conn, "DELETE FROM storage WHERE asset_id='{$id}'");
mysqli_query($conn, "DELETE FROM monitor WHERE asset_id='{$id}'");
mysqli_query($conn, "DELETE FROM software WHERE asset_id='{$id}'");
mysqli_query($conn, "DELETE FROM cpu WHERE asset_id='{$id}'");
mysqli_query($conn, "DELETE FROM asset_windows WHERE asset_id='{$id}'");

$deleteAsset = mysqli_query($conn, "DELETE FROM assets WHERE asset_id='{$id}'");
if(!$deleteAsset){
    die("Delete Error: ".mysqli_error($conn));
}

if($userId > 0){
    $check = mysqli_query($conn, "SELECT 1 FROM assets WHERE user_id='{$userId}' LIMIT 1");
    if($check && mysqli_num_rows($check) === 0){
        mysqli_query($conn, "DELETE FROM users WHERE user_id='{$userId}'");
    }
}

echo "<script>
alert('Asset deleted successfully!');
window.location.href='asset_list_option2.php';
</script>";
?>
