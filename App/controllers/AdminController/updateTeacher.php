<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
$categorizedSubjects = adminCategorizedSubjects($db);
$availableSubjects = $categorizedSubjects['all'];

$id = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$role = adminNormalizeRole($_POST['role'] ?? 'teacher');
$isActive = isset($_POST['is_active']) ? 1 : 0;
$submittedJunior = $_POST['subjects_junior'] ?? null;
$submittedSenior = $_POST['subjects_senior'] ?? null;

if ($submittedJunior !== null || $submittedSenior !== null) {
    // New UI: read subject assignment from category checkboxes.
    $categoryMap = adminBuildTeacherSubjectCategoryMap($submittedJunior ?? [], $submittedSenior ?? [], $availableSubjects);
    $subjects = array_keys($categoryMap);
} else {
    // Backward compatibility for older payload shape.
    $subjects = adminNormalizeSubjects($_POST['subjects'] ?? [], $availableSubjects);
    $categoryMap = [];
    foreach ($subjects as $subjectKey) {
        $categoryMap[$subjectKey] = 'both';
    }
}
$newPassword = trim($_POST['new_password'] ?? '');
$errors = [];

if ($id <= 0) {
    $errors['id'] = 'Invalid user selected.';
}

$targetUser = $db->query('SELECT * FROM teacher_users WHERE id = :id LIMIT 1', [
    'id' => $id
])->fetch();

if (!$targetUser) {
    $errors['not_found'] = 'User not found.';
}

$canSetQuestions = isset($_POST['can_set_questions'])
    ? 1
    : (int) ($targetUser['can_set_questions'] ?? 0);
$canManageStudents = isset($_POST['can_manage_students'])
    ? 1
    : (int) ($targetUser['can_manage_students'] ?? 0);

if (!Validation::string($name, 2, 50)) {
    $errors['name'] = 'Name must be between 2 and 50 characters.';
}

if ($newPassword !== '' && !Validation::string($newPassword, 6, 100)) {
    $errors['password'] = 'New password must be at least 6 characters.';
}

if (empty($subjects)) {
    $errors['subjects'] = 'Select at least one subject.';
}

$duplicate = $db->query('SELECT id FROM teacher_users WHERE name = :name AND id != :id LIMIT 1', [
    'name' => $name,
    'id' => $id
])->fetch();

if ($duplicate) {
    $errors['duplicate'] = 'Another user already has this name.';
}

$targetRole = adminNormalizeRole($targetUser['role'] ?? 'teacher');
$adminCountRow = $db->query("SELECT COUNT(*) AS total FROM teacher_users WHERE LOWER(role) = 'admin'")->fetch();
$adminCount = (int) ($adminCountRow['total'] ?? 0);

if ($targetRole === 'admin' && $role !== 'admin' && $adminCount <= 1) {
    // Never remove the last admin role.
    $errors['role'] = 'At least one admin user must remain.';
}

$activeAdminCountRow = $db->query("SELECT COUNT(*) AS total FROM teacher_users WHERE LOWER(role) = 'admin' AND is_active = 1")->fetch();
$activeAdminCount = (int) ($activeAdminCountRow['total'] ?? 0);
if ($targetRole === 'admin' && $isActive === 0 && (int) ($targetUser['is_active'] ?? 1) === 1 && $activeAdminCount <= 1) {
    // Never deactivate the last active admin.
    $errors['is_active'] = 'At least one active admin must remain.';
}

if (!empty($errors)) {
    // Show validation messages and keep user list visible.
    $users = $db->query("SELECT id, name, role, can_set_questions, is_active, can_manage_students, assigned_subjects, assigned_subject_categories, created_at FROM teacher_users WHERE LOWER(role) != 'admin' ORDER BY id DESC")->fetchAll();

    loadView('admin/teachers', [
        'errors' => $errors,
        'users' => $users,
        'availableSubjects' => $availableSubjects,
        'juniorSubjects' => $categorizedSubjects['junior'],
        'seniorSubjects' => $categorizedSubjects['senior'],
        'activeTab' => 'manage'
    ]);
    exit;
}

$params = [
    'id' => $id,
    'name' => $name,
    'role' => $role,
    'can_set_questions' => $canSetQuestions,
    'is_active' => $isActive,
    'can_manage_students' => $canManageStudents,
    'assigned_subjects' => adminSubjectsToStorage($subjects, $availableSubjects),
    'assigned_subject_categories' => adminAssignedSubjectCategoriesToStorage($categoryMap, $availableSubjects)
];

$passwordSql = '';
if ($newPassword !== '') {
    $passwordSql = ', password = :password';
    $params['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
}

$db->query(
    "UPDATE teacher_users
     SET name = :name,
         role = :role,
         can_set_questions = :can_set_questions,
         is_active = :is_active,
         can_manage_students = :can_manage_students,
         assigned_subjects = :assigned_subjects,
         assigned_subject_categories = :assigned_subject_categories
         {$passwordSql}
     WHERE id = :id",
    $params
);
adminAuditLog(
    $db,
    'teacher.update',
    'teacher_user',
    (string) $id,
    'Updated user "' . $name . '"',
    [
        'role' => $role,
        'is_active' => $isActive,
        'can_set_questions' => $canSetQuestions,
        'can_manage_students' => $canManageStudents,
        'subjects' => $subjects,
        'password_changed' => $newPassword !== ''
    ]
);

$sessionUser = Session::get('user');
if ((int) ($sessionUser['id'] ?? 0) === $id) {
    // If admin edited self, refresh session data immediately.
    Session::set('user', [
        'id' => $id,
        'name' => $name,
        'role' => $role,
        'can_set_questions' => $canSetQuestions,
        'is_active' => $isActive,
        'can_manage_students' => $canManageStudents,
        'assigned_subjects' => adminSubjectsToStorage($subjects, $availableSubjects),
        'assigned_subject_categories' => adminAssignedSubjectCategoriesToStorage($categoryMap, $availableSubjects)
    ]);
}

Session::setFlashMesssge('success_message', 'User updated successfully.');
redirect('/admin/teachers?tab=manage');
