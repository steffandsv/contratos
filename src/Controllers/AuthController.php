<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class AuthController extends Controller
{
    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->render('auth/login', ['csrf_token' => $_SESSION['csrf_token']]);
    }

    public function authenticate()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== $_SESSION['csrf_token']) {
            $this->render('auth/login', ['error' => 'Invalid CSRF token', 'csrf_token' => $_SESSION['csrf_token']]);
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->render('auth/login', ['error' => 'Please fill in all fields', 'csrf_token' => $_SESSION['csrf_token']]);
            return;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE email = :email AND is_active = 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $this->redirect('/dashboard');
        } else {
            $this->render('auth/login', ['error' => 'Invalid credentials', 'csrf_token' => $_SESSION['csrf_token']]);
        }
    }
}
