<?php include "config/db.php"; ?>

<link rel="stylesheet" href="css/style.css">
<?php include "components/navbar.php"; ?>

<?php

/* =========================
   GET PARAMETERS
========================= */

$user_id = $_GET['user_id'] ?? null;
$type    = $_GET['type'] ?? null;
$id      = $_GET['id'] ?? null;

/* =========================
   DECIDE QUERY MODE
========================= */

if($id){
    $where = "assets.asset_id = '$id'";
}
else if($user_id && $type){
    $where = "assets.user_id = '$user_id' AND assets.asset_type = '$type'";
}
else{
    die("❌ Invalid request");
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

GROUP_CONCAT(DISTINCT ram.ram_size ORDER BY ram.ram_size) AS ram,

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
LEFT JOIN ram ON assets.asset_id = ram.asset_id
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
    die("❌ No data found");
}

$asset_type = $data['asset_type'] ?? 'Desktop';

/* ================= PROCESS (Desktop/Laptop) ================= */

$ram_arr = !empty($data['ram']) ? explode(",", $data['ram']) : [];

$total = 0; $ramText = ""; $slots = 0;
foreach($ram_arr as $r){
    $val = intval($r);
    if($val > 0){
        $total   += $val;
        $ramText .= $val." GB + ";
        $slots++;
    }
}
$ramText = rtrim($ramText, " + ");

/* STORAGE */
$storageList = "";
$models = !empty($data['hdd_model'])    ? explode(",", $data['hdd_model'])    : [];
$caps   = !empty($data['hdd_capacity']) ? explode(",", $data['hdd_capacity']) : [];
$serial = !empty($data['hdd_serial'])   ? explode(",", $data['hdd_serial'])   : [];

for($i = 0; $i < count($models); $i++){
    if(trim($models[$i]) != ""){
        $storageList .= "• ".$models[$i]." (".($caps[$i] ?? '').") - ".($serial[$i] ?? '')."<br>";
    }
}

/* MONITOR */
$monitorList = "";
$m_model  = !empty($data['monitor_model'])  ? explode(",", $data['monitor_model'])  : [];
$m_size   = !empty($data['monitor_size'])   ? explode(",", $data['monitor_size'])   : [];
$m_serial = !empty($data['monitor_serial']) ? explode(",", $data['monitor_serial']) : [];

for($i = 0; $i < count($m_model); $i++){
    if(trim($m_model[$i]) != ""){
        $monitorList .= "• ".$m_model[$i]." (".($m_size[$i] ?? '').") - ".($m_serial[$i] ?? '')."<br>";
    }
}

/* SOFTWARE */
$softwareList = "";
$software_arr = !empty($data['software']) ? explode(",", $data['software']) : [];
foreach($software_arr as $s){
    if(trim($s) != ""){
        $softwareList .= "• ".$s."<br>";
    }
}
?>

<div class="container asset-container">

<h1 class="page-title">
<?php echo $data['name']; ?> — <?php echo $asset_type; ?> (Asset ID: <?php echo $data['ID']; ?>)
</h1>

<div class="detail-wrapper">

<!-- USER — shown for ALL device types -->
<div class="detail-card">
<div class="card-header-user">User Information</div>
<div class="card-body-user">
<div class="user-grid">

<div><b>Name</b><p><?php echo $data['name']; ?></p></div>
<div><b>Email</b><p><?php echo $data['email_id']; ?></p></div>

<div><b>Position</b><p><?php echo $data['position']; ?></p></div>
<div><b>Email Password</b><p><?php echo $data['email_password']; ?></p></div>

<div><b>Contact No</b><p><?php echo $data['contact_no']; ?></p></div>
<div><b>Mail Server</b><p><?php echo $data['mail_server']; ?></p></div>

<div><b>Asset ID</b><p><?php echo $data['ID']; ?></p></div>
<div><b>Device Type</b><p><?php echo $asset_type; ?></p></div>

</div>
</div>
</div>


<?php if($asset_type === 'Desktop' || $asset_type === 'Laptop'): ?>

<!-- PC -->
<div class="detail-card">
<div class="card-header-pc">PC Information</div>
<div class="card-body-pc">
<p><b>PC Name:</b> <?php echo $data['pc_name']; ?></p>
<p><b>PC Model:</b> <?php echo $data['pc_model']; ?></p>
<p><b>MAC LAN:</b> <?php echo $data['mac_lan']; ?></p>
<p><b>MAC WIFI:</b> <?php echo $data['mac_wifi']; ?></p>
<p><b>Antivirus:</b> <?php echo $data['antivirus']; ?></p>
<p><b>PC Username:</b> <?php echo $data['pc_username']; ?></p>
<p><b>PC Password:</b> <?php echo $data['pc_password']; ?></p>
</div>
</div>

