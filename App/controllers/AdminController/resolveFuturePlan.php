<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureFuturePlansSchema($db);

$adminUser = Session::get('user');
$adminUserId = (int) ($adminUser['id'] ?? 0);

$planId = (int) ($_POST['id'] ?? 0);
if ($planId <= 0) {
    Session::setFlashMessage('error_message', 'Invalid plan ID.');
    redirect('/admin/future-plans');
}

adminResolveFuturePlanById($db, $planId, $adminUserId);
adminAuditLog($db, 'future_plan_resolve', 'future_plan', (string) $planId, "Resolved future plan ID: {$planId}");
Session::setFlashMessage('success_message', 'Future plan marked as resolved.');

redirect('/admin/future-plans');
