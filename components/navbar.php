<nav class="navbar">
<div class="nav-container">

<button class="menu-toggle" type="button" onclick="openSidebar()">&#9776;</button>

<div class="logo">
Asset Management
</div>

<ul class="nav-links">
<li><a href="home.php">Home</a></li>
<li><a href="logout.php">Log Out</a></li>
</ul>

</div>
</nav>

<!-- Shared overlay for the sidebar drawer -->
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>

<!-- Shared sidebar navigation -->
<div id="sidebar" class="sidebar">

<button class="close-sidebar" type="button" onclick="closeSidebar()">&times;</button>

<div class="sidebar-menu">
<a href="hardware.php">Hardware</a>
<a href="add_asset.php">Add New Asset</a>
<a href="asset_list.php">Asset List</a>
<a href="users.php">User List</a>
<a href="asset_list_option2.php">Asset List 2</a>
</div>

<div class="sidebar-profile">
<a href="profile.php">Profile</a>
</div>

</div>

<script>
function openSidebar(){
document.getElementById("sidebar").classList.add("active");
document.getElementById("overlay").classList.add("active");
}

function closeSidebar(){
document.getElementById("sidebar").classList.remove("active");
document.getElementById("overlay").classList.remove("active");
}
</script>
