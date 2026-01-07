<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contract;

class DashboardController extends Controller
{
    private function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    public function index()
    {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];

        $stats = [
            'actions_today' => Contract::countByActionDeadlineTodayOrOverdue(),
            'next_30_days' => Contract::countByActionDeadlineNext30Days(),
            'my_contracts' => Contract::countByResponsible($userId),
            'high_risk' => Contract::countHighRisk()
        ];

        $myQueue = Contract::getQueue(10);
        $alerts = Contract::getAlerts();

        $this->render('dashboard/index', [
            'stats' => $stats,
            'myQueue' => $myQueue,
            'alerts' => $alerts
        ]);
    }
}
