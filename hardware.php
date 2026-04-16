<link rel="stylesheet" href="css/style.css">

<?php include "components/navbar.php"; ?>

<?php
include "config/db.php";
include_once "config/windows_asset_helpers.php";

/* Fetch ALL device assets per user */
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

MAX(ram_agg.ram)                                                                 AS ram,
GROUP_CONCAT(DISTINCT storage.hdd_model ORDER BY storage.hdd_model)              AS hdd_model,
GROUP_CONCAT(DISTINCT storage.hdd_capacity ORDER BY storage.hdd_model)           AS hdd_capacity,
GROUP_CONCAT(DISTINCT storage.hdd_serial ORDER BY storage.hdd_model)             AS hdd_serial,
GROUP_CONCAT(DISTINCT monitor.monitor_model ORDER BY monitor.monitor_model)      AS monitor_model,
GROUP_CONCAT(DISTINCT monitor.monitor_size ORDER BY monitor.monitor_model)       AS monitor_size,
GROUP_CONCAT(DISTINCT monitor.monitor_serial ORDER BY monitor.monitor_model)     AS monitor_serial,
GROUP_CONCAT(DISTINCT software.software_name)                                    AS software

FROM users
INNER JOIN assets ON assets.user_id = users.user_id
    AND assets.asset_type IN ('Desktop','Laptop','iPad','Phone')
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
ORDER BY users.user_id DESC, assets.asset_id ASC
";

$result = mysqli_query($conn, $query);
if(!$result){ die("Query Error: ".mysqli_error($conn)); }

/* Group rows by user */
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
    $asset_id = (int)($row['ID'] ?? 0);
    $windows_rows = asset_get_windows_items_for_asset(
        $windows_map,
        $asset_id,
        (string)($row['windows_key'] ?? '')
    );
    $windows_lines = [];
    foreach($windows_rows as $window_row){
        $window_os = trim((string)($window_row['window__os'] ?? ''));
        $window_serial = trim((string)($window_row['windows_serial'] ?? ''));
        if($window_os === ''){
            continue;
        }
        $line = $window_os;
        if($window_serial !== ''){
            $line .= " - ".$window_serial;
        }
        $windows_lines[] = $line;
    }
    $row['windows_display'] = !empty($windows_lines)
        ? implode("<br>", $windows_lines)
        : (string)($row['windows_key'] ?? '');

    $uid = $row['user_id'];
    if(!isset($users[$uid])){
        $users[$uid] = [
            'info'   => $row,
            'assets' => []
        ];
    }
    $users[$uid]['assets'][] = $row;
}
?>

<style>
.user-asset-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 28px;
    overflow: hidden;
}

.user-asset-header {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
    padding: 16px 20px;
    background: var(--card-header-user, #cad2e4);
    border-bottom: 2px solid rgba(0,0,0,0.06);
}

.user-asset-header .info-item b {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    opacity: 0.6;
    margin-bottom: 2px;
}

.user-asset-header .info-item span {
    font-size: 0.9rem;
    font-weight: 500;
}

.device-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #eee;
    background: #fafafa;
}

.device-tab {
    padding: 10px 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    color: #666;
    transition: all 0.2s;
}

.device-tab.active {
    border-bottom-color: #0b2a4a;
    color: #0b2a4a;
}

.device-tab:hover {
    background: #cad2e4;
}

.device-panel {
    display: none;
    padding: 16px 20px;
}

.device-panel.active {
    display: block;
}

.spec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 10px 20px;
    margin-bottom: 12px;
}

.spec-section {
    margin-bottom: 14px;
}

.spec-section-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 700;
    color: #0b2a4a;
    border-bottom: 1px solid #cad2e4;
    padding-bottom: 4px;
    margin-bottom: 8px;
}

.spec-item {
    font-size: 0.88rem;
    margin-bottom: 4px;
}

.spec-item b {
    color: #444;
}

.panel-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #eee;
}

.card-no {
    display: inline-block;
    background: #0b2a4a;
    color: #fff;
    border-radius: 50%;
    width: 26px;
    height: 26px;
    line-height: 26px;
    text-align: center;
    font-size: 0.8rem;
    font-weight: 700;
    margin-right: 8px;
}

.user-name-header {
    padding: 12px 20px 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #333;
}
</style>

<div class="container asset-container">

<h1 class="page-title">Hardware List</h1>

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
    placeholder="Search by name, model, CPU..."
    onkeyup="debouncedSearch()"
    onkeydown="handleKey(event)"
  >
</div>

<p id="noResult" class="no-result" style="display:none;">No results found 😢</p>

