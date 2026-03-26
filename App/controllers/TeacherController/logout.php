<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');
$config = require basePath('config/config-db.php');

$userId = (int) (Session::get('user')['id'] ?? 0);
if ($userId > 0) {
    // Mark user offline before clearing session.
    $db = new Database($config);
    adminEnsureTeacherUsersSchema($db);
    adminMarkUserOffline($db, $userId);
}

$studentId = (int) (Session::get('student')['id'] ?? 0);
$studentSessionToken = trim((string) (Session::get('student')['session_token'] ?? ''));
if ($studentId > 0) {
    $db = isset($db) && $db instanceof Database ? $db : new Database($config);
    adminEnsureStudentUsersSchema($db);
    adminClearStudentSession($db, $studentId, $studentSessionToken !== '' ? $studentSessionToken : null);
}

Session::clearAll();

// Remove session cookie from browser.
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
