<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('helpers.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

$subjectsConfig = require basePath('config/config-db2.php');
$subjectsDb = new Database($subjectsConfig);

adminEnsureTeacherUsersSchema($db);
adminEnsureControlSchema($db);

$commandType = strtolower(trim((string) ($_POST['command_type'] ?? '')));
$targetScope = strtolower(trim((string) ($_POST['target_scope'] ?? 'all')));
$payloadRaw = trim((string) ($_POST['payload'] ?? '{}'));
$payload = json_decode($payloadRaw, true);
if (!is_array($payload)) {
    $payload = [];
}

$allowedCommands = [
    'force_submit',
    'terminate_session',
    'pause_timer',
    'resume_timer',
    'add_time'
];

if (!in_array($commandType, $allowedCommands, true)) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Invalid command.']);
        exit;
    }

    Session::setFlashMesssge('error_message', 'Invalid command.');
    redirect('/admin/master-controls');
}

$adminUserId = (int) (Session::get('user')['id'] ?? 0);

$matchesTargetScope = function ($sessionRow) use ($targetScope) {
    $scope = strtolower(trim((string) $targetScope));
    if ($scope === '' || $scope === 'all') {
        return true;
    }

    if (strpos($scope, 'class:') === 0) {
        $requiredClass = strtoupper(trim(substr($scope, 6)));
        if ($requiredClass === '') {
            return true;
        }

        return strtoupper(trim((string) ($sessionRow['student_class'] ?? ''))) === $requiredClass;
    }

    return true;
};

$applyControlToSessions = function ($commandTypeValue, $payloadValue) use ($subjectsDb, $db, $adminUserId, $matchesTargetScope, $commandType, $targetScope) {
    ensureStudentExamSessionsSchema($subjectsDb);

    $targets = adminFetchActiveExamSessions($subjectsDb, 500);
    $affectedCount = 0;
    $now = date('Y-m-d H:i:s');

    foreach ($targets as $target) {
        if (!$matchesTargetScope($target)) {
            continue;
        }

        $sessionId = (int) ($target['id'] ?? 0);
        if ($sessionId <= 0) {
            continue;
        }

        $sessionRow = $subjectsDb->query(
            'SELECT * FROM student_exam_sessions WHERE id = :id LIMIT 1',
            ['id' => $sessionId]
        )->fetch();

        if (!is_array($sessionRow) || empty($sessionRow)) {
            continue;
        }

        if ($commandTypeValue === 'pause_timer') {
            $subjectsDb->query(
                'UPDATE student_exam_sessions
                 SET timer_paused = 1,
                     admin_paused_at = :admin_paused_at,
                     last_control_sync_at = :last_control_sync_at
                 WHERE id = :id
                 LIMIT 1',
                [
                    'admin_paused_at' => $now,
                    'last_control_sync_at' => $now,
                    'id' => $sessionId
                ]
            );
        } elseif ($commandTypeValue === 'resume_timer') {
            $subjectsDb->query(
                'UPDATE student_exam_sessions
                 SET timer_paused = 0,
                     admin_paused_at = NULL,
                     last_control_sync_at = :last_control_sync_at
                 WHERE id = :id
                 LIMIT 1',
                [
                    'last_control_sync_at' => $now,
                    'id' => $sessionId
                ]
            );
        } elseif ($commandTypeValue === 'add_time') {
            $seconds = max(0, (int) ($payloadValue['seconds'] ?? 0));
            if ($seconds <= 0) {
                continue;
            }

            $newBonus = max(0, (int) ($sessionRow['time_bonus_seconds'] ?? 0)) + $seconds;
            $newEndsAt = (int) ($sessionRow['ends_at'] ?? 0);
            if ($newEndsAt <= 0) {
                $newEndsAt = max(0, (int) ($sessionRow['started_at'] ?? time()) + (int) ($sessionRow['duration_seconds'] ?? 0) + $seconds);
            } else {
                $newEndsAt += $seconds;
            }

            $subjectsDb->query(
                'UPDATE student_exam_sessions
                 SET ends_at = :ends_at,
                     time_bonus_seconds = :time_bonus_seconds,
                     last_control_sync_at = :last_control_sync_at
                 WHERE id = :id
                 LIMIT 1',
                [
                    'ends_at' => $newEndsAt,
                    'time_bonus_seconds' => $newBonus,
                    'last_control_sync_at' => $now,
                    'id' => $sessionId
                ]
            );
        } elseif ($commandTypeValue === 'force_submit') {
            $sessionRow['last_control_sync_at'] = $now;
            studentFinalizeExamSession($subjectsDb, $sessionRow, false);
        } elseif ($commandTypeValue === 'terminate_session') {
            $subjectsDb->query(
                'UPDATE student_exam_sessions
                 SET status = :status,
                     attempt_logged = 1,
                     current_index = GREATEST(current_index, total_questions),
                     completed_at = :completed_at,
                     last_control_sync_at = :last_control_sync_at
                 WHERE id = :id
                 LIMIT 1',
                [
                    'status' => 'terminated',
                    'completed_at' => $now,
                    'last_control_sync_at' => $now,
                    'id' => $sessionId
                ]
            );
        }

        $affectedCount++;
    }

    return $affectedCount;
};

