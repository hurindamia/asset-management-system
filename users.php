<link rel="stylesheet" href="css/style.css">
<?php include "components/navbar.php"; ?>
<?php include "config/db.php"; ?>

<style>
.add-device-btn-small{
    display:inline-block;
    margin-left:6px;
    padding:5px 10px;
    color:#fff;
    font-size:11px;
    font-weight:600;
    text-decoration:none;
    background:#2d7dd2;
    border-radius:6px;
}

.add-device-btn-small:hover{
    background:#1b5fa8;
}
</style>

<div class="container asset-container">

<h1 class="page-title">User List</h1>

<div class="table-wrapper">
<table class="hardware-table">

<thead>
<tr>
<th>No</th>
<th class="user-col">Name</th>
<th class="user-col">Position</th>
<th class="user-col">Email</th>
<th class="action-col">Devices</th>
</tr>
</thead>

<?php
/*
|--------------------------------------------------------------------------
| Users table
|--------------------------------------------------------------------------
| Each row shows the user's summary plus quick links to their current
| devices and a shortcut to add another asset.
*/

$query = "
SELECT
    users.*,
    GROUP_CONCAT(DISTINCT assets.asset_type) AS devices
FROM users
LEFT JOIN assets ON users.user_id = assets.user_id
GROUP BY users.user_id
ORDER BY users.user_id DESC
";

$result = mysqli_query($conn, $query);
$rowIndex = 0;
$number = 1;

while($row = mysqli_fetch_assoc($result)){
    $rowClass = ($rowIndex % 2 === 0) ? "row-light" : "row-dark";

    echo "<tbody>";
    echo "<tr class='{$rowClass}'>";
    echo "<td>" . $number++ . "</td>";
    echo "<td class='user-col name-col'>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td class='user-col'>" . htmlspecialchars($row['position']) . "</td>";
    echo "<td class='user-col'>" . htmlspecialchars($row['email_id']) . "</td>";
    echo "<td class='action-col'>";

    if(!empty($row['devices'])){
        $devices = explode(",", $row['devices']);

        foreach($devices as $device){
            $device = trim($device);

            if($device === "Desktop"){
                echo "<a href='asset_detail.php?type=Desktop&user_id=" . (int)$row['user_id'] . "' class='device-btn pc'>PC</a>";
            } elseif($device === "Laptop"){
                echo "<a href='asset_detail.php?type=Laptop&user_id=" . (int)$row['user_id'] . "' class='device-btn laptop'>Laptop</a>";
            } elseif($device === "iPad"){
                echo "<a href='asset_detail.php?type=iPad&user_id=" . (int)$row['user_id'] . "' class='device-btn ipad'>iPad</a>";
            } elseif($device === "Phone"){
                echo "<a href='asset_detail.php?type=Phone&user_id=" . (int)$row['user_id'] . "' class='device-btn phone'>Phone</a>";
            }
        }
    } else {
        echo "<span style='color:#999;'>No Device</span>";
    }

    echo "<a href='add_asset.php?user_id=" . (int)$row['user_id'] . "' class='add-device-btn-small'>+ Add</a>";
    echo "</td>";
    echo "</tr>";
    echo "</tbody>";

    $rowIndex++;
}
?>

</table>
</div>
</div>

<?php include "components/footer.php"; ?>
