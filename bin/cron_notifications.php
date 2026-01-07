<?php

require __DIR__ . '/../src/Core/Config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Models\Contract;
use App\Models\Setting;
use App\Models\Notification;
use App\Models\ContractResponsible;

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// 1. Load Settings
$daysWithRenewal = Setting::get('notification_days_with_renewal', '180,120,90,60,30,15,7,3');
$daysWithoutRenewal = Setting::get('notification_days_without_renewal', '120,90,60,30,15,7,3');

$daysWithRenewalList = explode(',', $daysWithRenewal);
$daysWithoutRenewalList = explode(',', $daysWithoutRenewal);

// 2. Fetch Active Contracts
$contracts = Contract::all(['status_phase' => 'VIGENTE']);
// Note: Contract::all has filters but we can improve it or just filter array here for simplicity of script
// Actually I need to fetch all 'VIGENTE' or 'EM_PRORROGACAO'
// Let's create a specific method or raw query for performance?
// For now, I'll rely on a raw query inside this script for clarity and speed.

$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM contracts WHERE status_phase IN ('VIGENTE', 'EM_PRORROGACAO')");
$contracts = $stmt->fetchAll(PDO::FETCH_CLASS, \App\Models\Contract::class);

$today = new DateTime();

foreach ($contracts as $contract) {
    $end = new DateTime($contract->date_end_current);
    $diff = $today->diff($end);
    $daysRemaining = (int)$diff->format('%r%a');

    // Determine which list to use
    $checkList = ($contract->has_renewal) ? $daysWithRenewalList : $daysWithoutRenewalList;

    // Check if daysRemaining is in the list
    if (in_array((string)$daysRemaining, $checkList)) {
        createNotificationsForContract($contract, "Contrato {$contract->number} vence em $daysRemaining dias", $contract->next_action_text);
    }

    // Special Case: Expired and Vigente
    if ($daysRemaining < 0 && $contract->status_phase == 'VIGENTE') {
        createNotificationsForContract($contract, "URGENTE: Contrato {$contract->number} VENCIDO e VIGENTE", "Regularizar imediatamente!", true);
    }
}

function createNotificationsForContract($contract, $title, $message, $isCritical = false) {
    $recipients = [];

    // Manager
    if ($contract->manager_user_id) {
        $recipients[] = $contract->manager_user_id;
    }

    // Responsibles (Fiscais)
    $responsibles = ContractResponsible::getByContract($contract->id);
    foreach ($responsibles as $resp) {
        $recipients[] = $resp->user_id;
    }

    // If critical and no recipients, find admins? (Optional per spec)

    $recipients = array_unique($recipients);

    foreach ($recipients as $userId) {
        // Avoid duplicate notification for same contract/type/day?
        // For simplicity, we just insert.
        $notif = new Notification();
        $notif->contract_id = $contract->id;
        $notif->user_id = $userId;
        $notif->type = $isCritical ? 'RISCO_IRREGULAR' : 'PRAZO_VENCENDO';
        $notif->title = $title;
        $notif->message = $message;
        $notif->send_channel = 'APP'; // And EMAIL ideally, but let's start with APP
        $notif->scheduled_for = date('Y-m-d H:i:s');
        $notif->save();

        // Also queue EMAIL
        $notifEmail = new Notification();
        $notifEmail->contract_id = $contract->id;
        $notifEmail->user_id = $userId;
        $notifEmail->type = $isCritical ? 'RISCO_IRREGULAR' : 'PRAZO_VENCENDO';
        $notifEmail->title = $title;
        $notifEmail->message = $message; // Should include link in email processing
        $notifEmail->send_channel = 'EMAIL';
        $notifEmail->scheduled_for = date('Y-m-d H:i:s');
        $notifEmail->save();
    }
}

// Logic to process 'EMAIL' notifications would go here (sending actual emails)
// For V1, we just record them.
echo "Notifications processed.\n";
