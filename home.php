<link rel="stylesheet" href="css/style.css">

<?php include "components/navbar.php"; ?>
<?php include "config/db.php"; ?>

<?php
/* ── STATS QUERIES ── */

// Total users
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];

// Total assets
$totalAssets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM assets"))['c'];

// Device type breakdown
$typeResult = mysqli_query($conn, "SELECT asset_type, COUNT(*) AS c FROM assets GROUP BY asset_type");
$typeCounts = ['Desktop' => 0, 'Laptop' => 0, 'iPad' => 0, 'Phone' => 0];
while($t = mysqli_fetch_assoc($typeResult)){
    $typeCounts[$t['asset_type']] = intval($t['c']);
}

// Recent 5 assets
$recentResult = mysqli_query($conn, "
    SELECT assets.asset_id, assets.asset_type, assets.pc_model, users.name
    FROM assets
    LEFT JOIN users ON assets.user_id = users.user_id
    ORDER BY assets.asset_id DESC
    LIMIT 5
");
$recentAssets = [];
while($r = mysqli_fetch_assoc($recentResult)) $recentAssets[] = $r;

/* ── TOP USERS (MOST ASSETS) ── */
$topUsersResult = mysqli_query($conn, "
SELECT users.name, COUNT(assets.asset_id) AS total
FROM users
LEFT JOIN assets ON users.user_id = assets.user_id
GROUP BY users.user_id
ORDER BY total DESC
LIMIT 5
");

$topUsers = [];
while($u = mysqli_fetch_assoc($topUsersResult)) $topUsers[] = $u;


/* ── DEVICE PER USER DISTRIBUTION ── */
$deviceDistResult = mysqli_query($conn, "
SELECT COUNT(*) AS total_devices, COUNT(DISTINCT user_id) AS total_users
FROM assets
");

$deviceStats = mysqli_fetch_assoc($deviceDistResult);

$assignedUsers = intval($deviceStats['total_users'] ?? 0);
$totalTrackedDevices = intval($deviceStats['total_devices'] ?? 0);
$coveragePct = $totalUsers > 0 ? round(($assignedUsers / $totalUsers) * 100) : 0;
$avgDevicesPerUser = $assignedUsers > 0 ? round($totalTrackedDevices / $assignedUsers, 1) : 0;
$recentCount = count($recentAssets);
$topUserName = $topUsers[0]['name'] ?? 'No assignments yet';
$topUserTotal = intval($topUsers[0]['total'] ?? 0);

$leadingType = 'No devices yet';
$leadingTypeCount = 0;
foreach($typeCounts as $type => $count){
    if($count > $leadingTypeCount){
        $leadingType = $type;
        $leadingTypeCount = $count;
    }
}
?>

<style>
/* ── Dashboard Layout ── */
.dashboard-wrap {
    padding: 0 0 40px;
}

.dash-greeting {
    margin-bottom: 28px;
}

.dash-greeting h2 {
    font-size: 1.6rem;
    font-weight: 700;
    color: #0b2a4a;
    margin: 0 0 4px;
}

.dash-greeting p {
    color: #888;
    font-size: 0.95rem;
    margin: 0;
}

/* ── Stat Cards ── */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
    border-top: 4px solid transparent;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}

.stat-card.users   { border-top-color: #4f8ef7; }
.stat-card.assets  { border-top-color: #f7a64f; }
.stat-card.desktop { border-top-color: #2d6a2d; }
.stat-card.laptop  { border-top-color: #1a5276; }
.stat-card.ipad    { border-top-color: #884ea0; }
.stat-card.phone   { border-top-color: #c0392b; }

.stat-icon {
    font-size: 2rem;
    margin-bottom: 8px;
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #0b2a4a;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.78rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ── Charts Row ── */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 28px;
}

@media(max-width: 900px){
    .charts-row { grid-template-columns: 1fr; }
}

.chart-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    padding: 20px;
}

.chart-card h3 {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
    margin: 0 0 16px;
    font-weight: 700;
}

.chart-wrap {
    position: relative;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ── Bar Chart (OS / Antivirus) ── */
.bar-list {
    width: 100%;
}

.bar-item {
    margin-bottom: 12px;
}

.bar-label-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.82rem;
    color: #555;
    margin-bottom: 4px;
}

.bar-track {
    background: #f0f0f0;
    border-radius: 99px;
    height: 8px;
    overflow: hidden;
}

.bar-fill {
    height: 8px;
    border-radius: 99px;
    background: linear-gradient(90deg, #4f8ef7, #0b2a4a);
    transition: width 1s ease;
}

.bar-fill.green  { background: linear-gradient(90deg, #27ae60, #1a5276); }
.bar-fill.orange { background: linear-gradient(90deg, #f39c12, #c0392b); }

/* ── Bottom Row ── */
.bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media(max-width: 700px){
    .bottom-row { grid-template-columns: 1fr; }
}

/* ── Recent Assets ── */
.recent-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    padding: 20px;
}

.recent-card h3 {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
    margin: 0 0 16px;
    font-weight: 700;
}

.recent-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;
    border-radius: 6px;
    padding: 8px;
}

.recent-item:last-child { border-bottom: none; }
.recent-item:hover { background: #f8f9fb; }

.recent-badge {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.badge-desktop { background: #e8f4e8; }
.badge-laptop  { background: #e8f0f8; }
.badge-ipad    { background: #f3e8f8; }
.badge-phone   { background: #fce8e8; }

.recent-info strong {
    display: block;
    font-size: 0.88rem;
    color: #222;
}

.recent-info span {
    font-size: 0.78rem;
    color: #999;
}

/* ── Quick Links ── */
.quicklinks-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    padding: 20px;
    margin-bottom: 20px;
}

.quicklinks-card h3 {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
    margin: 0 0 16px;
    font-weight: 700;
}

.quicklink-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.quicklink-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 20px 12px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.88rem;
    transition: transform 0.15s, box-shadow 0.15s;
}

.quicklink-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.quicklink-btn .ql-icon { font-size: 1.6rem; }

.ql-hardware { background: #e8f4e8; color: #2d6a2d; }
.ql-users    { background: #e8f0f8; color: #1a5276; }
.ql-assets   { background: #fef3e2; color: #b7680a; }
.ql-add      { background: #fce8e8; color: #c0392b; }

/* ── Animate counters ── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

.stat-card { animation: fadeUp 0.4s ease both; }
.stat-card:nth-child(1){ animation-delay: 0.05s; }
.stat-card:nth-child(2){ animation-delay: 0.10s; }
.stat-card:nth-child(3){ animation-delay: 0.15s; }
.stat-card:nth-child(4){ animation-delay: 0.20s; }
.stat-card:nth-child(5){ animation-delay: 0.25s; }
.stat-card:nth-child(6){ animation-delay: 0.30s; }

/* Dashboard refresh */
.dashboard-shell{
    position:relative;
    overflow:hidden;
    background:
        radial-gradient(circle at top right, rgba(18,62,107,0.12), transparent 30%),
        radial-gradient(circle at left center, rgba(4,106,117,0.08), transparent 24%),
        linear-gradient(180deg, rgba(255,255,255,0.98), rgba(241,247,252,0.98));
    border:1px solid rgba(11,42,74,0.08);
    box-shadow:0 24px 48px rgba(11,42,74,0.12);
    padding:28px;
}

.dashboard-shell::before{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(90deg, rgba(255,255,255,0.30), rgba(255,255,255,0));
    pointer-events:none;
}

.dashboard-shell > *{
    position:relative;
    z-index:1;
}

.dashboard-hero{
    display:grid;
    grid-template-columns:minmax(0,1.5fr) minmax(280px,0.9fr);
    gap:20px;
    margin-bottom:24px;
}

.hero-main{
    padding:28px;
    border-radius:22px;
    background:linear-gradient(140deg, rgba(11,42,74,0.98), rgba(18,62,107,0.92));
    color:#fff;
    box-shadow:0 18px 34px rgba(11,42,74,0.22);
}

.hero-kicker{
    display:inline-flex;
    align-items:center;
    padding:8px 12px;
    border-radius:999px;
    background:rgba(255,255,255,0.10);
    color:rgba(255,255,255,0.82);
    text-transform:uppercase;
    letter-spacing:0.14em;
    font-size:0.72rem;
    font-weight:700;
}

.dashboard-title{
    text-align:left;
    color:#fff;
    margin:14px 0 0;
}

.dashboard-title::after{
    margin:12px 0 0;
    background:rgba(255,255,255,0.85);
}

.hero-main .dash-greeting{
    margin:16px 0 0;
}

.hero-main .dash-greeting p{
    color:rgba(255,255,255,0.76);
    font-size:1rem;
    line-height:1.7;
}

.hero-pill-row{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-top:24px;
}

.hero-pill{
    min-width:150px;
    padding:14px 16px;
    border-radius:16px;
    background:rgba(255,255,255,0.10);
}

.hero-pill span{
    display:block;
    color:rgba(255,255,255,0.70);
    text-transform:uppercase;
    letter-spacing:0.08em;
    font-size:0.72rem;
    margin-bottom:6px;
}

.hero-pill strong{
    font-size:1.2rem;
    font-weight:800;
}

.hero-side{
    display:grid;
    gap:14px;
}

.hero-side-card{
    padding:18px;
    border-radius:20px;
    background:linear-gradient(180deg, #ffffff, #eef5fb);
    border:1px solid rgba(11,42,74,0.08);
    box-shadow:0 12px 24px rgba(11,42,74,0.08);
}

.hero-side-card span{
    display:block;
    color:#6c8196;
    text-transform:uppercase;
    letter-spacing:0.10em;
    font-size:0.72rem;
    margin-bottom:8px;
    font-weight:700;
}

.hero-side-card strong{
    display:block;
    color:#0b2a4a;
    font-size:1.2rem;
    margin-bottom:6px;
}

.hero-side-card p{
    margin:0;
    color:#5f7488;
    line-height:1.5;
}

.stat-grid{
    grid-template-columns:repeat(6, minmax(0, 1fr));
    gap:16px;
}

.stat-card,
.chart-card,
.recent-card,
.quicklinks-card{
    border:1px solid rgba(11,42,74,0.08);
    box-shadow:0 16px 30px rgba(11,42,74,0.08);
}

.stat-card{
    position:relative;
    overflow:hidden;
    background:linear-gradient(180deg, #f4f9ff, #e7f0fb);
    border-radius:18px;
    padding:22px 18px;
    text-align:left;
    border:1px solid rgba(120,156,196,0.25);
    box-shadow:0 10px 22px rgba(84,124,166,0.12);
    transition:transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.stat-card::after{
    content:none;
}

.stat-card:hover{
    transform:translateY(-4px);
    box-shadow:0 16px 28px rgba(84,124,166,0.18);
    background:linear-gradient(180deg, #f8fbff, #eaf3fd);
}

.stat-icon{
    width:46px;
    height:46px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(183, 206, 229, 0.42);
    margin:0 0 14px;
}

.stat-card.users{
    background:linear-gradient(180deg, #f4f9ff, #e5eefb);
    border-top-color:#7ea5d3;
}

.stat-card.assets{
    background:linear-gradient(180deg, #f3f8ff, #e6f1fb);
    border-top-color:#8fb4dd;
}

.stat-card.desktop{
    background:linear-gradient(180deg, #f2f8fd, #e3eef7);
    border-top-color:#7da0c4;
}

.stat-card.laptop{
    background:linear-gradient(180deg, #f4f9ff, #e2edf9);
    border-top-color:#6f97c5;
}

.stat-card.ipad{
    background:linear-gradient(180deg, #f5f9ff, #e8f1fb);
    border-top-color:#9db2dc;
}

.stat-card.phone{
    background:linear-gradient(180deg, #f3f8ff, #e4eef8);
    border-top-color:#84a7cf;
}

.stat-number{
    font-size:2.15rem;
    margin-bottom:8px;
}

.stat-label{
    font-size:0.76rem;
    letter-spacing:0.12em;
    color:#70859a;
}

.quicklinks-card,
.chart-card,
.recent-card{
    border-radius:22px;
    background:linear-gradient(180deg, rgba(255,255,255,0.98), rgba(243,248,252,0.98));
    padding:22px;
}

.quicklinks-card h3,
.chart-card h3,
.recent-card h3{
    color:#0b2a4a;
    font-size:0.86rem;
    letter-spacing:0.12em;
    margin-bottom:18px;
}

.quicklink-btn{
    border-radius:16px;
    padding:22px 14px;
    border:1px solid rgba(11,42,74,0.06);
    box-shadow:inset 0 1px 0 rgba(255,255,255,0.6);
}

.charts-row{
    align-items:stretch;
    gap:18px;
}

.bottom-row{
    grid-template-columns:1fr;
}

.chart-wrap{
    position:relative;
    min-height:220px;
}

.donut-center{
    position:absolute;
    inset:50% auto auto 50%;
    transform:translate(-50%, -50%);
    display:flex;
    flex-direction:column;
    align-items:center;
    pointer-events:none;
}

.donut-center strong{
    color:#0b2a4a;
    font-size:1.85rem;
    line-height:1;
}

.donut-center span{
    margin-top:6px;
    color:#70859a;
    font-size:0.74rem;
    text-transform:uppercase;
    letter-spacing:0.10em;
}

.device-legend{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:10px;
    margin-top:18px;
}

.legend-pill{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
    padding:10px 12px;
    border-radius:14px;
    background:rgba(255,255,255,0.76);
    border:1px solid rgba(11,42,74,0.06);
    color:#53697c;
    font-size:0.86rem;
    font-weight:700;
}

.legend-pill strong{
    color:#0b2a4a;
}

.bar-track{
    height:10px;
    background:#e4edf6;
}

.overview-stack{
    display:grid;
    gap:12px;
}

.overview-box{
    padding:14px 16px;
    border-radius:16px;
    background:#f4f8fc;
    border:1px solid rgba(11,42,74,0.06);
}

.overview-box strong{
    display:block;
    color:#0b2a4a;
    font-size:1.5rem;
    margin-top:4px;
}

.overview-box span{
    color:#607487;
    font-size:0.78rem;
    letter-spacing:0.08em;
    text-transform:uppercase;
    font-weight:700;
}

.recent-item{
    border:1px solid rgba(11,42,74,0.06);
    border-radius:16px;
    padding:12px;
}

.recent-badge{
    width:44px;
    height:44px;
    border-radius:14px;
}

.recent-info strong{
    color:#0b2a4a;
    font-size:0.92rem;
}

.recent-info span{
    color:#5f7488;
    font-size:0.82rem;
}

@media(max-width: 1250px){
    .stat-grid{
        grid-template-columns:repeat(3, minmax(0, 1fr));
    }
}

@media(max-width: 980px){
    .dashboard-hero,
    .charts-row,
    .bottom-row{
        grid-template-columns:1fr;
    }
}

@media(max-width: 680px){
    .dashboard-shell{
        padding:20px;
    }

    .stat-grid,
    .quicklink-grid,
    .device-legend{
        grid-template-columns:1fr;
    }
}
</style>

<div class="container asset-container dashboard-shell">

<div class="dashboard-hero">
    <div class="hero-main">
        <span class="hero-kicker">Asset Command Center</span>
        <h1 class="page-title dashboard-title">Dashboard</h1>
        <div class="dash-greeting">
            <p>Track inventory balance, user coverage, and recent device activity from one place while keeping the same navy and soft-blue feel as the rest of the site.</p>
        </div>
        <div class="hero-pill-row">
            <div class="hero-pill">
                <span>Coverage</span>
                <strong><?php echo $coveragePct; ?>%</strong>
            </div>
            <div class="hero-pill">
                <span>Avg Assets/User</span>
                <strong><?php echo number_format($avgDevicesPerUser, 1); ?></strong>
            </div>
            <div class="hero-pill">
                <span>Recent Adds</span>
                <strong><?php echo $recentCount; ?></strong>
            </div>
        </div>
    </div>

    <div class="hero-side">
        <div class="hero-side-card">
            <span>Top User</span>
            <strong><?php echo htmlspecialchars($topUserName); ?></strong>
            <p><?php echo $topUserTotal; ?> assigned device<?php echo $topUserTotal === 1 ? '' : 's'; ?> right now.</p>
        </div>
        <div class="hero-side-card">
            <span>Leading Category</span>
            <strong><?php echo htmlspecialchars($leadingType); ?></strong>
            <p><?php echo $leadingTypeCount; ?> device<?php echo $leadingTypeCount === 1 ? '' : 's'; ?> currently lead the inventory mix.</p>
        </div>
        <div class="hero-side-card">
            <span>Assigned Users</span>
            <strong><?php echo $assignedUsers; ?></strong>
            <p>Out of <?php echo $totalUsers; ?> total users in the system.</p>
        </div>
    </div>
</div>


<!-- ── STAT CARDS ── -->
<div class="stat-grid">

    <div class="stat-card users">
        <div class="stat-icon">👥</div>
        <div class="stat-number" data-target="<?php echo $totalUsers; ?>">0</div>
        <div class="stat-label">Total Users</div>
    </div>

    <div class="stat-card assets">
        <div class="stat-icon">📦</div>
        <div class="stat-number" data-target="<?php echo $totalAssets; ?>">0</div>
        <div class="stat-label">Total Assets</div>
    </div>

    <div class="stat-card desktop">
        <div class="stat-icon">🖥</div>
        <div class="stat-number" data-target="<?php echo $typeCounts['Desktop']; ?>">0</div>
        <div class="stat-label">Desktops</div>
    </div>

    <div class="stat-card laptop">
        <div class="stat-icon">💻</div>
        <div class="stat-number" data-target="<?php echo $typeCounts['Laptop']; ?>">0</div>
        <div class="stat-label">Laptops</div>
    </div>

    <div class="stat-card ipad">
        <div class="stat-icon">📱</div>
        <div class="stat-number" data-target="<?php echo $typeCounts['iPad']; ?>">0</div>
        <div class="stat-label">iPads</div>
    </div>

    <div class="stat-card phone">
        <div class="stat-icon">📞</div>
        <div class="stat-number" data-target="<?php echo $typeCounts['Phone']; ?>">0</div>
        <div class="stat-label">Phones</div>
    </div>

    </div>

    <!-- Quick Links -->
    <div class="quicklinks-card">
        <h3>Quick Access</h3>
        <div class="quicklink-grid">
            <a href="hardware.php" class="quicklink-btn ql-hardware">
                <span class="ql-icon">🖥</span>
                Hardware
            </a>
            <a href="users.php" class="quicklink-btn ql-users">
                <span class="ql-icon">👥</span>
                Users
            </a>
            <a href="asset_list.php" class="quicklink-btn ql-assets">
                <span class="ql-icon">📦</span>
                Assets
            </a>
            <a href="add_asset.php" class="quicklink-btn ql-add">
                <span class="ql-icon">➕</span>
                Add Asset
            </a>
        </div>
    </div>


<!-- ── CHARTS ROW ── -->
<div class="charts-row">

    <!-- Donut — device types -->
    <div class="chart-card">
        <h3>Device Distribution</h3>
        <div class="chart-wrap">
            <canvas id="donutChart"></canvas>
            <div class="donut-center">
                <strong><?php echo $totalAssets; ?></strong>
                <span>Total Assets</span>
            </div>
        </div>
        <div class="device-legend">
            <div class="legend-pill"><span>Desktop</span><strong><?php echo $typeCounts['Desktop']; ?></strong></div>
            <div class="legend-pill"><span>Laptop</span><strong><?php echo $typeCounts['Laptop']; ?></strong></div>
            <div class="legend-pill"><span>iPad</span><strong><?php echo $typeCounts['iPad']; ?></strong></div>
            <div class="legend-pill"><span>Phone</span><strong><?php echo $typeCounts['Phone']; ?></strong></div>
        </div>
    </div>

    <!-- 🔥 TOP USERS -->
<div class="chart-card">
    <h3>Top Users (Most Assets)</h3>

    <div class="bar-list">
    <?php
    $max = max(array_column($topUsers, 'total')) ?: 1;

    foreach($topUsers as $index => $u):
        $pct = round($u['total'] / $max * 100);
        $toneClass = 'tone-' . (($index % 4) + 1);
    ?>
    <div class="bar-item">
        <div class="bar-label-row">
            <span><?php echo htmlspecialchars($u['name']); ?></span>
            <span><?php echo $u['total']; ?> devices</span>
        </div>
        <div class="bar-track">
            <div class="bar-fill <?php echo $toneClass; ?>" style="width:0%" data-width="<?php echo $pct; ?>%"></div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if(empty($topUsers)): ?>
        <p style="color:#ccc;text-align:center;margin-top:40px;">No data yet</p>
    <?php endif; ?>
    </div>
</div>


<!-- 🔥 DEVICE STATS -->
<div class="chart-card">
    <h3>Device Overview</h3>

    <div class="overview-stack">

        <div class="overview-box">
            <span>Total Devices</span>
            <strong>
                <?php echo $deviceStats['total_devices'] ?? 0; ?>
            </strong>
        </div>

        <div class="overview-box">
            <span>Total Users Assigned</span>
            <strong>
                <?php echo $deviceStats['total_users'] ?? 0; ?>
            </strong>
        </div>

        <div class="overview-box">
            <span>Most Common Device</span>
            <strong><?php echo htmlspecialchars($leadingType); ?></strong>
        </div>

    </div>
</div>

</div>

<!-- ── BOTTOM ROW ── -->
<div class="bottom-row">

    <!-- Recent Assets -->
    <div class="recent-card">
        <h3>Recently Added Assets</h3>
        <?php if(empty($recentAssets)): ?>
            <p style="color:#ccc;text-align:center;padding:20px 0;">No assets yet</p>
        <?php endif; ?>
        <?php foreach($recentAssets as $ra):
            $badgeClass = 'badge-'.strtolower($ra['asset_type']);
            $icon = match($ra['asset_type']){
                'Desktop' => '🖥',
                'Laptop'  => '💻',
                'iPad'    => '📱',
                'Phone'   => '📞',
                default   => '📦'
            };
        ?>
        <a href="asset_detail.php?id=<?php echo $ra['asset_id']; ?>" class="recent-item">
            <div class="recent-badge <?php echo $badgeClass; ?>"><?php echo $icon; ?></div>
            <div class="recent-info">
                <strong><?php echo htmlspecialchars($ra['name']); ?></strong>
                <span><?php echo htmlspecialchars($ra['asset_type']); ?> — <?php echo htmlspecialchars($ra['pc_model'] ?? 'N/A'); ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>


</div>

</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<script>
/* ── Donut chart ── */
const ctx = document.getElementById('donutChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Desktop', 'Laptop', 'iPad', 'Phone'],
        datasets: [{
            data: [
                <?php echo $typeCounts['Desktop']; ?>,
                <?php echo $typeCounts['Laptop']; ?>,
                <?php echo $typeCounts['iPad']; ?>,
                <?php echo $typeCounts['Phone']; ?>
            ],
            backgroundColor: ['#2d6a2d','#1a5276','#884ea0','#c0392b'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        cutout: '72%',
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

/* ── Animate bar fills ── */
document.querySelectorAll('.bar-fill').forEach(bar => {
    const target = bar.getAttribute('data-width');
    setTimeout(() => { bar.style.width = target; }, 300);
});

/* ── Animate counters ── */
document.querySelectorAll('.stat-number').forEach(el => {
    const target = parseInt(el.getAttribute('data-target'));
    if(target === 0){ el.textContent = '0'; return; }
    let current = 0;
    const step = Math.ceil(target / 30);
    const timer = setInterval(() => {
        current += step;
        if(current >= target){
            current = target;
            clearInterval(timer);
        }
        el.textContent = current;
    }, 40);
});
</script>

<?php include "components/footer.php"; ?>
