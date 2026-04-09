<?php
include "config/db.php";
include "config/asset_form_helpers.php";

/*
|--------------------------------------------------------------------------
| Save the user first
|--------------------------------------------------------------------------
| Every new asset row depends on a valid user_id, so the form starts by
| either updating the prefilled user or creating a new one.
*/

$name           = asset_escape($conn, asset_post('name'));
$position       = asset_escape($conn, asset_post('position'));
$contactNo      = asset_escape($conn, asset_post('contact_no'));
$emailId        = asset_escape($conn, asset_post('email_id'));
$emailPassword  = asset_escape($conn, asset_post('email_password'));
$mailServer     = asset_escape($conn, asset_post('mail_server'));
$pcUsername     = asset_escape($conn, asset_post('pc_username'));
$pcPassword     = asset_escape($conn, asset_post('pc_password'));
$prefillUserId  = (int)asset_post('prefill_user_id', 0);

if($prefillUserId > 0){
    mysqli_query(
        $conn,
        "UPDATE users SET
            name='{$name}',
            position='{$position}',
            contact_no='{$contactNo}',
            email_id='{$emailId}',
            email_password='{$emailPassword}',
            mail_server='{$mailServer}'
         WHERE user_id='{$prefillUserId}'"
    );

    $userId = $prefillUserId;
} else {
    mysqli_query(
        $conn,
        "INSERT INTO users (name, position, contact_no, email_id, email_password, mail_server)
         VALUES ('{$name}', '{$position}', '{$contactNo}', '{$emailId}', '{$emailPassword}', '{$mailServer}')"
    );

    $userId = mysqli_insert_id($conn);
}

/*
|--------------------------------------------------------------------------
| Save each submitted device
|--------------------------------------------------------------------------
| New devices arrive through devices[index][field]. Each type writes to the
| main assets table first, then to the related child tables if needed.
*/

$devices = $_POST['devices'] ?? [];

if(is_array($devices)){
    foreach($devices as $device){
        $assetType = asset_escape($conn, $device['asset_type'] ?? '');

        if($assetType === ''){
            continue;
        }

        if($assetType === 'Desktop' || $assetType === 'Laptop'){
            $pcModel    = asset_escape($conn, $device['pc_model'] ?? '');
            $pcName     = asset_escape($conn, $device['pc_name'] ?? '');
            $macLan     = asset_escape($conn, $device['mac_lan'] ?? '');
            $macWifi    = asset_escape($conn, $device['mac_wifi'] ?? '');
            $antivirus  = asset_escape($conn, $device['antivirus'] ?? '');
            $windowsKey = asset_escape($conn, asset_first_value($device, ['windows_key', 'windows']));

            mysqli_query(
                $conn,
                "INSERT INTO assets
                    (user_id, asset_type, pc_username, pc_password, pc_model, pc_name, mac_lan, mac_wifi, antivirus, windows_key)
                 VALUES
                    ('{$userId}', '{$assetType}', '{$pcUsername}', '{$pcPassword}', '{$pcModel}', '{$pcName}', '{$macLan}', '{$macWifi}', '{$antivirus}', '{$windowsKey}')"
            );

            $assetId = mysqli_insert_id($conn);

            asset_insert_cpu_row($conn, $assetId, $device);
            asset_insert_ram_rows($conn, $assetId, $device['ram_size'] ?? []);
            asset_insert_storage_rows(
                $conn,
                $assetId,
                $device['hdd_model'] ?? [],
                $device['hdd_capacity'] ?? [],
                $device['hdd_serial'] ?? []
            );
            asset_insert_monitor_rows(
                $conn,
                $assetId,
                $device['monitor_model'] ?? [],
                $device['monitor_size'] ?? [],
                $device['monitor_serial'] ?? [],
                true
            );
            asset_insert_software_rows($conn, $assetId, $device['software'] ?? []);

            continue;
        }

        if($assetType === 'iPad'){
            $model         = asset_escape($conn, $device['model'] ?? '');
            $serialNo      = asset_escape($conn, $device['serial'] ?? '');
            $storageCap    = asset_escape($conn, $device['storage_capacity'] ?? '');
            $osVersion     = asset_escape($conn, $device['ios_version'] ?? '');
            $imei          = asset_escape($conn, $device['imei'] ?? '');
            $appleId       = asset_escape($conn, $device['apple_id'] ?? '');
            $applePassword = asset_escape($conn, $device['apple_password'] ?? '');
            $macWifi       = asset_escape($conn, $device['mac_wifi'] ?? '');
            $simNo         = asset_escape($conn, $device['sim_no'] ?? '');

            mysqli_query(
                $conn,
                "INSERT INTO assets
                    (user_id, asset_type, pc_model, serial_no, storage_capacity, os_version, imei, apple_id, apple_password, mac_wifi, sim_no)
                 VALUES
                    ('{$userId}', 'iPad', '{$model}', '{$serialNo}', '{$storageCap}', '{$osVersion}', '{$imei}', '{$appleId}', '{$applePassword}', '{$macWifi}', '{$simNo}')"
            );

            $assetId = mysqli_insert_id($conn);
            asset_insert_software_rows($conn, $assetId, $device['software'] ?? []);

            continue;
        }

        if($assetType === 'Phone'){
            $model           = asset_escape($conn, $device['model'] ?? '');
            $serialNo        = asset_escape($conn, $device['serial'] ?? '');
            $imei            = asset_escape($conn, $device['imei'] ?? '');
            $osVersion       = asset_escape($conn, $device['os_version'] ?? '');
            $storageCap      = asset_escape($conn, $device['storage_capacity'] ?? '');
            $simNo           = asset_escape($conn, $device['sim_no'] ?? '');
            $carrier         = asset_escape($conn, $device['carrier'] ?? '');
            $macWifi         = asset_escape($conn, $device['mac_wifi'] ?? '');
            $accountEmail    = asset_escape($conn, $device['account_email'] ?? '');
            $accountPassword = asset_escape($conn, $device['account_password'] ?? '');

            mysqli_query(
                $conn,
                "INSERT INTO assets
                    (user_id, asset_type, pc_model, serial_no, imei, os_version, storage_capacity, sim_no, carrier, mac_wifi, account_email, account_password)
                 VALUES
                    ('{$userId}', 'Phone', '{$model}', '{$serialNo}', '{$imei}', '{$osVersion}', '{$storageCap}', '{$simNo}', '{$carrier}', '{$macWifi}', '{$accountEmail}', '{$accountPassword}')"
            );
        }
    }
}

header("Location: users.php");
exit();
