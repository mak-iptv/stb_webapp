<?php
require_once 'config.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (verifyUserCredentials($username, $password)) {
        $_SESSION['user'] = $username;
        $_SESSION['login_time'] = time();
        
        header('Location: /dashboard');
        exit;
    } else {
        $error = "Kredencialet janë të gabuara! Kontrolloni username dhe password.";
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
            max-width: 400px;
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
        }
        
        .login-btn:hover {
            background: #2980b9;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .provider-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-title">
            <h1>🔐 Stalker Player</h1>
            <p>Hyrë në sistemin tuaj IPTV</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Emri i përdoruesit:</label>
                <input type="text" id="username" name="username" placeholder="Shkruani username-in tuaj" required>
            </div>
            
            <div class="form-group">
                <label for="password">Fjalëkalimi:</label>
                <input type="password" id="password" name="password" placeholder="Shkruani fjalëkalimin" required>
            </div>
            
            <button type="submit" class="login-btn">Hyr në Sistem</button>
        </form>
        
        <div class="provider-info">
            <strong>🔧 Kredencialet e providerit tuaj</strong>
        </div>
    </div>
</body>
</html>
