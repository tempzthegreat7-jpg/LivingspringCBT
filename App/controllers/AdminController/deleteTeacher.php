<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);

$id = (int) ($_POST['id'] ?? 0);
$currentUserId = (int) (Session::get('user')['id'] ?? 0);

if ($id <= 0) {
    // Invalid or missing target id.
    Session::setFlashMesssge('error_message', 'Invalid user selected for deletion.');
    redirect('/admin/teachers');
}

if ($id === $currentUserId) {
    // Prevent deleting currently signed-in admin.
    Session::setFlashMesssge('error_message', 'You cannot delete your currently logged in admin account.');
    redirect('/admin/teachers');
}

$targetUser = $db->query('SELECT id, name, role FROM teacher_users WHERE id = :id LIMIT 1', [
    'id' => $id
])->fetch();

if (!$targetUser) {
    Session::setFlashMesssge('error_message', 'User not found.');
    redirect('/admin/teachers');
}

$targetRole = adminNormalizeRole($targetUser['role'] ?? 'teacher');
if ($targetRole === 'admin') {
    // Keep at least one admin account in the system.
    $adminCountRow = $db->query("SELECT COUNT(*) AS total FROM teacher_users WHERE LOWER(role) = 'admin'")->fetch();
    $adminCount = (int) ($adminCountRow['total'] ?? 0);

    if ($adminCount <= 1) {
        Session::setFlashMesssge('error_message', 'At least one admin must remain. Deletion cancelled.');
        redirect('/admin/teachers');
    }
}

$db->query('DELETE FROM teacher_users WHERE id = :id', [
    'id' => $id
]);
adminAuditLog(
    $db,
    'teacher.delete',
    'teacher_user',
    (string) $id,
    'Deleted user "' . (string) ($targetUser['name'] ?? 'Unknown') . '"',
    [
        'role' => adminNormalizeRole($targetUser['role'] ?? 'teacher')
    ]
);

Session::setFlashMesssge('success_message', 'User deleted successfully.');
redirect('/admin/teachers');
