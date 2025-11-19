<?php
// login.php

require_once 'includes/functions.php';

// Kontrollo nëse forma është dërguar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portal_url = $_POST['portal_url'] ?? '';
    $mac_address = $_POST['mac_address'] ?? '';
    
    // Valido të dhënat
    if (!filter_var($portal_url, FILTER_VALIDATE_URL)) {
        $error = "URL jo valide!";
    } else {
        // Ruaj në session
        $_SESSION['portal_url'] = $portal_url;
        if ($mac_address) {
            $_SESSION['user_mac'] = $mac_address;
        }
        
        // Merr kanalet nga provider-i
        $channels = getChannelsFromProvider($portal_url, $mac_address);
        $_SESSION['channels'] = $channels;
        
        // Ridrejto në faqen kryesore
        header('Location: index.php');
        exit;
    }
}

// Shfaq formën e login
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lidhu me Provider</title>
</head>
<body>
    <div class="login-form">
        <h2>Lidhu me Provider</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>URL e Provider-it:</label>
                <input type="url" name="portal_url" 
                       value="<?= htmlspecialchars($_POST['portal_url'] ?? '') ?>" 
                       placeholder="https://stb-webapp.onrender.com" required>
            </div>
            
            <div class="form-group">
                <label>MAC Address (opsional):</label>
                <input type="text" name="mac_address" 
                       value="<?= htmlspecialchars($_POST['mac_address'] ?? '') ?>" 
                       placeholder="00:1A:79:XX:XX:XX">
                <small>Lëreni bosh për auto-generim</small>
            </div>
            
            <button type="submit">Lidhu</button>
        </form>
    </div>
</body>
</html>
