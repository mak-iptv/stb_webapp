<?php
echo "<h1>✅ PHP është duke punuar!</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";

// Test form
echo '<form method="POST" action="/test.php">
        <input type="text" name="test_input" placeholder="Shkruaj diçka">
        <button type="submit">Testo</button>
      </form>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>Forma u dërgua me sukses!</p>";
    echo "<p>Input: " . htmlspecialchars($_POST['test_input'] ?? '') . "</p>";
}
?>
