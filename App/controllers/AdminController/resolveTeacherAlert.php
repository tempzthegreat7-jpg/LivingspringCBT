<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureTeacherAlertsSchema($db);

$alertId = (int) ($_POST['id'] ?? 0);
if ($alertId <= 0) {
    Session::setFlashMesssge('error_message', 'Invalid teacher alert.');
    redirect('/admin/teacher-messages');
}

adminResolveTeacherAlertById($db, $alertId);
adminAuditLog(
    $db,
    'teacher_alert.resolve',
    'teacher_alert',
    (string) $alertId,
    'Resolved teacher alert #' . $alertId
);

Session::setFlashMesssge('success_message', 'Teacher alert marked as resolved.');
redirect('/admin/teacher-messages');
