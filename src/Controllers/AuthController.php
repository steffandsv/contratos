<?php

namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller
{
    public function login()
    {
        $this->render('auth/login');
    }

    public function authenticate()
    {
        // Simple auth simulation
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($email === 'admin@admin.com' && $password === 'admin') {
            session_start();
            $_SESSION['user_id'] = 1;
            $this->redirect('/dashboard');
        } else {
            $this->render('auth/login', ['error' => 'Invalid credentials']);
        }
    }
}
