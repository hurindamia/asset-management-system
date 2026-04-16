<?php
include "config/db.php";
include_once "config/windows_asset_helpers.php";

function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function getMaskedPasswordDots(string $password): string{
    $length = strlen($password);
    $dotCount = max(6, min(12, $length > 0 ? $length : 6));
    return str_repeat('&bull;', $dotCount);
}

function renderPasswordCell(string $password): string{
    $password = trim($password);
    if($password === ''){
        return '<span class="asset-item-empty">N/A</span>';
    }

    $masked = getMaskedPasswordDots($password);
    $safePassword = h($password);

    return
        '<span class="password-pill" data-password="'.$safePassword.'" data-visible="0">'.
            '<span class="password-pill-text">'.$masked.'</span>'.
            '<button type="button" class="password-eye-btn" onclick="togglePasswordVisibility(this)" aria-label="Show password" title="Show password">'.
                '&#128065;'.
            '</button>'.
        '</span>';
}

function renderAssetItemButtons(array $items, string $label, int $uid, int $assetIndex, string $type){
    if(empty($items)){
        return '<span class="asset-item-empty">N/A</span>';
    }

    if($type === 'storage'){
        $class = 'asset-item-btn storage';
        $wrapClass = 'asset-item-btn-wrap compact-wrap';
    } elseif($type === 'monitor'){
        $class = 'asset-item-btn monitor';
        $wrapClass = 'asset-item-btn-wrap compact-wrap';
    } elseif($type === 'windows'){
        $class = 'asset-item-btn windows';
        $wrapClass = 'asset-item-btn-wrap compact-wrap';
    } else {
        $class = 'asset-item-btn software';
        $wrapClass = 'asset-item-btn-wrap software-wrap';
    }

    $html = '<div class="'.$wrapClass.'">';

    foreach($items as $index => $item){
        $num = $index + 1;
        $buttonLabel = $label.' '.$num;

        if($type === 'storage'){
            $model = trim((string)($item['model'] ?? ''));
            $capacity = trim((string)($item['capacity'] ?? ''));

            if($model !== '' && $capacity !== ''){
                $buttonLabel = $model.' ('.$capacity.')';
            } elseif($model !== ''){
                $buttonLabel = $model;
            } elseif($capacity !== ''){
                $buttonLabel = $capacity;
            }
        } elseif($type === 'monitor'){
            $model = trim((string)($item['model'] ?? ''));
            $size = trim((string)($item['size'] ?? ''));

            if($model !== '' && $size !== ''){
                $buttonLabel = $model.' ('.$size.')';
            } elseif($model !== ''){
                $buttonLabel = $model;
            } elseif($size !== ''){
                $buttonLabel = $size;
            }
        } elseif($type === 'windows'){
            $windowOs = trim((string)($item['window__os'] ?? ''));
            if($windowOs !== ''){
                $buttonLabel = $windowOs;
            }
        }

        $html .= '<button type="button" class="'.$class.'" title="'.h($buttonLabel).'" onclick="openAssetItemPopup(\''.$type.'\','.$uid.','.$assetIndex.','.$index.'); event.stopPropagation();">'.h($buttonLabel).'</button>';
    }

    $html .= '</div>';
    return $html;
}

function findDefaultAssetIndex(array $assets): int{
    foreach($assets as $index => $asset){
        if(in_array($asset['asset_type'] ?? '', ['Desktop', 'Laptop'], true)){
            return (int)$index;
        }
    }
    return 0;
}

function getAssetTypeBaseLabel(string $assetType): string{
    if($assetType === 'Desktop'){
        return 'PC';
    }
    if($assetType === 'Laptop'){
        return 'Laptop';
    }
    if($assetType === 'iPad'){
        return 'iPad';
    }
    if($assetType === 'Phone'){
        return 'Phone';
    }
    return trim($assetType) !== '' ? $assetType : 'Asset';
}

function renderActionButtons(array $asset, int $uid, int $assetIndex): string{
    $id = (int)($asset['id'] ?? 0);
    $type = $asset['asset_type'] ?? '';

    if(in_array($type, ['iPad', 'Phone'], true)){
        $view = '<button type="button" class="view-btn device-btn" onclick="openMobileAssetPopup('.$uid.', '.$assetIndex.'); event.stopPropagation();">View</button>';
    } else {
        $view = '<a href="asset_detail.php?id='.$id.'" class="view-btn device-btn">View</a>';
    }

    $edit = '<a href="edit_asset.php?id='.$id.'" class="edit-btn device-btn">Edit</a>';
    $delete = '<a href="delete_asset.php?id='.$id.'" class="delete-btn device-btn" onclick="return confirm(\'Delete this asset?\')">Delete</a>';

    return $view.$edit.$delete;
}

/* Fetch all assets per user */
$query = "
SELECT
users.user_id,
users.name,
users.position,
users.contact_no,
users.email_id,
users.email_password,
users.mail_server,

assets.asset_id AS ID,
assets.asset_type,
assets.pc_username,
assets.pc_password,
assets.pc_model,
assets.pc_name,
assets.pc_serial_no,
assets.mac_lan,
assets.mac_wifi,
assets.antivirus,
assets.windows_key,
assets.serial_no,
assets.imei,
assets.storage_capacity,
assets.os_version,
assets.sim_no,
assets.carrier,
assets.apple_id,
assets.apple_password,
assets.account_email,
assets.account_password,

cpu.cpu_model,
cpu.cpu_speed,
cpu.cpu_core,
cpu.cpu_hyper_thread,
cpu.graphic_card,

MAX(ram_agg.ram)                                                             AS ram,
GROUP_CONCAT(DISTINCT storage.hdd_model ORDER BY storage.hdd_model)           AS hdd_model,
GROUP_CONCAT(DISTINCT storage.hdd_capacity ORDER BY storage.hdd_model)        AS hdd_capacity,
GROUP_CONCAT(DISTINCT storage.hdd_serial ORDER BY storage.hdd_model)          AS hdd_serial,
GROUP_CONCAT(DISTINCT monitor.monitor_model ORDER BY monitor.monitor_model)   AS monitor_model,
GROUP_CONCAT(DISTINCT monitor.monitor_size ORDER BY monitor.monitor_model)    AS monitor_size,
GROUP_CONCAT(DISTINCT monitor.monitor_serial ORDER BY monitor.monitor_model)  AS monitor_serial,
GROUP_CONCAT(DISTINCT software.software_name)                                 AS software

FROM users
INNER JOIN assets ON assets.user_id = users.user_id
LEFT JOIN cpu      ON cpu.asset_id      = assets.asset_id
LEFT JOIN (
    SELECT asset_id, GROUP_CONCAT(ram_size SEPARATOR '||') AS ram
    FROM ram
    GROUP BY asset_id
) ram_agg  ON ram_agg.asset_id   = assets.asset_id
LEFT JOIN storage  ON storage.asset_id  = assets.asset_id
LEFT JOIN monitor  ON monitor.asset_id  = assets.asset_id
LEFT JOIN software ON software.asset_id = assets.asset_id

GROUP BY assets.asset_id
ORDER BY users.user_id DESC,
    CASE WHEN assets.asset_type IN ('Desktop','Laptop') THEN 0 ELSE 1 END,
    assets.asset_id ASC
";

$result = mysqli_query($conn, $query);
if(!$result){
    die("Query Error: ".mysqli_error($conn));
}

$rows = [];
$asset_ids = [];
while($row = mysqli_fetch_assoc($result)){
    $rows[] = $row;
    $asset_ids[] = (int)($row['ID'] ?? 0);
}
mysqli_free_result($result);

$windows_map = asset_fetch_windows_map($conn, $asset_ids);

