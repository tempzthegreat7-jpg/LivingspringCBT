<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

$subjectsConfig = require basePath('config/config-db2.php');
$subjectsDb = new Database($subjectsConfig);

adminEnsureTeacherUsersSchema($db);
adminEnsureControlSchema($db);

$activeExamSessions = adminFetchActiveExamSessions($subjectsDb, 300);
$activeTeachers = adminFetchActiveTeachers($db, 100);
$globalTimerPaused = adminGetGlobalControlValue($db, 'global_timer_pause', '0');
$globalTimeBonus = max(0, (int) adminGetGlobalControlValue($db, 'global_timer_offset_seconds', 0));

loadView('admin/master-controls', [
    'activeExamSessions' => $activeExamSessions,
    'activeTeachers' => $activeTeachers,
    'globalTimerPaused' => $globalTimerPaused,
    'globalTimeBonus' => $globalTimeBonus,
    'classOptions' => adminClassOptions()
]);
