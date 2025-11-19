<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Verifikimi i kredencialeve (duhet të implementohet sipas nevojës)
    if (verifyCredentials($username, $password)) {
        $_SESSION['user'] = $username;
        $_SESSION['user_mac'] = $_POST['mac'] ?? DEFAULT_MAC;
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Kredenciale të gabuara";
    }
}

function verifyCredentials($username, $password) {
    // Implementimi aktual i verifikimit
    // Mund të jetë nga database, API, etj.
    return !empty($username) && !empty($password);
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title>Login - Stalker Player</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h2>Hyrë në Stalker Player</h2>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Emri i përdoruesit" required>
                <input type="password" name="password" placeholder="Fjalëkalimi" required>
                <input type="text" name="mac" placeholder="MAC Address" value="<?= DEFAULT_MAC ?>">
                <button type="submit">Hyr</button>
            </form>
        </div>
    </div>
</body>
</html>