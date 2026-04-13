<?php
include "config/db.php";
include_once "components/asset_form_partials.php";

$prefill_user    = null;
$prefill_user_id = intval($_GET['user_id'] ?? 0);
$existing_devices = [];

if($prefill_user_id > 0){

    // Fetch user info
    $res = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $prefill_user_id");
    $prefill_user = mysqli_fetch_assoc($res);

    // Fetch all existing assets for this user with full details
    $dev_query = "
    SELECT 
    assets.asset_id,
    assets.asset_type,
    assets.pc_username, assets.pc_password,
    assets.pc_model, assets.pc_name, assets.pc_serial_no, assets.mac_lan, assets.mac_wifi,
    assets.antivirus, assets.windows_key,
    assets.serial_no, assets.imei, assets.storage_capacity, assets.os_version,
    assets.sim_no, assets.carrier, assets.apple_id, assets.apple_password,
    assets.account_email, assets.account_password,

    cpu.cpu_model, cpu.cpu_speed, cpu.cpu_core, cpu.cpu_hyper_thread, cpu.graphic_card,

    MAX(ram_agg.ram) AS ram,
    GROUP_CONCAT(DISTINCT storage.hdd_model) AS hdd_model,
    GROUP_CONCAT(DISTINCT storage.hdd_capacity) AS hdd_capacity,
    GROUP_CONCAT(DISTINCT storage.hdd_serial) AS hdd_serial,
    GROUP_CONCAT(DISTINCT monitor.monitor_model) AS monitor_model,
    GROUP_CONCAT(DISTINCT monitor.monitor_size) AS monitor_size,
    GROUP_CONCAT(DISTINCT monitor.monitor_serial) AS monitor_serial,
    GROUP_CONCAT(DISTINCT software.software_name) AS software

    FROM assets
    LEFT JOIN cpu ON assets.asset_id = cpu.asset_id
    LEFT JOIN (
        SELECT asset_id, GROUP_CONCAT(ram_size SEPARATOR '||') AS ram
        FROM ram
        GROUP BY asset_id
    ) ram_agg ON ram_agg.asset_id = assets.asset_id
    LEFT JOIN storage ON assets.asset_id = storage.asset_id
    LEFT JOIN monitor ON assets.asset_id = monitor.asset_id
    LEFT JOIN software ON assets.asset_id = software.asset_id

    WHERE assets.user_id = $prefill_user_id
    GROUP BY assets.asset_id
    ORDER BY assets.asset_id ASC
    ";

    $dev_result = mysqli_query($conn, $dev_query);
    while($dev_row = mysqli_fetch_assoc($dev_result)){
        $existing_devices[] = $dev_row;
    }

    // Get pc_username and pc_password from first Desktop/Laptop asset
    $prefill_pc_username = '';
    $prefill_pc_password = '';
    foreach($existing_devices as $d){
        if($d['asset_type'] === 'Desktop' || $d['asset_type'] === 'Laptop'){
            $prefill_pc_username = $d['pc_username'] ?? '';
            $prefill_pc_password = $d['pc_password'] ?? '';
            break;
        }
    }
}
?>

<link rel="stylesheet" href="css/style.css">
<script src="js/form.js"></script>
<script src="js/asset-form-validation.js"></script>

<?php include "components/navbar.php"; ?>

<div class="container">

<button type="button" class="remove-btn" onclick="history.back()">Cancel</button>

<div class="page-header">
<h1 class="page-title">
<?php if($prefill_user): ?>
    🖥 Add Device — <?php echo htmlspecialchars($prefill_user['name']); ?>
<?php else: ?>
    🖥 Asset List Form
<?php endif; ?>
</h1>
</div>

<form action="save_asset.php" method="POST" id="hardwareForm" novalidate>

<?php if($prefill_user): ?>
<input type="hidden" name="prefill_user_id" value="<?php echo $prefill_user_id; ?>">
<?php endif; ?>

<!-- USER INFORMATION -->
<div class="section">
<div class="section-title">User Information</div>

<div class="form-row">
<label>Name</label>
<input type="text" name="name" placeholder="Full Name" required
    value="<?php echo htmlspecialchars($prefill_user['name'] ?? ''); ?>">
</div>

<div class="form-row">
<label>Position</label>
<input type="text" name="position" placeholder="Position" required
    value="<?php echo htmlspecialchars($prefill_user['position'] ?? ''); ?>">
</div>

<div class="form-row">
<label>Contact No</label>
<input type="text" name="contact_no" placeholder="Contact Number" required
    value="<?php echo htmlspecialchars($prefill_user['contact_no'] ?? ''); ?>">
</div>

<div class="form-row">
<label>Email ID</label>
<input type="email" name="email_id" placeholder="Email Address" required
    value="<?php echo htmlspecialchars($prefill_user['email_id'] ?? ''); ?>">
</div>

<div class="form-row">
<label>Email Password</label>
<input type="text" name="email_password" placeholder="Email Password" required
    value="<?php echo htmlspecialchars($prefill_user['email_password'] ?? ''); ?>">
</div>

