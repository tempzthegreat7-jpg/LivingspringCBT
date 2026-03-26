<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureTeacherAlertsSchema($db);

$teacherUserId = (int) ($_POST['teacher_user_id'] ?? 0);
$title = trim((string) ($_POST['title'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$adminUserId = (int) (Session::get('user')['id'] ?? 0);

if ($teacherUserId <= 0) {
    Session::setFlashMesssge('error_message', 'Select a teacher to send a direct message.');
    Session::setFlashMesssge('old_teacher_direct_message', [
        'teacher_user_id' => $teacherUserId,
        'message' => $message
    ]);
    redirect('/admin/teacher-messages');
}

if (!Validation::string($message, 1, 2000)) {
    Session::setFlashMesssge('error_message', 'Message must be between 1 and 2000 characters.');
    Session::setFlashMesssge('old_teacher_direct_message', [
        'teacher_user_id' => $teacherUserId,
        'message' => $message
    ]);
    redirect('/admin/teacher-messages?teacher=' . urlencode((string) $teacherUserId));
}

$teacherRow = $db->query(
    'SELECT id FROM teacher_users WHERE id = :id LIMIT 1',
    ['id' => $teacherUserId]
)->fetch();

if (!$teacherRow) {
    Session::setFlashMesssge('error_message', 'Teacher was not found.');
    redirect('/admin/teacher-messages');
}

adminCreateAdminTeacherMessage($db, $teacherUserId, $adminUserId, $title, $message);
adminAuditLog(
    $db,
    'teacher_alert.send',
    'teacher_alert',
    (string) $teacherUserId,
    'Sent direct message to teacher #' . $teacherUserId
);

Session::setFlashMesssge('success_message', 'Message sent to teacher.');
redirect('/admin/teacher-messages?teacher=' . urlencode((string) $teacherUserId));
