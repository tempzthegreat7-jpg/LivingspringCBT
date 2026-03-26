<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureNotificationsSchema($db);
adminPurgeExpiredNotifications($db);

$notifications = adminFetchLatestNotifications($db, 120);
$notificationTypes = adminNotificationTypeOptions();

loadView('admin/notifications', [
    'notifications' => $notifications,
    'notificationTypes' => $notificationTypes
]);
