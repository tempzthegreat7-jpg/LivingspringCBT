<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureFuturePlansSchema($db);

$adminUser = Session::get('user');
$adminUserId = (int) ($adminUser['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($title !== '' && $description !== '') {
        adminCreateFuturePlan($db, $title, $description, $adminUserId);
        adminAuditLog($db, 'future_plan_create', 'future_plan', null, "Created future plan: {$title}");
        Session::setFlashMessage('success_message', 'Future plan added successfully.');
    } else {
        Session::setFlashMessage('error_message', 'Title and description are required.');
    }
    redirect('/admin/future-plans');
}

$plans = adminFetchFuturePlans($db, 100);

loadView('admin/future-plans', [
    'plans' => $plans,
    'adminUserId' => $adminUserId
]);
