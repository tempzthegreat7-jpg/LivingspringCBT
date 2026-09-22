<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureStudentUsersSchema($db);

$studentId = (int) ($_POST['id'] ?? 0);
$isLocked = isset($_POST['is_locked']) && (int) ($_POST['is_locked'] ?? 0) === 1;
$adminUserId = (int) (Session::get('user')['id'] ?? 0);

if ($studentId <= 0) {
    Session::setFlashMesssge('error_message', 'Invalid student ID.');
    redirect('/admin/students?tab=manage');
}

adminSetStudentLock($db, $studentId, $isLocked, $adminUserId > 0 ? $adminUserId : null);

$student = $db->query(
    'SELECT student_name, student_class FROM student_users WHERE id = :id LIMIT 1',
    ['id' => $studentId]
)->fetch();

$studentName = $student ? (string) ($student['student_name'] ?? 'Unknown') : 'Unknown';
$studentClass = $student ? (string) ($student['student_class'] ?? '') : '';

adminAuditLog(
    $db,
    'student.lock',
    'student_user',
    (string) $studentId,
    ($isLocked ? 'Locked ' : 'Unlocked ') . "student \"{$studentName}\" ({$studentClass})",
    [
        'student_id' => $studentId,
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'is_locked' => $isLocked ? 1 : 0
    ]
);

Session::setFlashMesssge('success_message', $studentName . ($isLocked ? ' locked successfully.' : ' unlocked successfully.'));

redirect('/admin/students?tab=manage');