<div class="form-row">
<label>Mail Server</label>
<input type="text" name="mail_server" placeholder="Mail Server" required
    value="<?php echo htmlspecialchars($prefill_user['mail_server'] ?? ''); ?>">
</div>

<?php
$has_pc = empty($existing_devices) || count(array_filter($existing_devices, function($d){
    return $d['asset_type'] === 'Desktop' || $d['asset_type'] === 'Laptop';
})) > 0;
?>
<?php if($has_pc): ?>
<div class="form-row">
<label>PC Username</label>
<input type="text" name="pc_username" placeholder="PC Username" required
    value="<?php echo htmlspecialchars($prefill_pc_username ?? ''); ?>">
</div>

<div class="form-row">
<label>PC Password</label>
<input type="text" name="pc_password" placeholder="PC Password" required
    value="<?php echo htmlspecialchars($prefill_pc_password ?? ''); ?>">
</div>
<?php endif; ?>

</div>

<!-- ================================
     DEVICES WRAPPER
================================= -->
<div id="devicesWrapper">

<?php if(!empty($existing_devices)): ?>
<?php foreach($existing_devices as $di => $dev): ?>
<?php
    $dtype      = $dev['asset_type'];
    $d_aid      = $dev['asset_id'];

    // RAM
    $d_ram_arr  = !empty($dev['ram']) ? explode("||", $dev['ram']) : [""];

    // Storage
    $d_hdd_model    = !empty($dev['hdd_model'])    ? explode(",", $dev['hdd_model'])    : [""];
    $d_hdd_capacity = !empty($dev['hdd_capacity']) ? explode(",", $dev['hdd_capacity']) : [""];
    $d_hdd_serial   = !empty($dev['hdd_serial'])   ? explode(",", $dev['hdd_serial'])   : [""];

    // Monitor
    $d_mon_model  = !empty($dev['monitor_model'])  ? explode(",", $dev['monitor_model'])  : [""];
    $d_mon_size   = !empty($dev['monitor_size'])   ? explode(",", $dev['monitor_size'])   : [""];
    $d_mon_serial = !empty($dev['monitor_serial']) ? explode(",", $dev['monitor_serial']) : [""];

    // Software
    $d_software = !empty($dev['software']) ? array_filter(explode(",", $dev['software'])) : [""];
?>

<div class="device-block" id="deviceBlock_<?php echo $di; ?>">

<div class="device-block-header">
    <span class="device-block-title">Device <?php echo $di+1; ?> — <?php echo htmlspecialchars($dtype); ?></span>
    <div style="display:flex;gap:10px;align-items:center;">
        <select class="device-type-select" onchange="changeDeviceType(<?php echo $di; ?>, this.value)" disabled>
            <option value="Desktop"  <?php if($dtype=='Desktop')  echo 'selected'; ?>>Desktop (PC)</option>
            <option value="Laptop"   <?php if($dtype=='Laptop')   echo 'selected'; ?>>Laptop</option>
            <option value="iPad"     <?php if($dtype=='iPad')     echo 'selected'; ?>>iPad</option>
            <option value="Phone"    <?php if($dtype=='Phone')    echo 'selected'; ?>>Phone</option>
        </select>
        <a href="edit_asset.php?id=<?php echo $d_aid; ?>&return_to=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'add_asset.php'); ?>" class="edit-btn device-btn header-action-btn">Edit</a>
        <a href="delete_asset.php?id=<?php echo $d_aid; ?>" class="delete-btn device-btn header-action-btn" onclick="return confirm('Delete this device?')">Delete</a>
    </div>
</div>

<input type="hidden" name="existing_asset_ids[]" value="<?php echo $d_aid; ?>">

<?php if($dtype === 'Desktop' || $dtype === 'Laptop'): ?>

<div class="section">
<div class="section-title">PC</div>
<div class="form-row"><label>PC Model</label><input type="text" name="existing[<?php echo $di; ?>][pc_model]" value="<?php echo htmlspecialchars($dev['pc_model'] ?? ''); ?>"></div>
<div class="form-row"><label>PC Name</label><input type="text" name="existing[<?php echo $di; ?>][pc_name]" value="<?php echo htmlspecialchars($dev['pc_name'] ?? ''); ?>"></div>
<div class="form-row"><label>PC Serial Number</label><input type="text" name="existing[<?php echo $di; ?>][pc_serial_no]" value="<?php echo htmlspecialchars($dev['pc_serial_no'] ?? ''); ?>"></div>
<div class="form-row"><label>MAC LAN</label><input type="text" name="existing[<?php echo $di; ?>][mac_lan]" value="<?php echo htmlspecialchars($dev['mac_lan'] ?? ''); ?>"></div>
<div class="form-row"><label>MAC WIFI</label><input type="text" name="existing[<?php echo $di; ?>][mac_wifi]" value="<?php echo htmlspecialchars($dev['mac_wifi'] ?? ''); ?>"></div>
<div class="form-row"><label>Antivirus</label><input type="text" name="existing[<?php echo $di; ?>][antivirus]" value="<?php echo htmlspecialchars($dev['antivirus'] ?? ''); ?>"></div>
<div class="form-row"><label>PC Username</label><input type="text" name="existing[<?php echo $di; ?>][pc_username]" value="<?php echo htmlspecialchars($dev['pc_username'] ?? ''); ?>"></div>
<div class="form-row"><label>PC Password</label><input type="text" name="existing[<?php echo $di; ?>][pc_password]" value="<?php echo htmlspecialchars($dev['pc_password'] ?? ''); ?>"></div>
</div>

