<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureTeacherAlertsSchema($db);

$rows = array_values(array_filter(adminFetchTeacherAlerts($db, 120), function ($row) {
    $origin = strtolower(trim((string) ($row['origin_role'] ?? 'teacher')));
    return $origin !== 'admin';
}));

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'alerts' => $rows
]);
exit;
