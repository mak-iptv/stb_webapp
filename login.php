<?php
require_once 'config.php';

// Nëse useri është tashmë i loguar, shko direkt në dashboard
if (isset($_SESSION['user']) && isset($_SESSION['portal_url']) && isset($_SESSION['mac_address'])) {
    header('Location: /dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portal_url = $_POST['portal_url'] ?? '';
    $portal_port = $_POST['portal_port'] ?? '';
    $mac_address = $_POST['mac_address'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validimi i të dhënave
    if (empty($portal_url) || empty($mac_address) || empty($username) || empty($password)) {
        $error = "Ju lutem plotësoni të gjitha fushat e detyrueshme!";
    } else {
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
            header('Location: /dashboard');
            exit;
        } else {
            $error = "Lidhja me providerin dështoi. Kontrolloni të dhënat!";
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
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-title">
            <h1>🔐 Stalker Player</h1>
            <p>Konfiguro lidhjen me providerin</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="portal_url">URL e Portalit *</label>
                    <input type="text" id="portal_url" name="portal_url" 
                           placeholder="http://portal-provider.com" required>
                </div>
                
                <div class="form-group">
                    <label for="portal_port">Porti</label>
                    <input type="number" id="portal_port" name="portal_port" 
                           placeholder="80 (default)" value="80">
                </div>
            </div>
            
            <div class="form-group">
                <label for="mac_address">MAC Address *</label>
                <input type="text" id="mac_address" name="mac_address" 
                       placeholder="00:1A:79:XX:XX:XX" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" 
                           placeholder="Emri i përdoruesit" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Fjalëkalimi" required>
                </div>
            </div>
            
            <button type="submit" class="login-btn">🔗 Lidhu me Providerin</button>
        </form>
        
        <div class="back-link">
            <a href="/">← Kthehu në Faqen Kryesore</a>
        </div>
    </div>
</body>
</html>
