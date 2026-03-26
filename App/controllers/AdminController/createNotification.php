<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureNotificationsSchema($db);

$title = trim((string) ($_POST['title'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$type = adminNormalizeNotificationType($_POST['type'] ?? 'general');
$errors = [];

if (!Validation::string($title, 3, 160)) {
    $errors['title'] = 'Title must be between 3 and 160 characters.';
}

if (!Validation::string($message, 6, 2000)) {
    $errors['message'] = 'Message must be between 6 and 2000 characters.';
}

if (!empty($errors)) {
    Session::setFlashMesssge('error_message', implode(' ', array_values($errors)));
    Session::setFlashMesssge('old_notification', [
        'title' => $title,
        'message' => $message,
        'type' => $type
    ]);
    redirect('/admin/notifications');
}

adminCreateNotification($db, $title, $message, $type, (int) (Session::get('user')['id'] ?? 0));
$newNotificationId = (int) $db->connection->lastInsertId();
adminAuditLog(
    $db,
    'notification.create',
    'notification',
    (string) $newNotificationId,
    'Created notification "' . $title . '"',
    [
        'type' => $type
    ]
);

Session::setFlashMesssge('success_message', 'Notification published for teachers.');
redirect('/admin/notifications');
