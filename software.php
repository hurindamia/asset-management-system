<link rel="stylesheet" href="css/style.css">

<?php include "components/navbar.php"; ?>
<?php include "config/db.php"; ?>

<?php
$softwareStats = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COUNT(*) AS total_records, COUNT(DISTINCT software_name) AS unique_titles FROM software"
    )
);

$trackedSoftware = [];
$trackedResult = mysqli_query($conn,
    "SELECT software_name, COUNT(*) AS total_uses
     FROM software
     WHERE TRIM(software_name) <> ''
     GROUP BY software_name
     ORDER BY total_uses DESC, software_name ASC
     LIMIT 6"
);
while($row = mysqli_fetch_assoc($trackedResult)) $trackedSoftware[] = $row;

function findSoftwareIcon(string $slug): ?string {
    $baseDir = __DIR__ . "/assets/software-icons/";
    $baseUrl = "assets/software-icons/";
    foreach(['svg','png','webp','jpg','jpeg'] as $ext){
        if(file_exists($baseDir . $slug . "." . $ext))
            return $baseUrl . $slug . "." . $ext;
    }
    return null;
}

$softwareCatalog = [
    ['name'=>'AutoCAD',    'slug'=>'autocad',    'abbr'=>'AC', 'tag'=>'2D and 3D drafting',       'tone'=>'cad'],
    ['name'=>'SketchUp',   'slug'=>'sketchup',   'abbr'=>'SU', 'tag'=>'Fast concept modeling',    'tone'=>'model'],
    ['name'=>'SketchUp 2', 'slug'=>'revit',       'abbr'=>'RV', 'tag'=>'Concept Modeling',         'tone'=>'bim'],
    ['name'=>'Lumion',     'slug'=>'lumion',     'abbr'=>'LU', 'tag'=>'Real-time rendering',      'tone'=>'render'],
    ['name'=>'3ds Max',    'slug'=>'3ds-max',    'abbr'=>'3D', 'tag'=>'Detailed visualization',   'tone'=>'studio'],
    ['name'=>'Rhino',      'slug'=>'rhino',      'abbr'=>'RH', 'tag'=>'Freeform modeling',        'tone'=>'shape'],
    ['name'=>'V-Ray',      'slug'=>'v-ray',      'abbr'=>'VR', 'tag'=>'Photoreal renderer',       'tone'=>'light'],
    ['name'=>'Enscape',    'slug'=>'enscape',    'abbr'=>'EN', 'tag'=>'Live walkthroughs',        'tone'=>'walk'],
    ['name'=>'Twinmotion', 'slug'=>'twinmotion', 'abbr'=>'TM', 'tag'=>'Presentation scenes',      'tone'=>'scene'],
    ['name'=>'Navisworks', 'slug'=>'navisworks', 'abbr'=>'NW', 'tag'=>'Coordination review',      'tone'=>'coord'],
    ['name'=>'Photoshop',  'slug'=>'photoshop',  'abbr'=>'PS', 'tag'=>'Boards and image editing', 'tone'=>'creative'],
    ['name'=>'Illustrator','slug'=>'illustrator','abbr'=>'AI', 'tag'=>'Diagrams and graphics',    'tone'=>'graphic'],
];

foreach($softwareCatalog as &$app){
    $app['icon_path'] = findSoftwareIcon($app['slug']);
}
unset($app);
?>

<style>
/* ── Page wrapper ── */
.software-hub {
    padding: 24px 24px 32px;
    background:
        radial-gradient(circle at top right, rgba(129,178,255,0.16), transparent 30%),
        linear-gradient(180deg, rgba(255,255,255,0.98), rgba(245,249,253,0.98));
    border: 1px solid rgba(11,42,74,0.08);
    box-shadow: 0 22px 40px rgba(11,42,74,0.10);
}

/* ── TOP ROW: hero card + software grid side by side ── */
.software-top-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr);
    gap: 18px;
    margin-bottom: 18px;
    align-items: start;
}

/* Hero card */
.software-hero-copy {
    padding: 24px 20px;
    border-radius: 22px;
    background:
        radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 28%),
        linear-gradient(135deg, rgba(12,46,81,0.98), rgba(30,81,130,0.93) 56%, rgba(98,116,187,0.88));
    color: #fff;
    height: 100%;
    box-sizing: border-box;
}

.software-kicker {
    display: inline-flex;
    align-items: center;
    padding: 7px 13px;
    margin-bottom: 14px;
    border-radius: 999px;
    background: rgba(255,255,255,0.12);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.software-hub .page-title {
    margin: 0 0 10px;
    color: #fff;
    text-align: left;
    font-size: 2.1rem;
}

.software-hub .page-title::after { display: none; }

.software-intro {
    margin: 0;
    color: rgba(255,255,255,0.82);
    font-size: 0.92rem;
    line-height: 1.55;
}

.software-stat-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.software-stat-chip {
    flex: 1;
    min-width: 110px;
    padding: 12px 14px;
    border-radius: 14px;
    background: rgba(255,255,255,0.11);
    border: 1px solid rgba(255,255,255,0.12);
}

.software-stat-chip span {
    display: block;
    color: rgba(255,255,255,0.68);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.software-stat-chip strong {
    display: block;
    margin-top: 6px;
    font-size: 1.6rem;
    font-weight: 800;
}

/* Software grid panel */
.software-screen {
    padding: 20px 18px 18px;
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(245,249,254,0.97), rgba(233,240,248,0.97));
    border: 1px solid rgba(11,42,74,0.08);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.65);
}

.software-screen-top {
    margin-bottom: 16px;
}

