<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureMaintenanceModeSchema($db);

$isEnabled = isset($_POST['is_enabled']) && (int) ($_POST['is_enabled'] ?? 0) === 1;
$message = trim((string) ($_POST['message'] ?? ''));
$adminUserId = (int) (Session::get('user')['id'] ?? 0);

adminSetMaintenanceMode($db, $isEnabled, $message, $adminUserId > 0 ? $adminUserId : null);

adminAuditLog(
    $db,
    'system.maintenance',
    'app_maintenance_mode',
    '1',
    $isEnabled ? 'Enabled maintenance mode' : 'Disabled maintenance mode',
    [
        'is_enabled' => $isEnabled ? 1 : 0,
        'message' => $message !== '' ? $message : 'We are updating the platform. Please check back shortly.'
    ]
);

Session::setFlashMesssge('success_message', $isEnabled ? 'Maintenance mode enabled.' : 'Maintenance mode disabled.');
redirect('/admin/dashboard');
