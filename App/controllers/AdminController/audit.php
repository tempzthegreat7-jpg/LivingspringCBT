<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureAuditLogsSchema($db);

$rows = $db->query(
    'SELECT id, admin_user_id, action_key, entity_type, entity_id, summary, context_json, ip_address, user_agent, created_at
     FROM admin_audit_logs
     ORDER BY id DESC
     LIMIT 300'
)->fetchAll();

loadView('admin/audit', [
    'auditRows' => $rows
]);
