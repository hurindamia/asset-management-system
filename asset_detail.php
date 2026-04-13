<?php include "config/db.php"; ?>

<link rel="stylesheet" href="css/style.css">
<?php include "components/navbar.php"; ?>

<?php

function h($value){
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function detailText($value, $fallback = 'Not provided'){
    $value = trim((string)($value ?? ''));
    if($value === ''){
        return '<span class="detail-empty">' . h($fallback) . '</span>';
    }
    return h($value);
}

function renderDetailGrid($rows, $columns = 2){
    $class = $columns === 1 ? 'detail-grid single' : 'detail-grid';
    $html = '<div class="' . $class . '">';
    foreach($rows as $row){
        $html .= '<div class="detail-item">';
        $html .= '<span class="detail-key">' . h($row[0]) . '</span>';
        $html .= '<span class="detail-value">' . detailText($row[1]) . '</span>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

function renderBulletList($items, $fallback = 'No information available'){
    if(empty($items)){
        return '<div class="detail-empty-block">' . h($fallback) . '</div>';
    }

    $html = '<ul class="detail-bullets">';
    foreach($items as $item){
        $html .= '<li>' . h($item) . '</li>';
    }
    $html .= '</ul>';
    return $html;
}

function buildCompositeList($models, $extrasA, $extrasB){
    $items = [];
    for($i = 0; $i < count($models); $i++){
        $model = trim((string)($models[$i] ?? ''));
        $extraA = trim((string)($extrasA[$i] ?? ''));
        $extraB = trim((string)($extrasB[$i] ?? ''));

        if($model === ''){
            continue;
        }

        $line = $model;
        if($extraA !== ''){
            $line .= ' (' . $extraA . ')';
        }
        if($extraB !== ''){
            $line .= ' - ' . $extraB;
        }

        $items[] = $line;
    }

    return $items;
}

/* =========================
   GET PARAMETERS
========================= */

$user_id = intval($_GET['user_id'] ?? 0);
$type    = isset($_GET['type']) ? mysqli_real_escape_string($conn, trim($_GET['type'])) : null;
$id      = intval($_GET['id'] ?? 0);

/* =========================
   DECIDE QUERY MODE
========================= */

if($id > 0){
    $where = "assets.asset_id = $id";
}
else if($user_id > 0 && $type){
    $where = "assets.user_id = $user_id AND assets.asset_type = '$type'";
}
else{
    die("Invalid request");
}

/* =========================
   MAIN QUERY
========================= */

$query = "
SELECT 
assets.asset_id AS ID,
assets.asset_type,

users.name,
users.position,
users.contact_no,
users.email_id,
users.email_password,
users.mail_server,

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

MAX(ram_agg.ram) AS ram,

GROUP_CONCAT(DISTINCT storage.hdd_model ORDER BY storage.hdd_model) AS hdd_model,
GROUP_CONCAT(DISTINCT storage.hdd_capacity ORDER BY storage.hdd_model) AS hdd_capacity,
GROUP_CONCAT(DISTINCT storage.hdd_serial ORDER BY storage.hdd_model) AS hdd_serial,

GROUP_CONCAT(DISTINCT monitor.monitor_model ORDER BY monitor.monitor_model) AS monitor_model,
GROUP_CONCAT(DISTINCT monitor.monitor_size ORDER BY monitor.monitor_model) AS monitor_size,
GROUP_CONCAT(DISTINCT monitor.monitor_serial ORDER BY monitor.monitor_model) AS monitor_serial,

GROUP_CONCAT(DISTINCT software.software_name) AS software

FROM assets
LEFT JOIN users ON assets.user_id = users.user_id
LEFT JOIN cpu ON assets.asset_id = cpu.asset_id
LEFT JOIN (
    SELECT asset_id, GROUP_CONCAT(ram_size SEPARATOR '||') AS ram
    FROM ram
    GROUP BY asset_id
) ram_agg ON ram_agg.asset_id = assets.asset_id
LEFT JOIN storage ON assets.asset_id = storage.asset_id
LEFT JOIN monitor ON assets.asset_id = monitor.asset_id
LEFT JOIN software ON assets.asset_id = software.asset_id

WHERE $where
GROUP BY assets.asset_id
LIMIT 1
";

$result = mysqli_query($conn, $query);
$data   = mysqli_fetch_assoc($result);

if(!$data){
    die("No data found");
}

$asset_type = $data['asset_type'] ?? 'Desktop';
$asset_name = trim((string)($data['name'] ?? 'Unassigned User'));
if($asset_name === ''){
    $asset_name = 'Unassigned User';
}

/* ================= PROCESS ================= */

$ram_arr = !empty($data['ram']) ? explode("||", $data['ram']) : [];
$ramSticks = [];
$totalRam = 0;
foreach($ram_arr as $r){
    $val = intval($r);
    if($val > 0){
        $ramSticks[] = $val . ' GB';
        $totalRam += $val;
    }
}
$ramBreakdown = !empty($ramSticks) ? implode(' + ', $ramSticks) : '';
$ramSlots = count($ramSticks);

$storageItems = buildCompositeList(
    !empty($data['hdd_model']) ? explode(",", $data['hdd_model']) : [],
    !empty($data['hdd_capacity']) ? explode(",", $data['hdd_capacity']) : [],
    !empty($data['hdd_serial']) ? explode(",", $data['hdd_serial']) : []
);

$monitorItems = buildCompositeList(
    !empty($data['monitor_model']) ? explode(",", $data['monitor_model']) : [],
    !empty($data['monitor_size']) ? explode(",", $data['monitor_size']) : [],
    !empty($data['monitor_serial']) ? explode(",", $data['monitor_serial']) : []
);

$softwareItems = [];
$software_arr = !empty($data['software']) ? explode(",", $data['software']) : [];
foreach($software_arr as $s){
    $s = trim((string)$s);
    if($s !== ''){
        $softwareItems[] = $s;
    }
}

$userRows = [
    ['Name', $data['name'] ?? ''],
    ['Email', $data['email_id'] ?? ''],
    ['Position', $data['position'] ?? ''],
    ['Email Password', $data['email_password'] ?? ''],
    ['Contact No', $data['contact_no'] ?? ''],
    ['Mail Server', $data['mail_server'] ?? ''],
    ['Asset ID', $data['ID'] ?? ''],
    ['Device Type', $asset_type]
];

$pcRows = [
    ['PC Name', $data['pc_name'] ?? ''],
    ['PC Model', $data['pc_model'] ?? ''],
    ['PC Serial Number', $data['pc_serial_no'] ?? ''],
    ['MAC LAN', $data['mac_lan'] ?? ''],
    ['MAC WiFi', $data['mac_wifi'] ?? ''],
    ['Antivirus', $data['antivirus'] ?? ''],
    ['PC Username', $data['pc_username'] ?? ''],
    ['PC Password', $data['pc_password'] ?? '']
];

$cpuRows = [
    ['CPU Model', $data['cpu_model'] ?? ''],
    ['CPU Speed', $data['cpu_speed'] ?? ''],
    ['Cores', $data['cpu_core'] ?? ''],
    ['Hyper Threading', $data['cpu_hyper_thread'] ?? ''],
    ['GPU', $data['graphic_card'] ?? '']
];

$ipadRows = [
    ['Model', $data['pc_model'] ?? ''],
    ['Serial Number', $data['serial_no'] ?? ''],
    ['Storage Capacity', $data['storage_capacity'] ?? ''],
    ['iOS Version', $data['os_version'] ?? ''],
    ['IMEI / UDID', $data['imei'] ?? '']
];

$ipadConnectivityRows = [
    ['MAC WiFi', $data['mac_wifi'] ?? ''],
    ['SIM Number', $data['sim_no'] ?? '']
];

$appleRows = [
    ['Apple ID', $data['apple_id'] ?? ''],
    ['Apple ID Password', $data['apple_password'] ?? '']
];

$phoneRows = [
    ['Model', $data['pc_model'] ?? ''],
    ['Serial Number', $data['serial_no'] ?? ''],
    ['IMEI', $data['imei'] ?? ''],
    ['OS Version', $data['os_version'] ?? ''],
    ['Storage Capacity', $data['storage_capacity'] ?? '']
];

$phoneNetworkRows = [
    ['SIM Number', $data['sim_no'] ?? ''],
    ['Carrier / Provider', $data['carrier'] ?? ''],
    ['MAC WiFi', $data['mac_wifi'] ?? '']
];

$accountRows = [
    ['Email', $data['account_email'] ?? ''],
    ['Password', $data['account_password'] ?? '']
];
?>

<style>
.asset-detail-shell {
    background:
        radial-gradient(circle at top right, rgba(18, 62, 107, 0.10), transparent 30%),
        linear-gradient(180deg, rgba(255,255,255,0.98), rgba(243,248,252,0.98));
    border: 1px solid rgba(11, 42, 74, 0.08);
    box-shadow: 0 20px 42px rgba(11, 42, 74, 0.10);
    padding: 20px;
}

.asset-hero {
    margin-bottom: 18px;
    padding: 18px 20px;
    border-radius: 20px;
    background:
        radial-gradient(circle at top left, rgba(255, 191, 214, 0.20), transparent 30%),
        radial-gradient(circle at right center, rgba(141, 189, 255, 0.18), transparent 26%),
        linear-gradient(135deg, rgba(12, 46, 81, 0.98), rgba(25, 77, 123, 0.94) 58%, rgba(88, 102, 176, 0.90)),
        linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0));
    color: #fff;
}

.hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}

