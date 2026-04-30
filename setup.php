<?php
// Setup script - Run once to fix admin password
// DELETE THIS FILE after running!
require_once __DIR__ . '/config.php';

$db = getDB();

// Generate proper BCrypt hash for admin123
$hash = password_hash('admin123', PASSWORD_DEFAULT);

// Update admin password
$stmt = $db->prepare('UPDATE usuarios SET password = ? WHERE email = ?');
$result = $stmt->execute([$hash, 'admin@mistore.com']);

if ($result) {
      echo '<h2 style="color:green">SUCCESS! Admin password updated.</h2>';
      echo '<p>Hash: ' . htmlspecialchars($hash) . '</p>';
      echo '<p>You can now login with: admin@mistore.com / admin123</p>';
      echo '<p><strong>IMPORTANT: Delete this file from the server now!</strong></p>';
} else {
      echo '<h2 style="color:red">ERROR updating password.</h2>';
}
?>
