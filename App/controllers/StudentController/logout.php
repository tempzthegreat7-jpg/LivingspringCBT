<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureStudentUsersSchema($db);

$student = Session::get('student');
$studentId = (int) ($student['id'] ?? 0);
$studentName = (string) ($student['name'] ?? '');
$studentClass = (string) ($student['class'] ?? '');
$studentSessionToken = trim((string) ($student['session_token'] ?? ''));

if ($studentId > 0) {
    // Lock the student account on logout
    adminSetStudentLock($db, $studentId, true);
    
    // Clear the active session token
    adminClearStudentSession($db, $studentId, $studentSessionToken !== '' ? $studentSessionToken : null);
    
    // Audit log
    adminAuditLog(
        $db,
        'student.auto_lock_logout',
        'student_user',
        (string) $studentId,
        "Student \"{$studentName}\" ({$studentClass}) automatically locked on logout",
        [
            'student_id' => $studentId,
            'student_name' => $studentName,
            'student_class' => $studentClass,
            'locked_reason' => 'logout'
        ]
    );
    
    // Also log the login event
    adminLogStudentLogin($db, [
        'id' => $studentId,
        'student_name' => $studentName,
        'student_class' => $studentClass
    ]);
}

// Clear session
Session::clearAll();

// Remove session cookie
$params = session_get_cookie_params();
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
setcookie('PHPSESSID', '', [
    'expires' => time() - 86400,
    'path' => (string) ($params['path'] ?? '/'),
    'domain' => (string) ($params['domain'] ?? ''),
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

redirect('/roles');