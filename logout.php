<?php
include 'includes/config.php';

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page
header("Location: index.php");
exit;
?>