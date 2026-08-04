<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureFuturePlansSchema($db);

$plans = adminFetchFuturePlans($db, 120);

loadView('admin/future-plans', [
    'plans' => $plans
]);