<!-- CPU -->
<div class="detail-card">
<div class="card-header-cpu">CPU & GPU</div>
<div class="card-body-cpu">
<p><b>CPU Model:</b> <?php echo $data['cpu_model']; ?></p>
<p><b>CPU Speed:</b> <?php echo $data['cpu_speed']; ?></p>
<p><b>Cores:</b> <?php echo $data['cpu_core']; ?></p>
<p><b>Hyper Threading:</b> <?php echo $data['cpu_hyper_thread']; ?></p>
<p><b>GPU:</b> <?php echo $data['graphic_card']; ?></p>
</div>
</div>

<!-- RAM -->
<div class="detail-card">
<div class="card-header-ram">RAM</div>
<div class="card-body-ram">
<p><?php echo $ramText." = ".$total." GB"; ?></p>
<p>(<?php echo $slots; ?> slots)</p>
</div>
</div>

<!-- STORAGE -->
<div class="detail-card">
<div class="card-header-storage">Storage</div>
<div class="card-body-storage"><?php echo $storageList; ?></div>
</div>

<!-- MONITOR — always show for Desktop, only show for Laptop if data exists -->
<?php if($monitorList || $asset_type === 'Desktop'): ?>
<div class="detail-card">
<div class="card-header-monitor">Monitor</div>
<div class="card-body-monitor"><?php echo $monitorList; ?></div>
</div>
<?php endif; ?>

<!-- WINDOWS -->
<div class="detail-card">
<div class="card-header-windows">Windows</div>
<div class="card-body-windows"><?php echo $data['windows_key']; ?></div>
</div>

<!-- SOFTWARE -->
<div class="detail-card">
<div class="card-header-software">Software</div>
<div class="card-body-software"><?php echo $softwareList; ?></div>
</div>


<?php elseif($asset_type === 'iPad'): ?>

<!-- iPAD INFO -->
<div class="detail-card">
<div class="card-header-pc">iPad Information</div>
<div class="card-body-pc">
<p><b>Model:</b> <?php echo $data['pc_model']; ?></p>
<p><b>Serial Number:</b> <?php echo $data['serial_no']; ?></p>
<p><b>Storage Capacity:</b> <?php echo $data['storage_capacity']; ?></p>
<p><b>iOS Version:</b> <?php echo $data['os_version']; ?></p>
<p><b>IMEI / UDID:</b> <?php echo $data['imei']; ?></p>
</div>
</div>

<!-- CONNECTIVITY -->
<div class="detail-card">
<div class="card-header-cpu">Connectivity</div>
<div class="card-body-cpu">
<p><b>MAC WiFi:</b> <?php echo $data['mac_wifi']; ?></p>
<p><b>SIM Number:</b> <?php echo $data['sim_no']; ?></p>
</div>
</div>

<!-- APPLE ACCOUNT -->
<div class="detail-card">
<div class="card-header-windows">Apple Account</div>
<div class="card-body-windows">
<p><b>Apple ID:</b> <?php echo $data['apple_id']; ?></p>
<p><b>Apple ID Password:</b> <?php echo $data['apple_password']; ?></p>
</div>
</div>

<!-- APPS -->
<div class="detail-card">
<div class="card-header-software">Apps / Software</div>
<div class="card-body-software"><?php echo $softwareList; ?></div>
</div>


<?php elseif($asset_type === 'Phone'): ?>

<!-- PHONE INFO -->
<div class="detail-card">
<div class="card-header-pc">Phone Information</div>
<div class="card-body-pc">
<p><b>Model:</b> <?php echo $data['pc_model']; ?></p>
<p><b>Serial Number:</b> <?php echo $data['serial_no']; ?></p>
<p><b>IMEI:</b> <?php echo $data['imei']; ?></p>
<p><b>OS Version:</b> <?php echo $data['os_version']; ?></p>
<p><b>Storage Capacity:</b> <?php echo $data['storage_capacity']; ?></p>
</div>
</div>

<!-- SIM & NETWORK -->
<div class="detail-card">
<div class="card-header-cpu">SIM & Network</div>
<div class="card-body-cpu">
<p><b>SIM Number:</b> <?php echo $data['sim_no']; ?></p>
<p><b>Carrier / Provider:</b> <?php echo $data['carrier']; ?></p>
<p><b>MAC WiFi:</b> <?php echo $data['mac_wifi']; ?></p>
</div>
</div>

<!-- ACCOUNT -->
<div class="detail-card">
<div class="card-header-windows">Account</div>
<div class="card-body-windows">
<p><b>Email:</b> <?php echo $data['account_email']; ?></p>
<p><b>Password:</b> <?php echo $data['account_password']; ?></p>
</div>
</div>

<?php endif; ?>

</div>

<div class="bottom-actions">
<a href="edit_asset.php?id=<?php echo $data['ID']; ?>" class="edit-btn big-btn">Edit</a>
<a href="delete_asset.php?id=<?php echo $data['ID']; ?>" class="delete-btn big-btn" onclick="return confirm('Delete this asset?')">Delete</a>
</div>

<a href="javascript:history.back()" class="add-hardware-btn">← Back</a>

</div>

<?php include "components/footer.php"; ?>