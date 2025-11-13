<?php
include 'includes/config.php';
include 'includes/auth.php';

// Check if user is logged in and is admin
checkAuthentication();
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Add new user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $username = sanitize_input(trim($_POST['username']));
        $full_name = sanitize_input(trim($_POST['full_name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $phone = sanitize_input(trim($_POST['phone']));
        $address = sanitize_input(trim($_POST['address']));
        $password = $_POST['password'];
        $role = sanitize_input(trim($_POST['role']));
        
        // Validate inputs
        if (empty($username) || empty($full_name) || empty($password)) {
            $error = "Username, Full Name, and Password are required!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
            $error = "Invalid email format!";
        } else {
            // Check if user already exists
            $sql = "SELECT * FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Username already exists!";
            } else {
                // Hash password
                $hashed_password = hash_password($password);
                
                // Insert new user
                $sql = "INSERT INTO users (username, full_name, email, phone, address, password, role)
                        VALUES (:username, :full_name, :email, :phone, :address, :password, :role)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                $stmt->bindParam(':address', $address, PDO::PARAM_STR);
                $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                
                if ($stmt->execute()) {
                    $success = "User added successfully!";
                    log_error("User added", ['username' => $username, 'added_by' => $_SESSION['user_id']]);
                } else {
                    $error = "Error adding user!";
                    log_error("Error adding user", ['username' => $username, 'error' => $stmt->errorInfo()]);
                }
            }
        }
    }
}

// Edit user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $user_id = sanitize_input(trim($_POST['user_id']));
        $username = sanitize_input(trim($_POST['username']));
        $full_name = sanitize_input(trim($_POST['full_name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $phone = sanitize_input(trim($_POST['phone']));
        $address = sanitize_input(trim($_POST['address']));
        $role = sanitize_input(trim($_POST['role']));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Update user
        $sql = "UPDATE users SET username = :username, full_name = :full_name, email = :email,
                phone = :phone, address = :address, role = :role, is_active = :is_active
                WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $success = "User updated successfully!";
            log_error("User updated", ['user_id' => $user_id, 'updated_by' => $_SESSION['user_id']]);
        } else {
            $error = "Error updating user!";
            log_error("Error updating user", ['user_id' => $user_id, 'error' => $stmt->errorInfo()]);
        }
    }
}

// Change password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $user_id = sanitize_input(trim($_POST['user_id']));
        $new_password = $_POST['new_password'];
        
        // Hash the new password
        $hashed_password = hash_password($new_password);
        
        // Update password
        $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $success = "Password changed successfully!";
            log_error("Password changed", ['user_id' => $user_id, 'changed_by' => $_SESSION['user_id']]);
        } else {
            $error = "Error changing password!";
            log_error("Error changing password", ['user_id' => $user_id, 'error' => $stmt->errorInfo()]);
        }
    }
}

// Delete user
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    
    // Prevent self-deletion
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        $sql = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $success = "User deleted successfully!";
            log_error("User deleted", ['user_id' => $user_id, 'deleted_by' => $_SESSION['user_id']]);
        } else {
            $error = "Error deleting user!";
            log_error("Error deleting user", ['user_id' => $user_id, 'error' => $stmt->errorInfo()]);
        }
    }
}

// Get all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/sidebar.php'; ?>

<div class="container">
    <h1><i class="fas fa-users"></i> User Management</h1>
    
    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
    
    <div class="form-section">
        <h2><i class="fas fa-plus-circle"></i> Add New User</h2>
        <form method="post" action="">
            <?php echo csrf_token_field(); ?>
            <input type="hidden" name="add_user" value="1">
            
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone">
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address"></textarea>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">Show</button>
                </div>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="cashier">Cashier</option>
                </select>
            </div>
            <button type="submit" class="btn"><i class="fas fa-save"></i> Add User</button>
        </form>
    </div>
    
    <div class="table-section">
        <h2><i class="fas fa-list"></i> Existing Users</h2>
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><?php echo ucfirst($user['role']); ?></td>
                    <td><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>)"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn btn-sm btn-warning" onclick="openPasswordModal('<?php echo $user['user_id']; ?>')"><i class="fas fa-key"></i> Change Password</button>
                            <a href="users.php?delete_user=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit User</h2>
        <form method="post" action="">
            <?php echo csrf_token_field(); ?>
            <input type="hidden" name="edit_user" value="1">
            <input type="hidden" id="edit_user_id" name="user_id">
            
            <div class="form-group">
                <label for="edit_username">Username:</label>
                <input type="text" id="edit_username" name="username" required>
            </div>
            <div class="form-group">
                <label for="edit_full_name">Full Name:</label>
                <input type="text" id="edit_full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="edit_email">Email:</label>
                <input type="email" id="edit_email" name="email">
            </div>
            <div class="form-group">
                <label for="edit_phone">Phone:</label>
                <input type="tel" id="edit_phone" name="phone">
            </div>
            <div class="form-group">
                <label for="edit_address">Address:</label>
                <textarea id="edit_address" name="address"></textarea>
            </div>
            <div class="form-group">
                <label for="edit_role">Role:</label>
                <select id="edit_role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="cashier">Cashier</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_is_active">Status:</label>
                <input type="checkbox" id="edit_is_active" name="is_active" value="1"> Active
            </div>
            <button type="submit" class="btn"><i class="fas fa-save"></i> Update User</button>
        </form>
    </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Change Password</h2>
        <form method="post" action="">
            <?php echo csrf_token_field(); ?>
            <input type="hidden" name="change_password" value="1">
            <input type="hidden" id="password_user_id" name="user_id">
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <div class="password-container">
                    <input type="password" id="new_password" name="new_password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password')">Show</button>
                </div>
            </div>
            <button type="submit" class="btn"><i class="fas fa-key"></i> Change Password</button>
        </form>
    </div>
</div>

<script>
// Modal functionality
const editModal = document.getElementById('editModal');
const passwordModal = document.getElementById('passwordModal');
const closeButtons = document.querySelectorAll('.close');

function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_username').value = user.username || '';
    document.getElementById('edit_full_name').value = user.full_name || '';
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_address').value = user.address || '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_is_active').checked = user.is_active == 1;
    
    editModal.style.display = 'block';
}

function openPasswordModal(userId) {
    document.getElementById('password_user_id').value = userId;
    passwordModal.style.display = 'block';
}

closeButtons.forEach(button => {
    button.addEventListener('click', function() {
        editModal.style.display = 'none';
        passwordModal.style.display = 'none';
    });
});

window.addEventListener('click', function(event) {
    if (event.target == editModal) {
        editModal.style.display = 'none';
    }
    if (event.target == passwordModal) {
        passwordModal.style.display = 'none';
    }
});

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Hide';
    } else {
        input.type = 'password';
        button.textContent = 'Show';
    }
}
</script>

<style>
/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 600px;
    animation: slideUp 0.3s;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #000;
}
</style>

<?php include 'includes/footer.php'; ?>