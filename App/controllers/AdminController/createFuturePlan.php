<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureFuturePlansSchema($db);

$planText = trim((string) ($_POST['plan_text'] ?? ''));
$errors = [];

if (Validation::string($planText, 2, 1000)) {
    adminCreateFuturePlan($db, $planText, (int) (Session::get('user')['id'] ?? 0));
    $newPlanId = (int) $db->connection->lastInsertId();

    adminAuditLog(
        $db,
        'future_plan.create',
        'future_plan',
        (string) $newPlanId,
        'Created future plan',
        [
            'plan_preview' => substr($planText, 0, 120)
        ]
    );

    Session::setFlashMesssge('success_message', 'Future plan saved.');
} else {
    Session::setFlashMesssge('error_message', 'Plan must be between 2 and 1000 characters.');
}

redirect('/admin/future-plans');
