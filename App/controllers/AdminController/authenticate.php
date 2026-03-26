<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
$availableSubjects = adminAvailableSubjects($db);

$maxLoginAttempts = 5;
$lockDurationSeconds = 30;
$lockSeconds = 0;
$attemptsLeft = null;
$lockUntil = (int) (Session::get('admin_login_lock_until') ?? 0);
if ($lockUntil > time()) {
    // If this browser is locked out, show countdown and stop.
    $lockSeconds = $lockUntil - time();
    loadView('admin/login', [
        'errors' => [],
        'lockSeconds' => $lockSeconds,
        'attemptsLeft' => $attemptsLeft
    ]);
    exit;
}

Session::clear('admin_login_lock_until');

$password = trim($_POST['password'] ?? '');
$errors = [];

if (!Validation::string($password, 1, 100)) {
    $errors['password'] = 'Please enter your password';
}

if (!empty($errors)) {
    loadView('admin/login', [
        'errors' => $errors,
        'lockSeconds' => $lockSeconds,
        'attemptsLeft' => $attemptsLeft
    ]);
    exit;
}

$admins = $db->query("SELECT * FROM teacher_users WHERE LOWER(role) = 'admin' AND is_active = 1 ORDER BY id ASC")->fetchAll();
$admin = null;

// Admin login uses password only; first matching admin is signed in.
foreach ($admins as $candidateAdmin) {
    if (adminVerifyPassword($password, $candidateAdmin['password'])) {
        $admin = $candidateAdmin;
        break;
    }
}

if (!$admin) {
    // Count failed tries and lock for a short time if too many.
    $failedAttempts = max(0, (int) (Session::get('admin_login_failed_attempts') ?? 0)) + 1;
    Session::set('admin_login_failed_attempts', $failedAttempts);

    if ($failedAttempts >= $maxLoginAttempts) {
        $lockUntilTs = time() + $lockDurationSeconds;
        Session::set('admin_login_lock_until', $lockUntilTs);
        Session::set('admin_login_failed_attempts', 0);

        loadView('admin/login', [
            'errors' => [],
            'lockSeconds' => $lockDurationSeconds,
            'attemptsLeft' => 0
        ]);
        exit;
    }

    $attemptsLeft = max(0, $maxLoginAttempts - $failedAttempts);
    loadView('admin/login', [
        'errors' => ['auth' => 'Invalid admin credentials'],
        'lockSeconds' => $lockSeconds,
        'attemptsLeft' => $attemptsLeft
    ]);
    exit;
}

Session::set('admin_login_failed_attempts', 0);
Session::clear('admin_login_lock_until');

Session::set('user', [
    'id' => (int) $admin['id'],
    'name' => $admin['name'],
    'role' => 'admin',
    'can_set_questions' => (int) ($admin['can_set_questions'] ?? 1),
    'is_active' => (int) ($admin['is_active'] ?? 1),
    'can_manage_students' => (int) ($admin['can_manage_students'] ?? 0),
    'assigned_subjects' => adminSubjectsToStorage($admin['assigned_subjects'] ?? '', $availableSubjects)
]);
Session::regenerate();
// Mark admin as online for live presence display.
adminMarkUserSeen($db, (int) $admin['id']);

redirect('/admin/dashboard');
