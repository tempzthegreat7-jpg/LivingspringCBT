<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureTeacherAlertsSchema($db);
adminEnsureTeacherAlertRepliesSchema($db);

$teacherUserId = (int) ($_GET['teacher'] ?? 0);
$alerts = adminFetchTeacherAlerts($db, 200, null, $teacherUserId > 0 ? $teacherUserId : null);
$replies = adminFetchTeacherAlertReplies($db, 1000, $teacherUserId > 0 ? $teacherUserId : null);
$latestAlertId = 0;
$latestActivityTs = 0;
foreach ($alerts as $row) {
    $latestAlertId = max($latestAlertId, (int) ($row['id'] ?? 0));
    $ts = strtotime((string) ($row['created_at'] ?? ''));
    if ($ts > $latestActivityTs) {
        $latestActivityTs = $ts;
    }
}

foreach ($replies as $row) {
    $ts = strtotime((string) ($row['created_at'] ?? ''));
    if ($ts > $latestActivityTs) {
        $latestActivityTs = $ts;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'latest_alert_id' => $latestAlertId,
    'latest_activity_ts' => $latestActivityTs,
    'total' => count($alerts) + count($replies)
]);
exit;
