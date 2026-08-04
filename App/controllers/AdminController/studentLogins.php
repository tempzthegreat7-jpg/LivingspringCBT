<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureStudentUsersSchema($db);
adminEnsureStudentLoginLogsSchema($db);

$rows = adminFetchStudentLoginLogs($db, 400);

loadView('admin/student-logins', [
    'loginRows' => $rows
]);
