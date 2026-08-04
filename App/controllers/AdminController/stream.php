<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

$subjectsConfig = require basePath('config/config-db2.php');
$subjectsDb = new Database($subjectsConfig);

adminEnsureTeacherUsersSchema($db);
adminEnsureControlSchema($db);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}

$role = 'guest';
$sessionIdentifier = '';

if (Session::has('user')) {
    $userRole = strtolower(trim((string) (Session::get('user')['role'] ?? '')));
    if ($userRole === 'admin') {
        $role = 'admin';
        $sessionIdentifier = (string) (Session::get('user')['id'] ?? '');
    } else {
        $role = 'teacher';
        $sessionIdentifier = (string) (Session::get('user')['id'] ?? '');
    }
} elseif (Session::has('student')) {
    $role = 'student';
    $studentName = trim((string) (Session::get('student')['name'] ?? ''));
    $studentClass = strtoupper(trim((string) (Session::get('student')['class'] ?? 'SS3')));
    $sessionIdentifier = $studentName . '|' . $studentClass;
}

if ($role === 'guest' || $sessionIdentifier === '') {
    http_response_code(401);
    echo "data: " . json_encode(['ok' => false, 'message' => 'Unauthorized']) . "\n\n";
    ob_flush();
    flush();
    exit;
}

$lastEventId = (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0);
$startTime = time();
$pingInterval = 15;
$lastMonitorHash = '';

set_time_limit(0);
ignore_user_abort(true);

while (true) {
    if (connection_aborted()) {
        break;
    }

    $elapsed = time() - $startTime;
    if ($elapsed >= 300) {
        echo "event: close\n";
        echo "data: " . json_encode(['message' => 'Stream closed. Please reconnect.']) . "\n\n";
        ob_flush();
        flush();
        break;
    }

    if ($elapsed % $pingInterval === 0) {
        echo ": keepalive " . date('c') . "\n\n";
        ob_flush();
        flush();
    }

    $updates = adminFetchControlUpdatesSince($db, $lastEventId);
    foreach ($updates as $update) {
        $eventType = (string) ($update['event_type'] ?? 'message');
        $eventId = (int) ($update['id'] ?? 0);
        $eventData = json_encode($update['data'] ?? []);

        echo "id: {$eventId}\n";
        echo "event: {$eventType}\n";
        echo "data: {$eventData}\n\n";
        ob_flush();
        flush();

        if ($eventId > $lastEventId) {
            $lastEventId = $eventId;
        }
    }

        if ($role === 'admin') {
            ensureStudentExamSessionsSchema($subjectsDb);
            $sessions = $subjectsDb->query(
                "SELECT id, student_name, student_class, subject,
                        assessment_id, ends_at, duration_seconds,
                        timer_paused, time_bonus_seconds, admin_paused_at,
                        current_index, total_questions, score
                 FROM student_exam_sessions
                 WHERE status = 'in_progress'
                   AND task_type = 'exam'
                   AND ends_at > UNIX_TIMESTAMP()
                   AND (attempt_logged = 0 OR attempt_logged IS NULL)
                 ORDER BY updated_at DESC, id DESC
                 LIMIT 300"
            )->fetchAll();

            $globalTimerPaused = adminGetGlobalControlValue($db, 'global_timer_pause', '0');
            $globalTimeBonus = max(0, (int) adminGetGlobalControlValue($db, 'global_timer_offset_seconds', 0));

            $monitorHash = crc32(json_encode($sessions) . $globalTimerPaused . $globalTimeBonus);
            if ($monitorHash !== $lastMonitorHash) {
                $lastMonitorHash = $monitorHash;
                $eventId = $lastEventId + 1;
                echo "id: {$eventId}\n";
                echo "event: monitor_update\n";
                echo "data: " . json_encode([
                    'sessions' => $sessions,
                    'count' => count($sessions),
                    'generated_at' => date('c'),
                    'global_timer_paused' => $globalTimerPaused === '1',
                    'global_time_bonus' => $globalTimeBonus
                ]) . "\n\n";
                ob_flush();
                flush();
                $lastEventId = $eventId;
            }
        }

    if ($role === 'student') {
        $pendingCommands = adminFetchControlCommandsForSession($db, 'student', $sessionIdentifier, $lastEventId);
        foreach ($pendingCommands as $cmd) {
            $eventId = (int) ($cmd['id'] ?? 0);
            if ($eventId <= $lastEventId) {
                continue;
            }

            echo "id: {$eventId}\n";
            echo "event: " . (string) ($cmd['command_type'] ?? 'command') . "\n";
            echo "data: " . json_encode([
                'command_id' => $eventId,
                'payload' => $cmd['payload'] ?? [],
                'target_scope' => $cmd['target_scope'] ?? 'all'
            ]) . "\n\n";
            ob_flush();
            flush();
            $lastEventId = $eventId;
        }
    }

    sleep(1);
}
