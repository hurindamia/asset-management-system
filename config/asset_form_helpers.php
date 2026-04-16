<?php

/*
|--------------------------------------------------------------------------
| Shared asset form helpers
|--------------------------------------------------------------------------
| These helpers keep save_asset.php and update_asset.php consistent when
| working with repeated child tables such as RAM, storage, monitor, and
| software.
*/

function asset_post($key, $default = ''){
    return $_POST[$key] ?? $default;
}

function asset_escape($conn, $value){
    return mysqli_real_escape_string($conn, trim((string)($value ?? '')));
}

function asset_first_value(array $source, array $keys, $default = ''){
    foreach($keys as $key){
        if(isset($source[$key]) && trim((string)$source[$key]) !== ''){
            return $source[$key];
        }
    }

    return $default;
}

function asset_delete_child_rows($conn, $table, $assetId){
    $allowedTables = ['cpu', 'monitor', 'ram', 'software', 'storage', 'asset_windows'];

    if(!in_array($table, $allowedTables, true)){
        return false;
    }

    $assetId = asset_escape($conn, $assetId);
    return mysqli_query($conn, "DELETE FROM {$table} WHERE asset_id='{$assetId}'");
}

function asset_insert_cpu_row($conn, $assetId, array $cpuData){
    $cpuModel  = asset_escape($conn, $cpuData['cpu_model'] ?? '');
    $cpuSpeed  = asset_escape($conn, $cpuData['cpu_speed'] ?? '');
    $cpuCore   = asset_escape($conn, $cpuData['cpu_core'] ?? '');
    $cpuThread = asset_escape($conn, $cpuData['cpu_thread'] ?? ($cpuData['cpu_hyper_thread'] ?? ''));
    $gpu       = asset_escape($conn, $cpuData['gpu'] ?? ($cpuData['graphic_card'] ?? ''));

    return mysqli_query(
        $conn,
        "INSERT INTO cpu (asset_id, cpu_model, cpu_speed, cpu_core, cpu_hyper_thread, graphic_card)
         VALUES ('{$assetId}', '{$cpuModel}', '{$cpuSpeed}', '{$cpuCore}', '{$cpuThread}', '{$gpu}')"
    );
}

function asset_insert_ram_rows($conn, $assetId, array $ramSizes){
    foreach($ramSizes as $ramSize){
        $ramSize = asset_escape($conn, $ramSize);

        if($ramSize === ''){
            continue;
        }

        mysqli_query(
            $conn,
            "INSERT INTO ram (asset_id, ram_size) VALUES ('{$assetId}', '{$ramSize}')"
        );
    }
}

function asset_insert_storage_rows($conn, $assetId, array $models, array $capacities = [], array $serials = []){
    $count = count($models);

    for($i = 0; $i < $count; $i++){
        $model    = asset_escape($conn, $models[$i] ?? '');
        $capacity = asset_escape($conn, $capacities[$i] ?? '');
        $serial   = asset_escape($conn, $serials[$i] ?? '');

        if($model === ''){
            continue;
        }

        mysqli_query(
            $conn,
            "INSERT INTO storage (asset_id, hdd_model, hdd_capacity, hdd_serial)
             VALUES ('{$assetId}', '{$model}', '{$capacity}', '{$serial}')"
        );
    }
}

function asset_insert_monitor_rows($conn, $assetId, array $models, array $sizes = [], array $serials = [], $skipDashOnly = true){
    $count = count($models);

    for($i = 0; $i < $count; $i++){
        $model  = asset_escape($conn, $models[$i] ?? '');
        $size   = asset_escape($conn, $sizes[$i] ?? '');
        $serial = asset_escape($conn, $serials[$i] ?? '');

        if($model === ''){
            continue;
        }

        if($skipDashOnly && $model === '-'){
            continue;
        }

        mysqli_query(
            $conn,
            "INSERT INTO monitor (asset_id, monitor_model, monitor_size, monitor_serial)
             VALUES ('{$assetId}', '{$model}', '{$size}', '{$serial}')"
        );
    }
}

function asset_insert_software_rows($conn, $assetId, array $softwareNames){
    foreach($softwareNames as $softwareName){
        $softwareName = asset_escape($conn, $softwareName);

        if($softwareName === ''){
            continue;
        }

        mysqli_query(
            $conn,
            "INSERT INTO software (asset_id, software_name)
             VALUES ('{$assetId}', '{$softwareName}')"
        );
    }
}

function asset_replace_cpu_row($conn, $assetId, array $cpuData){
    asset_delete_child_rows($conn, 'cpu', $assetId);
    asset_insert_cpu_row($conn, $assetId, $cpuData);
}

function asset_replace_ram_rows($conn, $assetId, array $ramSizes){
    asset_delete_child_rows($conn, 'ram', $assetId);
    asset_insert_ram_rows($conn, $assetId, $ramSizes);
}

function asset_replace_storage_rows($conn, $assetId, array $models, array $capacities = [], array $serials = []){
    asset_delete_child_rows($conn, 'storage', $assetId);
    asset_insert_storage_rows($conn, $assetId, $models, $capacities, $serials);
}

function asset_replace_monitor_rows($conn, $assetId, array $models, array $sizes = [], array $serials = [], $skipDashOnly = true){
    asset_delete_child_rows($conn, 'monitor', $assetId);
    asset_insert_monitor_rows($conn, $assetId, $models, $sizes, $serials, $skipDashOnly);
}

function asset_replace_software_rows($conn, $assetId, array $softwareNames){
    asset_delete_child_rows($conn, 'software', $assetId);
    asset_insert_software_rows($conn, $assetId, $softwareNames);
}
