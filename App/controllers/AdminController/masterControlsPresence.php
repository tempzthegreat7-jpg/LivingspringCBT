<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureControlSchema($db);

header('Content-Type: application/json');

$action = strtolower(trim((string) ($_GET['action'] ?? '')));

if ($action === 'ack') {
    $commandId = (int) ($_POST['command_id'] ?? 0);
    $role = Session::has('user') ? 'teacher' : (Session::has('student') ? 'student' : 'guest');
    
    if ($role === 'student') {
        $studentName = trim((string) (Session::get('student')['name'] ?? ''));
        $studentClass = strtoupper(trim((string) (Session::get('student')['class'] ?? 'SS3')));
        $sessionIdentifier = $studentName . '|' . $studentClass;
    } elseif ($role === 'teacher') {
        $sessionIdentifier = (string) (Session::get('user')['id'] ?? '');
    } else {
        echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
        exit;
    }

    if ($commandId > 0 && $sessionIdentifier !== '') {
        adminAcknowledgeControlCommand($db, $commandId, $role, $sessionIdentifier, ['acknowledged_at' => date('c')]);
    }

    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'sync') {
    $role = Session::has('user') ? 'teacher' : (Session::has('student') ? 'student' : 'guest');
    
    if ($role === 'student') {
        $studentName = trim((string) (Session::get('student')['name'] ?? ''));
        $studentClass = strtoupper(trim((string) (Session::get('student')['class'] ?? 'SS3')));
        $sessionIdentifier = $studentName . '|' . $studentClass;
    } elseif ($role === 'teacher') {
        $sessionIdentifier = (string) (Session::get('user')['id'] ?? '');
    } else {
        echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $globalTimerPaused = adminGetGlobalControlValue($db, 'global_timer_pause', '0');
    $globalTimeBonus = max(0, (int) adminGetGlobalControlValue($db, 'global_timer_offset_seconds', 0));

    echo json_encode([
        'ok' => true,
        'global_timer_paused' => $globalTimerPaused === '1',
        'global_time_bonus' => $globalTimeBonus
    ]);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Invalid action']);
