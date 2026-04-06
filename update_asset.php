<?php
include "config/db.php";

/* =========================
   SAFE HELPER
========================= */
function v($key){
    return $_POST[$key] ?? '';
}

$id         = v('id');
$user_id    = v('user_id');
$asset_type = v('asset_type');

/* =========================
   UPDATE USER
========================= */
$conn->query("UPDATE users SET
name='".v('name')."',
position='".v('position')."',
contact_no='".v('contact_no')."',
email_id='".v('email_id')."',
email_password='".v('email_password')."',
mail_server='".v('mail_server')."'
WHERE user_id='$user_id'");


/* ===================================================
   DEVICE UPDATE
=================================================== */

/* =========================
   DESKTOP / LAPTOP
========================= */
if($asset_type === 'Desktop' || $asset_type === 'Laptop'){

    $conn->query("UPDATE assets SET
    pc_username='".v('pc_username')."',
    pc_password='".v('pc_password')."',
    pc_model='".v('pc_model')."',
    pc_name='".v('pc_name')."',
    mac_lan='".v('mac_lan')."',
    mac_wifi='".v('mac_wifi')."',
    antivirus='".v('antivirus')."',
    windows_key='".v('windows_key')."'
    WHERE asset_id='$id'");

    /* CPU */
    $conn->query("DELETE FROM cpu WHERE asset_id='$id'");
    $conn->query("INSERT INTO cpu 
    (asset_id, cpu_model, cpu_speed, cpu_core, cpu_hyper_thread, graphic_card)
    VALUES (
        '$id',
        '".v('cpu_model')."',
        '".v('cpu_speed')."',
        '".v('cpu_core')."',
        '".v('cpu_hyper_thread')."',
        '".v('graphic_card')."'
    )");

    /* RAM */
    $conn->query("DELETE FROM ram WHERE asset_id='$id'");
    if(!empty($_POST['ram_size'])){
        foreach($_POST['ram_size'] as $ram){
            if(trim($ram) != ""){
                $conn->query("INSERT INTO ram (asset_id, ram_size)
                VALUES ('$id','$ram')");
            }
        }
    }

    /* STORAGE */
    $conn->query("DELETE FROM storage WHERE asset_id='$id'");
    if(!empty($_POST['hdd_model'])){
        $models = $_POST['hdd_model'];
        $caps   = $_POST['hdd_capacity'] ?? [];
        $serial = $_POST['hdd_serial'] ?? [];

        for($i=0; $i<count($models); $i++){
            if(trim($models[$i]) != ""){
                $conn->query("INSERT INTO storage 
                (asset_id, hdd_model, hdd_capacity, hdd_serial)
                VALUES (
                    '$id',
                    '".$models[$i]."',
                    '".$caps[$i]."',
                    '".$serial[$i]."'
                )");
            }
        }
    }

    /* MONITOR */
    $conn->query("DELETE FROM monitor WHERE asset_id='$id'");
    if(!empty($_POST['monitor_model'])){
        $models = $_POST['monitor_model'];
        $sizes  = $_POST['monitor_size'] ?? [];
        $serial = $_POST['monitor_serial'] ?? [];

        for($i=0; $i<count($models); $i++){
            if(trim($models[$i]) != ""){
                $conn->query("INSERT INTO monitor 
                (asset_id, monitor_model, monitor_size, monitor_serial)
                VALUES (
                    '$id',
                    '".$models[$i]."',
                    '".$sizes[$i]."',
                    '".$serial[$i]."'
                )");
            }
        }
    }

    /* SOFTWARE */
    $conn->query("DELETE FROM software WHERE asset_id='$id'");
    if(!empty($_POST['software'])){
        foreach($_POST['software'] as $s){
            if(trim($s) != ""){
                $conn->query("INSERT INTO software (asset_id, software_name)
                VALUES ('$id','$s')");
            }
        }
    }
}


/* =========================
   IPAD
========================= */
else if($asset_type === 'iPad'){

    $conn->query("UPDATE assets SET
    pc_model='".v('pc_model')."',
    serial_no='".v('serial_no')."',
    storage_capacity='".v('storage_capacity')."',
    os_version='".v('os_version')."',
    imei='".v('imei')."',
    apple_id='".v('apple_id')."',
    apple_password='".v('apple_password')."',
    mac_wifi='".v('mac_wifi')."',
    sim_no='".v('sim_no')."'
    WHERE asset_id='$id'");

    /* ✅ FIX: SOFTWARE UPDATE */
    $conn->query("DELETE FROM software WHERE asset_id='$id'");

    if(!empty($_POST['software'])){
        foreach($_POST['software'] as $s){
            if(trim($s) != ""){
                $conn->query("INSERT INTO software (asset_id, software_name)
                VALUES ('$id','$s')");
            }
        }
    }
}


/* =========================
   PHONE
========================= */
else if($asset_type === 'Phone'){

    $conn->query("UPDATE assets SET
    pc_model='".v('pc_model')."',
    serial_no='".v('serial_no')."',
    imei='".v('imei')."',
    os_version='".v('os_version')."',
    storage_capacity='".v('storage_capacity')."',
    sim_no='".v('sim_no')."',
    carrier='".v('carrier')."',
    mac_wifi='".v('mac_wifi')."',
    account_email='".v('account_email')."',
    account_password='".v('account_password')."'
    WHERE asset_id='$id'");

    /* ✅ FIX: SOFTWARE UPDATE */
    $conn->query("DELETE FROM software WHERE asset_id='$id'");

    if(!empty($_POST['software'])){
        foreach($_POST['software'] as $s){
            if(trim($s) != ""){
                $conn->query("INSERT INTO software (asset_id, software_name)
                VALUES ('$id','$s')");
            }
        }
    }
}


/* =========================
   SUCCESS
========================= */
echo "<script>
alert('✅ Asset updated successfully!');
window.location.href='asset_detail.php?id=$id';
</script>";
?>