<?php
include 'includes/config.php';
include 'includes/auth.php';

checkAuthentication();
checkRole(['admin']);

// Update admin password to use new hashing algorithm
$sql = "SELECT user_id, password FROM users";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    // Check if password is using old SHA2 hashing
    if (substr($user['password'], 0, 7) !== '$2y$10$') {
        // This is an old hash, update it
        // In a real scenario, you would need to know the original password
        // For now, we'll set a default password and prompt the user to change it
        $new_hash = hash_password('temp_password123');
        
        $update_sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([
            ':password' => $new_hash,
            ':user_id' => $user['user_id']
        ]);
        
        echo "Updated password for user: " . $user['user_id'] . " to 'temp_password123'<br>";
        echo "Please ask this user to change their password immediately.<br><br>";
    }
}

echo "Password update complete!";
?>