$users = [];
foreach($rows as $row){
    $uid = $row['user_id'];
    $assetId = (int)($row['ID'] ?? 0);

    if(!isset($users[$uid])){
        $users[$uid] = [
            'user_id'       => $row['user_id'],
            'name'          => $row['name'],
            'position'      => $row['position'],
            'contact_no'    => $row['contact_no'],
            'email_id'      => $row['email_id'],
            'email_password'=> $row['email_password'],
            'mail_server'   => $row['mail_server'],
            'assets'        => []
        ];
    }

    $hddModel    = !empty($row['hdd_model'])    ? explode(",", $row['hdd_model'])    : [];
    $hddCapacity = !empty($row['hdd_capacity']) ? explode(",", $row['hdd_capacity']) : [];
    $hddSerial   = !empty($row['hdd_serial'])   ? explode(",", $row['hdd_serial'])   : [];
    $storageItems = [];
    $storageSearch = [];
    $storageMax = max(count($hddModel), count($hddCapacity), count($hddSerial));

    for($i=0; $i<$storageMax; $i++){
        $model = trim($hddModel[$i] ?? '');
        $cap   = trim($hddCapacity[$i] ?? '');
        $serial= trim($hddSerial[$i] ?? '');

        if($model !== '' || $cap !== '' || $serial !== ''){
            $storageItems[] = [
                'model'    => $model,
                'capacity' => $cap,
                'serial'   => $serial
            ];
            $storageSearch[] = trim($model.' '.$cap.' '.$serial);
        }
    }

    $monModel  = !empty($row['monitor_model'])  ? explode(",", $row['monitor_model'])  : [];
    $monSize   = !empty($row['monitor_size'])   ? explode(",", $row['monitor_size'])   : [];
    $monSerial = !empty($row['monitor_serial']) ? explode(",", $row['monitor_serial']) : [];
    $monitorItems = [];
    $monitorSearch = [];
    $monitorMax = max(count($monModel), count($monSize), count($monSerial));

    for($i=0; $i<$monitorMax; $i++){
        $model = trim($monModel[$i] ?? '');
        $size  = trim($monSize[$i] ?? '');
        $serial= trim($monSerial[$i] ?? '');

        if($model !== '' || $size !== '' || $serial !== ''){
            $monitorItems[] = [
                'model'  => $model,
                'size'   => $size,
                'serial' => $serial
            ];
            $monitorSearch[] = trim($model.' '.$size.' '.$serial);
        }
    }

    $ramArr = !empty($row['ram']) ? explode("||", $row['ram']) : [];
    $total = 0;
    $ramText = "";
    $ramSticks = [];
    $slots = 0;
    foreach($ramArr as $ram){
        $val = intval($ram);
        if($val > 0){
            $total += $val;
            $ramText .= $val." GB + ";
            $ramSticks[] = $val." GB";
            $slots++;
        }
    }
    $ramText = rtrim($ramText, " + ");
    $ramDisplay = $slots > 0
        ? h($ramText." = ".$total." GB")."<br><span style='font-size:12px;opacity:0.7;'>(".h($slots)." slots)</span>"
        : '<span class="asset-item-empty">N/A</span>';

    $softwareItems = [];
    $softwareSearch = [];
    $softwareArr = !empty($row['software']) ? explode(",", $row['software']) : [];
    foreach($softwareArr as $s){
        $s = trim($s);
        if($s !== ""){
            $softwareItems[] = ['name' => $s];
            $softwareSearch[] = $s;
        }
    }

    $windowsItemsRaw = asset_get_windows_items_for_asset(
        $windows_map,
        $assetId,
        (string)($row['windows_key'] ?? '')
    );
    $windowsItems = [];
    $windowsSearch = [];
    foreach($windowsItemsRaw as $windowItem){
        $windowOs = trim((string)($windowItem['window__os'] ?? ''));
        $windowSerial = trim((string)($windowItem['windows_serial'] ?? ''));

        if($windowOs === ''){
            continue;
        }

        $windowsItems[] = [
            'window__os' => $windowOs,
            'windows_serial' => $windowSerial,
        ];
        $windowsSearch[] = trim($windowOs.' '.$windowSerial);
    }

    $isComputer = in_array($row['asset_type'], ['Desktop', 'Laptop'], true);
    if(!$isComputer){
        $mobileOs = trim((string)($row['os_version'] ?? ''));
        if($mobileOs !== ''){
            $windowsSearch[] = $mobileOs;
        }
    }
    $cpuDisplay = ($isComputer && trim((string)($row['cpu_model'] ?? '')) !== '')
        ? h($row['cpu_model'])."<br><span style='font-size:12px;opacity:0.7;'>".h($row['cpu_speed'])." GHz &bull; ".h($row['cpu_core'])." Cores</span>"
        : '<span class="asset-item-empty">N/A</span>';
    $assetLabel = trim(($row['pc_name'] ?? '') !== '' ? $row['pc_name'] : ($row['pc_model'] ?? ''));

    $displayPcUsername = $isComputer ? h($row['pc_username']) : '<span class="asset-item-empty">N/A</span>';
    $displayPcPassword = $isComputer ? renderPasswordCell((string)($row['pc_password'] ?? '')) : '<span class="asset-item-empty">N/A</span>';
    $displayPcModel    = trim((string)($row['pc_model'] ?? '')) !== '' ? h($row['pc_model']) : '<span class="asset-item-empty">N/A</span>';
    $displayPcName     = $isComputer
        ? (trim((string)($row['pc_name'] ?? '')) !== '' ? h($row['pc_name']) : '<span class="asset-item-empty">N/A</span>')
        : (trim((string)($row['serial_no'] ?? '')) !== '' ? h($row['serial_no']) : '<span class="asset-item-empty">N/A</span>');
    $displayPcSerialNo = $isComputer && trim((string)($row['pc_serial_no'] ?? '')) !== '' ? h($row['pc_serial_no']) : '<span class="asset-item-empty">N/A</span>';
    $displayMacLan     = $isComputer && trim((string)($row['mac_lan'] ?? '')) !== '' ? h($row['mac_lan']) : '<span class="asset-item-empty">N/A</span>';
    $displayMacWifi    = trim((string)($row['mac_wifi'] ?? '')) !== '' ? h($row['mac_wifi']) : '<span class="asset-item-empty">N/A</span>';
    $displayAntivirus  = $isComputer && trim((string)($row['antivirus'] ?? '')) !== '' ? h($row['antivirus']) : '<span class="asset-item-empty">N/A</span>';
    $displayGpu        = $isComputer && trim((string)($row['graphic_card'] ?? '')) !== '' ? h($row['graphic_card']) : '<span class="asset-item-empty">N/A</span>';
    $displayWindows    = $isComputer
        ? renderAssetItemButtons($windowsItems, 'Windows', (int)$uid, count($users[$uid]['assets']), 'windows')
        : (trim((string)($row['os_version'] ?? '')) !== '' ? h($row['os_version']) : '<span class="asset-item-empty">N/A</span>');

    $users[$uid]['assets'][] = [
        'id'             => $assetId,
        'asset_type'     => $row['asset_type'],
        'asset_type_label' => getAssetTypeBaseLabel((string)($row['asset_type'] ?? '')),
        'asset_label'    => $assetLabel,
        'pc_username'    => $displayPcUsername,
        'pc_password'    => $displayPcPassword,
        'pc_model'       => $displayPcModel,
        'pc_name'        => $displayPcName,
        'pc_serial_no'   => $displayPcSerialNo,
        'mac_lan'        => $displayMacLan,
        'mac_wifi'       => $displayMacWifi,
        'antivirus'      => $displayAntivirus,
        'windows_key'    => $displayWindows,
        'cpu'            => $cpuDisplay,
        'gpu'            => $displayGpu,
        'ram'            => $ramDisplay,
        'storage_items'  => $storageItems,
        'monitor_items'  => $monitorItems,
        'windows_items'  => $windowsItems,
        'storage_search' => implode(" ", $storageSearch),
        'monitor_search' => implode(" ", $monitorSearch),
        'windows_search' => implode(" ", $windowsSearch),
        'software_items' => $softwareItems,
        'software_search'=> implode(" ", $softwareSearch),
        'model_name'     => (string)($row['pc_model'] ?? ''),
        'pc_name_text'   => (string)($row['pc_name'] ?? ''),
        'pc_serial_no_text' => (string)($row['pc_serial_no'] ?? ''),
        'pc_username_text' => (string)($row['pc_username'] ?? ''),
        'pc_password_text' => (string)($row['pc_password'] ?? ''),
        'mac_lan_text'   => (string)($row['mac_lan'] ?? ''),
        'mac_wifi_text'  => (string)($row['mac_wifi'] ?? ''),
        'antivirus_text' => (string)($row['antivirus'] ?? ''),
        'user_name'      => (string)($row['name'] ?? ''),
        'user_position'  => (string)($row['position'] ?? ''),
        'user_contact_no'=> (string)($row['contact_no'] ?? ''),
        'user_email_id'  => (string)($row['email_id'] ?? ''),
        'user_email_password' => (string)($row['email_password'] ?? ''),
        'user_mail_server'    => (string)($row['mail_server'] ?? ''),
        'cpu_model_text' => (string)($row['cpu_model'] ?? ''),
        'cpu_speed_text' => (string)($row['cpu_speed'] ?? ''),
        'cpu_core_text'  => (string)($row['cpu_core'] ?? ''),
        'cpu_thread_text' => (string)($row['cpu_hyper_thread'] ?? ''),
        'gpu_text'       => (string)($row['graphic_card'] ?? ''),
        'ram_sticks'     => $ramSticks,
        'ram_total'      => $total,
        'ram_slots'      => $slots,
        'serial_no'      => (string)($row['serial_no'] ?? ''),
        'imei'           => (string)($row['imei'] ?? ''),
        'storage_capacity' => (string)($row['storage_capacity'] ?? ''),
        'os_version'     => (string)($row['os_version'] ?? ''),
        'sim_no'         => (string)($row['sim_no'] ?? ''),
        'carrier'        => (string)($row['carrier'] ?? ''),
        'apple_id'       => (string)($row['apple_id'] ?? ''),
        'apple_password' => (string)($row['apple_password'] ?? ''),
        'account_email'  => (string)($row['account_email'] ?? ''),
        'account_password' => (string)($row['account_password'] ?? ''),
        'windows_key_text' => asset_get_primary_windows_os($windowsItemsRaw),
    ];
}

foreach($users as &$user){
    $typeTotals = [];
    foreach($user['assets'] as $asset){
        $type = (string)($asset['asset_type'] ?? '');
        $typeTotals[$type] = ($typeTotals[$type] ?? 0) + 1;
    }

    $typeSeen = [];
    foreach($user['assets'] as &$asset){
        $type = (string)($asset['asset_type'] ?? '');
        $typeSeen[$type] = ($typeSeen[$type] ?? 0) + 1;

        $label = getAssetTypeBaseLabel($type);
        if(($typeTotals[$type] ?? 0) > 1){
            $label .= ' '.$typeSeen[$type];
        }

        $asset['asset_type_label'] = $label;
    }
    unset($asset);
}
unset($user);
?>