<div class="section">
<div class="section-title">CPU</div>
<div class="form-row"><label>CPU Model</label><input type="text" name="existing[<?php echo $di; ?>][cpu_model]" value="<?php echo htmlspecialchars($dev['cpu_model'] ?? ''); ?>"></div>
<div class="form-row"><label>CPU Speed</label><input type="text" name="existing[<?php echo $di; ?>][cpu_speed]" value="<?php echo htmlspecialchars($dev['cpu_speed'] ?? ''); ?>"></div>
<div class="form-row"><label>CPU Core</label><input type="text" name="existing[<?php echo $di; ?>][cpu_core]" value="<?php echo htmlspecialchars($dev['cpu_core'] ?? ''); ?>"></div>
<div class="form-row"><label>Hyper Thread</label><input type="text" name="existing[<?php echo $di; ?>][cpu_thread]" value="<?php echo htmlspecialchars($dev['cpu_hyper_thread'] ?? ''); ?>"></div>
<div class="form-row"><label>Graphic Card</label><input type="text" name="existing[<?php echo $di; ?>][gpu]" value="<?php echo htmlspecialchars($dev['graphic_card'] ?? ''); ?>"></div>
</div>

<div class="section">
<div class="section-title">RAM</div>
<div id="ramContainer_existing_<?php echo $di; ?>">
<?php foreach($d_ram_arr as $ri => $ram): ?>
<div class="ram-item">
<div class="ram-title">RAM <?php echo $ri+1; ?></div>
<div class="form-row"><label>RAM Size</label>
<select name="existing[<?php echo $di; ?>][ram_size][]">
<?php echo asset_form_render_ram_options($ram); ?>
</select>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="section">
<div class="section-title">Storage</div>
<div id="storageContainer_existing_<?php echo $di; ?>">
<?php for($si=0; $si<count($d_hdd_model); $si++): ?>
<div class="storage-item">
<div class="storage-title">Storage <?php echo $si+1; ?></div>
<div class="form-row"><label>Model</label><input type="text" name="existing[<?php echo $di; ?>][hdd_model][]" value="<?php echo htmlspecialchars($d_hdd_model[$si] ?? ''); ?>" placeholder="HDD Model"></div>
<div class="form-row"><label>Capacity</label><input type="text" name="existing[<?php echo $di; ?>][hdd_capacity][]" value="<?php echo htmlspecialchars($d_hdd_capacity[$si] ?? ''); ?>" placeholder="Capacity"></div>
<div class="form-row"><label>Serial</label><input type="text" name="existing[<?php echo $di; ?>][hdd_serial][]" value="<?php echo htmlspecialchars($d_hdd_serial[$si] ?? ''); ?>" placeholder="Serial Number"></div>
</div>
<?php endfor; ?>
</div>
</div>

<div class="section">
<?php if($dtype === 'Laptop'): ?>
<div class="collapsible-header" onclick="toggleCollapsible(this)">
    <div class="section-title">Monitor <span style="font-size:0.8rem;color:#999;">(optional)</span></div>
    <button type="button" class="collapsible-toggle"><?php echo !empty(array_filter($d_mon_model)) ? '- Hide' : '+ Show'; ?></button>
</div>
<div class="collapsible-body <?php echo !empty(array_filter($d_mon_model)) ? 'open' : ''; ?>">
<?php else: ?>
<div class="section-title">Monitor</div>
<?php endif; ?>
<div id="monitorContainer_existing_<?php echo $di; ?>">
<?php for($mi=0; $mi<count($d_mon_model); $mi++): ?>
<div class="monitor-item">
<div class="monitor-title">Monitor <?php echo $mi+1; ?></div>
<div class="form-row"><label>Model</label><input type="text" name="existing[<?php echo $di; ?>][monitor_model][]" value="<?php echo htmlspecialchars($d_mon_model[$mi] ?? ''); ?>" placeholder="Monitor Model" <?php if($dtype !== 'Laptop') echo 'required'; ?>></div>
<div class="form-row"><label>Size</label><input type="text" name="existing[<?php echo $di; ?>][monitor_size][]" value="<?php echo htmlspecialchars($d_mon_size[$mi] ?? ''); ?>" placeholder="Monitor Size" <?php if($dtype !== 'Laptop') echo 'required'; ?>></div>
<div class="form-row"><label>Serial</label><input type="text" name="existing[<?php echo $di; ?>][monitor_serial][]" value="<?php echo htmlspecialchars($d_mon_serial[$mi] ?? ''); ?>" placeholder="Serial Number" <?php if($dtype !== 'Laptop') echo 'required'; ?>></div>
</div>
<?php endfor; ?>
</div>
<?php if($dtype === 'Laptop'): ?>
</div><!-- end collapsible-body -->
<?php endif; ?>
</div>

