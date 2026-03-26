<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureTeacherAlertsSchema($db);
adminEnsureTeacherAlertRepliesSchema($db);

$teacherUserId = (int) (Session::get('user')['id'] ?? 0);
$myAlerts = [];
$myRepliesByAlert = [];
$conversation = [];
$unreadReplyCount = 0;

if ($teacherUserId > 0) {
    $myAlerts = adminFetchTeacherAlerts($db, 30, null, $teacherUserId);
    $myReplies = adminFetchTeacherAlertReplies($db, 300, $teacherUserId);
    $myRepliesByAlert = adminRepliesByAlert($myReplies);
    $unreadReplyCount = adminCountUnreadTeacherInbox($db, $teacherUserId);
    adminMarkTeacherAlertMessagesRead($db, $teacherUserId);
    adminMarkTeacherRepliesRead($db, $teacherUserId);

    foreach ($myAlerts as $row) {
        $conversation[] = [
            'id' => (int) ($row['id'] ?? 0),
            'origin_role' => strtolower(trim((string) ($row['origin_role'] ?? 'teacher'))) === 'admin' ? 'admin' : 'teacher',
            'text' => (string) ($row['message'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'status' => adminNormalizeTeacherAlertStatus($row['status'] ?? 'open')
        ];
    }

    foreach ($myReplies as $row) {
        $conversation[] = [
            'id' => (int) ($row['id'] ?? 0),
            'origin_role' => 'admin',
            'text' => (string) ($row['message'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'status' => 'open'
        ];
    }

    usort($conversation, function ($a, $b) {
        $aTime = strtotime((string) ($a['created_at'] ?? ''));
        $bTime = strtotime((string) ($b['created_at'] ?? ''));

        if ($aTime === $bTime) {
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        }
        return $aTime <=> $bTime;
    });
}

loadView('teacher/notify-admin', [
    'myAlerts' => $myAlerts,
    'myRepliesByAlert' => $myRepliesByAlert,
    'unreadReplyCount' => $unreadReplyCount,
    'conversation' => $conversation
]);
