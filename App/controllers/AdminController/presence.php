<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);
adminEnsureTeacherUsersSchema($db);

$rows = $db->query(
    "SELECT id, is_active,
            CASE
                WHEN last_seen_at IS NULL THEN 0
                WHEN TIMESTAMPDIFF(SECOND, last_seen_at, NOW()) <= 25 THEN 1
                ELSE 0
            END AS is_online
     FROM teacher_users
     WHERE LOWER(role) != 'admin'"
)->fetchAll();

$presenceByUser = [];
foreach ($rows as $row) {
    // Teacher is online when active and last ping is recent.
    $presenceByUser[(string) ((int) ($row['id'] ?? 0))] = [
        'online' => ((int) ($row['is_online'] ?? 0) === 1) && ((int) ($row['is_active'] ?? 1) === 1),
        'active' => (int) ($row['is_active'] ?? 1) === 1
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'presence' => $presenceByUser
]);
exit;
