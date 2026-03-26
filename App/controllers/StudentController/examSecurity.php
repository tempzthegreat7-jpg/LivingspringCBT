<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

header('Content-Type: application/json; charset=UTF-8');

$quiz = Session::get('quiz') ?? [];
$questions = is_array($quiz['questions'] ?? null) ? $quiz['questions'] : [];

if (empty($questions)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'No active exam session was found.'
    ]);
    return;
}

$student = Session::get('student') ?? [];
$subjects = Session::get('subjects') ?? [];
$studentName = trim((string) ($student['name'] ?? 'Unknown Student'));
$studentClass = strtoupper(trim((string) ($student['class'] ?? 'SS3')));
$subject = strtolower(trim((string) ($subjects['subject'] ?? '')));
$taskType = normalizeAssessmentTask((string) ($subjects['task'] ?? 'exam'));
$assessmentId = (int) ($subjects['assessment_id'] ?? 0);
$security = normalizeQuizSecurityState($quiz['security'] ?? []);
$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$eventType = strtolower(trim((string) ($_POST['event_type'] ?? '')));

$subjectsConfig = require basePath('config/config-db2.php');
$subjectsDb = new Database($subjectsConfig);
ensureStudentExamSessionsSchema($subjectsDb);
ensureExamSecurityEventsSchema($subjectsDb);

$adminConfig = require basePath('config/config-db.php');
$adminDb = new Database($adminConfig);
adminEnsureTeacherUsersSchema($adminDb);

$persistSecurityState = function (array $nextSecurity) use ($quiz, $subjectsDb, $studentName, $studentClass, $assessmentId, $subject, $taskType, $subjects) {
    $updatedQuiz = $quiz;
    $updatedQuiz['security'] = normalizeQuizSecurityState($nextSecurity);
    Session::set('quiz', $updatedQuiz);

    if ($taskType === 'exam') {
        $activeExamSession = studentFindActiveExamSession($subjectsDb, $studentName, $studentClass);
        if ($activeExamSession) {
            studentSaveExamSession($subjectsDb, [
                'id' => (int) ($activeExamSession['id'] ?? 0),
                'student_name' => $studentName,
                'student_class' => $studentClass,
                'assessment_id' => $assessmentId,
                'subject' => $subject,
                'task_type' => $taskType,
                'term_key' => (string) ($subjects['term'] ?? 'first_term'),
                'header_text' => (string) ($subjects['header'] ?? ''),
                'questions' => is_array($quiz['questions'] ?? null) ? $quiz['questions'] : [],
                'answers' => is_array($quiz['answers'] ?? null) ? $quiz['answers'] : [],
                'security' => $updatedQuiz['security'],
                'current_index' => (int) ($quiz['current_index'] ?? 0),
                'score' => (int) ($quiz['score'] ?? 0),
                'total_questions' => (int) ($quiz['total'] ?? count($quiz['questions'] ?? [])),
                'started_at' => (int) ($quiz['started_at'] ?? time()),
                'ends_at' => (int) ($quiz['ends_at'] ?? 0),
                'duration_seconds' => (int) ($quiz['duration_seconds'] ?? 0),
                'attempt_logged' => (bool) ($quiz['attempt_logged'] ?? false),
                'status' => 'in_progress'
            ]);
        }
    }
};

