<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
$categorizedSubjects = adminCategorizedSubjects($db);
$availableSubjects = $categorizedSubjects['all'];

$name = trim($_POST['name'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = adminNormalizeRole($_POST['role'] ?? 'teacher');
$canSetQuestions = isset($_POST['can_set_questions']) ? 1 : 0;
$isActive = isset($_POST['is_active']) ? 1 : 0;
$canManageStudents = isset($_POST['can_manage_students']) ? 1 : 0;
$categoryMap = adminBuildTeacherSubjectCategoryMap($_POST['subjects_junior'] ?? [], $_POST['subjects_senior'] ?? [], $availableSubjects);
$subjects = array_keys($categoryMap);
$errors = [];

if (!Validation::string($name, 2, 50)) {
    $errors['name'] = 'Name must be between 2 and 50 characters.';
}

if (!Validation::string($password, 6, 100)) {
    $errors['password'] = 'Password must be at least 6 characters.';
}

if (empty($subjects)) {
    $errors['subjects'] = 'Select at least one subject.';
}

$existingUser = $db->query('SELECT id FROM teacher_users WHERE name = :name LIMIT 1', [
    'name' => $name
])->fetch();

if ($existingUser) {
    // Keep names unique for easier login/management.
    $errors['duplicate'] = 'A user with this name already exists.';
}

if (!empty($errors)) {
    // Re-render form with old values when validation fails.
    $users = $db->query('SELECT id, name, role, can_set_questions, is_active, can_manage_students, assigned_subjects, assigned_subject_categories, created_at FROM teacher_users ORDER BY id DESC')->fetchAll();

    loadView('admin/teachers', [
        'errors' => $errors,
        'users' => $users,
        'availableSubjects' => $availableSubjects,
        'juniorSubjects' => $categorizedSubjects['junior'],
        'seniorSubjects' => $categorizedSubjects['senior'],
        'old' => [
            'name' => $name,
            'role' => $role,
            'can_set_questions' => $canSetQuestions,
            'is_active' => $isActive,
            'can_manage_students' => $canManageStudents,
            'subjects' => $subjects,
            'subjects_junior' => adminNormalizeSubjects($_POST['subjects_junior'] ?? [], $availableSubjects),
            'subjects_senior' => adminNormalizeSubjects($_POST['subjects_senior'] ?? [], $availableSubjects)
        ]
    ]);
    exit;
}

$db->query(
    'INSERT INTO teacher_users (name, password, role, can_set_questions, is_active, can_manage_students, assigned_subjects, assigned_subject_categories) VALUES (:name, :password, :role, :can_set_questions, :is_active, :can_manage_students, :assigned_subjects, :assigned_subject_categories)',
    [
        'name' => $name,
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'role' => $role,
        'can_set_questions' => $canSetQuestions,
        'is_active' => $isActive,
        'can_manage_students' => $canManageStudents,
        'assigned_subjects' => adminSubjectsToStorage($subjects, $availableSubjects),
        'assigned_subject_categories' => adminAssignedSubjectCategoriesToStorage($categoryMap, $availableSubjects)
    ]
);
$newUserId = (int) $db->connection->lastInsertId();
adminAuditLog(
    $db,
    'teacher.create',
    'teacher_user',
    (string) $newUserId,
    'Created user "' . $name . '"',
    [
        'role' => $role,
        'can_set_questions' => $canSetQuestions,
        'is_active' => $isActive,
        'can_manage_students' => $canManageStudents,
        'subjects' => $subjects
    ]
);

// Save quick profile preview data for success panel.
Session::setFlashMesssge('created_user', [
    'name' => $name,
    'role' => $role,
    'can_set_questions' => $canSetQuestions,
    'can_manage_students' => $canManageStudents,
    'is_active' => $isActive,
    'subjects' => adminSubjectsLabels($subjects, $availableSubjects),
    'subject_categories' => array_map(
        function ($subjectKey, $category) use ($availableSubjects) {
            $label = $availableSubjects[$subjectKey] ?? ucwords(str_replace('_', ' ', $subjectKey));
            return $label . ': ' . $category;
        },
        array_keys($categoryMap),
        array_values($categoryMap)
    ),
    'subject_level_rows' => array_map(
        function ($subjectKey, $category) use ($availableSubjects) {
            $label = $availableSubjects[$subjectKey] ?? ucwords(str_replace('_', ' ', $subjectKey));
            $normalized = strtolower((string) $category);
            $level = 'Both';
            if ($normalized === 'junior') {
                $level = 'Junior';
            } elseif ($normalized === 'senior') {
                $level = 'Senior';
            }

            return [
                'subject' => $label,
                'level' => $level
            ];
        },
        array_keys($categoryMap),
        array_values($categoryMap)
    )
]);
Session::setFlashMesssge('success_message', 'User created successfully.');
redirect('/admin/teachers');