if ($commandType === 'add_time') {
    $seconds = max(0, (int) ($payload['seconds'] ?? 0));
    if ($seconds <= 0) {
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Time increment must be greater than 0.']);
            exit;
        }

        Session::setFlashMesssge('error_message', 'Time increment must be greater than 0.');
        redirect('/admin/master-controls');
    }

    $current = max(0, (int) adminGetGlobalControlValue($db, 'global_timer_offset_seconds', 0));
    adminUpsertGlobalControl($db, 'global_timer_offset_seconds', (string) ($current + $seconds), $adminUserId);

    $affectedCount = $applyControlToSessions('add_time', $payload);
    $commandId = adminCreateControlCommand($db, 'add_time', $payload, $targetScope, $adminUserId);
    adminAuditLog(
        $db,
        'master_control.add_time',
        'exam_session',
        (string) $commandId,
        'Added ' . $seconds . ' seconds to active exams',
        ['scope' => $targetScope, 'seconds' => $seconds]
    );

    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'message' => 'Time added to active sessions.',
            'command_id' => $commandId,
            'seconds' => $seconds,
            'affected_count' => $affectedCount
        ]);
        exit;
    }

    Session::setFlashMesssge('success_message', 'Time added to active sessions.');
    redirect('/admin/master-controls');
}

if ($commandType === 'pause_timer') {
    adminUpsertGlobalControl($db, 'global_timer_pause', '1', $adminUserId);

    $affectedCount = $applyControlToSessions('pause_timer', $payload);
    $commandId = adminCreateControlCommand($db, 'pause_timer', $payload, $targetScope, $adminUserId);
    adminAuditLog(
        $db,
        'master_control.pause_timer',
        'exam_session',
        (string) $commandId,
        'Paused all active exam timers',
        ['scope' => $targetScope]
    );

    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'message' => 'All exam timers paused.',
            'command_id' => $commandId,
            'affected_count' => $affectedCount
        ]);
        exit;
    }

    Session::setFlashMesssge('success_message', 'All exam timers paused.');
    redirect('/admin/master-controls');
}

if ($commandType === 'resume_timer') {
    adminUpsertGlobalControl($db, 'global_timer_pause', '0', $adminUserId);

    $affectedCount = $applyControlToSessions('resume_timer', $payload);
    $commandId = adminCreateControlCommand($db, 'resume_timer', $payload, $targetScope, $adminUserId);
    adminAuditLog(
        $db,
        'master_control.resume_timer',
        'exam_session',
        (string) $commandId,
        'Resumed all active exam timers',
        ['scope' => $targetScope]
    );

    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'message' => 'All exam timers resumed.',
            'command_id' => $commandId,
            'affected_count' => $affectedCount
        ]);
        exit;
    }

    Session::setFlashMesssge('success_message', 'All exam timers resumed.');
    redirect('/admin/master-controls');
}