.software-screen-title {
    color: #0b2a4a;
    font-size: 1rem;
    font-weight: 700;
}

.software-screen-sub {
    color: #6f8193;
    font-size: 0.84rem;
    margin-top: 2px;
}

.software-app-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px 12px;
}

.software-app-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 14px 8px 12px;
    border-radius: 18px;
    background: rgba(255,255,255,0.5);
    border: 1px solid rgba(11,42,74,0.06);
    transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
    cursor: default;
}

.software-app-tile:hover {
    transform: translateY(-4px);
    background: rgba(255,255,255,0.8);
    box-shadow: 0 8px 20px rgba(11,42,74,0.10);
}

.software-icon {
    display: grid;
    place-items: center;
    width: 72px;
    height: 72px;
    border-radius: 20px;
    color: #fff;
    font-size: 1.3rem;
    font-weight: 800;
    background: #fff;
    box-shadow: 0 8px 16px rgba(11,42,74,0.16);
    overflow: hidden;
}

.software-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 20px;
    display: block;
}

.software-app-name {
    color: #0b2a4a;
    font-size: 0.85rem;
    font-weight: 700;
    text-align: center;
}

.software-app-tag {
    color: #6d7e8f;
    font-size: 0.75rem;
    text-align: center;
    line-height: 1.35;
}

/* ── BOTTOM ROW ── */
.software-bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.software-panel {
    padding: 18px;
    border-radius: 20px;
    background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(246,250,254,0.96));
    border: 1px solid rgba(11,42,74,0.07);
}

.software-panel h3 {
    margin: 0 0 14px;
    color: #0b2a4a;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.software-tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.software-tag {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    color: #1a4a6b;
    font-size: 0.82rem;
    font-weight: 600;
    background: #edf4fb;
    border: 1px solid rgba(11,42,74,0.08);
    border-radius: 999px;
}

.tracked-list { display: grid; gap: 9px; }

.tracked-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 11px 14px;
    border-radius: 13px;
    background: #f7fbff;
    border: 1px solid rgba(11,42,74,0.06);
}

.tracked-item strong { color: #0b2a4a; font-size: 0.9rem; }
.tracked-item span   { color: #6d7f90; font-size: 0.82rem; white-space: nowrap; }

.tracked-empty {
    padding: 16px;
    color: #6d8092;
    background: #f7fbff;
    border-radius: 14px;
    border: 1px dashed rgba(11,42,74,0.12);
    text-align: center;
    font-size: 0.88rem;
}

/* ── Responsive ── */
@media (max-width: 1050px) {
    .software-app-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 860px) {
    .software-top-row {
        grid-template-columns: 1fr;
    }
    .software-hero-copy { height: auto; }
    .software-app-grid { grid-template-columns: repeat(4, 1fr); }
}

@media (max-width: 640px) {
    .software-app-grid    { grid-template-columns: repeat(3, 1fr); }
    .software-bottom-row  { grid-template-columns: 1fr; }
    .software-hub         { padding: 16px 14px 20px; }
    .software-hub .page-title { font-size: 1.75rem; }
}

@media (max-width: 420px) {
    .software-app-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="container asset-container software-hub">

    <!-- ── TOP ROW ── -->
    <div class="software-top-row">

        <!-- Hero card -->
        <div class="software-hero-copy">
            <span class="software-kicker">Software Hub</span>
            <h1 class="page-title">Software</h1>
            <p class="software-intro">
                A software overview for the company workflow. Highlights the tools teams use for drafting, BIM, visualization, presentations, and design support.
            </p>
            <div class="software-stat-row">
                <div class="software-stat-chip">
                    <span>Total Records</span>
                    <strong><?php echo (int)($softwareStats['total_records'] ?? 0); ?></strong>
                </div>
                <div class="software-stat-chip">
                    <span>Unique Titles</span>
                    <strong><?php echo (int)($softwareStats['unique_titles'] ?? 0); ?></strong>
                </div>
            </div>
        </div>

        <!-- Software grid -->
        <div class="software-screen">
            <div class="software-screen-top">
                <div class="software-screen-title">Software</div>
                <div class="software-screen-sub">List of software used by staff at the company.</div>
            </div>

            <div class="software-app-grid">
                <?php foreach($softwareCatalog as $app): ?>
                <div class="software-app-tile">
                    <div class="software-icon">
                        <?php if(!empty($app['icon_path'])): ?>
                            <img src="<?php echo htmlspecialchars($app['icon_path']); ?>"
                                 alt="<?php echo htmlspecialchars($app['name']); ?>">
                        <?php else: ?>
                            <?php echo htmlspecialchars($app['abbr']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="software-app-name"><?php echo htmlspecialchars($app['name']); ?></div>
                    <div class="software-app-tag"><?php echo htmlspecialchars($app['tag']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- end top row -->

    <!-- ── BOTTOM ROW ── -->
    <div class="software-bottom-row">
        <div class="software-panel">
            <h3>Tracked In Your System</h3>
            <?php if(empty($trackedSoftware)): ?>
                <div class="tracked-empty">No software has been recorded yet.</div>
            <?php else: ?>
                <div class="tracked-list">
                    <?php foreach($trackedSoftware as $tracked): ?>
                    <div class="tracked-item">
                        <strong><?php echo htmlspecialchars($tracked['software_name']); ?></strong>
                        <span><?php echo (int)$tracked['total_uses']; ?> record<?php echo (int)$tracked['total_uses'] === 1 ? '' : 's'; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- end bottom row -->

</div>

<?php include "components/footer.php"; ?>