<div class="section">
<div class="section-title">Windows</div>
<div class="form-row"><label>Operating System</label>
<select name="existing[<?php echo $di; ?>][windows_key]">
<?php echo asset_form_render_windows_options($dev['windows_key'] ?? ''); ?>
</select>
</div>
</div>

<div class="section">
<div class="section-title">Software</div>
<div id="softwareContainer_existing_<?php echo $di; ?>">
<?php foreach($d_software as $swi => $sw): ?>
<div class="software-item">
<div class="software-title">Software <?php echo $swi+1; ?></div>
<div class="form-row"><input type="text" name="existing[<?php echo $di; ?>][software][]" value="<?php echo htmlspecialchars($sw); ?>" placeholder="Enter Software"></div>
</div>
<?php endforeach; ?>
</div>
</div>

<?php elseif($dtype === 'iPad'): ?>

<div class="section">
<div class="section-title">iPad Info</div>
<div class="form-row"><label>iPad Model</label><input type="text" name="existing[<?php echo $di; ?>][model]" value="<?php echo htmlspecialchars($dev['pc_model'] ?? ''); ?>"></div>
<div class="form-row"><label>Serial Number</label><input type="text" name="existing[<?php echo $di; ?>][serial]" value="<?php echo htmlspecialchars($dev['serial_no'] ?? ''); ?>"></div>
<div class="form-row"><label>Storage Capacity</label><input type="text" name="existing[<?php echo $di; ?>][storage_capacity]" value="<?php echo htmlspecialchars($dev['storage_capacity'] ?? ''); ?>"></div>
<div class="form-row"><label>iOS Version</label><input type="text" name="existing[<?php echo $di; ?>][ios_version]" value="<?php echo htmlspecialchars($dev['os_version'] ?? ''); ?>"></div>
<div class="form-row"><label>IMEI / UDID</label><input type="text" name="existing[<?php echo $di; ?>][imei]" value="<?php echo htmlspecialchars($dev['imei'] ?? ''); ?>"></div>
<div class="form-row"><label>Apple ID</label><input type="text" name="existing[<?php echo $di; ?>][apple_id]" value="<?php echo htmlspecialchars($dev['apple_id'] ?? ''); ?>"></div>
<div class="form-row"><label>Apple ID Password</label><input type="text" name="existing[<?php echo $di; ?>][apple_password]" value="<?php echo htmlspecialchars($dev['apple_password'] ?? ''); ?>"></div>
</div>

<div class="section">
<div class="section-title">Connectivity</div>
<div class="form-row"><label>MAC WiFi</label><input type="text" name="existing[<?php echo $di; ?>][mac_wifi]" value="<?php echo htmlspecialchars($dev['mac_wifi'] ?? ''); ?>"></div>
<div class="form-row"><label>SIM Number</label><input type="text" name="existing[<?php echo $di; ?>][sim_no]" value="<?php echo htmlspecialchars($dev['sim_no'] ?? ''); ?>"></div>
</div>

<div class="section">
<div class="section-title">Software / Apps</div>
<div id="softwareContainer_existing_<?php echo $di; ?>">
<?php foreach($d_software as $swi => $sw): ?>
<div class="software-item">
<div class="software-title">Software <?php echo $swi+1; ?></div>
<div class="form-row"><input type="text" name="existing[<?php echo $di; ?>][software][]" value="<?php echo htmlspecialchars($sw); ?>" placeholder="App / Software Name"></div>
</div>
<?php endforeach; ?>
</div>
</div>

<?php elseif($dtype === 'Phone'): ?>

<div class="section">
<div class="section-title">Phone Info</div>
<div class="form-row"><label>Phone Model</label><input type="text" name="existing[<?php echo $di; ?>][model]" value="<?php echo htmlspecialchars($dev['pc_model'] ?? ''); ?>"></div>
<div class="form-row"><label>Serial Number</label><input type="text" name="existing[<?php echo $di; ?>][serial]" value="<?php echo htmlspecialchars($dev['serial_no'] ?? ''); ?>"></div>
<div class="form-row"><label>IMEI</label><input type="text" name="existing[<?php echo $di; ?>][imei]" value="<?php echo htmlspecialchars($dev['imei'] ?? ''); ?>"></div>
<div class="form-row"><label>OS Version</label><input type="text" name="existing[<?php echo $di; ?>][os_version]" value="<?php echo htmlspecialchars($dev['os_version'] ?? ''); ?>"></div>
<div class="form-row"><label>Storage Capacity</label><input type="text" name="existing[<?php echo $di; ?>][storage_capacity]" value="<?php echo htmlspecialchars($dev['storage_capacity'] ?? ''); ?>"></div>
</div>

