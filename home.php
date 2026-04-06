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
</style>

<div class="container asset-container">

<h1 class="page-title">Dashboard</h1>
<div class="dash-greeting">
    <p>Here's a quick overview of your assets.</p>
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
        </div>
    </div>

    <!-- 🔥 TOP USERS -->
<div class="chart-card">
    <h3>Top Users (Most Assets)</h3>

    <div class="bar-list">
    <?php
    $max = max(array_column($topUsers, 'total')) ?: 1;

    foreach($topUsers as $u):
        $pct = round($u['total'] / $max * 100);
    ?>
    <div class="bar-item">
        <div class="bar-label-row">
            <span><?php echo htmlspecialchars($u['name']); ?></span>
            <span><?php echo $u['total']; ?> devices</span>
        </div>
        <div class="bar-track">
            <div class="bar-fill green" style="width:0%" data-width="<?php echo $pct; ?>%"></div>
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

    <div style="display:flex;flex-direction:column;gap:16px;margin-top:10px;">

        <div style="background:#f5f7fb;padding:12px;border-radius:10px;">
            <strong>Total Devices</strong>
            <div style="font-size:1.5rem;font-weight:700;color:#0b2a4a;">
                <?php echo $deviceStats['total_devices'] ?? 0; ?>
            </div>
        </div>

        <div style="background:#f5f7fb;padding:12px;border-radius:10px;">
            <strong>Total Users Assigned</strong>
            <div style="font-size:1.5rem;font-weight:700;color:#0b2a4a;">
                <?php echo $deviceStats['total_users'] ?? 0; ?>
            </div>
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
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 8
        }]
    },
    options: {
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 12, font: { size: 11 } }
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