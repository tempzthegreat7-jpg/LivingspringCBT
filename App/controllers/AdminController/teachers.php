<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
$categorizedSubjects = adminCategorizedSubjects($db);
$availableSubjects = $categorizedSubjects['all'];

$users = $db->query("SELECT id, name, role, can_set_questions, is_active, can_manage_students, assigned_subjects, assigned_subject_categories, created_at FROM teacher_users WHERE LOWER(role) != 'admin' ORDER BY id DESC")->fetchAll();

loadView('admin/teachers', [
    'users' => $users,
    'availableSubjects' => $availableSubjects,
    'juniorSubjects' => $categorizedSubjects['junior'],
    'seniorSubjects' => $categorizedSubjects['senior']
]);