if ($action === 'report_violation') {
    $eventLabels = [
        'visibility_hidden' => 'tab switch detected',
        'window_blur' => 'window switch detected',
        'fullscreen_exit' => 'fullscreen exit detected'
    ];
    $allowedEvents = array_keys($eventLabels);

    if (!in_array($eventType, $allowedEvents, true)) {
        $eventType = 'window_blur';
    }

    if (!$security['requires_admin_unlock']) {
        $security['violation_count']++;
        $security['requires_admin_unlock'] = true;
        $security['locked_at'] = time();
        $security['last_event'] = $eventType;
        $security['last_event_label'] = $eventLabels[$eventType] ?? 'exam exit detected';
        $persistSecurityState($security);
    }

    logExamSecurityEvent($subjectsDb, [
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'assessment_id' => $assessmentId,
        'subject' => $subject,
        'task_type' => $taskType,
        'event_type' => $eventType,
        'details' => [
            'violation_count' => (int) $security['violation_count'],
            'locked' => true,
            'message' => 'Admin password is now required to continue.'
        ]
    ]);

    echo json_encode([
        'ok' => true,
        'locked' => true,
        'violation_count' => (int) $security['violation_count'],
        'message' => 'Exam locked. Admin password is required to continue.',
        'security' => $security
    ]);
    return;
}

if ($action === 'admin_unlock') {
    $password = trim((string) ($_POST['password'] ?? ''));

    if ($password === '') {
        http_response_code(422);
        echo json_encode([
            'ok' => false,
            'message' => 'Enter the admin password to continue.'
        ]);
        return;
    }

    $admins = $adminDb->query(
        "SELECT id, name, password FROM teacher_users WHERE LOWER(role) = 'admin' AND is_active = 1 ORDER BY id ASC"
    )->fetchAll();

    $matchedAdmin = null;
    foreach ($admins as $candidateAdmin) {
        if (adminVerifyPassword($password, (string) ($candidateAdmin['password'] ?? ''))) {
            $matchedAdmin = $candidateAdmin;
            break;
        }
    }

    if (!$matchedAdmin) {
        logExamSecurityEvent($subjectsDb, [
            'student_name' => $studentName,
            'student_class' => $studentClass,
            'assessment_id' => $assessmentId,
            'subject' => $subject,
            'task_type' => $taskType,
            'event_type' => 'admin_unlock_failed',
            'details' => [
                'violation_count' => (int) $security['violation_count']
            ]
        ]);

        http_response_code(401);
        echo json_encode([
            'ok' => false,
            'message' => 'Invalid admin password.'
        ]);
        return;
    }

    $security['requires_admin_unlock'] = false;
    $security['locked_at'] = 0;
    $persistSecurityState($security);

    logExamSecurityEvent($subjectsDb, [
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'assessment_id' => $assessmentId,
        'subject' => $subject,
        'task_type' => $taskType,
        'event_type' => 'admin_unlock_success',
        'details' => [
            'violation_count' => (int) $security['violation_count'],
            'admin_user_id' => (int) ($matchedAdmin['id'] ?? 0),
            'admin_name' => (string) ($matchedAdmin['name'] ?? '')
        ]
    ]);

    $adminDb->query(
        'INSERT INTO admin_audit_logs (admin_user_id, action_key, entity_type, entity_id, summary, context_json, ip_address, user_agent, created_at)
         VALUES (:admin_user_id, :action_key, :entity_type, :entity_id, :summary, :context_json, :ip_address, :user_agent, NOW())',
        [
            'admin_user_id' => (int) ($matchedAdmin['id'] ?? 0),
            'action_key' => 'exam.security_unlock',
            'entity_type' => 'student_exam_session',
            'entity_id' => $studentName . '|' . $studentClass,
            'summary' => 'Unlocked exam session after security violation',
            'context_json' => json_encode([
                'student_name' => $studentName,
                'student_class' => $studentClass,
                'assessment_id' => $assessmentId,
                'subject' => $subject,
                'task_type' => $taskType,
                'violation_count' => (int) $security['violation_count']
            ], JSON_UNESCAPED_SLASHES),
            'ip_address' => adminClientIp(),
            'user_agent' => substr(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255)
        ]
    );

    echo json_encode([
        'ok' => true,
        'locked' => false,
        'message' => 'Exam unlocked. You can continue now.',
        'security' => $security
    ]);
    return;
}

http_response_code(400);
echo json_encode([
    'ok' => false,
    'message' => 'Unsupported security action.'
]);
