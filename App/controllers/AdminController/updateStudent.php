<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureStudentUsersSchema($db);

$id = (int) ($_POST['id'] ?? 0);
$studentName = trim((string) ($_POST['student_name'] ?? ''));
$studentClass = adminNormalizeStudentClass($_POST['student_class'] ?? 'SS3');
$newPassword = trim((string) ($_POST['new_password'] ?? ''));
$isActive = isset($_POST['is_active']) ? 1 : 0;

$errors = [];

if ($id <= 0) {
    $errors['id'] = 'Invalid student selected.';
}

if (!Validation::string($studentName, 2, 120)) {
    $errors['student_name'] = 'Student name must be between 2 and 120 characters.';
}

if ($newPassword !== '' && !Validation::string($newPassword, 6, 100)) {
    $errors['new_password'] = 'New password must be at least 6 characters.';
}

$target = $db->query('SELECT id, student_name, student_class, is_active FROM student_users WHERE id = :id LIMIT 1', [
    'id' => $id
])->fetch();

if (!$target) {
    $errors['not_found'] = 'Student login not found.';
}

$duplicate = $db->query(
    'SELECT id FROM student_users WHERE student_name = :student_name AND student_class = :student_class AND id != :id LIMIT 1',
    [
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'id' => $id
    ]
)->fetch();

if ($duplicate) {
    $errors['duplicate'] = 'Another student login already uses this name and class.';
}

if (!empty($errors)) {
    $students = $db->query('SELECT id, student_name, student_class, display_password, is_active, created_at FROM student_users ORDER BY id DESC')->fetchAll();
    loadView('admin/students', [
        'errors' => $errors,
        'students' => $students,
        'classOptions' => adminClassOptions()
    ]);
    exit;
}

$params = [
    'id' => $id,
    'student_name' => $studentName,
    'student_class' => $studentClass,
    'is_active' => $isActive
];

$passwordSql = '';
if ($newPassword !== '') {
    $passwordSql = ', password_hash = :password_hash, display_password = :display_password';
    $params['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
    $params['display_password'] = $newPassword;
}

$db->query(
    "UPDATE student_users
     SET student_name = :student_name,
         student_class = :student_class,
         is_active = :is_active
         {$passwordSql}
     WHERE id = :id",
    $params
);

adminAuditLog(
    $db,
    'student.update',
    'student_user',
    (string) $id,
    'Updated student login "' . $studentName . '"',
    [
        'class' => $studentClass,
        'is_active' => $isActive,
        'password_changed' => $newPassword !== ''
    ]
);

Session::setFlashMesssge('success_message', 'Student login updated successfully.');
redirect('/admin/students?tab=manage');
