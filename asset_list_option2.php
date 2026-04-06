<link rel="stylesheet" href="css/style.css">

<?php include "components/navbar.php"; ?>

<?php
include "config/db.php";

/* Fetch all Desktop/Laptop assets per user */
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
assets.mac_lan,
assets.mac_wifi,
assets.antivirus,
assets.windows_key,

cpu.cpu_model,
cpu.cpu_speed,
cpu.cpu_core,
cpu.cpu_hyper_thread,
cpu.graphic_card,

GROUP_CONCAT(DISTINCT ram.ram_size ORDER BY ram.ram_size)                     AS ram,
GROUP_CONCAT(DISTINCT storage.hdd_model ORDER BY storage.hdd_model)           AS hdd_model,
GROUP_CONCAT(DISTINCT storage.hdd_capacity ORDER BY storage.hdd_model)        AS hdd_capacity,
GROUP_CONCAT(DISTINCT storage.hdd_serial ORDER BY storage.hdd_model)          AS hdd_serial,
GROUP_CONCAT(DISTINCT monitor.monitor_model ORDER BY monitor.monitor_model)   AS monitor_model,
GROUP_CONCAT(DISTINCT monitor.monitor_size ORDER BY monitor.monitor_model)    AS monitor_size,
GROUP_CONCAT(DISTINCT monitor.monitor_serial ORDER BY monitor.monitor_model)  AS monitor_serial,
GROUP_CONCAT(DISTINCT software.software_name)                                 AS software

FROM users
INNER JOIN assets ON assets.user_id = users.user_id
    AND assets.asset_type IN ('Desktop','Laptop')
LEFT JOIN cpu      ON cpu.asset_id      = assets.asset_id
LEFT JOIN ram      ON ram.asset_id      = assets.asset_id
LEFT JOIN storage  ON storage.asset_id  = assets.asset_id
LEFT JOIN monitor  ON monitor.asset_id  = assets.asset_id
LEFT JOIN software ON software.asset_id = assets.asset_id

GROUP BY assets.asset_id
ORDER BY users.user_id DESC, assets.asset_id ASC
";

$result = mysqli_query($conn, $query);
if(!$result){ die("Query Error: ".mysqli_error($conn)); }

/* Group by user, store all assets per user */
$users = [];
while($row = mysqli_fetch_assoc($result)){
    $uid = $row['user_id'];
    if(!isset($users[$uid])){
        $users[$uid] = [
            'user_id'      => $row['user_id'],
            'name'         => $row['name'],
            'position'     => $row['position'],
            'contact_no'   => $row['contact_no'],
            'email_id'     => $row['email_id'],
            'email_password'=> $row['email_password'],
            'mail_server'  => $row['mail_server'],
            'assets'       => []
        ];
    }

    /* Build storage list */
    $hdd_model    = !empty($row['hdd_model'])    ? explode(",", $row['hdd_model'])    : [];
    $hdd_capacity = !empty($row['hdd_capacity']) ? explode(",", $row['hdd_capacity']) : [];
    $hdd_serial   = !empty($row['hdd_serial'])   ? explode(",", $row['hdd_serial'])   : [];
    $storageList = "";
    for($i=0; $i<count($hdd_model); $i++){
        if(trim($hdd_model[$i]) != "")
            $storageList .= "• ".$hdd_model[$i]." (".($hdd_capacity[$i] ?? '').") - ".($hdd_serial[$i] ?? '')."<br>";
    }

    /* Build monitor list */
    $mon_model  = !empty($row['monitor_model'])  ? explode(",", $row['monitor_model'])  : [];
    $mon_size   = !empty($row['monitor_size'])   ? explode(",", $row['monitor_size'])   : [];
    $mon_serial = !empty($row['monitor_serial']) ? explode(",", $row['monitor_serial']) : [];
    $monitorList = "";
    for($i=0; $i<count($mon_model); $i++){
        if(trim($mon_model[$i]) != "")
            $monitorList .= "• ".$mon_model[$i]." (".($mon_size[$i] ?? '').") - ".($mon_serial[$i] ?? '')."<br>";
    }
    /* N/A for Laptop with no monitor */
    if(empty($monitorList) && $row['asset_type'] === 'Laptop'){
        $monitorList = '<span style="color:#aaa;font-style:italic;">N/A</span>';
    }

    /* Build RAM text */
    $ram_arr = !empty($row['ram']) ? explode(",", $row['ram']) : [];
    $total = 0; $ramText = ""; $slots = 0;
    foreach($ram_arr as $r){
        $val = intval($r);
        if($val > 0){ $total += $val; $ramText .= $val." GB + "; $slots++; }
    }
    $ramText = rtrim($ramText, " + ");
    $ramDisplay = $ramText." = ".$total." GB<br><span style='font-size:12px;opacity:0.7;'>(".$slots." slots)</span>";

    /* Build software list */
    $softwareList = "";
    $sw_arr = !empty($row['software']) ? explode(",", $row['software']) : [];
    foreach($sw_arr as $s){
        if(trim($s) != "") $softwareList .= "• ".$s."<br>";
    }

    $users[$uid]['assets'][] = [
        'id'          => $row['ID'],
        'asset_type'  => $row['asset_type'],
        'pc_username' => $row['pc_username'],
        'pc_password' => $row['pc_password'],
        'pc_model'    => $row['pc_model'],
        'pc_name'     => $row['pc_name'],
        'mac_lan'     => $row['mac_lan'],
        'mac_wifi'    => $row['mac_wifi'],
        'antivirus'   => $row['antivirus'],
        'windows_key' => $row['windows_key'],
        'cpu'         => $row['cpu_model']."<br><span style='font-size:12px;opacity:0.7;'>".$row['cpu_speed']." GHz • ".$row['cpu_core']." Cores</span>",
        'gpu'         => $row['graphic_card'],
        'ram'         => $ramDisplay,
        'storage'     => $storageList,
        'monitor'     => $monitorList,
        'software'    => $softwareList,
    ];
}
?>