.hero-kicker {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.10);
    color: rgba(255,255,255,0.82);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.asset-title {
    margin: 10px 0 0;
    color: #fff;
    text-align: left;
}

.asset-title::after {
    margin: 8px 0 0;
    background: rgba(255,255,255,0.84);
}

.asset-subtitle {
    margin: 10px 0 0;
    color: rgba(255,255,255,0.76);
    max-width: 720px;
    line-height: 1.55;
    font-size: 0.94rem;
}

.hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,0.10);
    color: #fff;
    font-size: 0.8rem;
    font-weight: 700;
}

.hero-chip span {
    color: rgba(255,255,255,0.64);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.hero-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.hero-btn {
    min-width: 96px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    box-shadow: none;
}

.hero-btn.edit-btn {
    background: rgba(255,255,255,0.16);
    border: 1px solid rgba(255,255,255,0.18);
}

.hero-btn.edit-btn:hover {
    background: rgba(255,255,255,0.24);
}

.hero-btn.delete-btn {
    background: #ef5350;
}

.hero-btn.delete-btn:hover {
    background: #e23f3c;
}

.detail-wrapper {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
    align-items: start;
}

.detail-card {
    --accent: #4a7fae;
    --card-bg: linear-gradient(180deg, #fcfdff, #f6f9fc);
    background: var(--card-bg);
    border: 1px solid rgba(11, 42, 74, 0.08);
    border-radius: 18px;
    box-shadow: 0 10px 22px rgba(11, 42, 74, 0.08);
    overflow: hidden;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    align-self: start;
}

.detail-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(11, 42, 74, 0.10);
}

.detail-card.featured {
    grid-column: span 2;
}

.detail-card.user-card {
    --accent: #b56f8a;
    --card-bg: linear-gradient(135deg, #fff7fc 0%, #f9eef6 58%, #f5eef8 100%);
}

.detail-card.pc-card {
    --accent: #d18443;
    --card-bg: linear-gradient(180deg, #fffaf5, #fbf2e7);
}

.detail-card.cpu-card {
    --accent: #d0a23b;
    --card-bg: linear-gradient(180deg, #fffdf5, #fbf6e6);
}

.detail-card.ram-card {
    --accent: #6ea14a;
    --card-bg: linear-gradient(180deg, #fbfff8, #f0f9e9);
}

.detail-card.storage-card {
    --accent: #467aa7;
    --card-bg: linear-gradient(180deg, #f9fcff, #edf4fb);
}

.detail-card.monitor-card {
    --accent: #5b73b3;
    --card-bg: linear-gradient(180deg, #fafbff, #eef1fb);
}

.detail-card.windows-card {
    --accent: #4190b4;
    --card-bg: linear-gradient(180deg, #f7fcff, #ecf7fb);
}

.detail-card.software-card {
    --accent: #7865a4;
    --card-bg: linear-gradient(180deg, #fbf9ff, #f1eef8);
}

.card-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 16px 10px;
    color: #082744;
    font-size: 0.92rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.card-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 0 6px color-mix(in srgb, var(--accent) 18%, transparent);
}

.card-body {
    padding: 0 16px 14px;
    color: #0f304d;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 18px;
}

.detail-grid.single {
    grid-template-columns: 1fr;
}

.detail-item {
    padding: 10px 0;
    border-bottom: 1px solid rgba(11, 42, 74, 0.08);
}

.detail-item:last-child,
.detail-grid .detail-item:nth-last-child(-n+2) {
    border-bottom: none;
}

.detail-grid.single .detail-item:last-child {
    border-bottom: none;
}

.detail-key {
    display: block;
    color: #516b84;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.10em;
    text-transform: uppercase;
}

.detail-value {
    display: block;
    margin-top: 4px;
    color: #0d2b46;
    line-height: 1.45;
    word-break: break-word;
    font-size: 0.94rem;
    font-weight: 550;
}

.detail-empty {
    color: #8ca1b5;
    font-style: italic;
}

.detail-empty-block {
    padding: 14px 0 2px;
    color: #8ca1b5;
    font-style: italic;
}

.detail-bullets {
    list-style: none;
    margin: 0;
    padding: 0;
}

.detail-bullets li {
    position: relative;
    padding: 10px 0 10px 16px;
    border-bottom: 1px solid rgba(11, 42, 74, 0.08);
    line-height: 1.45;
    color: #0f304d;
    font-size: 0.94rem;
    font-weight: 550;
}

.detail-bullets li:last-child {
    border-bottom: none;
}

.detail-bullets li::before {
    content: "";
    position: absolute;
    left: 0;
    top: 18px;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--accent);
}

.ram-summary {
    padding: 2px 0 12px;
    border-bottom: 1px solid rgba(11, 42, 74, 0.08);
}

.ram-summary span {
    display: block;
    color: #627b90;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.10em;
    text-transform: uppercase;
}

.ram-summary strong {
    display: block;
    margin-top: 6px;
    color: #0b2a4a;
    font-size: 1.6rem;
    line-height: 1;
}

.ram-notes {
    padding-top: 12px;
}

.ram-notes p {
    margin: 0 0 8px;
    color: #0f304d;
    line-height: 1.45;
    font-size: 0.94rem;
    font-weight: 550;
}

.ram-notes p:last-child {
    margin-bottom: 0;
}

.detail-footer {
    display: flex;
    justify-content: flex-start;
    margin-top: 16px;
}

.detail-back-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 108px;
    height: 38px;
    padding: 0 16px;
    border-radius: 12px;
    background: #0b2a4a;
    border: 1px solid rgba(11, 42, 74, 0.18);
    color: #ffffff;
    text-decoration: none;
    font-weight: 700;
    box-shadow: 0 10px 20px rgba(11, 42, 74, 0.22);
}

.detail-back-link:hover {
    background: #123e6b;
    transform: translateY(-2px);
}

@media (max-width: 980px) {
    .hero-top {
        flex-direction: column;
    }

    .hero-actions {
        justify-content: flex-start;
    }

    .detail-card.featured {
        grid-column: span 1;
    }
}

@media (max-width: 700px) {
    .asset-detail-shell {
        padding: 16px;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .detail-grid .detail-item:nth-last-child(-n+2) {
        border-bottom: 1px solid rgba(11, 42, 74, 0.08);
    }

    .detail-grid .detail-item:last-child {
        border-bottom: none;
    }

    .hero-meta,
    .hero-actions {
        flex-wrap: wrap;
    }
}
</style>

<div class="container asset-container asset-detail-shell">

<div class="asset-hero">
    <div class="hero-top">
        <div>
            <span class="hero-kicker">Asset Detail</span>
            <h1 class="page-title asset-title"><?php echo h($asset_name); ?> - <?php echo h($asset_type); ?></h1>
            <p class="asset-subtitle">Assigned user, hardware information, and supporting details for this asset record.</p>
            <div class="hero-meta">
                <div class="hero-chip"><span>Asset ID</span> <?php echo h($data['ID']); ?></div>
                <div class="hero-chip"><span>Owner</span> <?php echo h($asset_name); ?></div>
                <div class="hero-chip"><span>Type</span> <?php echo h($asset_type); ?></div>
            </div>
        </div>

        <div class="hero-actions">
            <a href="edit_asset.php?id=<?php echo h($data['ID']); ?>" class="edit-btn hero-btn">Edit</a>
            <a href="delete_asset.php?id=<?php echo h($data['ID']); ?>" class="delete-btn hero-btn" onclick="return confirm('Delete this asset?')">Delete</a>
        </div>
    </div>
</div>

<div class="detail-wrapper">

<section class="detail-card user-card featured">
    <div class="card-head"><span class="card-dot"></span>User Information</div>
    <div class="card-body">
        <?php echo renderDetailGrid($userRows); ?>
    </div>
</section>

<?php if($asset_type === 'Desktop' || $asset_type === 'Laptop'): ?>

<section class="detail-card pc-card featured">
    <div class="card-head"><span class="card-dot"></span>PC Information</div>
    <div class="card-body">
        <?php echo renderDetailGrid($pcRows); ?>
    </div>
</section>

<section class="detail-card cpu-card">
    <div class="card-head"><span class="card-dot"></span>CPU & GPU</div>
    <div class="card-body">
        <?php echo renderDetailGrid($cpuRows, 1); ?>
    </div>
</section>

<section class="detail-card ram-card">
    <div class="card-head"><span class="card-dot"></span>RAM</div>
    <div class="card-body">
        <div class="ram-summary">
            <span>Total Installed Memory</span>
            <strong><?php echo $totalRam > 0 ? h($totalRam . ' GB') : 'Not recorded'; ?></strong>
        </div>
        <div class="ram-notes">
            <p><?php echo $ramBreakdown !== '' ? h($ramBreakdown) : '<span class="detail-empty">No RAM breakdown recorded</span>'; ?></p>
            <p><?php echo $ramSlots; ?> slot<?php echo $ramSlots === 1 ? '' : 's'; ?></p>
        </div>
    </div>
</section>

<section class="detail-card storage-card">
    <div class="card-head"><span class="card-dot"></span>Storage</div>
    <div class="card-body">
        <?php echo renderBulletList($storageItems, 'No storage details recorded'); ?>
    </div>
</section>

<?php if(!empty($monitorItems) || $asset_type === 'Desktop'): ?>
<section class="detail-card monitor-card">
    <div class="card-head"><span class="card-dot"></span>Monitor</div>
    <div class="card-body">
        <?php echo renderBulletList($monitorItems, $asset_type === 'Laptop' ? 'No external monitor recorded' : 'No monitor details recorded'); ?>
    </div>
</section>
<?php endif; ?>

<section class="detail-card windows-card">
    <div class="card-head"><span class="card-dot"></span>Windows</div>
    <div class="card-body">
        <?php echo renderDetailGrid([['Operating System', $data['windows_key'] ?? '']], 1); ?>
    </div>
</section>

<section class="detail-card software-card">
    <div class="card-head"><span class="card-dot"></span>Software</div>
    <div class="card-body">
        <?php echo renderBulletList($softwareItems, 'No software recorded'); ?>
    </div>
</section>

<?php elseif($asset_type === 'iPad'): ?>

<section class="detail-card pc-card featured">
    <div class="card-head"><span class="card-dot"></span>iPad Information</div>
    <div class="card-body">
        <?php echo renderDetailGrid($ipadRows); ?>
    </div>
</section>

<section class="detail-card cpu-card">
    <div class="card-head"><span class="card-dot"></span>Connectivity</div>
    <div class="card-body">
        <?php echo renderDetailGrid($ipadConnectivityRows, 1); ?>
    </div>
</section>

<section class="detail-card windows-card">
    <div class="card-head"><span class="card-dot"></span>Apple Account</div>
    <div class="card-body">
        <?php echo renderDetailGrid($appleRows, 1); ?>
    </div>
</section>

<section class="detail-card software-card">
    <div class="card-head"><span class="card-dot"></span>Apps / Software</div>
    <div class="card-body">
        <?php echo renderBulletList($softwareItems, 'No apps recorded'); ?>
    </div>
</section>

<?php elseif($asset_type === 'Phone'): ?>

<section class="detail-card pc-card featured">
    <div class="card-head"><span class="card-dot"></span>Phone Information</div>
    <div class="card-body">
        <?php echo renderDetailGrid($phoneRows); ?>
    </div>
</section>

<section class="detail-card cpu-card">
    <div class="card-head"><span class="card-dot"></span>SIM & Network</div>
    <div class="card-body">
        <?php echo renderDetailGrid($phoneNetworkRows, 1); ?>
    </div>
</section>

<section class="detail-card windows-card">
    <div class="card-head"><span class="card-dot"></span>Account</div>
    <div class="card-body">
        <?php echo renderDetailGrid($accountRows, 1); ?>
    </div>
</section>

<?php endif; ?>

</div>

<div class="detail-footer">
    <a href="javascript:history.back()" class="detail-back-link">Back</a>
</div>

</div>

<?php include "components/footer.php"; ?>