<div class="section">
<div class="section-title">SIM & Network</div>
<div class="form-row"><label>SIM Number</label><input type="text" name="existing[<?php echo $di; ?>][sim_no]" value="<?php echo htmlspecialchars($dev['sim_no'] ?? ''); ?>"></div>
<div class="form-row"><label>Carrier / Provider</label><input type="text" name="existing[<?php echo $di; ?>][carrier]" value="<?php echo htmlspecialchars($dev['carrier'] ?? ''); ?>"></div>
<div class="form-row"><label>MAC WiFi</label><input type="text" name="existing[<?php echo $di; ?>][mac_wifi]" value="<?php echo htmlspecialchars($dev['mac_wifi'] ?? ''); ?>"></div>
</div>

<div class="section">
<div class="section-title">Account</div>
<div class="form-row"><label>Google / Apple ID</label><input type="text" name="existing[<?php echo $di; ?>][account_email]" value="<?php echo htmlspecialchars($dev['account_email'] ?? ''); ?>"></div>
<div class="form-row"><label>Account Password</label><input type="text" name="existing[<?php echo $di; ?>][account_password]" value="<?php echo htmlspecialchars($dev['account_password'] ?? ''); ?>"></div>
</div>

<?php endif; ?>

</div><!-- end device-block -->
<?php endforeach; ?>
<?php endif; ?>

</div><!-- end devicesWrapper -->

<?php
echo asset_form_render_sticky_action_bar(
    'Save Asset',
    'submit',
    'save-btn',
    '<button type="button" class="add-device-btn sticky-secondary" onclick="addDevice()">+ Add Device</button>'
);
?>

</form>
</div>


<?php echo asset_form_render_error_popup('errorPopup', 'errorList', 'Error: Missing Information!', 'Please review the following fields:'); ?>


<script>

/* ==========================================
   FIELD TEMPLATES PER DEVICE TYPE
========================================== */

