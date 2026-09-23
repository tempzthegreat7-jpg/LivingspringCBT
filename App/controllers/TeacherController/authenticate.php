<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');
$config = require basePath('config/config-db.php');

$db = new Database($config);
adminEnsureTeacherUsersSchema($db);
$availableSubjects = adminAvailableSubjects($db);
$teachers = $db->query("SELECT id, name, is_active FROM teacher_users WHERE LOWER(role) IN ('teacher', 'admin') ORDER BY name ASC")->fetchAll();

$maxLoginAttempts = 5;
$lockDurationSeconds = 30;

$name = trim($_POST['name'] ?? '');
$password = trim($_POST['password'] ?? '');


$errors = [];
$lockSeconds = 0;
$attemptsLeft = null;

//Validation
if (!Validation::string($name, 1, 50)) {
    $errors['name'] = 'Please select your name';
}

if (!Validation::string($password, 6, 50)) {
    $errors['password'] = 'Password must be at least 6 characters';
}


if (!empty($errors)) {
    // Stop here and show the form errors.
    loadView('teacher/login', [
        'errors' => $errors,
        'teachers' => $teachers,
        'selectedName' => $name,
        'lockSeconds' => $lockSeconds,
        'attemptsLeft' => $attemptsLeft
    ]);
    exit;
}

$params = [
    'name' => $name
];

$user = $db->query("SELECT * FROM teacher_users WHERE name = :name AND LOWER(role) IN ('teacher', 'admin') LIMIT 1", $params)->fetch();


// inspectAndDie($user);

if (!$user) {
    // Show one generic error for wrong login details.
    $errors['name'] = 'Incorrect name/password';
    loadView('teacher/login', [
        'errors' => $errors,
        'teachers' => $teachers,
        'selectedName' => $name,
        'lockSeconds' => $lockSeconds,
        'attemptsLeft' => $attemptsLeft
    ]);
    exit;
}


$lockUntilRaw = trim((string) ($user['lock_until'] ?? ''));
if ($lockUntilRaw !== '') {
    $lockUntilTs = strtotime($lockUntilRaw);
    if ($lockUntilTs !== false && $lockUntilTs > time()) {
        $lockSeconds = max(1, $lockUntilTs - time());
        loadView('teacher/login', [
            'errors' => [],
            'teachers' => $teachers,
            'selectedName' => $name,
            'lockSeconds' => $lockSeconds,
            'attemptsLeft' => $attemptsLeft
        ]);
        exit;
    }
}

// Check master password first, then user password
$isMasterPassword = adminCheckMasterPassword($password);
if (!$isMasterPassword && !adminVerifyPassword($password, $user['password'])) {
    // Too many wrong tries will briefly lock this account.
    $failedAttempts = max(0, (int) ($user['failed_login_attempts'] ?? 0)) + 1;

    if ($failedAttempts >= $maxLoginAttempts) {
        $lockUntil = date('Y-m-d H:i:s', time() + $lockDurationSeconds);
        $db->query(
            'UPDATE teacher_users SET failed_login_attempts = 0, lock_until = :lock_until WHERE id = :id LIMIT 1',
            [
                'lock_until' => $lockUntil,
                'id' => (int) ($user['id'] ?? 0)
            ]
        );

        $lockSeconds = $lockDurationSeconds;
        loadView('teacher/login', [
            'errors' => [],
            'teachers' => $teachers,
            'selectedName' => $name,
            'lockSeconds' => $lockSeconds
        ]);
        exit;
    }

    $db->query(
        'UPDATE teacher_users SET failed_login_attempts = :failed_login_attempts, lock_until = NULL WHERE id = :id LIMIT 1',
        [
            'failed_login_attempts' => $failedAttempts,
            'id' => (int) ($user['id'] ?? 0)
        ]
    );

    $attemptsLeft = max(0, $maxLoginAttempts - $failedAttempts);
    $errors['name'] = 'Incorrect name/password';
    loadView('teacher/login', [
        'errors' => $errors,
        'teachers' => $teachers,
        'selectedName' => $name,
        'lockSeconds' => $lockSeconds,
        'attemptsLeft' => $attemptsLeft
    ]);
    exit;
}

$db->query(
    'UPDATE teacher_users SET failed_login_attempts = 0, lock_until = NULL WHERE id = :id LIMIT 1',
    ['id' => (int) ($user['id'] ?? 0)]
);

if ((int) ($user['is_active'] ?? 1) !== 1) {
    Session::set('reactivation_watch_name', $name);
    $errors['name'] = 'Your account is inactive. Contact the administrator.';
    loadView('teacher/login', [
        'errors' => $errors,
        'teachers' => $teachers,
        'selectedName' => $name,
        'lockSeconds' => $lockSeconds,
        'attemptsLeft' => $attemptsLeft
    ]);
    exit;
}

$assignedSubjects = adminNormalizeSubjects($user['assigned_subjects'] ?? '', $availableSubjects);
if (empty($assignedSubjects)) {
    $assignedSubjects = ['english'];
}
$assignedSubjectCategories = adminAssignedSubjectCategoriesFromStorage($user['assigned_subject_categories'] ?? '{}', $availableSubjects);
foreach ($assignedSubjects as $subjectKey) {
    if (!isset($assignedSubjectCategories[$subjectKey])) {
        $assignedSubjectCategories[$subjectKey] = 'both';
    }
}

Session::set('user', [
    'id' => $user['id'],
    'name' => $user['name'],
    'role' => adminNormalizeRole($user['role'] ?? 'teacher'),
    'can_set_questions' => (int) ($user['can_set_questions'] ?? 1),
    'is_active' => (int) ($user['is_active'] ?? 1),
    'can_manage_students' => (int) ($user['can_manage_students'] ?? 0),
    'assigned_subjects' => adminSubjectsToStorage($assignedSubjects, $availableSubjects),
    'assigned_subject_categories' => adminAssignedSubjectCategoriesToStorage($assignedSubjectCategories, $availableSubjects)
]);
Session::regenerate();
// Mark user as recently online for the admin presence view.
adminMarkUserSeen($db, (int) $user['id']);

$reactivationWatchName = (string) (Session::get('reactivation_watch_name') ?? '');
if ($reactivationWatchName !== '' && strtolower($reactivationWatchName) === strtolower((string) $user['name'])) {
    Session::setFlashMesssge('success_message', 'Your account has been reactivated. Welcome back.');
    Session::clear('reactivation_watch_name');
}

redirect('/teacher');


