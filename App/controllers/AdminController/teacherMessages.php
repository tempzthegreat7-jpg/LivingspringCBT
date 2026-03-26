<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureTeacherAlertsSchema($db);
adminEnsureTeacherAlertRepliesSchema($db);

$teachers = $db->query("SELECT id, name FROM teacher_users WHERE LOWER(role) = 'teacher' ORDER BY name ASC")->fetchAll();
$selectedTeacherId = (int) ($_GET['teacher'] ?? 0);

if ($selectedTeacherId <= 0 && !empty($teachers)) {
    $selectedTeacherId = (int) ($teachers[0]['id'] ?? 0);
}

$alerts = [];
$repliesByAlert = [];
$conversation = [];
$latestAlertId = 0;

if ($selectedTeacherId > 0) {
    $alerts = adminFetchTeacherAlerts($db, 300, null, $selectedTeacherId);
    $replyRows = adminFetchTeacherAlertReplies($db, 1000, $selectedTeacherId);
    $repliesByAlert = adminRepliesByAlert($replyRows);

    foreach ($alerts as $row) {
        $alertId = (int) ($row['id'] ?? 0);
        $latestAlertId = max($latestAlertId, $alertId);
        $originRole = strtolower(trim((string) ($row['origin_role'] ?? 'teacher')));
        $conversation[] = [
            'kind' => 'message',
            'id' => $alertId,
            'alert_id' => $alertId,
            'origin_role' => $originRole === 'admin' ? 'admin' : 'teacher',
            'sender_name' => $originRole === 'admin'
                ? ((string) ($row['admin_sender_name'] ?? 'Admin'))
                : ((string) ($row['teacher_name'] ?? 'Teacher')),
            'text' => (string) ($row['message'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'status' => adminNormalizeTeacherAlertStatus($row['status'] ?? 'open')
        ];
    }

    foreach ($replyRows as $row) {
        $replyId = (int) ($row['id'] ?? 0);
        $conversation[] = [
            'kind' => 'reply',
            'id' => $replyId,
            'alert_id' => (int) ($row['alert_id'] ?? 0),
            'origin_role' => 'admin',
            'sender_name' => (string) ($row['admin_name'] ?? 'Admin'),
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

loadView('admin/teacher-messages', [
    'teachers' => $teachers,
    'selectedTeacherId' => $selectedTeacherId,
    'alerts' => $alerts,
    'repliesByAlert' => $repliesByAlert,
    'conversation' => $conversation,
    'latestAlertId' => $latestAlertId
]);
