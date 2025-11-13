<?php
include 'includes/config.php';
include 'includes/auth.php';

// Check if user is logged in
checkAuthentication();

// Get user role
$role = $_SESSION['role'];
?>

<?php include 'includes/sidebar.php'; ?>

<div class="container">
    <h1>Dashboard - <?php echo ucfirst($role); ?> Panel</h1>
    
    <div class="dashboard-cards">
        <?php if ($role == 'admin' || $role == 'manager'): ?>
        <div class="card">
            <h2><i class="fas fa-box"></i> Inventory Management</h2>
            <p>View and manage products</p>
            <a href="inventory.php" class="btn"><i class="fas fa-arrow-right"></i> Go to Inventory</a>
        </div>
        <?php endif; ?>
        
        <?php if ($role == 'admin' || $role == 'cashier'): ?>
        <div class="card">
            <h2><i class="fas fa-cash-register"></i> Point of Sale</h2>
            <p>Process new sales</p>
            <a href="pos.php" class="btn"><i class="fas fa-arrow-right"></i> New Sale</a>
        </div>
        <?php endif; ?>

        <?php if ($role == 'admin' || $role == 'cashier'): ?>
        <div class="card">
            <h2><i class="fas fa-exchange-alt"></i> Return & Exchange</h2>
            <p>Process product returns and exchanges</p>
            <a href="return_exchange.php" class="btn"><i class="fas fa-arrow-right"></i> Process Returns</a>
        </div>
        <?php endif; ?>
        
        <?php if ($role == 'admin' || $role == 'manager'): ?>
        <div class="card">
            <h2><i class="fas fa-chart-bar"></i> Reports</h2>
            <p>View sales reports</p>
            <a href="reports.php" class="btn"><i class="fas fa-arrow-right"></i> View Reports</a>
        </div>
        <?php endif; ?>
        
        <?php if ($role == 'admin'): ?>
        <div class="card">
            <h2><i class="fas fa-users"></i> User Management</h2>
            <p>Manage system users</p>
            <a href="users.php" class="btn"><i class="fas fa-arrow-right"></i> Manage Users</a>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><i class="fas fa-search"></i> Product Search</h2>
            <p>Search for products</p>
            <a href="search.php" class="btn"><i class="fas fa-arrow-right"></i> Search Products</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>