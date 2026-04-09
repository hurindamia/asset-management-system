<link rel="stylesheet" href="css/style.css">

<?php include "components/navbar.php"; ?>

<?php
include "config/db.php";

$query = "
SELECT 
assets.asset_id AS ID,

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

cpu.cpu_model,
cpu.cpu_speed,
cpu.cpu_core,
cpu.cpu_hyper_thread,
cpu.graphic_card,

MAX(ram_agg.ram) AS ram,

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
LEFT JOIN (
    SELECT asset_id, GROUP_CONCAT(ram_size SEPARATOR '||') AS ram
    FROM ram
    GROUP BY asset_id
) ram_agg ON ram_agg.asset_id = assets.asset_id
LEFT JOIN storage ON assets.asset_id = storage.asset_id
LEFT JOIN monitor ON assets.asset_id = monitor.asset_id
LEFT JOIN software ON assets.asset_id = software.asset_id

WHERE assets.asset_type IN ('Desktop', 'Laptop')

GROUP BY assets.asset_id

ORDER BY assets.asset_id DESC
";

$result = mysqli_query($conn,$query);

if(!$result){
    die("Query Error: ".mysqli_error($conn));
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
  let words = text.split(" ");
  return words.some(word => word.startsWith(input) || word.includes(input));
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

      cell.innerHTML = cell.innerHTML.replace(
        /<span class="highlight">(.*?)<\/span>/gi,
        "$1"
      );

      if(input && fuzzyMatch(cell.innerText.toLowerCase(), input)){
        let regex = new RegExp(`(${input})`, "gi");
        cell.innerHTML = cell.innerHTML.replace(regex, '<span class="highlight">$1</span>');
      }

    });

    if(match){
      body.style.display = "";
      visibleRows.push(body);
      found = true;

      if(!firstMatch){
        firstMatch = body;
      }
    } else {
      body.style.display = "none";
    }

  });

  document.getElementById("noResult").style.display = found ? "none" : "block";

  if(firstMatch){
    firstMatch.scrollIntoView({
      behavior:"smooth",
      block:"center"
    });
  }
}

function handleKey(e){
  if(visibleRows.length === 0) return;

  if(e.key === "ArrowDown"){
    e.preventDefault();
    currentIndex = (currentIndex + 1) % visibleRows.length;
    highlightRow();
  }

  if(e.key === "ArrowUp"){
    e.preventDefault();
    currentIndex = (currentIndex - 1 + visibleRows.length) % visibleRows.length;
    highlightRow();
  }
}

function highlightRow(){
  visibleRows.forEach(r => r.classList.remove("active-row"));
  let row = visibleRows[currentIndex];
  row.classList.add("active-row");
}
</script>

<?php 
$rowIndex = 0;
$no = 1;

while($row = mysqli_fetch_assoc($result)) { 

echo "<tbody>";

$rowClass = ($rowIndex % 2 == 0) ? "row-light" : "row-dark";

$ram_arr = !empty($row['ram']) ? explode("||", $row['ram']) : [];

$hdd_model    = !empty($row['hdd_model'])    ? explode(",", $row['hdd_model'])    : [];
$hdd_capacity = !empty($row['hdd_capacity']) ? explode(",", $row['hdd_capacity']) : [];
$hdd_serial   = !empty($row['hdd_serial'])   ? explode(",", $row['hdd_serial'])   : [];

$monitor_model  = !empty($row['monitor_model'])  ? explode(",", $row['monitor_model'])  : [];
$monitor_size   = !empty($row['monitor_size'])   ? explode(",", $row['monitor_size'])   : [];
$monitor_serial = !empty($row['monitor_serial']) ? explode(",", $row['monitor_serial']) : [];

/* STORAGE */
$storageList = "";
for($i=0;$i<count($hdd_model);$i++){
  if(trim($hdd_model[$i])!=""){
    $storageList .= "• ".$hdd_model[$i]." (".($hdd_capacity[$i] ?? '').") - ".($hdd_serial[$i] ?? '')."<br>";
  }
}

/* MONITOR */
$monitorList = "";
for($i=0;$i<count($monitor_model);$i++){
  if(trim($monitor_model[$i])!=""){
    $monitorList .= "• ".$monitor_model[$i]." (".($monitor_size[$i] ?? '').") - ".($monitor_serial[$i] ?? '')."<br>";
  }
}

/* RAM */
$total = 0;
$ramText = "";
$slots = 0;

foreach($ram_arr as $r){
  $val = intval($r);
  if($val>0){
    $total += $val;
    $ramText .= $val." GB + ";
    $slots++;
  }
}
$ramText = rtrim($ramText," + ");

echo "<tr class='$rowClass' onclick=\"window.location='asset_detail.php?id=".$row['ID']."'\" style='cursor:pointer;'>";

echo "<td>".$no++."</td>";

echo "<td class='user-col'>".$row['name']."</td>";
echo "<td class='user-col'>".$row['position']."</td>";
echo "<td class='user-col'>".$row['contact_no']."</td>";
echo "<td class='user-col'>".$row['email_id']."</td>";
echo "<td class='user-col'>".$row['email_password']."</td>";
echo "<td class='user-col'>".$row['mail_server']."</td>";
echo "<td class='user-col'>".$row['pc_username']."</td>";
echo "<td class='user-col'>".$row['pc_password']."</td>";

echo "<td class='pc-col'>".$row['pc_model']."</td>";
echo "<td class='pc-col'>".$row['pc_name']."</td>";
echo "<td class='pc-col'>".$row['mac_lan']."</td>";
echo "<td class='pc-col'>".$row['mac_wifi']."</td>";
echo "<td class='pc-col'>".$row['antivirus']."</td>";

echo "<td class='cpu-col'>".$row['cpu_model']."<br><span style='font-size:12px;opacity:0.7;'>".$row['cpu_speed']." GHz • ".$row['cpu_core']." Cores</span></td>";
echo "<td class='gpu-col'>".$row['graphic_card']."</td>";

echo "<td class='ram-col'>
".$ramText." = ".$total." GB <br>
<span style='font-size:12px; opacity:0.7;'>(".$slots." slots)</span>
</td>";

echo "<td class='storage-col'>".$storageList."</td>";
echo "<td class='monitor-col'>".$monitorList."</td>";

echo "<td class='windows-col'>".$row['windows_key']."</td>";

$softwareList = "";
foreach(explode(",",$row['software']) as $s){
  if(trim($s)!=""){
    $softwareList .= "• ".$s."<br>";
  }
}

echo "<td class='software-col'>".$softwareList."</td>";

echo "<td class='action-col' onclick='event.stopPropagation();'>
<a href='edit_asset.php?id=".$row['ID']."' class='edit-btn device-btn'>Edit</a>
<a href='delete_asset.php?id=".$row['ID']."' class='delete-btn device-btn' onclick=\"return confirm('Delete this asset?')\">Delete</a>
</td>";

echo "</tr>";
echo "</tbody>";

$rowIndex++;
}
?>

</table>
</div>
</div>

<?php include "components/footer.php"; ?>
