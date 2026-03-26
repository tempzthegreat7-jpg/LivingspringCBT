<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');
$config = require basePath('config/config-db.php');

$db = new Database($config);
adminEnsureTeacherUsersSchema($db);

// Load teacher/admin names for selector on login page.
$teachers = $db->query("SELECT id, name, is_active FROM teacher_users WHERE LOWER(role) IN ('teacher', 'admin') ORDER BY name ASC")->fetchAll();

loadView('teacher/login', [
    'teachers' => $teachers
]);
