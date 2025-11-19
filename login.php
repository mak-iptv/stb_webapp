<?php
require_once 'config.php';

// Debug info
error_log("Login page accessed - Method: " . $_SERVER['REQUEST_METHOD']);

// Nëse useri është tashmë i loguar, shko direkt në dashboard
if (isset($_SESSION['user']) && isset($_SESSION['portal_url']) && isset($_SESSION['mac_address'])) {
    error_log("User already logged in, redirecting to dashboard");
    header('Location: /dashboard');
    exit;
}

// Kontrollo nëse ka të dhëna POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data received: " . print_r($_POST, true));
    
    $portal_url = $_POST['portal_url'] ?? '';
    $portal_port = $_POST['portal_port'] ?? '80';
    $mac_address = $_POST['mac_address'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validimi i të dhënave
    if (empty($portal_url) || empty($mac_address) || empty($username) || empty($password)) {
        $error = "Ju lutem plotësoni të gjitha fushat e detyrueshme!";
        error_log("Validation failed - missing fields");
    } else {
        error_log("Attempting login with: URL=$portal_url, MAC=$mac_address, User=$username");
        
        // Ruaj të dhënat në session
        $_SESSION['portal_url'] = $portal_url;
        $_SESSION['portal_port'] = $portal_port;
        $_SESSION['mac_address'] = $mac_address;
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
        $_SESSION['user'] = $username;
        $_SESSION['login_time'] = time();
        
        // Testo lidhjen me providerin
        require_once 'includes/functions.php';
        $channels = getChannelsFromProvider(true); // force refresh
        
        if (!empty($channels)) {
            error_log("Login successful - " . count($channels) . " channels found");
            header('Location: /dashboard');
            exit;
        } else {
            $error = "Lidhja me providerin dështoi. Kontrolloni të dhënat!";
            error_log("Login failed - no channels received");
            // Fshi të dhënat e session nëse lidhja dështon
            unset($_SESSION['portal_url'], $_SESSION['portal_port'], $_SESSION['mac_address'], 
                  $_SESSION['username'], $_SESSION['password'], $_SESSION['user']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stalker Player</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .login-title h1 {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .login-btn {
            width: 100%;
            padding: 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .login-btn:hover {
            background: #2980b9;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #e74c3c;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #28a745;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        
        .debug-info {
            background: #f8f9fa;
            color: #6c757d;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 12px;
            border-left: 4px solid #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-title">
            <h1>🔐 Stalker Player</h1>
            <p>Konfiguro lidhjen me providerin tuaj IPTV</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                ✅ <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/login.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="portal_url">🌐 URL e Portalit *</label>
                    <input type="url" id="portal_url" name="portal_url" 
                           placeholder="http://portal-provider.com" 
                           value="<?= htmlspecialchars($_POST['portal_url'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="portal_port">🔌 Porti</label>
                    <input type="number" id="portal_port" name="portal_port" 
                           placeholder="80" 
                           value="<?= htmlspecialchars($_POST['portal_port'] ?? '80') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="mac_address">📟 MAC Address *</label>
                <input type="text" id="mac_address" name="mac_address" 
                       placeholder="00:1A:79:XX:XX:XX" 
                       value="<?= htmlspecialchars($_POST['mac_address'] ?? '') ?>" 
                       pattern="[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}"
                       required>
                <small style="color: #666; font-size: 12px;">Format: 00:1A:79:XX:XX:XX</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">👤 Username *</label>
                    <input type="text" id="username" name="username" 
                           placeholder="Emri i përdoruesit" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">🔑 Password *</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Fjalëkalimi" required>
                </div>
            </div>
            
            <button type="submit" class="login-btn">🔗 Lidhu me Providerin</button>
        </form>
        
        <div class="back-link">
            <a href="/">← Kthehu në Faqen Kryesore</a>
        </div>
        
        <!-- Debug Info -->
        <div class="debug-info">
            <strong>Debug Info:</strong><br>
            Method: <?= $_SERVER['REQUEST_METHOD'] ?><br>
            PHP Version: <?= PHP_VERSION ?><br>
            Session: <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active' ?>
        </div>
    </div>

    <script>
        // JavaScript për të kontrolluar formën para dërgimit
        document.querySelector('form').addEventListener('submit', function(e) {
            const portalUrl = document.getElementById('portal_url').value;
            const macAddress = document.getElementById('mac_address').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!portalUrl || !macAddress || !username || !password) {
                alert('Ju lutem plotësoni të gjitha fushat e detyrueshme!');
                e.preventDefault();
                return;
            }
            
            // Valido MAC address format
            const macPattern = /^[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}$/;
            if (!macPattern.test(macAddress)) {
                alert('MAC Address duhet të jetë në format: 00:1A:79:XX:XX:XX');
                e.preventDefault();
                return;
            }
            
            console.log('Form submitted successfully');
        });
        
        // Auto-format MAC address
        document.getElementById('mac_address').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9A-Fa-f]/g, '').toUpperCase();
            if (value.length > 12) value = value.substr(0, 12);
            
            // Format as MAC address
            const formatted = value.match(/.{1,2}/g);
            if (formatted) {
                e.target.value = formatted.join(':');
            }
        });
    </script>
</body>
</html>
