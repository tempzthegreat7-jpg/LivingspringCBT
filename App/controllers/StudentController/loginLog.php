<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$student = Session::get('student') ?? [];
$studentId = (int) ($student['id'] ?? 0);
$studentName = trim((string) ($student['name'] ?? 'Student'));
$studentClass = adminNormalizeStudentClass((string) ($student['class'] ?? 'SS3'));

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureStudentUsersSchema($db);
adminEnsureStudentLoginLogsSchema($db);

$unlockExpiry = (int) (Session::get('student_login_log_unlocked_until') ?? 0);
$isUnlocked = $unlockExpiry > time();
if (!$isUnlocked) {
    Session::clear('student_login_log_unlocked_until');
}

$rows = $isUnlocked ? adminFetchStudentLoginLogs($db, 120, $studentId) : [];

loadView('student-login-log', [
    'student' => [
        'id' => $studentId,
        'name' => $studentName,
        'class' => $studentClass
    ],
    'isUnlocked' => $isUnlocked,
    'loginRows' => $rows
]);
