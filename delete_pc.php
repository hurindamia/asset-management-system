<?php

include "config/db.php";

if(isset($_GET['id'])){

$pc_id = $_GET['id'];

/* delete related tables first */

$conn->query("DELETE FROM cpu WHERE PC_ID='$pc_id'");

$conn->query("DELETE FROM ram WHERE PC_ID='$pc_id'");

$conn->query("DELETE FROM storage WHERE PC_ID='$pc_id'");

$conn->query("DELETE FROM monitor WHERE PC_ID='$pc_id'");

$conn->query("DELETE FROM windows WHERE PC_ID='$pc_id'");

/* delete main PC */

$conn->query("DELETE FROM pc WHERE PC_ID='$pc_id'");

/* redirect back */

header("Location: hardware.php");

exit();

}

?>