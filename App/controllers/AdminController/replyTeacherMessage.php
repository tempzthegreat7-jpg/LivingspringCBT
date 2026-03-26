<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureTeacherAlertsSchema($db);
adminEnsureTeacherAlertRepliesSchema($db);

$alertId = (int) ($_POST['alert_id'] ?? 0);
$teacherUserId = (int) ($_POST['teacher_user_id'] ?? 0);
$message = trim((string) ($_POST['message'] ?? ''));
$adminUserId = (int) (Session::get('user')['id'] ?? 0);

if ($alertId <= 0 || $teacherUserId <= 0) {
    Session::setFlashMesssge('error_message', 'Invalid teacher message.');
    redirect('/admin/teacher-messages');
}

if (!Validation::string($message, 2, 2000)) {
    Session::setFlashMesssge('error_message', 'Reply must be between 2 and 2000 characters.');
    redirect('/admin/teacher-messages?teacher=' . urlencode((string) $teacherUserId));
}

$alertRow = $db->query(
    'SELECT id, teacher_user_id
     FROM teacher_admin_alerts
     WHERE id = :id
     LIMIT 1',
    ['id' => $alertId]
)->fetch();

if (!$alertRow || (int) ($alertRow['teacher_user_id'] ?? 0) !== $teacherUserId) {
    Session::setFlashMesssge('error_message', 'Teacher message was not found.');
    redirect('/admin/teacher-messages?teacher=' . urlencode((string) $teacherUserId));
}

adminCreateTeacherAlertReply($db, $alertId, $teacherUserId, $adminUserId, $message);
adminAuditLog(
    $db,
    'teacher_alert.reply',
    'teacher_alert',
    (string) $alertId,
    'Replied to teacher message #' . $alertId,
    [
        'teacher_user_id' => $teacherUserId
    ]
);

Session::setFlashMesssge('success_message', 'Reply sent to teacher.');
redirect('/admin/teacher-messages?teacher=' . urlencode((string) $teacherUserId));
