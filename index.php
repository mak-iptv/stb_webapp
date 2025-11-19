<?php
require_once 'config.php';

// Kontrollo nëse useri është i loguar
if (isset($_SESSION['user']) && isset($_SESSION['portal_url']) && isset($_SESSION['mac_address'])) {
    // Useri është i loguar, ridrejto në dashboard
    header('Location: /dashboard');
    exit;
} else {
    // Useri nuk është i loguar, ridrejto në login
    header('Location: /login');
    exit;
}
