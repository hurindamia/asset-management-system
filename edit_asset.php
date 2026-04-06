<?php
include "config/db.php";

/* ✅ SAFE ID */
$id = $_GET['id'] ?? 0;

if(!$id){
    die("❌ No ID provided");
}

/* ✅ FIXED QUERY */
$query = "
SELECT 
assets.asset_id AS ID,
assets.asset_type,

users.user_id,
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

GROUP_CONCAT(DISTINCT ram.ram_size) AS ram,
GROUP_CONCAT(DISTINCT storage.hdd_model) AS hdd_model,
GROUP_CONCAT(DISTINCT storage.hdd_capacity) AS hdd_capacity,
GROUP_CONCAT(DISTINCT storage.hdd_serial) AS hdd_serial,

GROUP_CONCAT(DISTINCT monitor.monitor_model) AS monitor_model,
GROUP_CONCAT(DISTINCT monitor.monitor_size) AS monitor_size,
GROUP_CONCAT(DISTINCT monitor.monitor_serial) AS monitor_serial,

GROUP_CONCAT(DISTINCT software.software_name) AS software

FROM assets
LEFT JOIN users ON assets.user_id = users.user_id
LEFT JOIN cpu ON assets.asset_id = cpu.asset_id
LEFT JOIN ram ON assets.asset_id = ram.asset_id
LEFT JOIN storage ON assets.asset_id = storage.asset_id
LEFT JOIN monitor ON assets.asset_id = monitor.asset_id
LEFT JOIN software ON assets.asset_id = software.asset_id

WHERE assets.asset_id = '$id'
GROUP BY assets.asset_id
";

$result = mysqli_query($conn, $query);

if(!$result){
    die("Query Error: ".mysqli_error($conn));
}

$data = mysqli_fetch_assoc($result);

if(!$data){
    die("❌ No data found for ID: ".$id);
}

$asset_type = $data['asset_type'] ?? 'Desktop';

/* ✅ SAFE ARRAYS */
$ram_arr      = !empty($data['ram'])            ? explode(",", $data['ram'])            : [""];
$hdd_model    = !empty($data['hdd_model'])      ? explode(",", $data['hdd_model'])      : [""];
$hdd_capacity = !empty($data['hdd_capacity'])   ? explode(",", $data['hdd_capacity'])   : [""];
$hdd_serial   = !empty($data['hdd_serial'])     ? explode(",", $data['hdd_serial'])     : [""];

$monitor_model  = !empty($data['monitor_model'])  ? explode(",", $data['monitor_model'])  : [""];
$monitor_size   = !empty($data['monitor_size'])   ? explode(",", $data['monitor_size'])   : [""];
$monitor_serial = !empty($data['monitor_serial']) ? explode(",", $data['monitor_serial']) : [""];

$software_arr = !empty($data['software'])
    ? array_filter(explode(",", $data['software']))
    : [""];

?>

<link rel="stylesheet" href="css/style.css">
<script src="js/form.js"></script>

<?php include "components/navbar.php"; ?>

<div class="container">

<button type="button" class="remove-btn" onclick="history.back()">Cancel</button>

<h1 class="page-title">Edit Asset — <?php echo htmlspecialchars($asset_type); ?></h1>

<form action="update_asset.php" method="POST">

<input type="hidden" name="id" value="<?php echo $data['ID']; ?>">
<input type="hidden" name="user_id" value="<?php echo $data['user_id']; ?>">
<input type="hidden" name="asset_type" value="<?php echo htmlspecialchars($asset_type); ?>">

<!-- USER -->
<div class="section">
<div class="section-title">User Information</div>

<div class="form-row"><label>Name</label>
<input type="text" name="name" value="<?php echo $data['name'] ?? ''; ?>"></div>

<div class="form-row"><label>Position</label>
<input type="text" name="position" value="<?php echo $data['position'] ?? ''; ?>"></div>

<div class="form-row"><label>Contact</label>
<input type="text" name="contact_no" value="<?php echo $data['contact_no'] ?? ''; ?>"></div>

