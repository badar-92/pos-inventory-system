<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pos_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security configuration
define('CSRF_TOKEN_SECRET', 'your-secret-key-here-' . time());

// Create connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("ERROR: Could not connect to database. Please try again later.");
}

// Start session with proper configuration
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define base URL for consistent asset paths
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$app_path = dirname($_SERVER['SCRIPT_NAME']);
$full_base_url = $base_url . $app_path;

// Error logging function
function log_error($message, $context = []) {
    $log_message = date('[Y-m-d H:i:s]') . " " . $message;
    if (!empty($context)) {
        $log_message .= " Context: " . json_encode($context);
    }
    $log_message .= "\n";
    
    $log_file = __DIR__ . '/../logs/error.log';
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

// Input sanitization function
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

// CSRF token validation
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token field for forms
function csrf_token_field() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

// Password verification function
function verify_password($password, $hash) {
    // First try modern password_verify
    if (password_verify($password, $hash)) {
        return true;
    }
    
    // Then try SHA2 for legacy passwords
    if (hash('sha256', $password) === $hash) {
        return true;
    }
    
    // Finally, try plain text comparison (for development only)
    if ($password === $hash) {
        return true;
    }
    
    return false;
}

// Password hashing function
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
?>