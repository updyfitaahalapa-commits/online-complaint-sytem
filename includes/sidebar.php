<!-- Sidebar -->
<?php
// Determine path prefix based on file location
$path_prefix = file_exists('config/db_connect.php') ? '' : '../';
?>
<nav id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-shield-alt me-2"></i>OCMS</h3>
    </div>

    <ul class="list-unstyled components">
        <p class="text-center small text-white-50">Logged in as <?php echo htmlspecialchars($_SESSION["role"]); ?></p>
        
        <?php if($_SESSION["role"] == 'Admin'): ?>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="<?php echo $path_prefix; ?>admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <a href="<?php echo $path_prefix; ?>admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            </li>
        
        <?php elseif($_SESSION["role"] == 'Officer'): ?>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="<?php echo $path_prefix; ?>officer/index.php"><i class="fas fa-clipboard-list"></i> Assigned Cases</a>
            </li>

        <?php elseif($_SESSION["role"] == 'Citizen'): ?>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="<?php echo $path_prefix; ?>citizen/index.php"><i class="fas fa-home"></i> My Dashboard</a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'lodge_complaint.php' ? 'active' : ''; ?>">
                <a href="<?php echo $path_prefix; ?>citizen/lodge_complaint.php"><i class="fas fa-edit"></i> Lodge Complaint</a>
            </li>
        <?php endif; ?>

        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <a href="<?php echo $path_prefix; ?>profile.php"><i class="fas fa-user"></i> My Profile</a>
        </li>
    </ul>

    <div class="text-center p-3">
        <a href="<?php echo $path_prefix; ?>logout.php" class="btn btn-danger btn-sm w-100">Logout</a>
    </div>
</nav>
