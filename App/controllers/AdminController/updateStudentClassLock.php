<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureStudentUsersSchema($db);
adminEnsureStudentClassLocksSchema($db);

$studentClass = adminNormalizeStudentClass($_POST['student_class'] ?? 'SS3');
$isLocked = isset($_POST['is_locked']) && (int) ($_POST['is_locked'] ?? 0) === 1;
$adminUserId = (int) (Session::get('user')['id'] ?? 0);

adminSetStudentClassLock($db, $studentClass, $isLocked, $adminUserId > 0 ? $adminUserId : null);

adminAuditLog(
    $db,
    'student.class_lock',
    'student_class',
    $studentClass,
    ($isLocked ? 'Locked ' : 'Unlocked ') . 'student class "' . $studentClass . '"',
    [
        'class' => $studentClass,
        'is_locked' => $isLocked ? 1 : 0
    ]
);

Session::setFlashMesssge('success_message', $studentClass . ($isLocked ? ' locked successfully.' : ' unlocked successfully.'));
redirect('/admin/students?tab=manage');
