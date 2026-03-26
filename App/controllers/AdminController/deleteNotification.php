<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureNotificationsSchema($db);
adminPurgeExpiredNotifications($db);

$notificationId = (int) ($_POST['id'] ?? 0);
if ($notificationId <= 0) {
    Session::setFlashMesssge('error_message', 'Invalid notification.');
    redirect('/admin/notifications');
}

adminDeleteNotificationById($db, $notificationId);
adminAuditLog(
    $db,
    'notification.delete',
    'notification',
    (string) $notificationId,
    'Deleted notification #' . $notificationId
);

Session::setFlashMesssge('success_message', 'Notification deleted.');
redirect('/admin/notifications');
