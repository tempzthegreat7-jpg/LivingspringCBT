<?php

require basePath('Framework/Database.php');

header('Content-Type: application/json; charset=UTF-8');
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode((string) $rawInput, true);
$payload = is_array($jsonInput) ? $jsonInput : $_POST;
$quiz = Session::get('quiz') ?? [];
$subjects = Session::get('subjects') ?? [];
$student = Session::get('student') ?? [];

$isAutosaveRequest = !empty($payload)
    || array_key_exists('current_index', $payload)
    || array_key_exists('selected_choice', $payload)
    || array_key_exists('flagged', $payload)
    || array_key_exists('question_times', $payload)
    || array_key_exists('action', $payload)
    || array_key_exists('reviewed_before_submit', $payload);

if (empty($quiz['questions']) || empty($student['name'])) {
    if (!$isAutosaveRequest) {
        echo json_encode([
            'ok' => true,
            'keepalive' => true
        ]);
        return;
    }

    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'No active exam session.'
    ]);
    return;
}
$clientIp = null;
foreach ([(string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''), (string) ($_SERVER['REMOTE_ADDR'] ?? '')] as $candidate) {
    $parts = array_filter(array_map('trim', explode(',', $candidate)));
    if (!empty($parts)) {
        $clientIp = (string) $parts[0];
        break;
    }
}

$currentIndex = max(0, min((int) ($payload['current_index'] ?? ($quiz['current_index'] ?? 0)), max(0, ((int) ($quiz['total'] ?? count($quiz['questions'])) - 1))));
$selectedChoice = array_key_exists('selected_choice', $payload) ? trim((string) ($payload['selected_choice'] ?? '')) : null;
$flagged = array_key_exists('flagged', $payload) ? (bool) $payload['flagged'] : null;
$questionTimes = is_array($payload['question_times'] ?? null) ? $payload['question_times'] : [];
$answersPayload = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
$flagsPayload = is_array($payload['flags'] ?? null) ? $payload['flags'] : [];
$timeBonusSeconds = max(0, (int) ($payload['time_bonus_seconds'] ?? 0));
$action = trim((string) ($payload['action'] ?? 'autosave'));
$reviewedBeforeSubmit = !empty($payload['reviewed_before_submit']) || !empty($quiz['reviewed_before_submit']);

$answers = is_array($quiz['answers'] ?? null) ? $quiz['answers'] : [];
$flags = is_array($quiz['flags'] ?? null) ? $quiz['flags'] : [];
$storedQuestionTimes = is_array($quiz['question_times'] ?? null) ? $quiz['question_times'] : [];

// Merge full answers array if provided (for flush action)
if (!empty($answersPayload)) {
    $answers = $answersPayload;
}

// Merge full flags array if provided
if (!empty($flagsPayload)) {
    $flags = $flagsPayload;
}

if ($selectedChoice !== null) {
    $answers[$currentIndex] = $selectedChoice;
}

if ($flagged !== null) {
    $flags[$currentIndex] = $flagged;
}

foreach ($questionTimes as $index => $seconds) {
    $safeIndex = (int) $index;
    if ($safeIndex < 0) {
        continue;
    }
    $storedQuestionTimes[$safeIndex] = max(0, (int) $seconds);
}

$score = studentExamSessionCalculateScore(
    is_array($quiz['questions'] ?? null) ? $quiz['questions'] : [],
    $answers
);
$savedAt = date('Y-m-d H:i:s');

$updatedQuiz = $quiz;
$updatedQuiz['current_index'] = $currentIndex;
$updatedQuiz['answers'] = $answers;
$updatedQuiz['flags'] = $flags;
$updatedQuiz['question_times'] = $storedQuestionTimes;
$updatedQuiz['score'] = $score;
$updatedQuiz['reviewed_before_submit'] = $reviewedBeforeSubmit;
$updatedQuiz['last_autosaved_at'] = $savedAt;
Session::set('quiz', $updatedQuiz);

$config = require basePath('config/config-db2.php');
$db = new Database($config);
ensureStudentExamSessionsSchema($db);
ensureExamSessionEventsSchema($db);

$sessionRow = studentFindActiveExamSession($db, (string) ($student['name'] ?? ''), (string) ($student['class'] ?? 'SS3'));
$sessionId = studentSaveExamSession($db, [
    'id' => (int) ($sessionRow['id'] ?? 0),
    'student_name' => (string) ($student['name'] ?? ''),
    'student_class' => (string) ($student['class'] ?? 'SS3'),
    'assessment_id' => (int) ($subjects['assessment_id'] ?? 0),
    'subject' => (string) ($subjects['subject'] ?? ''),
    'task_type' => (string) ($subjects['task'] ?? 'exam'),
    'term_key' => (string) ($subjects['term'] ?? 'first_term'),
    'header_text' => (string) ($subjects['header'] ?? ''),
    'questions' => $quiz['questions'],
    'answers' => $answers,
    'flags' => $flags,
    'question_times' => $storedQuestionTimes,
    'current_index' => $currentIndex,
    'score' => $score,
    'total_questions' => (int) ($quiz['total'] ?? count($quiz['questions'])),
    'started_at' => (int) ($quiz['started_at'] ?? time()),
    'ends_at' => (int) ($quiz['ends_at'] ?? 0),
    'duration_seconds' => (int) ($quiz['duration_seconds'] ?? 0),
    'timer_paused' => (int) (!empty($quiz['timer_paused'])),
    'time_bonus_seconds' => $timeBonusSeconds,
    'admin_paused_at' => (string) ($quiz['admin_paused_at'] ?? ''),
    'last_control_sync_at' => date('Y-m-d H:i:s'),
    'last_autosaved_at' => $savedAt,
    'last_activity_at' => $savedAt,
    'attempt_logged' => !empty($quiz['attempt_logged']),
    'reviewed_before_submit' => $reviewedBeforeSubmit,
    'resume_count' => (int) ($quiz['resume_count'] ?? 0),
    'last_ip_address' => $clientIp,
    'last_user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
    'status' => 'in_progress'
]);

if ($action !== '') {
    studentLogExamSessionEvent($db, [
        'session_id' => $sessionId,
        'student_name' => (string) ($student['name'] ?? ''),
        'student_class' => (string) ($student['class'] ?? 'SS3'),
        'assessment_id' => (int) ($subjects['assessment_id'] ?? 0),
        'subject' => (string) ($subjects['subject'] ?? ''),
        'task_type' => (string) ($subjects['task'] ?? 'exam'),
        'event_key' => $action,
        'summary' => $action === 'review_opened'
            ? 'Student opened the final review panel.'
            : ($action === 'flag_toggled'
                ? 'Student updated a review flag.'
                : 'Exam progress autosaved.'),
        'current_index' => $currentIndex,
        'payload' => [
            'selected_choice' => $selectedChoice,
            'flagged' => $flagged,
            'reviewed_before_submit' => $reviewedBeforeSubmit
        ]
    ]);
}

$answeredCount = 0;
foreach ($answers as $answerValue) {
    if (trim((string) $answerValue) !== '') {
        $answeredCount++;
    }
}

echo json_encode([
    'ok' => true,
    'saved_at' => $savedAt,
    'answered_count' => $answeredCount,
    'flagged_count' => count(array_filter($flags)),
    'score' => $score
]);