if ($commandType === 'force_submit') {
    $targets = adminFetchActiveExamSessions($subjectsDb, 500);
    $totalTargets = count($targets);

    if ($totalTargets === 0) {
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'No active exam sessions found.']);
            exit;
        }

        Session::setFlashMesssge('error_message', 'No active exam sessions found.');
        redirect('/admin/master-controls');
    }

    $affectedCount = $applyControlToSessions('force_submit', $payload);
    $commandId = adminCreateControlCommand($db, 'force_submit', $payload, $targetScope, $adminUserId);
    $db->query(
        'UPDATE admin_control_commands SET total_targets = :total_targets WHERE id = :id LIMIT 1',
        [
            'total_targets' => $totalTargets,
            'id' => $commandId
        ]
    );

    foreach ($targets as $target) {
        $identifier = trim((string) ($target['student_name'] ?? '')) . '|' . trim((string) ($target['student_class'] ?? ''));
        adminAcknowledgeControlCommand($db, $commandId, 'student', $identifier);
    }

    adminAuditLog(
        $db,
        'master_control.force_submit',
        'exam_session',
        (string) $commandId,
        'Force submitted ' . $totalTargets . ' active exams',
        ['scope' => $targetScope, 'target_count' => $totalTargets]
    );

    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'message' => 'Force submit command sent to ' . $totalTargets . ' sessions.',
            'command_id' => $commandId,
            'target_count' => $totalTargets,
            'affected_count' => $affectedCount
        ]);
        exit;
    }

    Session::setFlashMesssge('success_message', 'Force submit command sent to ' . $totalTargets . ' sessions.');
    redirect('/admin/master-controls');
}

if ($commandType === 'terminate_session') {
    $targets = array_merge(adminFetchActiveExamSessions($subjectsDb, 500), adminFetchActiveTeachers($db, 200));
    $totalTargets = count($targets);

    if ($totalTargets === 0) {
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'No active sessions found.']);
            exit;
        }

        Session::setFlashMesssge('error_message', 'No active sessions found.');
        redirect('/admin/master-controls');
    }

    $affectedCount = $applyControlToSessions('terminate_session', $payload);
    $commandId = adminCreateControlCommand($db, 'terminate_session', $payload, $targetScope, $adminUserId);
    $db->query(
        'UPDATE admin_control_commands SET total_targets = :total_targets WHERE id = :id LIMIT 1',
        [
            'total_targets' => $totalTargets,
            'id' => $commandId
        ]
    );

    foreach ($targets as $target) {
        if (isset($target['student_name'])) {
            $identifier = trim((string) ($target['student_name'] ?? '')) . '|' . trim((string) ($target['student_class'] ?? ''));
            adminAcknowledgeControlCommand($db, $commandId, 'student', $identifier);
        } elseif (isset($target['id']) && isset($target['name'])) {
            adminAcknowledgeControlCommand($db, $commandId, 'teacher', (string) $target['id']);
        }
    }

    adminAuditLog(
        $db,
        'master_control.terminate_session',
        'session',
        (string) $commandId,
        'Terminating ' . $totalTargets . ' active sessions',
        ['scope' => $targetScope, 'target_count' => $totalTargets]
    );

    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'message' => 'Termination command sent to ' . $totalTargets . ' sessions.',
            'command_id' => $commandId,
            'target_count' => $totalTargets,
            'affected_count' => $affectedCount
        ]);
        exit;
    }

    Session::setFlashMesssge('success_message', 'Termination command sent to ' . $totalTargets . ' sessions.');
    redirect('/admin/master-controls');
}

if (isAjaxRequest()) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => 'Unhandled command type.']);
    exit;
}

Session::setFlashMesssge('error_message', 'Unhandled command type.');
redirect('/admin/master-controls');