function getFieldsForType(type, i) {

    /* ---------- DESKTOP / LAPTOP ---------- */
    const pcFields = `

        <div class="section">
        <div class="section-title">PC</div>

        <div class="form-row">
        <label>PC Model</label>
        <input type="text" name="devices[${i}][pc_model]" placeholder="PC Model" required>
        </div>

        <div class="form-row">
        <label>PC Name</label>
        <input type="text" name="devices[${i}][pc_name]" placeholder="PC Name" required>
        </div>

        <div class="form-row">
        <label>PC Serial Number</label>
        <input type="text" name="devices[${i}][pc_serial_no]" placeholder="PC Serial Number" required>
        </div>

        <div class="form-row">
        <label>MAC LAN</label>
        <input type="text" name="devices[${i}][mac_lan]" placeholder="MAC LAN Address" required>
        </div>

        <div class="form-row">
        <label>MAC WIFI</label>
        <input type="text" name="devices[${i}][mac_wifi]" placeholder="MAC WIFI Address" required>
        </div>

        <div class="form-row">
        <label>Antivirus</label>
        <input type="text" name="devices[${i}][antivirus]" placeholder="Antivirus Software" required>
        </div>

        </div>

        <div class="section">
        <div class="section-title">CPU</div>

        <div class="form-row">
        <label>CPU Model</label>
        <input type="text" name="devices[${i}][cpu_model]" placeholder="CPU Model" required>
        </div>

        <div class="form-row">
        <label>CPU Speed</label>
        <input type="text" name="devices[${i}][cpu_speed]" placeholder="CPU Speed" required>
        </div>

        <div class="form-row">
        <label>CPU Core</label>
        <input type="number" name="devices[${i}][cpu_core]" placeholder="CPU Core Count" required>
        </div>

        <div class="form-row">
        <label>Hyper Thread</label>
        <input type="text" name="devices[${i}][cpu_thread]" placeholder="Hyper Threading" required>
        </div>

        </div>

        <div class="section">
        <div class="section-title">GPU</div>

        <div class="form-row">
        <label>Graphic Card</label>
        <input type="text" name="devices[${i}][gpu]" placeholder="Graphic Card" required>
        </div>

        </div>

        <div class="section">
        <div class="section-title">RAM</div>

        <div id="ramContainer_${i}">
        <div class="ram-item">
        <div class="ram-title">RAM 1</div>
        <div class="form-row">
        <label>RAM Size</label>
        <select name="devices[${i}][ram_size][]" data-label="RAM Size" required>
            <option value="">Select RAM</option>
            <option>2 GB</option>
            <option>4 GB</option>
            <option>8 GB</option>
            <option>16 GB</option>
            <option>32 GB</option>
            <option>64 GB</option>
        </select>
        </div>
        </div>
        </div>

        <button type="button" class="add-btn" onclick="addRamTo(${i})">+ Add RAM</button>

        </div>

        <div class="section">
        <div class="section-title">Storage</div>

        <div id="storageContainer_${i}">
        <div class="storage-item">
        <div class="storage-title">Storage 1</div>

        <div class="form-row">
        <label>Model</label>
        <input type="text" name="devices[${i}][hdd_model][]" placeholder="HDD Model" required>
        </div>

        <div class="form-row">
        <label>Capacity</label>
        <input type="text" name="devices[${i}][hdd_capacity][]" placeholder="Capacity" required>
        </div>

        <div class="form-row">
        <label>Serial</label>
        <input type="text" name="devices[${i}][hdd_serial][]" placeholder="Serial Number" required>
        </div>

        </div>
        </div>

        <button type="button" class="add-btn" onclick="addStorageTo(${i})">+ Add Storage</button>

        </div>

        <div class="section">
        <div class="section-title">Monitor</div>

        <div id="monitorContainer_${i}">
        <div class="monitor-item">
        <div class="monitor-title">Monitor 1</div>

        <div class="form-row">
        <label>Model</label>
        <input type="text" name="devices[${i}][monitor_model][]" placeholder="Monitor Model" required>
        </div>

        <div class="form-row">
        <label>Size</label>
        <input type="text" name="devices[${i}][monitor_size][]" placeholder="Monitor Size" required>
        </div>

        <div class="form-row">
        <label>Serial</label>
        <input type="text" name="devices[${i}][monitor_serial][]" placeholder="Serial Number" required>
        </div>

        </div>
        </div>

        <button type="button" class="add-btn" onclick="addMonitorTo(${i})">+ Add Monitor</button>

        </div>

        <div class="section">
        <div class="section-title">Windows</div>

        <div class="form-row">
        <label>Operating System</label>
        <select name="devices[${i}][windows_key]" data-label="Windows Version" required>
            <option value="">Select Windows</option>
            <option>Windows 7</option>
            <option>Windows 8.1</option>
            <option>Windows 10</option>
            <option>Windows 11</option>
            <option>Mac OS</option>
        </select>
        </div>

        </div>

        <div class="section">
        <div class="section-title">Software</div>

        <div id="softwareContainer_${i}">
        <div class="software-item">
        <div class="item-header">
        <div class="software-title">Software 1</div>
        </div>
        <div class="form-row">
        <input type="text" name="devices[${i}][software][]" placeholder="Enter Software" required>
        </div>
        </div>
        </div>

        <button type="button" class="add-btn" onclick="addSoftwareTo(${i})">+ Add Software</button>

        </div>
    `;

    /* ---------- iPAD ---------- */
    const ipadFields = `

        <div class="section">
        <div class="section-title">iPad Info</div>

        <div class="form-row">
        <label>iPad Model</label>
        <input type="text" name="devices[${i}][model]" placeholder="iPad Model" required>
        </div>

        <div class="form-row">
        <label>Serial Number</label>
        <input type="text" name="devices[${i}][serial]" placeholder="Serial Number" required>
        </div>

        <div class="form-row">
        <label>Storage Capacity</label>
        <input type="text" name="devices[${i}][storage_capacity]" placeholder="Storage capacity" required>
        </div>

        <div class="form-row">
        <label>iOS Version</label>
        <input type="text" name="devices[${i}][ios_version]" placeholder="iOS Version" required>
        </div>

        <div class="form-row">
        <label>IMEI / UDID</label>
        <input type="text" name="devices[${i}][imei]" placeholder="IMEI / UDID" required>
        </div>

        <div class="form-row">
        <label>Apple ID</label>
        <input type="text" name="devices[${i}][apple_id]" placeholder="Apple ID" required>
        </div>

        <div class="form-row">
        <label>Apple ID Password</label>
        <input type="text" name="devices[${i}][apple_password]" placeholder="Apple ID Password" required>
        </div>

        </div>

        <div class="section">
        <div class="section-title">Connectivity</div>

        <div class="form-row">
        <label>MAC WiFi</label>
        <input type="text" name="devices[${i}][mac_wifi]" placeholder="MAC WiFi Address" required>
        </div>

        <div class="form-row">
        <label>SIM Number</label>
        <input type="text" name="devices[${i}][sim_no]" placeholder="SIM Number (if cellular)">
        </div>

        </div>

        <div class="section">
        <div class="section-title">Software / Apps</div>


        <div id="softwareContainer_${i}">
        <div class="software-item">
        <div class="item-header">
        <div class="software-title">Software 1</div>
        </div>
        <div class="form-row">
        <input type="text" name="devices[${i}][software][]" placeholder="App / Software Name">
        </div>
        </div>
        </div>

        <button type="button" class="add-btn" onclick="addSoftwareTo(${i})">+ Add Software</button>

        </div>
    `;

    /* ---------- PHONE ---------- */
    const phoneFields = `

        <div class="section">
        <div class="section-title">Phone Info</div>

        <div class="form-row">
        <label>Phone Model</label>
        <input type="text" name="devices[${i}][model]" placeholder="Phone Model" required>
        </div>

        <div class="form-row">
        <label>Serial Number</label>
        <input type="text" name="devices[${i}][serial]" placeholder="Serial Number" required>
        </div>

        <div class="form-row">
        <label>IMEI</label>
        <input type="text" name="devices[${i}][imei]" placeholder="IMEI" required>
        </div>

        <div class="form-row">
        <label>OS Version</label>
        <input type="text" name="devices[${i}][os_version]" placeholder="OS Version (e.g. Android 14 / iOS 17)" required>
        </div>

        <div class="form-row">
        <label>Storage Capacity</label>
        <input type="text" name="devices[${i}][storage_capacity]" placeholder="Storage capacity (e.g. 128GB)" required>
        </div>

        </div>

        <div class="section">
        <div class="section-title">SIM & Network</div>

        <div class="form-row">
        <label>SIM Number</label>
        <input type="text" name="devices[${i}][sim_no]" placeholder="SIM Number" required>
        </div>

        <div class="form-row">
        <label>Carrier / Provider</label>
        <input type="text" name="devices[${i}][carrier]" placeholder="Carrier / Provider" required>
        </div>

        <div class="form-row">
        <label>MAC WiFi</label>
        <input type="text" name="devices[${i}][mac_wifi]" placeholder="MAC WiFi Address">
        </div>

        </div>

        <div class="section">
        <div class="section-title">Account</div>

        <div class="form-row">
        <label>Google / Apple ID</label>
        <input type="text" name="devices[${i}][account_email]" placeholder="Google / Apple ID Email" required>
        </div>

        <div class="form-row">
        <label>Account Password</label>
        <input type="text" name="devices[${i}][account_password]" placeholder="Account Password" required>
        </div>

        </div>
    `;

    /* ---------- LAPTOP (same as Desktop but Monitor is collapsible) ---------- */
    const laptopFields = pcFields.replace(
        `<div class="section">
        <div class="section-title">Monitor</div>

        <div id="monitorContainer_\${i}">
        <div class="monitor-item">
        <div class="monitor-title">Monitor 1</div>

        <div class="form-row">
        <label>Model</label>
        <input type="text" name="devices[\${i}][monitor_model][]" placeholder="Monitor Model" required>
        </div>

        <div class="form-row">
        <label>Size</label>
        <input type="text" name="devices[\${i}][monitor_size][]" placeholder="Monitor Size" required>
        </div>

        <div class="form-row">
        <label>Serial</label>
        <input type="text" name="devices[\${i}][monitor_serial][]" placeholder="Serial Number" required>
        </div>

        </div>
        </div>

        <button type="button" class="add-btn" onclick="addMonitorTo(\${i})">+ Add Monitor</button>

        </div>`,

        `<div class="section">
        <div class="collapsible-header" onclick="toggleCollapsible(this)">
            <div class="section-title">Monitor <span style="font-size:0.8rem;color:#999;">(optional)</span></div>
            <button type="button" class="collapsible-toggle">+ Show</button>
        </div>
        <div class="collapsible-body" id="monitorCollapsible_\${i}">
        <div id="monitorContainer_\${i}">
        <div class="monitor-item">
        <div class="monitor-title">Monitor 1</div>

        <div class="form-row">
        <label>Model</label>
        <input type="text" name="devices[\${i}][monitor_model][]" placeholder="Monitor Model">
        </div>

        <div class="form-row">
        <label>Size</label>
        <input type="text" name="devices[\${i}][monitor_size][]" placeholder="Monitor Size">
        </div>

        <div class="form-row">
        <label>Serial</label>
        <input type="text" name="devices[\${i}][monitor_serial][]" placeholder="Serial Number">
        </div>

        </div>
        </div>

        <button type="button" class="add-btn" onclick="addMonitorTo(\${i})">+ Add Monitor</button>
        </div>
        </div>`
    );

    switch(type){
        case 'Desktop': return pcFields;
        case 'Laptop':  return laptopFields;
        case 'iPad':    return ipadFields;
        case 'Phone':   return phoneFields;
        default:        return '<p style="color:#aaa;padding:10px;">Please select a device type above.</p>';
    }
}

