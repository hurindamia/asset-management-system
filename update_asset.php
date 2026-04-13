<?php
include "config/db.php";
include "config/asset_form_helpers.php";

function asset_resolve_return_path(string $rawPath, string $fallback = 'asset_list_option2.php'): string{
    $rawPath = trim($rawPath);
    if($rawPath === '' || preg_match('/^\s*(javascript|data):/i', $rawPath)){
        return $fallback;
    }

    $parts = parse_url($rawPath);
    if($parts === false){
        return $fallback;
    }

    if(isset($parts['host']) || isset($parts['scheme'])){
        $targetHost = strtolower((string)($parts['host'] ?? ''));
        $currentHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if($targetHost === '' || $currentHost === '' || $targetHost !== $currentHost){
            return $fallback;
        }
    }

    $path = (string)($parts['path'] ?? '');
    if($path === '' || strpos($path, '..') !== false){
        return $fallback;
    }

    if(substr($path, 0, 1) === '/'){
        $basePath = '/asset_management_normalization/';
        if(strpos($path, $basePath) === 0){
            $path = substr($path, strlen($basePath));
        } else {
            $path = ltrim($path, '/');
        }
    }

    if($path === '' || strpos($path, ':') !== false){
        return $fallback;
    }

    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    return $path . $query;
}

/*
|--------------------------------------------------------------------------
| Load the current asset context
|--------------------------------------------------------------------------
*/

$assetId   = asset_escape($conn, asset_post('id'));
$userId    = asset_escape($conn, asset_post('user_id'));
$assetType = asset_escape($conn, asset_post('asset_type'));

/*
|--------------------------------------------------------------------------
| Update shared user details
|--------------------------------------------------------------------------
| The edit form always includes the owning user's profile fields, so they are
| updated before device-specific data is replaced.
*/

mysqli_query(
    $conn,
    "UPDATE users SET
        name='" . asset_escape($conn, asset_post('name')) . "',
        position='" . asset_escape($conn, asset_post('position')) . "',
        contact_no='" . asset_escape($conn, asset_post('contact_no')) . "',
        email_id='" . asset_escape($conn, asset_post('email_id')) . "',
        email_password='" . asset_escape($conn, asset_post('email_password')) . "',
        mail_server='" . asset_escape($conn, asset_post('mail_server')) . "'
     WHERE user_id='{$userId}'"
);

/*
|--------------------------------------------------------------------------
| Replace device-specific data
|--------------------------------------------------------------------------
*/

if($assetType === 'Desktop' || $assetType === 'Laptop'){
    mysqli_query(
        $conn,
        "UPDATE assets SET
            pc_username='" . asset_escape($conn, asset_post('pc_username')) . "',
            pc_password='" . asset_escape($conn, asset_post('pc_password')) . "',
            pc_model='" . asset_escape($conn, asset_post('pc_model')) . "',
            pc_name='" . asset_escape($conn, asset_post('pc_name')) . "',
            pc_serial_no='" . asset_escape($conn, asset_post('pc_serial_no')) . "',
            mac_lan='" . asset_escape($conn, asset_post('mac_lan')) . "',
            mac_wifi='" . asset_escape($conn, asset_post('mac_wifi')) . "',
            antivirus='" . asset_escape($conn, asset_post('antivirus')) . "',
            windows_key='" . asset_escape($conn, asset_post('windows_key')) . "'
         WHERE asset_id='{$assetId}'"
    );

    asset_replace_cpu_row(
        $conn,
        $assetId,
        [
            'cpu_model'        => asset_post('cpu_model'),
            'cpu_speed'        => asset_post('cpu_speed'),
            'cpu_core'         => asset_post('cpu_core'),
            'cpu_hyper_thread' => asset_post('cpu_hyper_thread'),
            'graphic_card'     => asset_post('graphic_card'),
        ]
    );

    asset_replace_ram_rows($conn, $assetId, $_POST['ram_size'] ?? []);
    asset_replace_storage_rows(
        $conn,
        $assetId,
        $_POST['hdd_model'] ?? [],
        $_POST['hdd_capacity'] ?? [],
        $_POST['hdd_serial'] ?? []
    );
    asset_replace_monitor_rows(
        $conn,
        $assetId,
        $_POST['monitor_model'] ?? [],
        $_POST['monitor_size'] ?? [],
        $_POST['monitor_serial'] ?? [],
        true
    );
    asset_replace_software_rows($conn, $assetId, $_POST['software'] ?? []);
} elseif($assetType === 'iPad'){
    mysqli_query(
        $conn,
        "UPDATE assets SET
            pc_model='" . asset_escape($conn, asset_post('pc_model')) . "',
            serial_no='" . asset_escape($conn, asset_post('serial_no')) . "',
            storage_capacity='" . asset_escape($conn, asset_post('storage_capacity')) . "',
            os_version='" . asset_escape($conn, asset_post('os_version')) . "',
            imei='" . asset_escape($conn, asset_post('imei')) . "',
            apple_id='" . asset_escape($conn, asset_post('apple_id')) . "',
            apple_password='" . asset_escape($conn, asset_post('apple_password')) . "',
            mac_wifi='" . asset_escape($conn, asset_post('mac_wifi')) . "',
            sim_no='" . asset_escape($conn, asset_post('sim_no')) . "'
         WHERE asset_id='{$assetId}'"
    );

    asset_replace_software_rows($conn, $assetId, $_POST['software'] ?? []);
} elseif($assetType === 'Phone'){
    mysqli_query(
        $conn,
        "UPDATE assets SET
            pc_model='" . asset_escape($conn, asset_post('pc_model')) . "',
            serial_no='" . asset_escape($conn, asset_post('serial_no')) . "',
            imei='" . asset_escape($conn, asset_post('imei')) . "',
            os_version='" . asset_escape($conn, asset_post('os_version')) . "',
            storage_capacity='" . asset_escape($conn, asset_post('storage_capacity')) . "',
            sim_no='" . asset_escape($conn, asset_post('sim_no')) . "',
            carrier='" . asset_escape($conn, asset_post('carrier')) . "',
            mac_wifi='" . asset_escape($conn, asset_post('mac_wifi')) . "',
            account_email='" . asset_escape($conn, asset_post('account_email')) . "',
            account_password='" . asset_escape($conn, asset_post('account_password')) . "'
         WHERE asset_id='{$assetId}'"
    );

    asset_replace_software_rows($conn, $assetId, $_POST['software'] ?? []);
}

$returnTo = asset_resolve_return_path((string)($_POST['return_to'] ?? ''));
header("Location: {$returnTo}");
exit();
