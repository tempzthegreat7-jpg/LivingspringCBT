<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureFuturePlansSchema($db);

$planId = (int) ($_POST['plan_id'] ?? 0);
$action = strtolower(trim((string) ($_POST['action'] ?? '')));

if ($planId <= 0 || !in_array($action, ['resolve', 'unresolve'], true)) {
    Session::setFlashMesssge('error_message', 'Invalid request.');
    redirect('/admin/future-plans');
}

if ($action === 'resolve') {
    adminResolveFuturePlan($db, $planId);
    adminAuditLog(
        $db,
        'future_plan.resolve',
        'future_plan',
        (string) $planId,
        'Resolved future plan'
    );
    Session::setFlashMesssge('success_message', 'Plan marked as resolved.');
} else {
    adminUnresolveFuturePlan($db, $planId);
    adminAuditLog(
        $db,
        'future_plan.unresolve',
        'future_plan',
        (string) $planId,
        'Reopened future plan'
    );
    Session::setFlashMesssge('success_message', 'Plan reopened.');
}

redirect('/admin/future-plans');