/* ==========================================
   DEVICE COUNTER
========================================== */
let deviceCount = 0;

function addDevice(){
    // Total blocks already on page (existing + previously added new ones)
    const totalBlocks = document.querySelectorAll('.device-block').length;
    const idx = deviceCount++;
    const displayNum = totalBlocks + 1;
    const wrapper = document.getElementById('devicesWrapper');

    const block = document.createElement('div');
    block.className = 'device-block';
    block.id = `deviceBlock_${idx}`;

    block.innerHTML = `
        <div class="device-block-header">
            <span class="device-block-title">Device ${displayNum}</span>
            <div style="display:flex;gap:10px;align-items:center;">
                <select class="device-type-select" onchange="changeDeviceType(${idx}, this.value)">
                    <option value="">-- Select Type --</option>
                    <option value="Desktop">Desktop (PC)</option>
                    <option value="Laptop">Laptop</option>
                    <option value="iPad">iPad</option>
                    <option value="Phone">Phone</option>
                </select>
                <button type="button" class="remove-device-btn" onclick="removeDevice(${idx})">✕ Remove</button>
            </div>
        </div>
        <input type="hidden" name="devices[${idx}][asset_type]" id="assetType_${idx}" value="">
        <div class="device-fields" id="deviceFields_${idx}">
            <p style="color:#aaa;padding:10px;">Please select a device type above.</p>
        </div>
    `;

    wrapper.appendChild(block);
}

function changeDeviceType(idx, type){
    document.getElementById(`assetType_${idx}`).value = type;
    document.getElementById(`deviceFields_${idx}`).innerHTML = getFieldsForType(type, idx);
    syncMonitorRequirements(idx, type);
}