<div id="cardsWrapper">
<?php
$no = 1;
foreach($users as $uid => $user):
    $info   = $user['info'];
    $assets = $user['assets'];
    $card_id = "card_user_".$uid;
?>

<div class="user-asset-card" id="<?php echo $card_id; ?>">

    <!-- User name row -->
    <div class="user-name-header">
        <span class="card-no"><?php echo $no++; ?></span>
        <span class="highlight-name"><?php echo htmlspecialchars($info['name']); ?></span>
        <span class="highlight-position" style="font-size:0.85rem;color:#888;font-weight:400;margin-left:8px;"><?php echo htmlspecialchars($info['position']); ?></span>
    </div>

    <!-- User Info Grid -->
    <div class="user-asset-header">
        <div class="info-item"><b>Contact</b><span><?php echo $info['contact_no']; ?></span></div>
        <div class="info-item"><b>Email</b><span><?php echo $info['email_id']; ?></span></div>
        <div class="info-item"><b>Email Password</b><span><?php echo $info['email_password']; ?></span></div>
        <div class="info-item"><b>Mail Server</b><span><?php echo $info['mail_server']; ?></span></div>
    </div>

    <!-- Device Tabs -->
    <div class="device-tabs">
    <?php foreach($assets as $ai => $asset): ?>
        <?php
        $label = $asset['asset_type'];
        if($label == 'Desktop') $label = 'PC';
        ?>
        <div class="device-tab <?php echo $ai === 0 ? 'active' : ''; ?>"
             onclick="switchTab(this, 'panel_<?php echo $asset['ID']; ?>')">
            <?php echo $label; ?>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- Device Panels -->
    <?php foreach($assets as $ai => $asset):

        $atype = $asset['asset_type'];

        /* RAM */
