<?php
namespace App\Controllers;

use DB;
use PDO;
use Exception;

class AuthController {
    public function showLogin() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!empty($_SESSION['user'])) {
            header('Location: index.php');
            exit;
        }
        ob_start();
        include __DIR__ . '/../Views/auth/login.php';
        return ob_get_clean();
    }

    public function login() {
        csrf_check();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = DB::conn()->prepare("SELECT u.id, u.name, u.email, u.password_hash FROM users u WHERE u.email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = 'Credenciales inválidas';
            header('Location: login.php');
            exit;
        }
        // load permissions
        $permStmt = DB::conn()->prepare("
            SELECT p.key_name FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            JOIN user_roles ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = ?
        ");
        $permStmt->execute([$user['id']]);
        $perms = array_map(fn($r) => $r['key_name'], $permStmt->fetchAll());

        $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']];
        $_SESSION['permissions'] = $perms;
        header('Location: index.php');
        exit;
    }

    public function logout() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        session_destroy();
        header('Location: login.php');
        exit;
    }
}