function removeDevice(idx){
    const block = document.getElementById(`deviceBlock_${idx}`);
    if(block) block.remove();
}

function syncMonitorRequirements(idx, type){
    const fields = document.getElementById(`deviceFields_${idx}`);
    if(!fields) return;

    const monitorInputs = fields.querySelectorAll('.monitor-item input');
    if(monitorInputs.length === 0) return;

    monitorInputs.forEach(input => {
        if(type === 'Laptop'){
            input.removeAttribute('required');
        } else if(type === 'Desktop'){
            input.setAttribute('required', 'required');
        }
    });
}

/* ==========================================
   ADD RAM / STORAGE / MONITOR / SOFTWARE
   (scoped per device index)
========================================== */

function addRamTo(idx){
    const container = document.getElementById(`ramContainer_${idx}`);
    const count = container.querySelectorAll('.ram-item').length + 1;
    const div = document.createElement('div');
    div.className = 'ram-item';
    div.innerHTML = `
        <div class="item-header">
            <div class="ram-title">RAM ${count}</div>
            <button type="button" class="remove-btn" onclick="this.closest('.ram-item').remove()">✕</button>
        </div>
        <div class="form-row">
        <label>RAM Size</label>
        <select name="devices[${idx}][ram_size][]" required>
            <option value="">Select RAM</option>
            <option>2 GB</option>
            <option>4 GB</option>
            <option>8 GB</option>
            <option>16 GB</option>
            <option>32 GB</option>
            <option>64 GB</option>
        </select>
        </div>
    `;
    container.appendChild(div);
}

function addStorageTo(idx){
    const container = document.getElementById(`storageContainer_${idx}`);
    const count = container.querySelectorAll('.storage-item').length + 1;
    const div = document.createElement('div');
    div.className = 'storage-item';
    div.innerHTML = `
        <div class="item-header">
            <div class="storage-title">Storage ${count}</div>
            <button type="button" class="remove-btn" onclick="this.closest('.storage-item').remove()">✕</button>
        </div>
        <div class="form-row">
        <label>Model</label>
        <input type="text" name="devices[${idx}][hdd_model][]" placeholder="HDD Model" required>
        </div>
        <div class="form-row">
        <label>Capacity</label>
        <input type="text" name="devices[${idx}][hdd_capacity][]" placeholder="Capacity" required>
        </div>
        <div class="form-row">
        <label>Serial</label>
        <input type="text" name="devices[${idx}][hdd_serial][]" placeholder="Serial Number" required>
        </div>
    `;
    container.appendChild(div);
}

function addMonitorTo(idx){
    const container = document.getElementById(`monitorContainer_${idx}`);
    const count = container.querySelectorAll('.monitor-item').length + 1;

    /* Check if this is inside a laptop block — look up for device-type-select */
    const block = container.closest('.device-block');
    const select = block ? block.querySelector('.device-type-select') : null;
    const isLaptop = select && select.value === 'Laptop';
    const req = isLaptop ? '' : 'required';

    const div = document.createElement('div');
    div.className = 'monitor-item';
    div.innerHTML = `
        <div class="item-header">
            <div class="monitor-title">Monitor ${count}</div>
            <button type="button" class="remove-btn" onclick="this.closest('.monitor-item').remove()">✕</button>
        </div>
        <div class="form-row">
        <label>Model</label>
        <input type="text" name="devices[${idx}][monitor_model][]" placeholder="Monitor Model" ${req}>
        </div>
        <div class="form-row">
        <label>Size</label>
        <input type="text" name="devices[${idx}][monitor_size][]" placeholder="Monitor Size" ${req}>
        </div>
        <div class="form-row">
        <label>Serial</label>
        <input type="text" name="devices[${idx}][monitor_serial][]" placeholder="Serial Number" ${req}>
        </div>
    `;
    container.appendChild(div);
    syncMonitorRequirements(idx, isLaptop ? 'Laptop' : 'Desktop');
}

function addSoftwareTo(idx){
    const container = document.getElementById(`softwareContainer_${idx}`);
    const count = container.querySelectorAll('.software-item').length + 1;
    const div = document.createElement('div');
    div.className = 'software-item';
    div.innerHTML = `
        <div class="item-header">
            <div class="software-title">Software ${count}</div>
            <button type="button" class="remove-btn" onclick="this.closest('.software-item').remove()">✕</button>
        </div>
        <div class="form-row">
        <input type="text" name="devices[${idx}][software][]" placeholder="Enter Software">
        </div>
    `;
    container.appendChild(div);
}

/* ==========================================
   INIT
========================================== */
document.addEventListener("DOMContentLoaded", function(){
    // Only auto-add blank device if no existing devices are pre-filled.
    const existingBlocks = document.querySelectorAll('.device-block');
    if(existingBlocks.length === 0){
        addDevice();
    }
});

function toggleCollapsible(header){
    const body = header.nextElementSibling;
    const btn  = header.querySelector('.collapsible-toggle');
    body.classList.toggle('open');
    btn.textContent = body.classList.contains('open') ? '- Hide' : '+ Show';
}


</script>

<?php include "components/footer.php"; ?>
