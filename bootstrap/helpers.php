<?php
function env($key, $default=null) {
    static $env = null;
    if ($env === null) {
        $env = [];
        $path = __DIR__ . '/../.env';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                $env[$k] = $v;
            }
        }
    }
    return $env[$key] ?? $default;
}

function dd($v) { echo '<pre>'; print_r($v); echo '</pre>'; exit; }

function view($name, $data = []) {
    extract($data);
    $path = __DIR__ . '/../app/Views/' . $name . '.php';
    if (!file_exists($path)) {
        http_response_code(404);
        echo "View not found: {$name}";
        exit;
    }
    include __DIR__ . '/../app/Views/partials/header.php';
    include $path;
    include __DIR__ . '/../app/Views/partials/footer.php';
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_csrf'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
            http_response_code(419);
            echo "CSRF token mismatch";
            exit;
        }
    }
}

function money_format_local($amount) {
    return number_format((float)$amount, 0, ',', '.');
}

function require_login() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function can($permissionKey) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $perms = $_SESSION['permissions'] ?? [];
    return in_array($permissionKey, $perms, true);
}