$ram_arr = !empty($asset['ram']) ? explode("||", $asset['ram']) : [];
        $total = 0; $ramText = ""; $slots = 0;
        foreach($ram_arr as $r){
            $val = intval($r);
            if($val > 0){ $total += $val; $ramText .= $val." GB + "; $slots++; }
        }
        $ramText = rtrim($ramText, " + ");

        /* STORAGE */
        $hdd_model    = !empty($asset['hdd_model'])    ? explode(",", $asset['hdd_model'])    : [];
        $hdd_capacity = !empty($asset['hdd_capacity']) ? explode(",", $asset['hdd_capacity']) : [];
        $hdd_serial   = !empty($asset['hdd_serial'])   ? explode(",", $asset['hdd_serial'])   : [];
        $storageList = "";
        for($i=0; $i<count($hdd_model); $i++){
            if(trim($hdd_model[$i]) != "")
                $storageList .= "• ".$hdd_model[$i]." (".($hdd_capacity[$i] ?? '').") — ".($hdd_serial[$i] ?? '')."<br>";
        }

        /* MONITOR */
        $mon_model  = !empty($asset['monitor_model'])  ? explode(",", $asset['monitor_model'])  : [];
        $mon_size   = !empty($asset['monitor_size'])   ? explode(",", $asset['monitor_size'])   : [];
        $mon_serial = !empty($asset['monitor_serial']) ? explode(",", $asset['monitor_serial']) : [];
        $monitorList = "";
        for($i=0; $i<count($mon_model); $i++){
            if(trim($mon_model[$i]) != "")
                $monitorList .= "• ".$mon_model[$i]." (".($mon_size[$i] ?? '').") — ".($mon_serial[$i] ?? '')."<br>";
        }

        /* SOFTWARE */
        $softwareList = "";
        $sw_arr = !empty($asset['software']) ? explode(",", $asset['software']) : [];
        foreach($sw_arr as $s){
            if(trim($s) != "") $softwareList .= "• ".$s."<br>";
        }
    ?>

    <div class="device-panel <?php echo $ai === 0 ? 'active' : ''; ?>"
         id="panel_<?php echo $asset['ID']; ?>">

        <div class="spec-grid">

        <?php if($atype === 'Desktop' || $atype === 'Laptop'): ?>

            <!-- PC -->
            <div class="spec-section">
                <div class="spec-section-title">PC</div>
                <div class="spec-item"><b>Model:</b> <?php echo $asset['pc_model']; ?></div>
                <div class="spec-item"><b>Name:</b> <?php echo $asset['pc_name']; ?></div>
                <div class="spec-item"><b>Serial No:</b> <?php echo $asset['pc_serial_no']; ?></div>
                <div class="spec-item"><b>MAC LAN:</b> <?php echo $asset['mac_lan']; ?></div>
                <div class="spec-item"><b>MAC WIFI:</b> <?php echo $asset['mac_wifi']; ?></div>
                <div class="spec-item"><b>Antivirus:</b> <?php echo $asset['antivirus']; ?></div>
                <div class="spec-item"><b>PC Username:</b> <?php echo $asset['pc_username']; ?></div>
                <div class="spec-item"><b>PC Password:</b> <?php echo $asset['pc_password']; ?></div>
                <div class="spec-item"><b>Windows:</b> <?php echo $asset['windows_display']; ?></div>
            </div>

            <!-- CPU -->
            <div class="spec-section">
                <div class="spec-section-title">CPU & GPU</div>
                <div class="spec-item"><b>CPU:</b> <?php echo $asset['cpu_model']; ?></div>
                <div class="spec-item"><b>Speed:</b> <?php echo $asset['cpu_speed']; ?></div>
                <div class="spec-item"><b>Cores:</b> <?php echo $asset['cpu_core']; ?></div>
                <div class="spec-item"><b>Hyper Thread:</b> <?php echo $asset['cpu_hyper_thread']; ?></div>
                <div class="spec-item"><b>GPU:</b> <?php echo $asset['graphic_card']; ?></div>
            </div>

            <!-- RAM -->
            <div class="spec-section">
                <div class="spec-section-title">RAM</div>
                <div class="spec-item"><?php echo $ramText." = ".$total." GB"; ?></div>
                <div class="spec-item">(<?php echo $slots; ?> slots)</div>
            </div>

            <!-- STORAGE -->
            <div class="spec-section">
                <div class="spec-section-title">Storage</div>
                <div class="spec-item"><?php echo $storageList ?: '—'; ?></div>
            </div>

            <?php if($monitorList): ?>
            <!-- MONITOR -->
            <div class="spec-section">
                <div class="spec-section-title">Monitor</div>
                <div class="spec-item"><?php echo $monitorList; ?></div>
            </div>
            <?php endif; ?>

            <?php if($softwareList): ?>
            <!-- SOFTWARE -->
            <div class="spec-section">
                <div class="spec-section-title">Software</div>
                <div class="spec-item"><?php echo $softwareList; ?></div>
            </div>
            <?php endif; ?>

        <?php elseif($atype === 'iPad'): ?>

            <!-- iPAD INFO -->
            <div class="spec-section">
                <div class="spec-section-title">iPad Info</div>
                <div class="spec-item"><b>Model:</b> <?php echo $asset['pc_model']; ?></div>
                <div class="spec-item"><b>Serial No:</b> <?php echo $asset['serial_no']; ?></div>
                <div class="spec-item"><b>Storage:</b> <?php echo $asset['storage_capacity']; ?></div>
                <div class="spec-item"><b>iOS Version:</b> <?php echo $asset['os_version']; ?></div>
                <div class="spec-item"><b>IMEI / UDID:</b> <?php echo $asset['imei']; ?></div>
            </div>

            <!-- CONNECTIVITY -->
            <div class="spec-section">
                <div class="spec-section-title">Connectivity</div>
                <div class="spec-item"><b>MAC WiFi:</b> <?php echo $asset['mac_wifi']; ?></div>
                <div class="spec-item"><b>SIM No:</b> <?php echo $asset['sim_no'] ?: '—'; ?></div>
            </div>

            <!-- APPLE ACCOUNT -->
            <div class="spec-section">
                <div class="spec-section-title">Apple Account</div>
                <div class="spec-item"><b>Apple ID:</b> <?php echo $asset['apple_id']; ?></div>
                <div class="spec-item"><b>Password:</b> <?php echo $asset['apple_password']; ?></div>
            </div>

            <?php if($softwareList): ?>
            <!-- APPS -->
            <div class="spec-section">
                <div class="spec-section-title">Apps / Software</div>
                <div class="spec-item"><?php echo $softwareList; ?></div>
            </div>
            <?php endif; ?>

        <?php elseif($atype === 'Phone'): ?>

            <!-- PHONE INFO -->
            <div class="spec-section">
                <div class="spec-section-title">Phone Info</div>
                <div class="spec-item"><b>Model:</b> <?php echo $asset['pc_model']; ?></div>
                <div class="spec-item"><b>Serial No:</b> <?php echo $asset['serial_no']; ?></div>
                <div class="spec-item"><b>IMEI:</b> <?php echo $asset['imei']; ?></div>
                <div class="spec-item"><b>OS Version:</b> <?php echo $asset['os_version']; ?></div>
                <div class="spec-item"><b>Storage:</b> <?php echo $asset['storage_capacity']; ?></div>
            </div>

            <!-- SIM & NETWORK -->
            <div class="spec-section">
                <div class="spec-section-title">SIM & Network</div>
                <div class="spec-item"><b>SIM No:</b> <?php echo $asset['sim_no']; ?></div>
                <div class="spec-item"><b>Carrier:</b> <?php echo $asset['carrier']; ?></div>
                <div class="spec-item"><b>MAC WiFi:</b> <?php echo $asset['mac_wifi'] ?: '—'; ?></div>
            </div>

            <!-- ACCOUNT -->
            <div class="spec-section">
                <div class="spec-section-title">Account</div>
                <div class="spec-item"><b>Email:</b> <?php echo $asset['account_email']; ?></div>
                <div class="spec-item"><b>Password:</b> <?php echo $asset['account_password']; ?></div>
            </div>

        <?php endif; ?>

        </div>

        <!-- Actions -->
        <div class="panel-actions">
            <a href="asset_detail.php?id=<?php echo $asset['ID']; ?>" class="view-btn big-btn" style="text-decoration:none;">View Detail</a>
        </div>

    </div>
    <?php endforeach; ?>

