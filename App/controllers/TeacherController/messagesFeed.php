<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureTeacherAlertsSchema($db);
adminEnsureTeacherAlertRepliesSchema($db);

$teacherUserId = (int) (Session::get('user')['id'] ?? 0);
$alerts = [];
$unreadReplyCount = 0;
$latestActivityTs = 0;
$activityCount = 0;

if ($teacherUserId > 0) {
    $alerts = adminFetchTeacherAlerts($db, 40, null, $teacherUserId);
    $unreadReplyCount = adminCountUnreadTeacherInbox($db, $teacherUserId);
    $replies = adminFetchTeacherAlertReplies($db, 300, $teacherUserId);

    foreach ($alerts as $row) {
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

    $activityCount = count($alerts) + count($replies);
}

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'alerts' => $alerts,
    'unread_reply_count' => $unreadReplyCount,
    'latest_activity_ts' => $latestActivityTs,
    'activity_count' => $activityCount
]);
exit;
