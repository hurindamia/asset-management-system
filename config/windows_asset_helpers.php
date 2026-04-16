<?php

/*
|--------------------------------------------------------------------------
| Windows asset helper functions
|--------------------------------------------------------------------------
| Shared logic for handling up to 10 Windows entries per computer asset.
| Each entry stores:
| - window__os
| - windows_serial
*/

if(!defined('ASSET_WINDOWS_MAX_ITEMS')){
    define('ASSET_WINDOWS_MAX_ITEMS', 10);
}

if(!function_exists('asset_windows_table_exists')){
    function asset_windows_table_exists($conn): bool{
        static $cache = null;

        if($cache !== null){
            return $cache;
        }

        $result = mysqli_query($conn, "SHOW TABLES LIKE 'asset_windows'");
        $cache = $result && mysqli_num_rows($result) > 0;

        if($result){
            mysqli_free_result($result);
        }

        return $cache;
    }
}

if(!function_exists('asset_windows_array_values')){
    function asset_windows_array_values($value): array{
        if(is_array($value)){
            return array_values($value);
        }

        $text = trim((string)$value);
        if($text === ''){
            return [];
        }

        return [$text];
    }
}

if(!function_exists('asset_windows_normalize_entries')){
    function asset_windows_normalize_entries(array $oses, array $serials = [], int $max = ASSET_WINDOWS_MAX_ITEMS): array{
        $items = [];
        $count = max(count($oses), count($serials));

        for($i = 0; $i < $count; $i++){
            if(count($items) >= $max){
                break;
            }

            $os = trim((string)($oses[$i] ?? ''));
            $serial = trim((string)($serials[$i] ?? ''));

            if($os === ''){
                continue;
            }

            $items[] = [
                'window_index' => count($items) + 1,
                'window__os' => $os,
                'windows_serial' => $serial,
            ];
        }

        return $items;
    }
}

if(!function_exists('asset_windows_from_payload')){
    function asset_windows_from_payload(array $payload, int $max = ASSET_WINDOWS_MAX_ITEMS): array{
        $osValues = [];
        if(array_key_exists('window__os', $payload)){
            $osValues = asset_windows_array_values($payload['window__os']);
        } elseif(array_key_exists('windows_key', $payload)){
            $osValues = asset_windows_array_values($payload['windows_key']);
        } elseif(array_key_exists('windows', $payload)){
            $osValues = asset_windows_array_values($payload['windows']);
        }

        $serialValues = [];
        if(array_key_exists('windows_serial', $payload)){
            $serialValues = asset_windows_array_values($payload['windows_serial']);
        }

        return asset_windows_normalize_entries($osValues, $serialValues, $max);
    }
}

if(!function_exists('asset_get_primary_windows_os')){
    function asset_get_primary_windows_os(array $items): string{
        return trim((string)($items[0]['window__os'] ?? ''));
    }
}

if(!function_exists('asset_insert_windows_rows')){
    function asset_insert_windows_rows($conn, $assetId, array $windowsEntries): bool{
        if(!asset_windows_table_exists($conn)){
            return false;
        }

        $assetId = asset_escape($conn, $assetId);

        foreach($windowsEntries as $entry){
            $windowIndex = (int)($entry['window_index'] ?? 0);
            $windowOs = asset_escape($conn, $entry['window__os'] ?? '');
            $windowSerial = asset_escape($conn, $entry['windows_serial'] ?? '');

            if($windowIndex < 1 || $windowOs === ''){
                continue;
            }

            mysqli_query(
                $conn,
                "INSERT INTO asset_windows (asset_id, window_index, window__os, windows_serial)
                 VALUES ('{$assetId}', '{$windowIndex}', '{$windowOs}', '{$windowSerial}')"
            );
        }

        return true;
    }
}

if(!function_exists('asset_replace_windows_rows')){
    function asset_replace_windows_rows($conn, $assetId, array $windowsEntries): bool{
        if(!asset_windows_table_exists($conn)){
            return false;
        }

        asset_delete_child_rows($conn, 'asset_windows', $assetId);
        asset_insert_windows_rows($conn, $assetId, $windowsEntries);
        return true;
    }
}

if(!function_exists('asset_fetch_windows_map')){
    function asset_fetch_windows_map($conn, array $assetIds): array{
        $map = [];

        if(empty($assetIds) || !asset_windows_table_exists($conn)){
            return $map;
        }

        $safeIds = [];
        foreach($assetIds as $assetId){
            $assetId = (int)$assetId;
            if($assetId > 0){
                $safeIds[$assetId] = $assetId;
            }
        }

        if(empty($safeIds)){
            return $map;
        }

        $idList = implode(',', $safeIds);
        $sql = "
            SELECT asset_id, window_index, window__os, windows_serial
            FROM asset_windows
            WHERE asset_id IN ({$idList})
            ORDER BY asset_id ASC, window_index ASC, window_id ASC
        ";

        $result = mysqli_query($conn, $sql);
        if(!$result){
            return $map;
        }

        while($row = mysqli_fetch_assoc($result)){
            $assetId = (int)($row['asset_id'] ?? 0);
            if($assetId <= 0){
                continue;
            }

            if(!isset($map[$assetId])){
                $map[$assetId] = [];
            }

            if(count($map[$assetId]) >= ASSET_WINDOWS_MAX_ITEMS){
                continue;
            }

            $map[$assetId][] = [
                'window_index' => count($map[$assetId]) + 1,
                'window__os' => trim((string)($row['window__os'] ?? '')),
                'windows_serial' => trim((string)($row['windows_serial'] ?? '')),
            ];
        }

        mysqli_free_result($result);
        return $map;
    }
}

if(!function_exists('asset_get_windows_items_for_asset')){
    function asset_get_windows_items_for_asset(array $windowsMap, int $assetId, string $legacyWindowsKey = ''): array{
        $items = $windowsMap[$assetId] ?? [];

        if(empty($items)){
            $legacy = trim($legacyWindowsKey);
            if($legacy !== ''){
                $items[] = [
                    'window_index' => 1,
                    'window__os' => $legacy,
                    'windows_serial' => '',
                ];
            }
        }

        return array_slice($items, 0, ASSET_WINDOWS_MAX_ITEMS);
    }
}

