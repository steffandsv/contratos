<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Router;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$router = new Router();

// Routes
$router->add('GET', '/', ['App\Controllers\AuthController', 'login']);
$router->add('POST', '/login', ['App\Controllers\AuthController', 'authenticate']);
$router->add('GET', '/logout', ['App\Controllers\AuthController', 'logout']);

$router->add('GET', '/dashboard', ['App\Controllers\DashboardController', 'index']);

// Contracts
$router->add('GET', '/contracts', ['App\Controllers\ContractController', 'index']);
$router->add('GET', '/contracts/create', ['App\Controllers\ContractController', 'create']);
$router->add('POST', '/contracts/store', ['App\Controllers\ContractController', 'store']);
$router->add('GET', '/contracts/view', ['App\Controllers\ContractController', 'view']);
$router->add('GET', '/contracts/edit', ['App\Controllers\ContractController', 'edit']);
$router->add('POST', '/contracts/update', ['App\Controllers\ContractController', 'update']);

// Users
$router->add('GET', '/users', ['App\Controllers\UserController', 'index']);
$router->add('GET', '/users/create', ['App\Controllers\UserController', 'create']);
$router->add('POST', '/users/store', ['App\Controllers\UserController', 'store']);
$router->add('GET', '/users/edit', ['App\Controllers\UserController', 'edit']);
$router->add('POST', '/users/update', ['App\Controllers\UserController', 'update']);

// Import
$router->add('GET', '/import', ['App\Controllers\ImportController', 'index']);
$router->add('POST', '/import/process', ['App\Controllers\ImportController', 'process']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
