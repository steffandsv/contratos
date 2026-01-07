<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    private function checkAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        $user = User::find($_SESSION['user_id']);
        if (!$user || $user->role !== 'ADMIN') {
            // Simple 403 or redirect
            echo "Access Denied";
            exit;
        }
    }

    public function index()
    {
        $this->checkAdmin();
        $users = User::all();
        $this->render('users/index', ['users' => $users]);
    }

    public function create()
    {
        $this->checkAdmin();
        $this->render('users/create');
    }

    public function store()
    {
        $this->checkAdmin();
        $user = new User();
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        $user->password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user->role = $_POST['role'];
        $user->is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($user->save()) {
            $this->redirect('/users');
        } else {
            $this->render('users/create', ['error' => 'Erro ao criar usuário', 'user' => $user]);
        }
    }

    public function edit()
    {
        $this->checkAdmin();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect('/users');
        }
        $user = User::find($id);
        $this->render('users/create', ['user' => $user]); // Reusing create view for edit
    }

    public function update()
    {
        $this->checkAdmin();
        $id = $_POST['id'] ?? null;
        if (!$id) {
            $this->redirect('/users');
        }
        $user = User::find($id);
        if (!$user) {
            $this->redirect('/users');
        }

        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        if (!empty($_POST['password'])) {
            $user->password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        $user->role = $_POST['role'];
        $user->is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($user->save()) {
            $this->redirect('/users');
        } else {
            $this->render('users/create', ['error' => 'Erro ao atualizar usuário', 'user' => $user]);
        }
    }
}
