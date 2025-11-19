<?php
// login.php
session_start();
require_once __DIR__ . '/includes/functions.php';

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
        
        // Përpunoni MAC address
        if (!empty($mac_address)) {
            if (isValidMacAddress($mac_address)) {
                $_SESSION['user_mac'] = formatMacAddress($mac_address);
            } else {
                $error = "MAC address jo valide! Do të gjenerohet automatikisht.";
                $_SESSION['user_mac'] = generateMacAddress();
            }
        } else {
            // Gjenero MAC automatikisht
            $_SESSION['user_mac'] = generateMacAddress();
        }
        
        // Merr kanalet nga provider-i
        $channels = getChannelsFromProvider($portal_url, $_SESSION['user_mac']);
        $_SESSION['channels'] = $channels;
        
        // Ridrejto në faqen kryesore
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lidhu me Provider</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .login-form { border: 1px solid #ddd; padding: 30px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="url"], input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007BFF; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background: #f8d7da; border-radius: 4px; }
        .info { color: #0c5460; margin-bottom: 15px; padding: 10px; background: #d1ecf1; border-radius: 4px; }
        small { color: #666; }
        .mac-example { font-family: monospace; background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Lidhu me Provider</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_mac'])): ?>
            <div class="info">
                <strong>MAC e përdorur:</strong> <?= htmlspecialchars($_SESSION['user_mac']) ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>URL e Provider-it:</label>
                <input type="url" name="portal_url" 
                       value="<?= htmlspecialchars($_POST['portal_url'] ?? 'https://stb-webapp.onrender.com') ?>" 
                       placeholder="https://stb-webapp.onrender.com" required>
            </div>
            
            <div class="form-group">
                <label>MAC Address (opsional):</label>
                <input type="text" name="mac_address" 
                       value="<?= htmlspecialchars($_POST['mac_address'] ?? '') ?>" 
                       placeholder="00:1A:79:XX:XX:XX">
                <small>
                    Formate të pranueshme: 
                    <span class="mac-example">00:1A:79:AB:CD:EF</span>, 
                    <span class="mac-example">00-1A-79-AB-CD-EF</span>, 
                    <span class="mac-example">001A79ABCDEF</span>
                </small>
            </div>
            
            <button type="submit">Lidhu</button>
        </form>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
            <h4>Informacion:</h4>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Vendosni URL-në e provider-it tuaj</li>
                <li>MAC address do të gjenerohet automatikisht nëse lihet bosh</li>
                <li>Për provider-in tuaj, përdorni: <strong>https://stb-webapp.onrender.com</strong></li>
            </ul>
        </div>
    </div>
</body>
</html>