<link rel="stylesheet" href="css/style.css">
<?php include "components/navbar.php"; ?>

<style>
.asset-list-option2 .hardware-table{
    --sticky-id-width: 68px;
    --sticky-name-width: 220px;
    width: max-content;
    min-width: 100%;
    table-layout: auto;
    margin-top: 8px;
    overflow: visible;
    border-collapse: separate;
    border-spacing: 0;
    isolation: isolate;
}

.asset-list-option2{
    min-height: calc(100vh - 108px);
    display: flex;
    flex-direction: column;
    margin-top: 14px;
    padding: 14px 16px 12px;
}

.asset-list-option2 .asset-list-topbar{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px 14px;
    flex-wrap: wrap;
    margin-bottom: 6px;
}

.asset-list-option2 .page-title{
    margin: 0;
    text-align: left;
    font-size: 32px;
    line-height: 1.05;
}

.asset-list-option2 .page-title::after{
    margin: 6px 0 0;
    width: 64px;
    height: 3px;
}

.asset-list-option2 .asset-list-toolbar{
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.asset-list-option2 .add-hardware-btn{
    margin-top: 0;
    padding: 10px 16px;
    font-size: 16px;
    border-radius: 9px;
}

.asset-list-option2 .search-bar-clean{
    margin: 0;
    max-width: 320px;
    min-width: 230px;
}

.asset-list-option2 .search-bar-clean input{
    padding: 10px 14px 10px 34px;
    border-radius: 10px;
    font-size: 15px;
}

.asset-list-option2 .search-icon{
    left: 10px;
    font-size: 13px;
}

.asset-list-option2 .table-wrapper{
    flex: 1;
    min-height: 0;
    max-height: calc(100vh - 188px);
    overflow: auto;
    border-radius: 12px;
    position: relative;
    background: #f4f8ff;
}

.asset-list-option2 .hardware-table th{
    padding: 12px 12px;
    font-size: 16px;
    white-space: nowrap;
    box-sizing: border-box;
    position: sticky;
    top: 0;
    z-index: 5;
    background-clip: padding-box;
    box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.55), 0 6px 14px rgba(11, 42, 74, 0.12);
}

.asset-list-option2 .hardware-table td{
    color: #102f49;
    padding: 12px 10px;
    line-height: 1.35;
    min-width: 0;
    max-width: none;
    width: auto;
    overflow-wrap: anywhere;
    word-break: break-word;
    box-sizing: border-box;
}

.asset-list-option2 .hardware-table .sticky-id-col,
.asset-list-option2 .hardware-table .sticky-name-col{
    position: sticky;
    z-index: 4;
    background-clip: padding-box;
    box-sizing: border-box;
}

.asset-list-option2 .hardware-table th.sticky-id-col,
.asset-list-option2 .hardware-table th.sticky-name-col{
    z-index: 7;
}

.asset-list-option2 .hardware-table .sticky-id-col{
    left: 0;
    min-width: var(--sticky-id-width);
    width: var(--sticky-id-width);
    text-align: center;
}

.asset-list-option2 .hardware-table .sticky-name-col{
    left: var(--sticky-id-width);
    min-width: var(--sticky-name-width);
    width: var(--sticky-name-width);
    border-right: 1px solid rgba(11, 42, 74, 0.10);
    box-shadow: 12px 0 18px -16px rgba(11, 42, 74, 0.52);
}

.asset-list-option2 .hardware-table th.sticky-id-col{
    background: #0b2a4a;
}

.asset-list-option2 .hardware-table td.sticky-id-col{
    background: #eef3f8;
    font-weight: 700;
}

.asset-list-option2 .hardware-table th.sticky-name-col{
    background: #7e2151;
}

.asset-list-option2 .hardware-table td.sticky-name-col{
    background: #f6e8ef;
}

.asset-list-option2 .hardware-table .user-col,
.asset-list-option2 .hardware-table .pc-col,
.asset-list-option2 .hardware-table .cpu-col,
.asset-list-option2 .hardware-table .gpu-col,
.asset-list-option2 .hardware-table .ram-col,
.asset-list-option2 .hardware-table .storage-col,
.asset-list-option2 .hardware-table .monitor-col,
.asset-list-option2 .hardware-table .windows-col,
.asset-list-option2 .hardware-table .software-col,
.asset-list-option2 .hardware-table .action-col{
    min-width: 0;
    width: auto;
    max-width: none;
}

.asset-list-option2 .hardware-table .user-col{
    background: #f6e8ef;
}

.asset-list-option2 .hardware-table th.user-col{
    background: #7e2151;
}

.asset-list-option2 .hardware-table .pc-col{
    background: #f7e4d5;
}

.asset-list-option2 .hardware-table th.pc-col{
    background: #bf6a2a;
}

.asset-list-option2 .hardware-table .cpu-col,
.asset-list-option2 .hardware-table .gpu-col{
    background: #faefd5;
}

.asset-list-option2 .hardware-table th.cpu-col,
.asset-list-option2 .hardware-table th.gpu-col{
    background: #c69229;
}

.asset-list-option2 .hardware-table .ram-col{
    background: #e6f5da;
}

.asset-list-option2 .hardware-table th.ram-col{
    background: #4f7f2c;
}

.asset-list-option2 .hardware-table .storage-col{
    background: #e6effa;
}

.asset-list-option2 .hardware-table th.storage-col{
    background: #2f557b;
}

.asset-list-option2 .hardware-table .monitor-col{
    background: #e8edfb;
}

.asset-list-option2 .hardware-table th.monitor-col{
    background: #4f68a0;
}

.asset-list-option2 .hardware-table .windows-col{
    background: #ddf2f6;
}

.asset-list-option2 .hardware-table th.windows-col{
    background: #2f7b86;
}

.asset-list-option2 .hardware-table .software-col{
    background: #efe6f7;
}

.asset-list-option2 .hardware-table th.software-col{
    background: #6f4f96;
}

.asset-list-option2 .hardware-table .action-col{
    background: #f5f1fb;
}

.asset-list-option2 .hardware-table th.action-col{
    background: #5e4d95;
}

.asset-list-option2 .hardware-table .email-col{
    min-width: 0;
    max-width: none;
    width: auto;
    white-space: normal;
}

.asset-list-option2 .hardware-table td.email-col{
    overflow-wrap: anywhere;
    word-break: break-word;
}

.asset-list-option2 .password-pill{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 3px 5px 3px 9px;
    background: rgba(11, 42, 74, 0.09);
    border: 1px solid rgba(11, 42, 74, 0.13);
}

.asset-list-option2 .password-pill-text{
    min-width: 54px;
    letter-spacing: 0.08em;
    font-size: 13px;
    line-height: 1;
    color: #173c5b;
}

.asset-list-option2 .password-eye-btn{
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 999px;
    background: rgba(11, 42, 74, 0.13);
    color: #0b2a4a;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.14s ease, transform 0.14s ease;
}

.asset-list-option2 .password-eye-btn:hover{
    background: rgba(11, 42, 74, 0.22);
    transform: translateY(-1px);
}

.asset-list-option2 .hardware-table tbody tr:hover td{
    box-shadow: inset 0 0 0 9999px rgba(11, 42, 74, 0.08);
}

