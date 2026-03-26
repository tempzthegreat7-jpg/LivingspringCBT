<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureStudentUsersSchema($db);
adminEnsureStudentClassLocksSchema($db);

$students = $db->query('SELECT id, student_name, student_class, display_password, is_active, created_at FROM student_users ORDER BY id DESC')->fetchAll();
$classLocks = adminStudentClassLockMap($db);

loadView('admin/students', [
    'students' => $students,
    'classOptions' => adminClassOptions(),
    'classLocks' => $classLocks
]);
