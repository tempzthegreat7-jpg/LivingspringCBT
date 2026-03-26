<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureNotificationsSchema($db);
adminPurgeExpiredNotifications($db);

$notificationId = (int) ($_POST['id'] ?? 0);
$title = trim((string) ($_POST['title'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$type = adminNormalizeNotificationType($_POST['type'] ?? 'general');

if ($notificationId <= 0) {
    Session::setFlashMesssge('error_message', 'Invalid notification.');
    redirect('/admin/notifications');
}

if (!Validation::string($title, 3, 160)) {
    Session::setFlashMesssge('error_message', 'Title must be between 3 and 160 characters.');
    redirect('/admin/notifications');
}

if (!Validation::string($message, 6, 2000)) {
    Session::setFlashMesssge('error_message', 'Message must be between 6 and 2000 characters.');
    redirect('/admin/notifications');
}

adminUpdateNotificationById($db, $notificationId, $title, $message, $type);
adminAuditLog(
    $db,
    'notification.update',
    'notification',
    (string) $notificationId,
    'Updated notification "' . $title . '"',
    [
        'type' => $type
    ]
);

Session::setFlashMesssge('success_message', 'Notification updated.');
redirect('/admin/notifications');