.asset-item-btn-wrap{
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.asset-item-btn-wrap.compact-wrap{
    display: grid;
    grid-template-rows: repeat(2, min-content);
    grid-auto-flow: column;
    grid-auto-columns: max-content;
    gap: 6px;
    overflow-x: auto;
    overflow-y: hidden;
    max-width: 100%;
    max-height: 68px;
    padding-bottom: 2px;
}

.asset-item-btn-wrap.compact-wrap::-webkit-scrollbar{
    height: 6px;
}

.asset-item-btn-wrap.compact-wrap::-webkit-scrollbar-thumb{
    background: rgba(11, 42, 74, 0.28);
    border-radius: 999px;
}

.asset-item-btn-wrap.software-wrap{
    display: grid;
    grid-template-rows: repeat(2, min-content);
    grid-auto-flow: column;
    grid-auto-columns: 96px;
    gap: 6px;
    overflow-x: auto;
    overflow-y: hidden;
    max-width: 100%;
    max-height: 68px;
    padding-bottom: 2px;
}

.asset-item-btn-wrap.software-wrap::-webkit-scrollbar{
    height: 6px;
}

.asset-item-btn-wrap.software-wrap::-webkit-scrollbar-thumb{
    background: rgba(111, 79, 150, 0.45);
    border-radius: 999px;
}

@media (max-width: 900px){
    .asset-list-option2{
        min-height: auto;
        padding: 12px 10px 10px;
    }

    .asset-list-option2 .asset-list-topbar{
        align-items: flex-start;
    }

    .asset-list-option2 .page-title{
        width: 100%;
        text-align: center;
    }

    .asset-list-option2 .page-title::after{
        margin: 8px auto 0;
    }

    .asset-list-option2 .asset-list-toolbar{
        width: 100%;
        justify-content: center;
    }

    .asset-list-option2 .search-bar-clean{
        width: min(100%, 420px);
        min-width: 0;
        max-width: none;
    }

    .asset-list-option2 .table-wrapper{
        max-height: calc(100vh - 220px);
    }
}

.asset-item-btn{
    border: none;
    border-radius: 6px;
    padding: 6px 11px;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-size: 12px;
    letter-spacing: 0.35px;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
    line-height: 1.15;
    border: 1px solid transparent;
    box-shadow: 0 3px 9px rgba(11, 42, 74, 0.16);
    transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
}

.asset-item-btn.storage{
    min-width: 148px;
    max-width: 240px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background: linear-gradient(180deg, #143f69 0%, #0b2a4a 100%);
    border-color: rgba(255, 255, 255, 0.18);
}

.asset-item-btn.monitor{
    min-width: 148px;
    max-width: 240px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background: linear-gradient(180deg, #3e84cf 0%, #2d7dd2 100%);
    border-color: rgba(255, 255, 255, 0.2);
}

.asset-item-btn.windows{
    min-width: 148px;
    max-width: 240px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background: #2f7b86;
    border-color: rgba(255, 255, 255, 0.22);
}

.asset-item-btn.software{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 96px;
    padding: 6px 0;
    background: linear-gradient(180deg, #8863b4 0%, #6f4f96 100%);
    border-color: rgba(255, 255, 255, 0.2);
}

.asset-item-btn:hover{
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(11, 42, 74, 0.2);
    filter: brightness(1.03);
}

.asset-item-btn:active{
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(11, 42, 74, 0.14);
}

.asset-item-empty{
    color: #90a2b5;
    font-style: italic;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-size: 14px;
}

.asset-item-popup{
    position: fixed;
    inset: 0;
    background: rgba(8, 25, 43, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1100;
    padding: 20px;
}

.asset-item-popup.open{
    display: flex;
}

.asset-item-popup-box{
    --popup-bg: #ffffff;
    --popup-panel: #f4f8fd;
    --popup-accent: #0b2a4a;
    --popup-muted: #607891;
    --popup-border: #dce8f5;
    width: min(460px, 100%);
    background: var(--popup-bg);
    border-radius: 16px;
    padding: 18px 18px 16px;
    position: relative;
    border: 1px solid var(--popup-border);
    box-shadow: 0 16px 34px rgba(11, 42, 74, 0.18);
}

.asset-item-popup-box.storage-theme{
    --popup-bg: #edf4fb;
    --popup-panel: #dce8f7;
    --popup-accent: #2f557b;
    --popup-muted: #4f6f90;
    --popup-border: #bfd1e6;
}

.asset-item-popup-box.monitor-theme{
    --popup-bg: #eef2fb;
    --popup-panel: #e1e9f8;
    --popup-accent: #4f68a0;
    --popup-muted: #627aa8;
    --popup-border: #c7d4ee;
}

.asset-item-popup-box.windows-theme{
    --popup-bg: #eaf5f7;
    --popup-panel: #d7eef1;
    --popup-accent: #2f7b86;
    --popup-muted: #3f6f76;
    --popup-border: #bfdfe4;
}

.asset-item-popup-box.software-theme{
    --popup-bg: #f4effa;
    --popup-panel: #eadff6;
    --popup-accent: #6f4f96;
    --popup-muted: #7d67a0;
    --popup-border: #d7c6ea;
}

.asset-item-popup-close{
    position: absolute;
    top: 10px;
    right: 10px;
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 50%;
    background: var(--popup-panel);
    color: var(--popup-accent);
    font-size: 1.2rem;
    line-height: 1;
    cursor: pointer;
}

.asset-item-popup-title{
    margin: 0 0 4px;
    color: var(--popup-accent);
    font-size: 1.22rem;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.asset-item-popup-sub{
    margin: 0 0 14px;
    color: var(--popup-muted);
    font-size: 0.92rem;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-weight: 600;
}

.asset-item-popup-grid{
    display: grid;
    gap: 10px;
}

.asset-item-popup-row{
    border-radius: 10px;
    background: var(--popup-panel);
    padding: 10px 12px;
}

.asset-item-popup-row strong{
    display: block;
    margin-bottom: 3px;
    color: var(--popup-muted);
    font-size: 0.74rem;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.asset-item-popup-row span{
    color: var(--popup-accent);
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-size: 1rem;
    font-weight: 600;
}

.mobile-asset-popup{
    position: fixed;
    inset: 0;
    background: rgba(8, 25, 43, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1150;
    padding: 20px;
}

.mobile-asset-popup.open{
    display: flex;
}

.mobile-asset-popup-box{
    --detail-bg: linear-gradient(180deg, #ffffff 0%, #f6f9fc 100%);
    --detail-border: #d6e1ec;
    --detail-accent: #1d4773;
    --detail-accent-soft: rgba(29, 71, 115, 0.10);
    --detail-card: rgba(255, 255, 255, 0.82);
    --detail-hero-start: #1d4773;
    --detail-hero-end: #4d77a2;
    --detail-label: #5e7488;
    width: min(880px, 100%);
    max-height: calc(100vh - 40px);
    overflow: auto;
    border-radius: 22px;
    border: 1px solid var(--detail-border);
    background: var(--detail-bg);
    box-shadow: 0 24px 48px rgba(11, 42, 74, 0.18);
}

.mobile-asset-popup-box.ipad-theme{
    --detail-bg:
        radial-gradient(circle at top right, rgba(131, 79, 196, 0.18), transparent 28%),
        linear-gradient(180deg, #fffaff 0%, #f7f1fc 100%);
    --detail-border: #dbccef;
    --detail-accent: #6f4f96;
    --detail-accent-soft: rgba(111, 79, 150, 0.12);
    --detail-card: rgba(255, 255, 255, 0.84);
    --detail-hero-start: #6f4f96;
    --detail-hero-end: #9270bb;
    --detail-label: #876ea4;
}

.mobile-asset-popup-box.phone-theme{
    --detail-bg:
        radial-gradient(circle at top right, rgba(43, 157, 167, 0.18), transparent 28%),
        linear-gradient(180deg, #f8ffff 0%, #eef8f8 100%);
    --detail-border: #c8e8ea;
    --detail-accent: #0f6f78;
    --detail-accent-soft: rgba(15, 111, 120, 0.12);
    --detail-card: rgba(255, 255, 255, 0.84);
    --detail-hero-start: #0f6f78;
    --detail-hero-end: #35919a;
    --detail-label: #5f8790;
}

.mobile-asset-popup-box.pc-theme{
    --detail-bg:
        radial-gradient(circle at top right, rgba(207, 83, 0, 0.18), transparent 28%),
        linear-gradient(180deg, #fff9f4 0%, #fdf0e3 100%);
    --detail-border: #efcfb2;
    --detail-accent: #cf5300;
    --detail-accent-soft: rgba(207, 83, 0, 0.12);
    --detail-card: rgba(255, 255, 255, 0.86);
    --detail-hero-start: #cf5300;
    --detail-hero-end: #e18747;
    --detail-label: #b56f3a;
}

.mobile-asset-popup-box.laptop-theme{
    --detail-bg:
        radial-gradient(circle at top right, rgba(11, 42, 74, 0.18), transparent 28%),
        linear-gradient(180deg, #f7fbff 0%, #edf4fb 100%);
    --detail-border: #c8d8e8;
    --detail-accent: #0b2a4a;
    --detail-accent-soft: rgba(11, 42, 74, 0.12);
    --detail-card: rgba(255, 255, 255, 0.86);
    --detail-hero-start: #0b2a4a;
    --detail-hero-end: #315980;
    --detail-label: #58748f;
}

.mobile-asset-popup-head{
    position: sticky;
    top: 0;
    z-index: 1;
    padding: 22px 24px 18px;
    background: linear-gradient(135deg, var(--detail-hero-start) 0%, var(--detail-hero-end) 100%);
    color: #fff;
}

.mobile-asset-popup-kicker{
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,0.14);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.mobile-asset-popup-title{
    margin: 10px 0 4px;
    color: #fff;
    font-size: 2rem;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-weight: 700;
    letter-spacing: 0.03em;
}

.mobile-asset-popup-sub{
    margin: 0;
    color: rgba(255,255,255,0.82);
    font-size: 0.95rem;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-weight: 600;
}

.mobile-asset-popup-actions{
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 14px;
}

.mobile-asset-popup-action{
    min-width: 108px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 14px;
    border-radius: 12px;
    text-decoration: none;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    transition: transform 0.16s ease, background 0.16s ease, box-shadow 0.16s ease;
}

.mobile-asset-popup-action svg{
    width: 14px;
    height: 14px;
    fill: currentColor;
    flex: 0 0 auto;
}

.mobile-asset-popup-action.edit{
    background: rgba(255,255,255,0.18);
    border: 1px solid rgba(255,255,255,0.24);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(11, 42, 74, 0.12);
}

.mobile-asset-popup-action.edit:hover{
    background: rgba(255,255,255,0.26);
    transform: translateY(-1px);
}

.mobile-asset-popup-action.delete{
    background: rgba(230, 57, 70, 0.94);
    border: 1px solid rgba(255,255,255,0.12);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(135, 25, 36, 0.18);
}

.mobile-asset-popup-action.delete:hover{
    background: #d62f3d;
    transform: translateY(-1px);
}

.mobile-asset-popup-close{
    position: absolute;
    top: 18px;
    right: 18px;
    width: 34px;
    height: 34px;
    border: none;
    border-radius: 50%;
    background: rgba(255,255,255,0.16);
    color: #fff;
    font-size: 1.3rem;
    line-height: 1;
    cursor: pointer;
}

.mobile-asset-popup-body{
    padding: 18px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
    align-items: start;
}

.mobile-asset-popup-card{
    border: 1px solid var(--detail-border);
    border-radius: 18px;
    background: var(--detail-card);
    box-shadow: 0 10px 22px rgba(11, 42, 74, 0.08);
    overflow: hidden;
}

.mobile-asset-popup-card-head{
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 16px 10px;
    color: var(--detail-accent);
    font-size: 0.88rem;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.mobile-asset-popup-card-dot{
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--detail-accent);
    box-shadow: 0 0 0 6px var(--detail-accent-soft);
}

.mobile-asset-popup-card-body{
    padding: 0 16px 16px;
}

.mobile-asset-popup-row{
    padding: 10px 0;
    border-bottom: 1px solid rgba(11, 42, 74, 0.08);
}

.mobile-asset-popup-row:last-child{
    border-bottom: none;
}

.mobile-asset-popup-row strong{
    display: block;
    margin-bottom: 3px;
    color: var(--detail-label);
    font-size: 0.74rem;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.mobile-asset-popup-row span{
    color: #12314b;
    font-size: 1rem;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-weight: 600;
    line-height: 1.4;
}

.mobile-asset-popup-list{
    list-style: disc;
    margin: 0;
    padding: 6px 0 0 18px;
    color: #12314b;
}

.mobile-asset-popup-list li{
    margin-bottom: 8px;
    font-size: 1rem;
    font-family: "Tw Cen MT", "Segoe UI", sans-serif;
    font-weight: 600;
}

.mobile-asset-popup-empty{
    color: #8094a8;
    font-style: italic;
    font-weight: 500;
}

.asset-list-option2 .detail-trigger-cell{
    cursor: pointer;
    font-weight: 600;
    color: #0b2a4a;
    transition: color 0.16s ease, text-decoration-color 0.16s ease;
}

.asset-list-option2 .detail-trigger-cell:hover{
    text-decoration: underline;
    text-decoration-thickness: 1.5px;
    text-underline-offset: 3px;
}

@media (max-width: 700px){
    .mobile-asset-popup{
        padding: 10px;
    }

    .mobile-asset-popup-title{
        font-size: 1.55rem;
    }

    .mobile-asset-popup-head{
        padding: 20px 18px 16px;
    }

    .mobile-asset-popup-actions{
        gap: 8px;
    }

    .mobile-asset-popup-action{
        min-width: 96px;
        height: 36px;
        padding: 0 12px;
    }

    .mobile-asset-popup-body{
        grid-template-columns: 1fr;
        padding: 14px;
    }
}
</style>

<div class="container asset-container asset-list-option2">

<div class="asset-list-topbar">
  <h1 class="page-title">Assets List</h1>

  <div class="asset-list-toolbar">
    <a href="add_asset.php" class="add-hardware-btn">+ Add Asset</a>

    <div class="search-bar-clean">
      <span class="search-icon">&#128269;</span>
      <input
        type="text"
        id="searchInput"
        placeholder="Search assets..."
        onkeyup="debouncedSearch()"
        onkeydown="handleKey(event)"
      >
    </div>
  </div>
</div>

<p id="noResult" class="no-result">No results found</p>

<div class="table-wrapper">
<table class="hardware-table">
<thead>
<tr>
<th class="sticky-id-col">No</th>
<th class="user-col sticky-name-col">Name</th>
<th class="user-col">Position</th>
<th class="user-col">Contact</th>
<th class="user-col email-col">Email</th>
<th class="user-col">Email Password</th>
<th class="user-col">Mail Server</th>
<th class="user-col">Asset Type</th>
<th class="user-col">PC Username</th>
<th class="user-col">PC Password</th>
<th class="pc-col">PC Model</th>
<th class="pc-col">PC Name</th>
<th class="pc-col">PC Serial Number</th>
<th class="pc-col">MAC LAN</th>
<th class="pc-col">MAC WIFI</th>
<th class="pc-col">Antivirus</th>
<th class="cpu-col">CPU</th>
<th class="gpu-col">GPU</th>
<th class="ram-col">RAM</th>
<th class="storage-col">Storage</th>
<th class="monitor-col">Monitor</th>
<th class="windows-col">Windows</th>
<th class="software-col">Software</th>
<th class="action-col">Action</th>
</tr>
</thead>

<?php
echo "<script>window.userAssets = ".json_encode(
    array_map(fn($u) => $u['assets'], $users),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
).";</script>";

$no = 1;
foreach($users as $uid => $user):
    $assets = $user['assets'];
    $displayIndex = findDefaultAssetIndex($assets);
    $first = $assets[$displayIndex];
?>
<tbody id="tbody_user_<?php echo (int)$uid; ?>">
<tr id="row_user_<?php echo (int)$uid; ?>" data-current-index="<?php echo (int)$displayIndex; ?>">
<td class="sticky-id-col"><?php echo $no++; ?></td>
<td class="user-col name-col sticky-name-col"><?php echo h($user['name']); ?></td>
<td class="user-col"><?php echo h($user['position']); ?></td>
<td class="user-col"><?php echo h($user['contact_no']); ?></td>
<td class="user-col email-col"><?php echo h($user['email_id']); ?></td>
<td class="user-col"><?php echo renderPasswordCell((string)($user['email_password'] ?? '')); ?></td>
<td class="user-col"><?php echo h($user['mail_server']); ?></td>

<td class="user-col">
<?php foreach($assets as $ai => $asset): ?>
    <?php
        $label = $asset['asset_type_label'] ?? getAssetTypeBaseLabel((string)($asset['asset_type'] ?? ''));
        $btnClass = 'device-btn laptop';
        if($asset['asset_type'] === 'Desktop'){
            $btnClass = 'device-btn pc';
        } elseif($asset['asset_type'] === 'Laptop'){
            $btnClass = 'device-btn laptop';
        } elseif($asset['asset_type'] === 'iPad'){
            $btnClass = 'device-btn ipad';
        } elseif($asset['asset_type'] === 'Phone'){
            $btnClass = 'device-btn phone';
        }
    ?>
    <a class="<?php echo $btnClass; ?> asset-type-btn <?php echo $ai === $displayIndex ? 'active' : ''; ?>"
       data-uid="<?php echo (int)$uid; ?>"
       data-index="<?php echo (int)$ai; ?>"
       onclick="return handleAssetTypeClick(<?php echo (int)$uid; ?>, <?php echo (int)$ai; ?>);"
       style="display:inline-block;margin:2px;cursor:pointer;">
        <?php echo h($label); ?>
    </a>
<?php endforeach; ?>
</td>

<td class="user-col cell-pc-username"><?php echo $first['pc_username']; ?></td>
<td class="user-col cell-pc-password"><?php echo $first['pc_password']; ?></td>
<td class="pc-col cell-pc-model"><?php echo $first['pc_model']; ?></td>
<td class="pc-col cell-pc-name detail-trigger-cell" onclick="openCurrentDisplayedAssetPopup(<?php echo (int)$uid; ?>)"><?php echo $first['pc_name']; ?></td>
<td class="pc-col cell-pc-serial"><?php echo $first['pc_serial_no']; ?></td>
<td class="pc-col cell-mac-lan"><?php echo $first['mac_lan']; ?></td>
<td class="pc-col cell-mac-wifi"><?php echo $first['mac_wifi']; ?></td>
<td class="pc-col cell-antivirus"><?php echo $first['antivirus']; ?></td>
<td class="cpu-col cell-cpu"><?php echo $first['cpu']; ?></td>
<td class="gpu-col cell-gpu"><?php echo $first['gpu']; ?></td>
<td class="ram-col cell-ram"><?php echo $first['ram']; ?></td>
<td class="storage-col cell-storage" data-search-text="<?php echo h($first['storage_search']); ?>"><?php echo renderAssetItemButtons($first['storage_items'], 'Storage', (int)$uid, $displayIndex, 'storage'); ?></td>
<td class="monitor-col cell-monitor" data-search-text="<?php echo h($first['monitor_search']); ?>"><?php echo renderAssetItemButtons($first['monitor_items'], 'Monitor', (int)$uid, $displayIndex, 'monitor'); ?></td>
<td class="windows-col cell-windows" data-search-text="<?php echo h($first['windows_search']); ?>"><?php echo $first['windows_key']; ?></td>
<td class="software-col cell-software" data-search-text="<?php echo h($first['software_search']); ?>"><?php echo renderAssetItemButtons($first['software_items'], 'Software', (int)$uid, $displayIndex, 'software'); ?></td>

<td class="action-col cell-action">
    <?php echo renderActionButtons($first, (int)$uid, $displayIndex); ?>
</td>
</tr>
</tbody>
<?php endforeach; ?>

</table>
</div>
</div>

<div id="assetItemPopup" class="asset-item-popup" onclick="closeAssetItemPopup()">
  <div class="asset-item-popup-box" onclick="event.stopPropagation();">
    <button type="button" class="asset-item-popup-close" onclick="closeAssetItemPopup()" aria-label="Close">&times;</button>
    <h3 id="assetItemPopupTitle" class="asset-item-popup-title">Storage 1</h3>
    <p id="assetItemPopupSub" class="asset-item-popup-sub"></p>
    <div id="assetItemPopupGrid" class="asset-item-popup-grid"></div>
  </div>
</div>

<div id="mobileAssetPopup" class="mobile-asset-popup" onclick="closeMobileAssetPopup()">
  <div class="mobile-asset-popup-box" onclick="event.stopPropagation();">
    <div class="mobile-asset-popup-head">
      <button type="button" class="mobile-asset-popup-close" onclick="closeMobileAssetPopup()" aria-label="Close">&times;</button>
      <span id="mobileAssetPopupKicker" class="mobile-asset-popup-kicker">Mobile Asset</span>
      <h3 id="mobileAssetPopupTitle" class="mobile-asset-popup-title">Asset Detail</h3>
      <p id="mobileAssetPopupSub" class="mobile-asset-popup-sub"></p>
      <div class="mobile-asset-popup-actions">
        <a id="mobileAssetPopupEdit" class="mobile-asset-popup-action edit" href="#">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.04a1.003 1.003 0 0 0 0-1.42L18.21 3.29a1.003 1.003 0 0 0-1.42 0L14.83 5.25l3.75 3.75 2.13-1.79z"/></svg>
          <span>Edit</span>
        </a>
        <a id="mobileAssetPopupDelete" class="mobile-asset-popup-action delete" href="#">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 7h12l-1 14H7L6 7zm3-3h6l1 2h4v2H4V6h4l1-2z"/></svg>
          <span>Delete</span>
        </a>
      </div>
    </div>
    <div id="mobileAssetPopupBody" class="mobile-asset-popup-body"></div>
  </div>
</div>

<div id="computerAssetPopup" class="mobile-asset-popup" onclick="closeComputerAssetPopup()">
  <div class="mobile-asset-popup-box" onclick="event.stopPropagation();">
    <div class="mobile-asset-popup-head">
      <button type="button" class="mobile-asset-popup-close" onclick="closeComputerAssetPopup()" aria-label="Close">&times;</button>
      <span id="computerAssetPopupKicker" class="mobile-asset-popup-kicker">Computer Asset</span>
      <h3 id="computerAssetPopupTitle" class="mobile-asset-popup-title">Computer Detail</h3>
      <p id="computerAssetPopupSub" class="mobile-asset-popup-sub"></p>
      <div class="mobile-asset-popup-actions">
        <a id="computerAssetPopupEdit" class="mobile-asset-popup-action edit" href="#">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.04a1.003 1.003 0 0 0 0-1.42L18.21 3.29a1.003 1.003 0 0 0-1.42 0L14.83 5.25l3.75 3.75 2.13-1.79z"/></svg>
          <span>Edit</span>
        </a>
        <a id="computerAssetPopupDelete" class="mobile-asset-popup-action delete" href="#">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 7h12l-1 14H7L6 7zm3-3h6l1 2h4v2H4V6h4l1-2z"/></svg>
          <span>Delete</span>
        </a>
      </div>
    </div>
    <div id="computerAssetPopupBody" class="mobile-asset-popup-body"></div>
  </div>
</div>

<script>
let debounceTimer;
let currentIndex = -1;
let visibleRows = [];

function debouncedSearch(){
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(searchTable, 250);
}

function fuzzyMatch(text, input){
  if(text.includes(input)) return true;
  return text.split(" ").some(w => w.startsWith(input) || w.includes(input));
}

function escapeRegExp(value){
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function isHighlightableCell(cell){
  if(!cell){
    return false;
  }

  const interactiveSelector = "a,button,input,select,textarea,svg,img,.asset-item-btn-wrap,.password-pill";
  if(cell.querySelector(interactiveSelector)){
    return false;
  }

  const children = Array.from(cell.children || []);
  return children.length === 0 || children.every(child => child.classList.contains("highlight"));
}

function clearCellHighlight(cell){
  if(!isHighlightableCell(cell)){
    return;
  }

  cell.innerHTML = cell.innerHTML.replace(/<span class="highlight">(.*?)<\/span>/gi, "$1");
}

function applyCellHighlight(cell, input){
  if(!input || !isHighlightableCell(cell)){
    return;
  }

  const cellText = String(cell.textContent || "").trim();
  if(cellText === ""){
    return;
  }

  const regex = new RegExp(`(${escapeRegExp(input)})`, "gi");
  cell.innerHTML = escapeHtml(cellText).replace(regex, '<span class="highlight">$1</span>');
}

function searchTable(){
  const input = document.getElementById("searchInput").value.toLowerCase().trim();
  const bodies = document.querySelectorAll(".hardware-table tbody");
  let found = false;
  let firstMatch = null;
  visibleRows = [];
  currentIndex = -1;

  bodies.forEach(body => {
    const hidden = Array.from(body.querySelectorAll("[data-search-text]"))
      .map(cell => cell.dataset.searchText || "")
      .join(" ");
    const textContent = (body.innerText + " " + hidden).toLowerCase();
    const match = input ? fuzzyMatch(textContent, input) : true;

    body.querySelectorAll("td").forEach(cell => {
      clearCellHighlight(cell);
      if(input && fuzzyMatch(cell.innerText.toLowerCase(), input)){
        applyCellHighlight(cell, input);
      }
    });

    if(match){
      body.style.display = "";
      visibleRows.push(body);
      found = true;
      if(!firstMatch) firstMatch = body;
    } else {
      body.style.display = "none";
    }
  });

  document.getElementById("noResult").style.display = found ? "none" : "block";
  if(firstMatch) firstMatch.scrollIntoView({ behavior:"smooth", block:"center" });
}

function handleKey(e){
  if(visibleRows.length === 0) return;
  if(e.key === "ArrowDown"){ e.preventDefault(); currentIndex=(currentIndex+1)%visibleRows.length; highlightRow(); }
  if(e.key === "ArrowUp"){ e.preventDefault(); currentIndex=(currentIndex-1+visibleRows.length)%visibleRows.length; highlightRow(); }
}

function highlightRow(){
  visibleRows.forEach(r => r.classList.remove("active-row"));
  visibleRows[currentIndex].classList.add("active-row");
}

function escapeHtml(value){
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function buildMaskedPassword(value){
  const text = String(value ?? "").trim();
  if(text === ""){
    return '<span class="asset-item-empty">N/A</span>';
  }

  const dotCount = Math.max(6, Math.min(12, text.length));
  const dots = "&bull;".repeat(dotCount);
  const safeText = escapeHtml(text);

  return ''
    + '<span class="password-pill" data-password="' + safeText + '" data-visible="0">'
    + '  <span class="password-pill-text">' + dots + '</span>'
    + '  <button type="button" class="password-eye-btn" onclick="togglePasswordVisibility(this)" aria-label="Show password" title="Show password">&#128065;</button>'
    + '</span>';
}

function togglePasswordVisibility(button){
  const pill = button.closest(".password-pill");
  if(!pill){
    return;
  }

  const textNode = pill.querySelector(".password-pill-text");
  if(!textNode){
    return;
  }

  const raw = pill.dataset.password || "";
  const visible = pill.dataset.visible === "1";

  if(visible){
    const dotCount = Math.max(6, Math.min(12, raw.length > 0 ? raw.length : 6));
    textNode.innerHTML = "&bull;".repeat(dotCount);
    pill.dataset.visible = "0";
    button.setAttribute("aria-label", "Show password");
    button.setAttribute("title", "Show password");
  } else {
    textNode.textContent = raw;
    pill.dataset.visible = "1";
    button.setAttribute("aria-label", "Hide password");
    button.setAttribute("title", "Hide password");
  }
}

function renderAssetItemButtonsClient(items, label, type, uid, assetIndex){
  if(!Array.isArray(items) || items.length === 0){
    return '<span class="asset-item-empty">N/A</span>';
  }

  let btnClass = "asset-item-btn storage";
  let wrapClass = "asset-item-btn-wrap compact-wrap";

  if(type === "monitor"){
    btnClass = "asset-item-btn monitor";
  } else if(type === "windows"){
    btnClass = "asset-item-btn windows";
  } else if(type === "software"){
    btnClass = "asset-item-btn software";
    wrapClass = "asset-item-btn-wrap software-wrap";
  }

  const getButtonLabel = (item, index) => {
    const fallback = `${label} ${index + 1}`;

    if(type === "storage"){
      const model = String((item && item.model) || "").trim();
      const capacity = String((item && item.capacity) || "").trim();

      if(model && capacity){
        return `${model} (${capacity})`;
      }
      if(model){
        return model;
      }
      if(capacity){
        return capacity;
      }
      return fallback;
    }

    if(type === "monitor"){
      const model = String((item && item.model) || "").trim();
      const size = String((item && item.size) || "").trim();

      if(model && size){
        return `${model} (${size})`;
      }
      if(model){
        return model;
      }
      if(size){
        return size;
      }
      return fallback;
    }

    if(type === "windows"){
      const os = String((item && item.window__os) || "").trim();
      if(os){
        return os;
      }
      return fallback;
    }

    return fallback;
  };

  return `
    <div class="${wrapClass}">
      ${items.map((item, index) => {
        const buttonLabel = getButtonLabel(item, index);
        return `<button type="button" class="${btnClass}" title="${escapeHtml(buttonLabel)}" onclick="openAssetItemPopup('${type}', ${uid}, ${assetIndex}, ${index}); event.stopPropagation();">${escapeHtml(buttonLabel)}</button>`;
      }).join("")}
    </div>
  `;
}

function openAssetItemPopup(type, uid, assetIndex, itemIndex){
  closeMobileAssetPopup();
  closeComputerAssetPopup();
  const asset = (window.userAssets[uid] || [])[assetIndex];
  if(!asset){ return; }

  let items = asset.software_items;
  if(type === "storage"){
    items = asset.storage_items;
  } else if(type === "monitor"){
    items = asset.monitor_items;
  } else if(type === "windows"){
    items = asset.windows_items;
  }
  const item = Array.isArray(items) ? items[itemIndex] : null;
  if(!item){ return; }

  const label = type === "storage"
    ? "Storage"
    : (type === "monitor" ? "Monitor" : (type === "windows" ? "Windows" : "Software"));
  const title = `${label} ${itemIndex + 1}`;
  const subtitleParts = [];
  if(asset.asset_type){ subtitleParts.push(asset.asset_type); }
  if(asset.asset_label){ subtitleParts.push(asset.asset_label); }

  let rows = "";
  if(type === "storage"){
    rows += `<div class="asset-item-popup-row"><strong>Model</strong><span>${escapeHtml(item.model || "-")}</span></div>`;
    rows += `<div class="asset-item-popup-row"><strong>Capacity</strong><span>${escapeHtml(item.capacity || "-")}</span></div>`;
    rows += `<div class="asset-item-popup-row"><strong>Serial</strong><span>${escapeHtml(item.serial || "-")}</span></div>`;
  } else if(type === "monitor") {
    rows += `<div class="asset-item-popup-row"><strong>Model</strong><span>${escapeHtml(item.model || "-")}</span></div>`;
    rows += `<div class="asset-item-popup-row"><strong>Size</strong><span>${escapeHtml(item.size || "-")}</span></div>`;
    rows += `<div class="asset-item-popup-row"><strong>Serial</strong><span>${escapeHtml(item.serial || "-")}</span></div>`;
  } else if(type === "windows") {
    rows += `<div class="asset-item-popup-row"><strong>Operating System</strong><span>${escapeHtml(item.window__os || "-")}</span></div>`;
    rows += `<div class="asset-item-popup-row"><strong>Windows Serial / Key</strong><span>${escapeHtml(item.windows_serial || "-")}</span></div>`;
  } else {
    rows += `<div class="asset-item-popup-row"><strong>Software Name</strong><span>${escapeHtml(item.name || "-")}</span></div>`;
  }

  const popupBox = document.querySelector("#assetItemPopup .asset-item-popup-box");
  popupBox.classList.remove("storage-theme", "monitor-theme", "windows-theme", "software-theme");
  if(type === "storage"){
    popupBox.classList.add("storage-theme");
  } else if(type === "monitor"){
    popupBox.classList.add("monitor-theme");
  } else if(type === "windows"){
    popupBox.classList.add("windows-theme");
  } else {
    popupBox.classList.add("software-theme");
  }

  document.getElementById("assetItemPopupTitle").textContent = title;
  document.getElementById("assetItemPopupSub").textContent = subtitleParts.join(" - ");
  document.getElementById("assetItemPopupGrid").innerHTML = rows;
  document.getElementById("assetItemPopup").classList.add("open");
}

function closeAssetItemPopup(){
  document.getElementById("assetItemPopup").classList.remove("open");
}

function isMobileAsset(asset){
  return !!asset && (asset.asset_type === "iPad" || asset.asset_type === "Phone");
}

function isComputerAsset(asset){
  return !!asset && (asset.asset_type === "Desktop" || asset.asset_type === "Laptop");
}

function renderActionButtonsClient(asset, uid, assetIndex){
  if(isMobileAsset(asset)){
    return '<button type="button" class="view-btn device-btn" onclick="openMobileAssetPopup('+uid+', '+assetIndex+'); event.stopPropagation();">View</button>'
      + '<a href="edit_asset.php?id='+asset.id+'" class="edit-btn device-btn">Edit</a>'
      + '<a href="delete_asset.php?id='+asset.id+'" class="delete-btn device-btn" onclick="return confirm(\'Delete this asset?\')">Delete</a>';
  }

  return '<a href="asset_detail.php?id='+asset.id+'" class="view-btn device-btn">View</a>'
    + '<a href="edit_asset.php?id='+asset.id+'" class="edit-btn device-btn">Edit</a>'
    + '<a href="delete_asset.php?id='+asset.id+'" class="delete-btn device-btn" onclick="return confirm(\'Delete this asset?\')">Delete</a>';
}

function popupValue(value, fallback = "Not provided"){
  const text = String(value ?? "").trim();
  if(text === ""){
    return '<span class="mobile-asset-popup-empty">'+escapeHtml(fallback)+'</span>';
  }
  return escapeHtml(text);
}

function renderMobilePopupRows(rows){
  return rows.map(([label, value]) => `
    <div class="mobile-asset-popup-row">
      <strong>${escapeHtml(label)}</strong>
      <span>${popupValue(value)}</span>
    </div>
  `).join("");
}

function renderMobilePopupCard(title, rows){
  if(!Array.isArray(rows) || rows.length === 0){
    return "";
  }

  return `
    <section class="mobile-asset-popup-card">
      <div class="mobile-asset-popup-card-head">
        <span class="mobile-asset-popup-card-dot"></span>
        <span>${escapeHtml(title)}</span>
      </div>
      <div class="mobile-asset-popup-card-body">
        ${renderMobilePopupRows(rows)}
      </div>
    </section>
  `;
}

function renderMobilePopupListCard(title, items, emptyText = "No information available"){
  const list = Array.isArray(items) ? items.filter(item => String((item && item.name) || item || "").trim() !== "") : [];
  let body = '<span class="mobile-asset-popup-empty">'+escapeHtml(emptyText)+'</span>';

  if(list.length > 0){
    body = '<ul class="mobile-asset-popup-list">'
      + list.map(item => '<li>'+escapeHtml((item && item.name) || item)+'</li>').join("")
      + '</ul>';
  }

  return `
    <section class="mobile-asset-popup-card">
      <div class="mobile-asset-popup-card-head">
        <span class="mobile-asset-popup-card-dot"></span>
        <span>${escapeHtml(title)}</span>
      </div>
      <div class="mobile-asset-popup-card-body">
        ${body}
      </div>
    </section>
  `;
}

function formatCompositeItems(items, formatter){
  if(!Array.isArray(items)){
    return [];
  }

  return items
    .map(item => formatter(item || {}))
    .filter(line => String(line || "").trim() !== "");
}

function openComputerAssetPopup(uid, assetIndex){
  closeAssetItemPopup();
  closeMobileAssetPopup();

  const asset = (window.userAssets[uid] || [])[assetIndex];
  if(!isComputerAsset(asset)){
    return;
  }

  const popup = document.getElementById("computerAssetPopup");
  const box = popup.querySelector(".mobile-asset-popup-box");
  const isDesktop = asset.asset_type === "Desktop";
  const typeLabel = asset.asset_type_label || (isDesktop ? "PC" : "Laptop");
  const title = typeLabel + " Detail";
  const kicker = typeLabel + " Asset";
  const subtitle = [asset.user_name, typeLabel, "Asset ID: " + asset.id]
    .filter(part => String(part || "").trim() !== "")
    .join(" - ");

  box.classList.remove("pc-theme", "laptop-theme");
  box.classList.add(isDesktop ? "pc-theme" : "laptop-theme");

  document.getElementById("computerAssetPopupKicker").textContent = kicker;
  document.getElementById("computerAssetPopupTitle").textContent = title;
  document.getElementById("computerAssetPopupSub").textContent = subtitle;
  document.getElementById("computerAssetPopupEdit").href = "edit_asset.php?id=" + asset.id;
  document.getElementById("computerAssetPopupDelete").href = "delete_asset.php?id=" + asset.id;
  document.getElementById("computerAssetPopupDelete").onclick = function(){
    return confirm("Delete this asset?");
  };

  const storageLines = formatCompositeItems(asset.storage_items, item => {
    const bits = [];
    if(item.model){ bits.push(item.model); }
    if(item.capacity){ bits.push("(" + item.capacity + ")"); }
    let line = bits.join(" ");
    if(item.serial){
      line += (line ? " - " : "") + item.serial;
    }
    return line;
  });

  const monitorLines = formatCompositeItems(asset.monitor_items, item => {
    const bits = [];
    if(item.model){ bits.push(item.model); }
    if(item.size){ bits.push("(" + item.size + ")"); }
    let line = bits.join(" ");
    if(item.serial){
      line += (line ? " - " : "") + item.serial;
    }
    return line;
  });

  const softwareLines = Array.isArray(asset.software_items)
    ? asset.software_items.map(item => (item && item.name) || "").filter(Boolean)
    : [];
  const windowsLines = formatCompositeItems(asset.windows_items, item => {
    const os = String((item && item.window__os) || "").trim();
    const serial = String((item && item.windows_serial) || "").trim();
    if(!os && !serial){
      return "";
    }
    return serial ? `${os || "Windows"} - ${serial}` : os;
  });

  const ramRows = [];
  if(Number(asset.ram_total) > 0){
    ramRows.push(["Installed Memory", asset.ram_total + " GB"]);
  }
  if(Array.isArray(asset.ram_sticks) && asset.ram_sticks.length > 0){
    ramRows.push(["Configuration", asset.ram_sticks.join(" + ")]);
  }
  if(Number(asset.ram_slots) > 0){
    ramRows.push(["Slots", asset.ram_slots + (Number(asset.ram_slots) === 1 ? " slot" : " slots")]);
  }

  let body = renderMobilePopupCard("User Information", [
    ["Name", asset.user_name],
    ["Position", asset.user_position],
    ["Contact No", asset.user_contact_no],
    ["Email", asset.user_email_id],
    ["Email Password", asset.user_email_password],
    ["Mail Server", asset.user_mail_server]
  ]);

  body += renderMobilePopupCard("PC Information", [
    ["PC Name", asset.pc_name_text],
    ["PC Model", asset.model_name],
    ["PC Serial Number", asset.pc_serial_no_text],
    ["MAC LAN", asset.mac_lan_text],
    ["MAC WiFi", asset.mac_wifi_text],
    ["Antivirus", asset.antivirus_text],
    ["PC Username", asset.pc_username_text],
    ["PC Password", asset.pc_password_text]
  ]);

  body += renderMobilePopupCard("CPU & GPU", [
    ["CPU Model", asset.cpu_model_text],
    ["CPU Speed", asset.cpu_speed_text ? asset.cpu_speed_text + " GHz" : ""],
    ["Cores", asset.cpu_core_text],
    ["Hyper Threading", asset.cpu_thread_text],
    ["GPU", asset.gpu_text]
  ]);

  body += renderMobilePopupCard("RAM", ramRows);
  body += renderMobilePopupListCard("Storage", storageLines, "No storage recorded");
  body += renderMobilePopupListCard("Monitor", monitorLines, "No monitor recorded");
  body += renderMobilePopupListCard("Windows", windowsLines, "No windows recorded");
  body += renderMobilePopupListCard("Software", softwareLines, "No software recorded");

  document.getElementById("computerAssetPopupBody").innerHTML = body;
  popup.classList.add("open");
}

function openMobileAssetPopup(uid, assetIndex){
  closeAssetItemPopup();
  closeComputerAssetPopup();
  const asset = (window.userAssets[uid] || [])[assetIndex];
  if(!isMobileAsset(asset)){
    return;
  }

  const popup = document.getElementById("mobileAssetPopup");
  const box = popup.querySelector(".mobile-asset-popup-box");
  const isIpad = asset.asset_type === "iPad";
  const typeLabel = asset.asset_type_label || (isIpad ? "iPad" : "Phone");
  const title = typeLabel + " Detail";
  const kicker = typeLabel + " Asset";
  const subtitle = [asset.user_name, typeLabel, "Asset ID: " + asset.id]
    .filter(part => String(part || "").trim() !== "")
    .join(" - ");

  box.classList.remove("ipad-theme", "phone-theme");
  box.classList.add(isIpad ? "ipad-theme" : "phone-theme");

  document.getElementById("mobileAssetPopupKicker").textContent = kicker;
  document.getElementById("mobileAssetPopupTitle").textContent = title;
  document.getElementById("mobileAssetPopupSub").textContent = subtitle;
  document.getElementById("mobileAssetPopupEdit").href = "edit_asset.php?id=" + asset.id;
  document.getElementById("mobileAssetPopupDelete").href = "delete_asset.php?id=" + asset.id;
  document.getElementById("mobileAssetPopupDelete").onclick = function(){
    return confirm("Delete this asset?");
  };

  const userRows = [
    ["Name", asset.user_name],
    ["Position", asset.user_position],
    ["Contact No", asset.user_contact_no],
    ["Email", asset.user_email_id],
    ["Email Password", asset.user_email_password],
    ["Mail Server", asset.user_mail_server]
  ];

  let body = renderMobilePopupCard("User Information", userRows);

  if(isIpad){
    body += renderMobilePopupCard("iPad Information", [
      ["Model", asset.model_name],
      ["Serial Number", asset.serial_no],
      ["Storage Capacity", asset.storage_capacity],
      ["iOS Version", asset.os_version],
      ["IMEI / UDID", asset.imei]
    ]);

    body += renderMobilePopupCard("Connectivity", [
      ["MAC WiFi", asset.mac_wifi_text],
      ["SIM Number", asset.sim_no]
    ]);

    body += renderMobilePopupCard("Apple ID", [
      ["Apple ID", asset.apple_id],
      ["Apple ID Password", asset.apple_password]
    ]);

    body += renderMobilePopupListCard("Software / Apps", asset.software_items, "No apps recorded");
  } else {
    body += renderMobilePopupCard("Phone Information", [
      ["Model", asset.model_name],
      ["Serial Number", asset.serial_no],
      ["IMEI", asset.imei],
      ["OS Version", asset.os_version],
      ["Storage Capacity", asset.storage_capacity]
    ]);

    body += renderMobilePopupCard("SIM & Network", [
      ["SIM Number", asset.sim_no],
      ["Carrier / Provider", asset.carrier],
      ["MAC WiFi", asset.mac_wifi_text]
    ]);

    body += renderMobilePopupCard("Account", [
      ["Google / Apple ID", asset.account_email],
      ["Account Password", asset.account_password]
    ]);

    if(Array.isArray(asset.software_items) && asset.software_items.length > 0){
      body += renderMobilePopupListCard("Software / Apps", asset.software_items, "No apps recorded");
    }
  }

  document.getElementById("mobileAssetPopupBody").innerHTML = body;
  popup.classList.add("open");
}

function closeMobileAssetPopup(){
  document.getElementById("mobileAssetPopup").classList.remove("open");
}

function closeComputerAssetPopup(){
  document.getElementById("computerAssetPopup").classList.remove("open");
}

function openCurrentDisplayedAssetPopup(uid){
  const row = document.getElementById("row_user_" + uid);
  if(!row){
    return;
  }

  const assetIndex = Number(row.dataset.currentIndex || 0);
  const asset = (window.userAssets[uid] || [])[assetIndex];
  if(!asset){
    return;
  }

  if(isComputerAsset(asset)){
    openComputerAssetPopup(uid, assetIndex);
  } else if(isMobileAsset(asset)){
    openMobileAssetPopup(uid, assetIndex);
  }
}

function handleAssetTypeClick(uid, assetIndex){
  const asset = (window.userAssets[uid] || [])[assetIndex];
  if(!asset){
    return false;
  }

  if(isMobileAsset(asset)){
    openMobileAssetPopup(uid, assetIndex);
    return false;
  }

  switchAsset(uid, assetIndex);
  return false;
}

document.addEventListener("keydown", function(e){
  if(e.key === "Escape"){
    closeAssetItemPopup();
    closeMobileAssetPopup();
    closeComputerAssetPopup();
  }
});

function switchAsset(uid, assetIndex){
    const assets = window.userAssets[uid];
    const a = assets[assetIndex];

    document.querySelectorAll('.asset-type-btn[data-uid="'+uid+'"]').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector('.asset-type-btn[data-uid="'+uid+'"][data-index="'+assetIndex+'"]').classList.add('active');

    const row = document.getElementById('row_user_'+uid);
    row.dataset.currentIndex = assetIndex;
    row.querySelector('.cell-pc-username').innerHTML = a.pc_username;
    row.querySelector('.cell-pc-password').innerHTML = buildMaskedPassword(a.pc_password_text);
    row.querySelector('.cell-pc-model').innerHTML = a.pc_model;
    row.querySelector('.cell-pc-name').innerHTML = a.pc_name;
    row.querySelector('.cell-pc-serial').innerHTML = a.pc_serial_no;
    row.querySelector('.cell-mac-lan').innerHTML = a.mac_lan;
    row.querySelector('.cell-mac-wifi').innerHTML = a.mac_wifi;
    row.querySelector('.cell-antivirus').innerHTML = a.antivirus;
    row.querySelector('.cell-cpu').innerHTML = a.cpu;
    row.querySelector('.cell-gpu').innerHTML = a.gpu;
    row.querySelector('.cell-ram').innerHTML = a.ram;
    row.querySelector('.cell-storage').innerHTML = renderAssetItemButtonsClient(a.storage_items, "Storage", "storage", uid, assetIndex);
    row.querySelector('.cell-storage').dataset.searchText = a.storage_search || "";
    row.querySelector('.cell-monitor').innerHTML = renderAssetItemButtonsClient(a.monitor_items, "Monitor", "monitor", uid, assetIndex);
    row.querySelector('.cell-monitor').dataset.searchText = a.monitor_search || "";
    row.querySelector('.cell-windows').innerHTML = isComputerAsset(a)
      ? renderAssetItemButtonsClient(a.windows_items, "Windows", "windows", uid, assetIndex)
      : a.windows_key;
    row.querySelector('.cell-windows').dataset.searchText = a.windows_search || "";
    row.querySelector('.cell-software').innerHTML = renderAssetItemButtonsClient(a.software_items, "Software", "software", uid, assetIndex);
    row.querySelector('.cell-software').dataset.searchText = a.software_search || "";

    row.querySelector('.cell-action').innerHTML = renderActionButtonsClient(a, uid, assetIndex);
}
</script>

<?php include "components/footer.php"; ?>