</div><!-- end user-asset-card -->

<?php endforeach; ?>
</div><!-- end cardsWrapper -->

</div>

<script>
let debounceTimer;
let currentIndex = -1;
let visibleCards = [];

function debouncedSearch(){
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(searchCards, 250);
}

function fuzzyMatch(text, input){
  if(text.includes(input)) return true;
  return text.split(" ").some(w => w.startsWith(input) || w.includes(input));
}

function searchCards(){
    const input = document.getElementById('searchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.user-asset-card');
    let found = false;
    let firstMatch = null;

    visibleCards = [];
    currentIndex = -1;

    cards.forEach(card => {
        const text = card.innerText.toLowerCase();
        const match = input === '' || fuzzyMatch(text, input);

        /* Remove previous highlights */
        card.querySelectorAll('.spec-item, .info-item span').forEach(el => {
            el.innerHTML = el.innerHTML.replace(/<span class="highlight">(.*?)<\/span>/gi, '$1');
        });
        /* Remove highlights from name/position separately */
        card.querySelectorAll('.user-name-header .highlight-name, .user-name-header .highlight-position').forEach(el => {
            el.innerHTML = el.innerHTML.replace(/<span class="highlight">(.*?)<\/span>/gi, '$1');
        });

        /* Apply new highlights */
        if(input && match){
            card.querySelectorAll('.spec-item, .info-item span').forEach(el => {
                const elText = el.innerText.toLowerCase();
                if(fuzzyMatch(elText, input)){
                    const regex = new RegExp(`(${input})`, 'gi');
                    el.innerHTML = el.innerHTML.replace(regex, '<span class="highlight">$1</span>');
                }
            });
            /* Highlight name and position separately without touching span structure */
            const nameEl = card.querySelector('.highlight-name');
            const posEl  = card.querySelector('.highlight-position');
            if(nameEl && fuzzyMatch(nameEl.innerText.toLowerCase(), input)){
                const regex = new RegExp(`(${input})`, 'gi');
                nameEl.innerHTML = nameEl.innerHTML.replace(regex, '<span class="highlight">$1</span>');
            }
            if(posEl && fuzzyMatch(posEl.innerText.toLowerCase(), input)){
                const regex = new RegExp(`(${input})`, 'gi');
                posEl.innerHTML = posEl.innerHTML.replace(regex, '<span class="highlight">$1</span>');
            }
        }

        if(match){
            card.style.display = '';
            visibleCards.push(card);
            found = true;
            if(!firstMatch) firstMatch = card;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('noResult').style.display = found ? 'none' : 'block';
    if(firstMatch) firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function handleKey(e){
    if(visibleCards.length === 0) return;
    if(e.key === 'ArrowDown'){
        e.preventDefault();
        currentIndex = (currentIndex + 1) % visibleCards.length;
        highlightCard();
    }
    if(e.key === 'ArrowUp'){
        e.preventDefault();
        currentIndex = (currentIndex - 1 + visibleCards.length) % visibleCards.length;
        highlightCard();
    }
}

function highlightCard(){
    visibleCards.forEach(c => c.classList.remove('active-row'));
    visibleCards[currentIndex].classList.add('active-row');
    visibleCards[currentIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function switchTab(tabEl, panelId){
    const card = tabEl.closest('.user-asset-card');
    card.querySelectorAll('.device-tab').forEach(t => t.classList.remove('active'));
    card.querySelectorAll('.device-panel').forEach(p => p.classList.remove('active'));
    tabEl.classList.add('active');
    document.getElementById(panelId).classList.add('active');
}
</script>

<?php include "components/footer.php"; ?>