<div class="form-row"><label>Email</label>
<input type="text" name="email_id" value="<?php echo $data['email_id'] ?? ''; ?>"></div>

<div class="form-row"><label>Email Password</label>
<input type="text" name="email_password" value="<?php echo $data['email_password'] ?? ''; ?>"></div>

<div class="form-row"><label>Mail Server</label>
<input type="text" name="mail_server" value="<?php echo $data['mail_server'] ?? ''; ?>"></div>

<?php if($asset_type === 'Desktop' || $asset_type === 'Laptop'): ?>
<div class="form-row"><label>PC Username</label>
<input type="text" name="pc_username" value="<?php echo $data['pc_username'] ?? ''; ?>"></div>

<div class="form-row"><label>PC Password</label>
<input type="text" name="pc_password" value="<?php echo $data['pc_password'] ?? ''; ?>"></div>
<?php endif; ?>

</div>


<?php if($asset_type === 'Desktop' || $asset_type === 'Laptop'): ?>

<!-- PC -->
<div class="section">
<div class="section-title">PC</div>

<div class="form-row"><label>PC Model</label>
<input type="text" name="pc_model" value="<?php echo $data['pc_model'] ?? ''; ?>"></div>

<div class="form-row"><label>PC Name</label>
<input type="text" name="pc_name" value="<?php echo $data['pc_name'] ?? ''; ?>"></div>

<div class="form-row"><label>MAC LAN</label>
<input type="text" name="mac_lan" value="<?php echo $data['mac_lan'] ?? ''; ?>"></div>

<div class="form-row"><label>MAC WIFI</label>
<input type="text" name="mac_wifi" value="<?php echo $data['mac_wifi'] ?? ''; ?>"></div>

<div class="form-row"><label>Antivirus</label>
<input type="text" name="antivirus" value="<?php echo $data['antivirus'] ?? ''; ?>"></div>

</div>

<!-- CPU -->
<div class="section">
<div class="section-title">CPU</div>

<div class="form-row"><label>CPU Model</label>
<input type="text" name="cpu_model" value="<?php echo $data['cpu_model'] ?? ''; ?>"></div>

<div class="form-row"><label>CPU Speed</label>
<input type="text" name="cpu_speed" value="<?php echo $data['cpu_speed'] ?? ''; ?>"></div>

<div class="form-row"><label>CPU Core</label>
<input type="text" name="cpu_core" value="<?php echo $data['cpu_core'] ?? ''; ?>"></div>

<div class="form-row"><label>Hyper Thread</label>
<input type="text" name="cpu_hyper_thread" value="<?php echo $data['cpu_hyper_thread'] ?? ''; ?>"></div>

</div>

<!-- GPU -->
<div class="section">
<div class="section-title">GPU</div>

<div class="form-row">
<input type="text" name="graphic_card" value="<?php echo $data['graphic_card'] ?? ''; ?>">
</div>
</div>

<!-- RAM -->
<div class="section">
<div class="section-title">RAM</div>

<div id="ramContainer">
<?php
$ram_arr = array_filter($ram_arr);
if(empty($ram_arr)){ $ram_arr = [""]; }
foreach($ram_arr as $i => $ram){
?>
<div class="ram-item">
<div class="ram-title">RAM <?php echo $i+1; ?></div>
<div class="form-row">
<select name="ram_size[]">
<option value="">Select RAM</option>
<option <?php if($ram=="2 GB") echo "selected"; ?>>2 GB</option>
<option <?php if($ram=="4 GB") echo "selected"; ?>>4 GB</option>
<option <?php if($ram=="8 GB") echo "selected"; ?>>8 GB</option>
<option <?php if($ram=="16 GB") echo "selected"; ?>>16 GB</option>
<option <?php if($ram=="32 GB") echo "selected"; ?>>32 GB</option>
<option <?php if($ram=="64 GB") echo "selected"; ?>>64 GB</option>
</select>
</div>
</div>
<?php } ?>
</div>

