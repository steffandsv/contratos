<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Router;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$router = new Router();

// Routes
$router->add('GET', '/', ['App\Controllers\AuthController', 'login']);
$router->add('GET', '/dashboard', ['App\Controllers\DashboardController', 'index']);
$router->add('POST', '/login', ['App\Controllers\AuthController', 'authenticate']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
