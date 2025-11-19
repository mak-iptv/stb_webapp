<?php
require_once 'config.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (verifyUserCredentials($username, $password)) {
        $_SESSION['user'] = $username;
        $_SESSION['login_time'] = time();
        
        // Ruaj kredencialet për provider nëse janë ato reale
        if ($username !== 'demo') {
            $_SESSION['provider_username'] = $username;
            $_SESSION['provider_password'] = $password;
        }
        
        header('Location: /dashboard');
        exit;
    } else {
        $error = "Kredencialet janë të gabuara!";
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stalker Player</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h1>🔐 Hyrë në Stalker Player</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Emri i përdoruesit:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Fjalëkalimi:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">Hyr</button>
            </form>
            
            <div class="demo-info">
                <strong>Kredenciale testimi:</strong><br>
                Përdoruesi: demo<br>
                Fjalëkalimi: demo
            </div>
        </div>
    </div>
</body>
</html>
