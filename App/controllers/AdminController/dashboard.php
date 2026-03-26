<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureMaintenanceModeSchema($db);

// Dashboard summary cards.
$totalUsersRow = $db->query('SELECT COUNT(*) AS total FROM teacher_users')->fetch();
$teacherCountRow = $db->query("SELECT COUNT(*) AS total FROM teacher_users WHERE LOWER(role) = 'teacher'")->fetch();
$adminCountRow = $db->query("SELECT COUNT(*) AS total FROM teacher_users WHERE LOWER(role) = 'admin'")->fetch();
$questionManagersRow = $db->query('SELECT COUNT(*) AS total FROM teacher_users WHERE can_set_questions = 1')->fetch();

$recentUsers = $db->query("SELECT id, name, role, can_set_questions, is_active, can_manage_students, assigned_subjects, created_at FROM teacher_users WHERE LOWER(role) != 'admin' ORDER BY id DESC LIMIT 6")->fetchAll();
$maintenanceMode = adminGetMaintenanceMode($db);

loadView('admin/dashboard', [
    'totalUsers' => (int) ($totalUsersRow['total'] ?? 0),
    'teacherCount' => (int) ($teacherCountRow['total'] ?? 0),
    'adminCount' => (int) ($adminCountRow['total'] ?? 0),
    'questionManagers' => (int) ($questionManagersRow['total'] ?? 0),
    'recentUsers' => $recentUsers,
    'maintenanceMode' => $maintenanceMode
]);
