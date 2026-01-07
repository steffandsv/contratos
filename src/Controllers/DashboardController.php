<?php

namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $this->render('dashboard/index');
    }
}
