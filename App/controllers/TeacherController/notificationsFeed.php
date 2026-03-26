<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureNotificationsSchema($db);
adminPurgeExpiredNotifications($db);

$rows = adminFetchLatestNotifications($db, 120, false);

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'notifications' => $rows
]);
exit;
