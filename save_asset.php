<?php
include "config/db.php";

// =====================
// HELPER — escape
// =====================
function e($conn, $val){
    return mysqli_real_escape_string($conn, trim($val ?? ''));
}


// =====================
// 1. INSERT OR UPDATE USER
// =====================
$name           = e($conn, $_POST['name']);
$position       = e($conn, $_POST['position']);
$contact_no     = e($conn, $_POST['contact_no']);
$email_id       = e($conn, $_POST['email_id']);
$email_password = e($conn, $_POST['email_password']);
$mail_server    = e($conn, $_POST['mail_server']);
$pc_username    = e($conn, $_POST['pc_username']);
$pc_password    = e($conn, $_POST['pc_password']);

$prefill_user_id = intval($_POST['prefill_user_id'] ?? 0);

if($prefill_user_id > 0){
    // User already exists — UPDATE their info and reuse their user_id
    mysqli_query($conn, "UPDATE users SET
        name='$name', position='$position', contact_no='$contact_no',
        email_id='$email_id', email_password='$email_password', mail_server='$mail_server'
        WHERE user_id='$prefill_user_id'");
    $user_id = $prefill_user_id;
} else {
    // New user — INSERT
    $userQuery = "INSERT INTO users 
    (name, position, contact_no, email_id, email_password, mail_server)
    VALUES 
    ('$name','$position','$contact_no','$email_id','$email_password','$mail_server')";
    mysqli_query($conn, $userQuery);
    $user_id = mysqli_insert_id($conn);
}


// =====================
// 2. LOOP EACH DEVICE
// =====================
if(!empty($_POST['devices']) && is_array($_POST['devices'])){

    foreach($_POST['devices'] as $device){

        $asset_type = e($conn, $device['asset_type'] ?? '');
        if(empty($asset_type)) continue;


        /* ==============================
           DESKTOP / LAPTOP
        ============================== */
        if($asset_type === 'Desktop' || $asset_type === 'Laptop'){

            $pc_model    = e($conn, $device['pc_model'] ?? '');
            $pc_name     = e($conn, $device['pc_name'] ?? '');
            $mac_lan     = e($conn, $device['mac_lan'] ?? '');
            $mac_wifi    = e($conn, $device['mac_wifi'] ?? '');
            $antivirus   = e($conn, $device['antivirus'] ?? '');
            $windows_key = e($conn, $device['windows_key'] ?? '');

            $assetQuery = "INSERT INTO assets 
            (user_id, asset_type, pc_username, pc_password, pc_model, pc_name, mac_lan, mac_wifi, antivirus, windows_key)
            VALUES
            ('$user_id','$asset_type','$pc_username','$pc_password','$pc_model','$pc_name','$mac_lan','$mac_wifi','$antivirus','$windows_key')";

            mysqli_query($conn, $assetQuery);
            $asset_id = mysqli_insert_id($conn);

            // CPU
            $cpu_model  = e($conn, $device['cpu_model'] ?? '');
            $cpu_speed  = e($conn, $device['cpu_speed'] ?? '');
            $cpu_core   = e($conn, $device['cpu_core'] ?? '');
            $cpu_thread = e($conn, $device['cpu_thread'] ?? '');
            $gpu        = e($conn, $device['gpu'] ?? '');

            $cpuQuery = "INSERT INTO cpu 
            (asset_id, cpu_model, cpu_speed, cpu_core, cpu_hyper_thread, graphic_card)
            VALUES
            ('$asset_id','$cpu_model','$cpu_speed','$cpu_core','$cpu_thread','$gpu')";

            mysqli_query($conn, $cpuQuery);

            // RAM (multiple)
            if(!empty($device['ram_size']) && is_array($device['ram_size'])){
                foreach($device['ram_size'] as $ram){
                    $ram = e($conn, $ram);
                    if($ram != ''){
                        mysqli_query($conn, "INSERT INTO ram (asset_id, ram_size)
                                            VALUES ('$asset_id','$ram')");
                    }
                }
            }

            // STORAGE (multiple)
            if(!empty($device['hdd_model']) && is_array($device['hdd_model'])){
                $hdd_models  = $device['hdd_model'];
                $hdd_caps    = $device['hdd_capacity'] ?? [];
                $hdd_serials = $device['hdd_serial'] ?? [];

                for($i = 0; $i < count($hdd_models); $i++){
                    $hdd_model  = e($conn, $hdd_models[$i]);
                    $hdd_cap    = e($conn, $hdd_caps[$i] ?? '');
                    $hdd_serial = e($conn, $hdd_serials[$i] ?? '');

                    if($hdd_model != ''){
                        mysqli_query($conn, "INSERT INTO storage (asset_id, hdd_model, hdd_capacity, hdd_serial)
                                            VALUES ('$asset_id','$hdd_model','$hdd_cap','$hdd_serial')");
                    }
                }
            }

            // MONITOR (multiple) — skip empty or dash-only rows
            if(!empty($device['monitor_model']) && is_array($device['monitor_model'])){
                $m_models  = $device['monitor_model'];
                $m_sizes   = $device['monitor_size'] ?? [];
                $m_serials = $device['monitor_serial'] ?? [];

                for($i = 0; $i < count($m_models); $i++){
                    $m_model  = e($conn, $m_models[$i]);
                    $m_size   = e($conn, $m_sizes[$i] ?? '');
                    $m_serial = e($conn, $m_serials[$i] ?? '');

                    /* Skip blank or dash-placeholder rows */
                    if($m_model != '' && $m_model != '-'){
                        mysqli_query($conn, "INSERT INTO monitor (asset_id, monitor_model, monitor_size, monitor_serial)
                                            VALUES ('$asset_id','$m_model','$m_size','$m_serial')");
                    }
                }
            }

            // SOFTWARE (multiple)
            if(!empty($device['software']) && is_array($device['software'])){
                foreach($device['software'] as $s){
                    $s = e($conn, $s);
                    if($s != ''){
                        mysqli_query($conn, "INSERT INTO software (asset_id, software_name)
                                            VALUES ('$asset_id','$s')");
                    }
                }
            }

        }


        /* ==============================
           iPAD
        ============================== */
        else if($asset_type === 'iPad'){

            $model         = e($conn, $device['model'] ?? '');
            $serial_no     = e($conn, $device['serial'] ?? '');
            $storage_cap   = e($conn, $device['storage_capacity'] ?? '');
            $os_version    = e($conn, $device['ios_version'] ?? '');
            $imei          = e($conn, $device['imei'] ?? '');
            $apple_id      = e($conn, $device['apple_id'] ?? '');
            $apple_password= e($conn, $device['apple_password'] ?? '');
            $mac_wifi      = e($conn, $device['mac_wifi'] ?? '');
            $sim_no        = e($conn, $device['sim_no'] ?? '');

            $assetQuery = "INSERT INTO assets 
            (user_id, asset_type, pc_model, serial_no, storage_capacity, os_version, imei, apple_id, apple_password, mac_wifi, sim_no)
            VALUES
            ('$user_id','iPad','$model','$serial_no','$storage_cap','$os_version','$imei','$apple_id','$apple_password','$mac_wifi','$sim_no')";

            mysqli_query($conn, $assetQuery);
            $asset_id = mysqli_insert_id($conn);

            // APPS (software table)
            if(!empty($device['software']) && is_array($device['software'])){
                foreach($device['software'] as $s){
                    $s = e($conn, $s);
                    if($s != ''){
                        mysqli_query($conn, "INSERT INTO software (asset_id, software_name)
                                            VALUES ('$asset_id','$s')");
                    }
                }
            }

        }


        /* ==============================
           PHONE
        ============================== */
        else if($asset_type === 'Phone'){

            $model          = e($conn, $device['model'] ?? '');
            $serial_no      = e($conn, $device['serial'] ?? '');
            $imei           = e($conn, $device['imei'] ?? '');
            $os_version     = e($conn, $device['os_version'] ?? '');
            $storage_cap    = e($conn, $device['storage_capacity'] ?? '');
            $sim_no         = e($conn, $device['sim_no'] ?? '');
            $carrier        = e($conn, $device['carrier'] ?? '');
            $mac_wifi       = e($conn, $device['mac_wifi'] ?? '');
            $account_email  = e($conn, $device['account_email'] ?? '');
            $account_password = e($conn, $device['account_password'] ?? '');

            $assetQuery = "INSERT INTO assets 
            (user_id, asset_type, pc_model, serial_no, imei, os_version, storage_capacity, sim_no, carrier, mac_wifi, account_email, account_password)
            VALUES
            ('$user_id','Phone','$model','$serial_no','$imei','$os_version','$storage_cap','$sim_no','$carrier','$mac_wifi','$account_email','$account_password')";

            mysqli_query($conn, $assetQuery);

        }

    } // end foreach device

} // end if devices


// =====================
// DONE
// =====================
header("Location: users.php");
exit();
?>