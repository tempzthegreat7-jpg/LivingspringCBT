<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureStudentUsersSchema($db);
adminEnsureStudentClassLocksSchema($db);
$classLocks = adminStudentClassLockMap($db);

$namesByClass = [];
foreach (adminClassOptions() as $classLabel) {
    $namesByClass[$classLabel] = [];
}

$rows = $db->query(
    'SELECT student_name, student_class
     FROM student_users
     WHERE is_active = 1
     ORDER BY student_class ASC, student_name ASC'
)->fetchAll();

foreach ($rows as $row) {
    $classLabel = adminNormalizeStudentClass((string) ($row['student_class'] ?? 'SS3'));
    if (!empty($classLocks[$classLabel])) {
        continue;
    }
    $studentName = trim((string) ($row['student_name'] ?? ''));
    if ($studentName === '') {
        continue;
    }

    $namesByClass[$classLabel][] = $studentName;
}

loadView('names', [
    'namesByClass' => $namesByClass
]);
