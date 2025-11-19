<?php
session_start();

// Nëse useri është tashmë i loguar, shko në dashboard
if (isset($_SESSION['user'])) {
    header('Location: ?page=dashboard');
    exit;
}

// Kontrollo nëse ka të dhëna POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portal_url = $_POST['portal_url'] ?? '';
    $portal_port = $_POST['portal_port'] ?? '';
    $mac_address = $_POST['mac_address'] ?? '';
    
    // Validimi i të dhënave
    if (empty($portal_url) || empty($mac_address)) {
        $error = "Ju lutem plotësoni URL dhe MAC Address!";
    } else {
        // Shto /c në fund të URL-së nëse nuk e ka
        if (!str_ends_with($portal_url, '/c')) {
            $portal_url = rtrim($portal_url, '/') . '/c';
        }
        
        // Ruaj të dhënat në session
        $_SESSION['portal_url'] = $portal_url;
        $_SESSION['portal_port'] = $portal_port;
        $_SESSION['mac_address'] = $mac_address;
        $_SESSION['user'] = 'provider_user'; // User fixed
        
        // Testo lidhjen me providerin
        require_once 'includes/functions.php';
        $channels = getChannelsFromProvider(true);
        
        if (!empty($channels)) {
            header('Location: ?page=dashboard');
            exit;
        } else {
            $error = "❌ Lidhja me providerin dështoi. Kontrolloni të dhënat!";
            // Fshi të dhënat e session nëse lidhja dështon
            unset($_SESSION['portal_url'], $_SESSION['portal_port'], $_SESSION['mac_address'], $_SESSION['user']);
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
            margin-top: 10px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info-message {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        .example {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-title">
            <h1>🔐 Stalker Player</h1>
            <p>Lidhu me providerin tuaj</p>
        </div>
        
        <div class="info-message">
            💡 <strong>Vetëm URL dhe MAC nevojiten</strong><br>
            Nuk nevojiten username dhe password
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="portal_url">🌐 URL e Portalit *</label>
                <input type="text" id="portal_url" name="portal_url" 
                       placeholder="http://server.com:8080/c" 
                       value="<?= htmlspecialchars($_POST['portal_url'] ?? '') ?>" required>
                <small style="color: #666;">Format: http://server:port/c</small>
            </div>
            
            <div class="form-group">
                <label for="mac_address">📟 MAC Address *</label>
                <input type="text" id="mac_address" name="mac_address" 
                       placeholder="00:1A:79:XX:XX:XX" 
                       value="<?= htmlspecialchars($_POST['mac_address'] ?? '') ?>" required>
                <small style="color: #666;">Format: 00:1A:79:XX:XX:XX</small>
            </div>
            
            <button type="submit" class="login-btn">🔗 Testo Lidhjen</button>
        </form>
        
        <div class="example">
            <strong>Shembuj të saktë:</strong><br>
            • http://portal.iptv.com:8080/c<br>
            • https://server.com:80/c<br>
            • http://192.168.1.100:6544/c
        </div>
        
        <div class="back-link">
            <a href="?page=home">← Kthehu në Faqen Kryesore</a>
        </div>
    </div>

    <script>
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
        
        // Shto /c automatikisht në URL
        document.getElementById('portal_url').addEventListener('blur', function(e) {
            let value = e.target.value.trim();
            if (value && !value.endsWith('/c')) {
                e.target.value = value.replace(/\/+$/, '') + '/c';
            }
        });
    </script>
</body>
</html>
