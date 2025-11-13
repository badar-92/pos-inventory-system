<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>POS System</h1>
            <p><?php echo ucfirst($_SESSION['role']); ?> Panel</p>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'cashier'): ?>
                <li>
                    <a href="pos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cash-register"></i>
                        <span>Point of Sale</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'cashier'): ?>
                <li>
                    <a href="return_exchange.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'return_exchange.php' ? 'active' : ''; ?>">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Return & Exchange</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'): ?>
                <li>
                    <a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        <span>Inventory</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <li>
                    <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="search.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'search.php' ? 'active' : ''; ?>">
                        <i class="fas fa-search"></i>
                        <span>Product Search</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h2><?php
                $page = basename($_SERVER['PHP_SELF'], '.php');
                $pageNames = [
                    'dashboard' => 'Dashboard',
                    'pos' => 'Point of Sale',
                    'return_exchange' => 'Return & Exchange',
                    'inventory' => 'Inventory Management',
                    'users' => 'User Management',
                    'reports' => 'Sales Reports',
                    'search' => 'Product Search'
                ];
                echo $pageNames[$page] ?? ucfirst($page);
            ?></h2>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <div><?php echo $_SESSION['full_name']; ?></div>
                    <small><?php echo ucfirst($_SESSION['role']); ?></small>
                </div>
            </div>
        </div>