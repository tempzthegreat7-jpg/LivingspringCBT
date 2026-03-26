<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureStudentUsersSchema($db);

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    Session::setFlashMesssge('error_message', 'Invalid student selected for deletion.');
    redirect('/admin/students?tab=manage');
}

$target = $db->query('SELECT id, student_name, student_class FROM student_users WHERE id = :id LIMIT 1', [
    'id' => $id
])->fetch();

if (!$target) {
    Session::setFlashMesssge('error_message', 'Student login not found.');
    redirect('/admin/students?tab=manage');
}

$db->query('DELETE FROM student_users WHERE id = :id', ['id' => $id]);

adminAuditLog(
    $db,
    'student.delete',
    'student_user',
    (string) $id,
    'Deleted student login "' . (string) ($target['student_name'] ?? 'Unknown') . '"',
    [
        'class' => (string) ($target['student_class'] ?? 'SS3')
    ]
);

Session::setFlashMesssge('success_message', 'Student login deleted successfully.');
redirect('/admin/students?tab=manage');
