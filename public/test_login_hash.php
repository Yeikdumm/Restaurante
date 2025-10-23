<?php
require __DIR__ . '/../bootstrap/autoload.php';
require __DIR__ . '/../bootstrap/helpers.php';
require __DIR__ . '/../config/database.php';

try {
    $pdo = DB::conn();
    $user = $pdo->query("SELECT id, email, password_hash FROM users WHERE email='admin@local' LIMIT 1")->fetch();
    $ok = password_verify('Admin123*', $user['password_hash'] ?? '');
    echo "<h3>PHP ".PHP_VERSION."</h3>";
    echo "<p>Hash en BD: ".htmlspecialchars($user['password_hash'] ?? '(no)')."</p>";
    echo "<p>password_verify('Admin123*') => ".($ok ? 'TRUE ✅' : 'FALSE ❌')."</p>";
} catch (Throwable $e) {
    echo "<pre>ERROR: ".htmlspecialchars($e->getMessage())."</pre>";
}
