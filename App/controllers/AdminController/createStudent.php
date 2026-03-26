<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureStudentUsersSchema($db);

$studentName = trim((string) ($_POST['student_name'] ?? ''));
$studentClass = adminNormalizeStudentClass($_POST['student_class'] ?? 'SS3');
$passwordInput = trim((string) ($_POST['password'] ?? ''));
$finalPassword = $passwordInput !== '' ? $passwordInput : adminGenerateStudentPassword(8);
$isActive = isset($_POST['is_active']) ? 1 : 0;

$errors = [];

if (!Validation::string($studentName, 2, 120)) {
    $errors['student_name'] = 'Student name must be between 2 and 120 characters.';
}

if (!Validation::string($finalPassword, 6, 100)) {
    $errors['password'] = 'Password must be at least 6 characters.';
}

$duplicate = $db->query(
    'SELECT id FROM student_users WHERE student_name = :student_name AND student_class = :student_class LIMIT 1',
    [
        'student_name' => $studentName,
        'student_class' => $studentClass
    ]
)->fetch();

if ($duplicate) {
    $errors['duplicate'] = 'This student already has a login for the selected class.';
}

if (!empty($errors)) {
    $students = $db->query('SELECT id, student_name, student_class, display_password, is_active, created_at FROM student_users ORDER BY id DESC')->fetchAll();
    loadView('admin/students', [
        'errors' => $errors,
        'students' => $students,
        'classOptions' => adminClassOptions(),
        'old' => [
            'student_name' => $studentName,
            'student_class' => $studentClass,
            'is_active' => $isActive
        ]
    ]);
    exit;
}

$db->query(
    'INSERT INTO student_users (student_name, student_class, password_hash, display_password, is_active)
     VALUES (:student_name, :student_class, :password_hash, :display_password, :is_active)',
    [
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'password_hash' => password_hash($finalPassword, PASSWORD_BCRYPT),
        'display_password' => $finalPassword,
        'is_active' => $isActive
    ]
);

$newStudentId = (int) $db->connection->lastInsertId();

adminAuditLog(
    $db,
    'student.create',
    'student_user',
    (string) $newStudentId,
    'Created student login "' . $studentName . '"',
    [
        'class' => $studentClass,
        'is_active' => $isActive,
        'generated_password' => $passwordInput === ''
    ]
);

Session::setFlashMesssge('created_student_login', [
    'student_name' => $studentName,
    'student_class' => $studentClass,
    'password' => $finalPassword
]);
Session::setFlashMesssge('success_message', 'Student login created successfully.');
redirect('/admin/students?tab=create');