<button type="button" class="add-btn" onclick="addRam()">+ Add RAM</button>
</div>

<!-- STORAGE -->
<div class="section">
<div class="section-title">Storage</div>

<div id="storageContainer">
<?php
$hdd_model = array_filter($hdd_model);
if(empty($hdd_model)){ $hdd_model = [""]; }
for($i=0; $i<count($hdd_model); $i++){
?>
<div class="storage-item">
<div class="storage-title">Storage <?php echo $i+1; ?></div>

<div class="form-row">
<input type="text" name="hdd_model[]" value="<?php echo $hdd_model[$i] ?? ''; ?>">
</div>

<div class="form-row">
<input type="text" name="hdd_capacity[]" value="<?php echo $hdd_capacity[$i] ?? ''; ?>">
</div>

<div class="form-row">
<input type="text" name="hdd_serial[]" value="<?php echo $hdd_serial[$i] ?? ''; ?>">
</div>

</div>
<?php } ?>
</div>

<button type="button" class="add-btn" onclick="addStorage()">+ Add Storage</button>
</div>

<!-- MONITOR -->
<div class="section">
<div class="section-title">Monitor</div>

<div id="monitorContainer">
<?php
$monitor_model = array_filter($monitor_model);
if(empty($monitor_model)){ $monitor_model = [""]; }
for($i=0; $i<count($monitor_model); $i++){
?>
<div class="monitor-item">
<div class="monitor-title">Monitor <?php echo $i+1; ?></div>

<div class="form-row">
<input type="text" name="monitor_model[]" value="<?php echo $monitor_model[$i] ?? ''; ?>">
</div>

<div class="form-row">
<input type="text" name="monitor_size[]" value="<?php echo $monitor_size[$i] ?? ''; ?>">
</div>

<div class="form-row">
<input type="text" name="monitor_serial[]" value="<?php echo $monitor_serial[$i] ?? ''; ?>">
</div>

</div>
<?php } ?>
</div>

<button type="button" class="add-btn" onclick="addMonitor()">+ Add Monitor</button>
</div>

<!-- WINDOWS -->
<div class="section">
<div class="section-title">Windows</div>

<div class="form-row">
<label>Operating System</label>
<select name="windows_key">
<option value="">Select Windows</option>
<option <?php if($data['windows_key']=="Windows 7")  echo "selected"; ?>>Windows 7</option>
<option <?php if($data['windows_key']=="Windows 8.1") echo "selected"; ?>>Windows 8.1</option>
<option <?php if($data['windows_key']=="Windows 10") echo "selected"; ?>>Windows 10</option>
<option <?php if($data['windows_key']=="Windows 11") echo "selected"; ?>>Windows 11</option>
<option <?php if($data['windows_key']=="Mac OS")     echo "selected"; ?>>Mac OS</option>
</select>
</div>
</div>

<!-- SOFTWARE -->
<div class="section">
<div class="section-title">Software</div>

<div id="softwareContainer">
<?php foreach($software_arr as $i => $s){ ?>
<div class="software-item">
<div class="software-title">Software <?php echo $i+1; ?></div>
<?php if($i != 0){ ?>
<button type="button" class="remove-btn" onclick="removeSoftware(this)">Cancel</button>
<?php } ?>
<div class="form-row">
<input type="text" name="software[]" value="<?php echo $s; ?>" placeholder="Enter Software">
</div>
</div>
<?php } ?>
</div>

<button type="button" class="add-btn" onclick="addSoftware()">+ Add Software</button>
</div>


<?php elseif($asset_type === 'iPad'): ?>

<!-- iPAD INFO -->
<div class="section">
<div class="section-title">iPad Info</div>

<div class="form-row"><label>iPad Model</label>
<input type="text" name="pc_model" value="<?php echo $data['pc_model'] ?? ''; ?>"></div>

<div class="form-row"><label>Serial Number</label>
<input type="text" name="serial_no" value="<?php echo $data['serial_no'] ?? ''; ?>"></div>

