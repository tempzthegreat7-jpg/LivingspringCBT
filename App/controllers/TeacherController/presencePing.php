<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);
adminEnsureTeacherUsersSchema($db);

$userId = (int) (Session::get('user')['id'] ?? 0);
$sessionRole = strtolower((string) (Session::get('user')['role'] ?? 'teacher'));

if ($userId <= 0) {
    // No active session user: client should redirect to login.
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false,
        'inactive' => true,
        'redirect' => $sessionRole === 'admin' ? '/admin/login' : '/teacher/login'
    ]);
    exit;
}

$currentUser = $db->query(
    'SELECT id, role, is_active FROM teacher_users WHERE id = :id LIMIT 1',
    ['id' => $userId]
)->fetch();

if (
    !$currentUser ||
    strtolower((string) ($currentUser['role'] ?? 'teacher')) !== $sessionRole ||
    (int) ($currentUser['is_active'] ?? 1) !== 1
) {
    // Session no longer matches DB state (role changed/deactivated/deleted).
    if (!empty($currentUser['name'])) {
        Session::set('reactivation_watch_name', (string) $currentUser['name']);
    } else {
        $sessionName = (string) (Session::get('user')['name'] ?? '');
        if ($sessionName !== '') {
            Session::set('reactivation_watch_name', $sessionName);
        }
    }
    Session::setFlashMesssge('error_message', 'Your account is inactive. Contact the administrator.');
    Session::clear('user');

    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false,
        'inactive' => true,
        'redirect' => $sessionRole === 'admin' ? '/admin/login' : '/teacher/login'
    ]);
    exit;
}

adminMarkUserSeen($db, $userId);
// Keep-alive ping succeeded.

header('Content-Type: application/json');
echo json_encode([
    'ok' => true
]);
exit;
