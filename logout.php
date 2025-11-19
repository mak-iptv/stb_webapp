<?php
require_once 'config.php';

// Fshi session
$_SESSION = array();

// Fshi cookie-in e session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Shkatërro session
session_destroy();

// Ridrejto në login
header('Location: /login');
exit;
?>