<div class="form-row"><label>Storage Capacity</label>
<input type="text" name="storage_capacity" value="<?php echo $data['storage_capacity'] ?? ''; ?>"></div>

<div class="form-row"><label>iOS Version</label>
<input type="text" name="os_version" value="<?php echo $data['os_version'] ?? ''; ?>"></div>

<div class="form-row"><label>IMEI / UDID</label>
<input type="text" name="imei" value="<?php echo $data['imei'] ?? ''; ?>"></div>

<div class="form-row"><label>Apple ID</label>
<input type="text" name="apple_id" value="<?php echo $data['apple_id'] ?? ''; ?>"></div>

<div class="form-row"><label>Apple ID Password</label>
<input type="text" name="apple_password" value="<?php echo $data['apple_password'] ?? ''; ?>"></div>

</div>

<!-- CONNECTIVITY -->
<div class="section">
<div class="section-title">Connectivity</div>

<div class="form-row"><label>MAC WiFi</label>
<input type="text" name="mac_wifi" value="<?php echo $data['mac_wifi'] ?? ''; ?>"></div>

<div class="form-row"><label>SIM Number</label>
<input type="text" name="sim_no" value="<?php echo $data['sim_no'] ?? ''; ?>"></div>

</div>

<!-- APPS -->
<div class="section">
<div class="section-title">Software / Apps</div>

<div id="softwareContainer">
<?php foreach($software_arr as $i => $s){ ?>
<div class="software-item">
<div class="software-title">App <?php echo $i+1; ?></div>
<?php if($i != 0){ ?>
<button type="button" class="remove-btn" onclick="removeSoftware(this)">Cancel</button>
<?php } ?>
<div class="form-row">
<input type="text" name="software[]" value="<?php echo $s; ?>" placeholder="App / Software Name">
</div>
</div>
<?php } ?>
</div>

<button type="button" class="add-btn" onclick="addSoftware()">+ Add App</button>
</div>


<?php elseif($asset_type === 'Phone'): ?>

<!-- PHONE INFO -->
<div class="section">
<div class="section-title">Phone Info</div>

<div class="form-row"><label>Phone Model</label>
<input type="text" name="pc_model" value="<?php echo $data['pc_model'] ?? ''; ?>"></div>

<div class="form-row"><label>Serial Number</label>
<input type="text" name="serial_no" value="<?php echo $data['serial_no'] ?? ''; ?>"></div>

<div class="form-row"><label>IMEI</label>
<input type="text" name="imei" value="<?php echo $data['imei'] ?? ''; ?>"></div>

<div class="form-row"><label>OS Version</label>
<input type="text" name="os_version" value="<?php echo $data['os_version'] ?? ''; ?>"></div>

<div class="form-row"><label>Storage Capacity</label>
<input type="text" name="storage_capacity" value="<?php echo $data['storage_capacity'] ?? ''; ?>"></div>

</div>

<!-- SIM & NETWORK -->
<div class="section">
<div class="section-title">SIM & Network</div>

<div class="form-row"><label>SIM Number</label>
<input type="text" name="sim_no" value="<?php echo $data['sim_no'] ?? ''; ?>"></div>

<div class="form-row"><label>Carrier / Provider</label>
<input type="text" name="carrier" value="<?php echo $data['carrier'] ?? ''; ?>"></div>

<div class="form-row"><label>MAC WiFi</label>
<input type="text" name="mac_wifi" value="<?php echo $data['mac_wifi'] ?? ''; ?>"></div>

</div>

<!-- ACCOUNT -->
<div class="section">
<div class="section-title">Account</div>

<div class="form-row"><label>Google / Apple ID</label>
<input type="text" name="account_email" value="<?php echo $data['account_email'] ?? ''; ?>"></div>

<div class="form-row"><label>Account Password</label>
<input type="text" name="account_password" value="<?php echo $data['account_password'] ?? ''; ?>"></div>

</div>

<?php endif; ?>

<button type="submit" class="save-btn">Update Asset</button>

</form>
</div>

<?php include "components/footer.php"; ?>