<div class="container asset-container">

<h1 class="page-title">Assets List</h1>

<div class="hardware-header">
<a href="add_asset.php" class="add-hardware-btn" style="display:block;width:fit-content;margin:20px auto;">
+ Add Asset
</a>
</div>

<!-- SEARCH -->
<div class="search-bar-clean">
  <span class="search-icon">🔍</span>
  <input
    type="text"
    id="searchInput"
    placeholder="Search assets..."
    onkeyup="debouncedSearch()"
    onkeydown="handleKey(event)"
  >
</div>

<p id="noResult" class="no-result">No results found 😢</p>

<div class="table-wrapper">
<table class="hardware-table">
<thead>
<tr>
<th>No</th>
<th class="user-col">Name</th>
<th class="user-col">Position</th>
<th class="user-col">Contact</th>
<th class="user-col">Email</th>
<th class="user-col">Email Password</th>
<th class="user-col">Mail Server</th>
<th class="user-col">Asset Type</th>
<th class="user-col">PC Username</th>
<th class="user-col">PC Password</th>
<th class="pc-col">PC Model</th>
<th class="pc-col">PC Name</th>
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

function searchTable(){
  let input = document.getElementById("searchInput").value.toLowerCase();
  let bodies = document.querySelectorAll(".hardware-table tbody");
  let found = false;
  let firstMatch = null;
  visibleRows = [];
  currentIndex = -1;

  bodies.forEach(body => {
    let textContent = body.innerText.toLowerCase();
    let match = input ? fuzzyMatch(textContent, input) : true;

    body.querySelectorAll("td").forEach(cell => {
      cell.innerHTML = cell.innerHTML.replace(/<span class="highlight">(.*?)<\/span>/gi, "$1");
      if(input && fuzzyMatch(cell.innerText.toLowerCase(), input)){
        let regex = new RegExp(`(${input})`, "gi");
        cell.innerHTML = cell.innerHTML.replace(regex, '<span class="highlight">$1</span>');
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
  if(e.key === "ArrowUp"){   e.preventDefault(); currentIndex=(currentIndex-1+visibleRows.length)%visibleRows.length; highlightRow(); }
}

function highlightRow(){
  visibleRows.forEach(r => r.classList.remove("active-row"));
  visibleRows[currentIndex].classList.add("active-row");
}

/* ── Switch active asset in a row ── */
function switchAsset(uid, assetIndex){
    const assets = window.userAssets[uid];
    const a = assets[assetIndex];

    /* Update button active state */
    document.querySelectorAll('.asset-type-btn[data-uid="'+uid+'"]').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector('.asset-type-btn[data-uid="'+uid+'"][data-index="'+assetIndex+'"]').classList.add('active');

    /* Update row cells */
    const row = document.getElementById('row_user_'+uid);
    row.querySelector('.cell-pc-username').innerHTML = a.pc_username;
    row.querySelector('.cell-pc-password').innerHTML = a.pc_password;
    row.querySelector('.cell-pc-model').innerHTML    = a.pc_model;
    row.querySelector('.cell-pc-name').innerHTML     = a.pc_name;
    row.querySelector('.cell-mac-lan').innerHTML     = a.mac_lan;
    row.querySelector('.cell-mac-wifi').innerHTML    = a.mac_wifi;
    row.querySelector('.cell-antivirus').innerHTML   = a.antivirus;
    row.querySelector('.cell-cpu').innerHTML         = a.cpu;
    row.querySelector('.cell-gpu').innerHTML         = a.gpu;
    row.querySelector('.cell-ram').innerHTML         = a.ram;
    row.querySelector('.cell-storage').innerHTML     = a.storage;
    row.querySelector('.cell-monitor').innerHTML     = a.monitor;
    row.querySelector('.cell-windows').innerHTML     = a.windows_key;
    row.querySelector('.cell-software').innerHTML    = a.software;

    /* Update action buttons */
    row.querySelector('.cell-action').innerHTML =
        '<a href="asset_detail.php?id='+a.id+'" class="view-btn device-btn" >View</a>'+
        '<a href="edit_asset.php?id='+a.id+'" class="edit-btn device-btn" >Edit</a>'+
        '<a href="delete_asset.php?id='+a.id+'" class="delete-btn device-btn" onclick="return confirm(\'Delete this asset?\')">Delete</a>';

    /* Update row click */
    row.setAttribute('onclick', "window.location='asset_detail.php?id="+a.id+"'");
}
</script>

<?php
/* Embed all asset data as JS object for dynamic row switching */
echo "<script>window.userAssets = ".json_encode(
    array_map(fn($u) => $u['assets'], $users)
).";</script>";

$no = 1;
foreach($users as $uid => $user):
    $assets  = $user['assets'];
    $first   = $assets[0]; // default shown asset
?>

<tbody id="tbody_user_<?php echo $uid; ?>">
<tr id="row_user_<?php echo $uid; ?>"
    onclick="window.location='asset_detail.php?id=<?php echo $first['id']; ?>'"
    style="cursor:pointer;">

<td><?php echo $no++; ?></td>
<td class="user-col name-col"><?php echo htmlspecialchars($user['name']); ?></td>
<td class="user-col"><?php echo htmlspecialchars($user['position']); ?></td>
<td class="user-col"><?php echo htmlspecialchars($user['contact_no']); ?></td>
<td class="user-col"><?php echo htmlspecialchars($user['email_id']); ?></td>
<td class="user-col"><?php echo htmlspecialchars($user['email_password']); ?></td>
<td class="user-col"><?php echo htmlspecialchars($user['mail_server']); ?></td>

<!-- ASSET TYPE BUTTONS -->
<td class="user-col" onclick="event.stopPropagation();">
<?php foreach($assets as $ai => $asset): ?>
    <?php $label = ($asset['asset_type'] === 'Desktop') ? 'PC' : 'Laptop'; ?>
    <?php $btnClass = ($asset['asset_type'] === 'Desktop') ? 'device-btn pc' : 'device-btn laptop'; ?>
    <a class="<?php echo $btnClass; ?> asset-type-btn <?php echo $ai === 0 ? 'active' : ''; ?>"
       data-uid="<?php echo $uid; ?>"
       data-index="<?php echo $ai; ?>"
       onclick="switchAsset(<?php echo $uid; ?>, <?php echo $ai; ?>)"
       style="display:inline-block;margin:2px;cursor:pointer;">
        <?php echo $label; ?>
    </a>
<?php endforeach; ?>
</td>

<!-- ASSET DATA CELLS — show first asset by default -->
<td class="user-col cell-pc-username"><?php echo $first['pc_username']; ?></td>
<td class="user-col cell-pc-password"><?php echo $first['pc_password']; ?></td>
<td class="pc-col cell-pc-model"><?php echo $first['pc_model']; ?></td>
<td class="pc-col cell-pc-name"><?php echo $first['pc_name']; ?></td>
<td class="pc-col cell-mac-lan"><?php echo $first['mac_lan']; ?></td>
<td class="pc-col cell-mac-wifi"><?php echo $first['mac_wifi']; ?></td>
<td class="pc-col cell-antivirus"><?php echo $first['antivirus']; ?></td>
<td class="cpu-col cell-cpu"><?php echo $first['cpu']; ?></td>
<td class="gpu-col cell-gpu"><?php echo $first['gpu']; ?></td>
<td class="ram-col cell-ram"><?php echo $first['ram']; ?></td>
<td class="storage-col cell-storage"><?php echo $first['storage']; ?></td>
<td class="monitor-col cell-monitor"><?php echo $first['monitor']; ?></td>
<td class="windows-col cell-windows"><?php echo $first['windows_key']; ?></td>
<td class="software-col cell-software"><?php echo $first['software']; ?></td>

<!-- ACTION -->
<td class="action-col cell-action" onclick="event.stopPropagation();">
    <a href="asset_detail.php?id=<?php echo $first['id']; ?>" class="view-btn device-btn">View</a>
    <a href="edit_asset.php?id=<?php echo $first['id']; ?>" class="edit-btn device-btn">Edit</a>
    <a href="delete_asset.php?id=<?php echo $first['id']; ?>" class="delete-btn device-btn" onclick="return confirm('Delete this asset?')">Delete</a>
</td>

</tr>
</tbody>

<?php endforeach; ?>

</table>
</div>
</div>

<?php include "components/footer.php"; ?>