<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureStudentUsersSchema($db);
adminEnsureStudentClassLocksSchema($db);

$studentName = trim((string) ($_POST['student_name'] ?? ''));
$studentClass = adminNormalizeStudentClass($_POST['student_class'] ?? 'SS3');
$studentPassword = trim((string) ($_POST['student_password'] ?? ''));

$maxLoginAttempts = 5;
$lockDurationSeconds = 30;
$lockUntil = (int) (Session::get('student_login_lock_until') ?? 0);
if ($lockUntil > time()) {
    $fail('Too many login attempts. Please wait ' . ($lockUntil - time()) . ' seconds.');
    return;
}
Session::clear('student_login_lock_until');

$acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
$isJsonExpected = strpos($acceptHeader, 'application/json') !== false
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

$fail = function ($message) use ($isJsonExpected) {
    if ($isJsonExpected) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'message' => (string) $message
        ]);
        return;
    }

    Session::setFlashMesssge('error_message', (string) $message);
    redirect('/student/names');
};

if (!Validation::string($studentName, 2, 120) || !Validation::string($studentPassword, 6, 100)) {
    $fail('Enter your full name, class, and password to continue.');
    return;
}

if (adminIsStudentClassLocked($db, $studentClass)) {
    $fail('This class is currently locked. Contact the admin.');
    return;
}

$student = $db->query(
    'SELECT id, student_name, student_class, password_hash, display_password, is_active, active_session_token, active_session_seen_at,
            CASE
                WHEN active_session_token IS NOT NULL
                 AND active_session_seen_at IS NOT NULL
                 AND TIMESTAMPDIFF(SECOND, active_session_seen_at, NOW()) <= 120
                THEN 1
                ELSE 0
            END AS active_session_is_live
     FROM student_users
     WHERE student_name = :student_name AND student_class = :student_class
     LIMIT 1',
    [
        'student_name' => $studentName,
        'student_class' => $studentClass
    ]
)->fetch();

if (!$student) {
    $fail('Student login not found. Contact the admin.');
    return;
}

if ((int) ($student['is_active'] ?? 0) !== 1) {
    $fail('This student login is inactive. Contact the admin.');
    return;
}

if ((int) ($student['is_locked'] ?? 0) === 1) {
    $fail('This student account is locked. Contact the admin.');
    return;
}

if (!adminVerifyPassword($studentPassword, (string) ($student['password_hash'] ?? ''))) {
    $failedAttempts = max(0, (int) (Session::get('student_login_failed_attempts') ?? 0)) + 1;
    Session::set('student_login_failed_attempts', $failedAttempts);
    if ($failedAttempts >= $maxLoginAttempts) {
        Session::set('student_login_lock_until', time() + $lockDurationSeconds);
        Session::set('student_login_failed_attempts', 0);
    }
    $fail('Incorrect student password.');
    return;
}

$hasActiveSession = (int) ($student['active_session_is_live'] ?? 0) === 1;

if ($hasActiveSession) {
    $fail('Nice try. This account is already in use. Duplicate access is being watched.');
    return;
}

$sessionToken = bin2hex(random_bytes(32));
adminMarkStudentSessionActive($db, (int) ($student['id'] ?? 0), $sessionToken);
adminLogStudentLogin($db, $student);

Session::regenerate();
Session::set('student_login_failed_attempts', 0);
Session::clear('student_login_lock_until');
Session::set('student', [
    'id' => (int) ($student['id'] ?? 0),
    'name' => (string) ($student['student_name'] ?? $studentName),
    'class' => (string) ($student['student_class'] ?? $studentClass),
    'session_token' => $sessionToken
]);
Session::clear('quiz');
Session::clear('subjects');

if ($isJsonExpected) {
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'redirect' => '/student/dashboard'
    ]);
    return;
}

redirect('/student/dashboard');
