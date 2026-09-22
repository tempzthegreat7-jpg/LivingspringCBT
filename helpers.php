<?php

/**
 * Give a path
 * 
 * @param string $path
 * @return string 
 */

function basePath($path = '')
{
    return __DIR__ . '/' . $path;
}

/**
 * Load a View
 * 
 * @param string $name
 * @return void
 */

function loadView($name, $data = [])
{
    $viewPath = basePath("App/views/{$name}-view.php");
    if (file_exists($viewPath)) {
        extract($data, EXTR_SKIP);
        require $viewPath;
    } else {
        echo "No such File as '{$name}-view'.php!";
    }
}

/**
 * Load a Partial
 * 
 * @param string $name
 * @return void
 */

function loadPartial($name, $data = [])
{
    $partialPath = basePath("App/views/partials/{$name}.php");
    if (file_exists($partialPath)) {
        extract($data, EXTR_SKIP);
        require $partialPath;
    } else {
        echo "Partial '{$name}' not found!";
    }
}

/**
 * Inspect a value(s)
 * 
 * @params mixed $value
 * @return void
 */

function inspect($value)
{
    echo '<pre>';
    var_dump($value);
    echo '</pre>';
}


/**
 * Inspect and kills a value(s)
 * 
 * @params mixed $value
 * @return void
 */

function inspectAndDie($value)
{
    echo '<pre>';
    var_dump($value);
    echo '</pre>';
    die();
}

/**
 * Format salary
 * 
 * @param string $salary
 * @return string $formatted 
 */


function formatSalary($salary)
{
    return '$' . number_format(floatval($salary));
}

/**
 * Sanitizes Data
 * 
 * @param string $dirty
 * @return string
 */

function sanitize($dirty)
{
    return filter_var(trim($dirty), FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * Validate a dynamic table name to prevent SQL injection.
 * Only allows lowercase alphanumeric and underscore, 1-64 chars.
 *
 * @param string $name
 * @return string
 * @throws InvalidArgumentException
 */
function safeTableName($name)
{
    $clean = (string) $name;
    if ($clean === '' || !preg_match('/^[a-z0-9_]{1,64}$/', $clean)) {
        throw new InvalidArgumentException('Invalid table name: ' . substr($clean, 0, 80));
    }
    return $clean;
}

/**
 * Redirects to pages
 * 
 */

function redirect($url)
{
    header("Location: {$url}");
    exit;
}

/**
 * Return current csrf token
 *
 * @return string
 */
function csrfToken()
{
    if (class_exists('Session')) {
        return Session::csrfToken();
    }

    return '';
}

/**
 * Render hidden csrf field for forms
 *
 * @return string
 */
function csrfField()
{
    $token = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_token" value="' . $token . '" />';
}

/**
 * Formats user ID for UI display without changing database ID
 *
 * @param int|string $id
 * @return string
 */
function displayUserId($id)
{
    $numericId = max(0, (int) $id);
    return 'USR-' . str_pad((string) $numericId, 4, '0', STR_PAD_LEFT);
}

/**
 * Supported school terms for question banks
 *
 * @return array<string,string>
 */
function examTermOptions()
{
    return [
        'first_term' => '1st Term',
        'second_term' => '2nd Term',
        'third_term' => '3rd Term'
    ];
}

/**
 * Normalize incoming term value
 *
 * @param string|null $term
 * @param string $default
 * @return string
 */
function normalizeExamTerm($term, $default = 'first_term')
{
    $value = strtolower(trim((string) $term));
    $allowed = examTermOptions();

    if (array_key_exists($value, $allowed)) {
        return $value;
    }

    return $default;
}

/**
 * Supported assessment task types
 *
 * @return array<string,string>
 */
function assessmentTaskOptions()
{
    return [
        'assignment' => 'Assignment',
        'classwork' => 'Classwork',
        'test' => 'Test',
        'exam' => 'Exam',
        'others' => 'Others'
    ];
}

/**
 * Normalize incoming task value
 *
 * @param string|null $task
 * @param string $default
 * @return string
 */
function normalizeAssessmentTask($task, $default = 'exam')
{
    $value = strtolower(trim((string) $task));
    $allowed = assessmentTaskOptions();

    if (array_key_exists($value, $allowed)) {
        return $value;
    }

    return $default;
}

/**
 * Normalize feedback review status values.
 *
 * @param string|null $status
 * @param string $default
 * @return string
 */
function normalizeFeedbackStatus($status, $default = 'pending_review')
{
    $value = strtolower(trim((string) $status));
    $allowed = [
        'pending_review' => 'pending_review',
        'approved' => 'approved',
        'rejected' => 'rejected',
        'archived' => 'archived'
    ];

    if (array_key_exists($value, $allowed)) {
        return $allowed[$value];
    }

    return $default;
}

/**
 * Return the label for a feedback review status.
 *
 * @param string|null $status
 * @return string
 */
function feedbackStatusLabel($status)
{
    $map = [
        'pending_review' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'archived' => 'Archived'
    ];

    return $map[normalizeFeedbackStatus($status)] ?? 'Pending Review';
}

/**
 * Determine whether a task requires a free-text header
 *
 * @param string|null $task
 * @return bool
 */
function assessmentTaskNeedsHeader($task)
{
    $taskKey = normalizeAssessmentTask($task);
    return in_array($taskKey, ['assignment', 'classwork', 'test', 'others'], true);
}

/**
 * Determine whether a task requires duration
 *
 * @param string|null $task
 * @return bool
 */
function assessmentTaskNeedsDuration($task)
{
    return normalizeAssessmentTask($task) === 'exam';
}

/**
 * Create a safe slug fragment for custom headers
 *
 * @param string|null $header
 * @return string
 */
function assessmentHeaderSlug($header)
{
    $value = strtolower(trim((string) $header));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    $value = trim((string) $value, '_');

    if ($value === '') {
        return 'untitled';
    }

    return substr($value, 0, 30);
}

/**
 * Build question bank table name
 *
 * @param string $subject
 * @param string $studentClass
 * @param string $term
 * @return string
 */
function questionBankTableName($subject, $studentClass, $term)
{
    return questionBankTableNameForTask($subject, $studentClass, 'exam', '', $term);
}

/**
 * Build question bank table name for task context
 *
 * @param string $subject
 * @param string $studentClass
 * @param string $task
 * @param string $header
 * @param string $term
 * @return string
 */
function questionBankTableNameForTask($subject, $studentClass, $task = 'exam', $header = '', $term = 'first_term')
{
    $subjectKey = preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string) $subject)));
    $classKey = preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string) $studentClass)));
    $taskKey = normalizeAssessmentTask($task);

    if ($subjectKey === '' || $classKey === '') {
        return '_';
    }

    if ($taskKey === 'exam') {
        $contextKey = preg_replace('/[^a-z0-9_]/', '', normalizeExamTerm($term));
    } else {
        $contextKey = $taskKey . '_' . assessmentHeaderSlug($header);
    }

    $raw = $subjectKey . '_' . $classKey . '_' . $contextKey;
    if (strlen($raw) <= 64) {
        return $raw;
    }

    // Keep MySQL table names within 64 chars while remaining deterministic.
    return substr($subjectKey, 0, 16) . '_' . substr($classKey, 0, 8) . '_' . substr($taskKey, 0, 10) . '_' . substr(md5($contextKey), 0, 12);
}

/**
 * Ensure assessment metadata table exists
 *
 * @param mixed $db
 * @return void
 */
function ensureAssessmentConfigsSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS assessment_configs (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            teacher_user_id INT(11) NOT NULL,
            subject VARCHAR(80) NOT NULL,
            student_class VARCHAR(20) NOT NULL,
            task_type VARCHAR(20) NOT NULL,
            header_text VARCHAR(160) NULL,
            term_key VARCHAR(20) NULL,
            duration_seconds INT(11) NULL,
            table_name VARCHAR(80) NOT NULL UNIQUE,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )'
    );

    $columns = $db->query('SHOW COLUMNS FROM assessment_configs')->fetchAll();
    $hasQuestionLimit = false;

    foreach ($columns as $column) {
        if (strtolower((string) ($column['Field'] ?? '')) === 'question_limit') {
            $hasQuestionLimit = true;
            break;
        }
    }

    if (!$hasQuestionLimit) {
        $db->query('ALTER TABLE assessment_configs ADD COLUMN question_limit INT(11) NULL AFTER duration_seconds');
    }
}

/**
 * Ensure exam attempts table exists
 *
 * @param mixed $db
 * @return void
 */
function ensureExamAttemptsSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS exam_attempts (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(120) NOT NULL,
            student_class VARCHAR(20) NOT NULL,
            subject VARCHAR(80) NOT NULL,
            task_type VARCHAR(20) NOT NULL DEFAULT "exam",
            score INT(11) NOT NULL,
            total_questions INT(11) NOT NULL,
            time_spent_seconds INT(11) NOT NULL,
            timed_out TINYINT(1) NOT NULL DEFAULT 0,
            completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $attemptColumns = $db->query('SHOW COLUMNS FROM exam_attempts')->fetchAll();
    $hasTaskType = false;
    foreach ($attemptColumns as $attemptColumn) {
        if (strtolower((string) ($attemptColumn['Field'] ?? '')) === 'task_type') {
            $hasTaskType = true;
            break;
        }
    }

    if (!$hasTaskType) {
        $db->query('ALTER TABLE exam_attempts ADD COLUMN task_type VARCHAR(20) NOT NULL DEFAULT "exam" AFTER subject');
    }

    $knownColumns = [];
    foreach ($attemptColumns as $attemptColumn) {
        $knownColumns[strtolower((string) ($attemptColumn['Field'] ?? ''))] = true;
    }

    $requiredColumns = [
        'assessment_id' => 'ALTER TABLE exam_attempts ADD COLUMN assessment_id INT(11) NOT NULL DEFAULT 0 AFTER student_class',
        'term_key' => 'ALTER TABLE exam_attempts ADD COLUMN term_key VARCHAR(20) NULL AFTER task_type',
        'header_text' => 'ALTER TABLE exam_attempts ADD COLUMN header_text VARCHAR(160) NULL AFTER term_key',
        'question_breakdown_json' => 'ALTER TABLE exam_attempts ADD COLUMN question_breakdown_json LONGTEXT NULL AFTER timed_out',
        'flags_json' => 'ALTER TABLE exam_attempts ADD COLUMN flags_json LONGTEXT NULL AFTER question_breakdown_json',
        'reviewed_before_submit' => 'ALTER TABLE exam_attempts ADD COLUMN reviewed_before_submit TINYINT(1) NOT NULL DEFAULT 0 AFTER flags_json'
    ];

    foreach ($requiredColumns as $columnName => $query) {
        if (!isset($knownColumns[$columnName])) {
            $db->query($query);
        }
    }
}

/**
 * Ensure persistent student exam sessions table exists
 *
 * @param mixed $db
 * @return void
 */
function ensureStudentExamSessionsSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS student_exam_sessions (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(120) NOT NULL,
            student_class VARCHAR(20) NOT NULL,
            assessment_id INT(11) NOT NULL,
            subject VARCHAR(80) NOT NULL,
            task_type VARCHAR(20) NOT NULL DEFAULT "exam",
            term_key VARCHAR(20) NULL,
            header_text VARCHAR(160) NULL,
            questions_json LONGTEXT NOT NULL,
            answers_json LONGTEXT NOT NULL,
            current_index INT(11) NOT NULL DEFAULT 0,
            score INT(11) NOT NULL DEFAULT 0,
            total_questions INT(11) NOT NULL DEFAULT 0,
            started_at INT(11) NOT NULL DEFAULT 0,
            ends_at INT(11) NOT NULL DEFAULT 0,
            duration_seconds INT(11) NOT NULL DEFAULT 0,
            attempt_logged TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT "in_progress",
            completed_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_student_exam_session_lookup (student_name, student_class, status, updated_at),
            KEY idx_student_exam_session_assessment (assessment_id, student_name, student_class)
        )'
    );

    $columns = $db->query('SHOW COLUMNS FROM student_exam_sessions')->fetchAll();
    $knownColumns = [];
    foreach ($columns as $column) {
        $knownColumns[strtolower((string) ($column['Field'] ?? ''))] = true;
    }

    $requiredColumns = [
        'flags_json' => 'ALTER TABLE student_exam_sessions ADD COLUMN flags_json LONGTEXT NULL AFTER answers_json',
        'question_times_json' => 'ALTER TABLE student_exam_sessions ADD COLUMN question_times_json LONGTEXT NULL AFTER flags_json',
        'last_autosaved_at' => 'ALTER TABLE student_exam_sessions ADD COLUMN last_autosaved_at DATETIME NULL AFTER duration_seconds',
        'last_activity_at' => 'ALTER TABLE student_exam_sessions ADD COLUMN last_activity_at DATETIME NULL AFTER last_autosaved_at',
        'reviewed_before_submit' => 'ALTER TABLE student_exam_sessions ADD COLUMN reviewed_before_submit TINYINT(1) NOT NULL DEFAULT 0 AFTER attempt_logged',
        'resume_count' => 'ALTER TABLE student_exam_sessions ADD COLUMN resume_count INT(11) NOT NULL DEFAULT 0 AFTER reviewed_before_submit',
        'last_ip_address' => 'ALTER TABLE student_exam_sessions ADD COLUMN last_ip_address VARCHAR(64) NULL AFTER resume_count',
        'last_user_agent' => 'ALTER TABLE student_exam_sessions ADD COLUMN last_user_agent VARCHAR(255) NULL AFTER last_ip_address',
        'timer_paused' => 'ALTER TABLE student_exam_sessions ADD COLUMN timer_paused TINYINT(1) NOT NULL DEFAULT 0 AFTER duration_seconds',
        'time_bonus_seconds' => 'ALTER TABLE student_exam_sessions ADD COLUMN time_bonus_seconds INT(11) NOT NULL DEFAULT 0 AFTER timer_paused',
        'admin_paused_at' => 'ALTER TABLE student_exam_sessions ADD COLUMN admin_paused_at DATETIME NULL AFTER time_bonus_seconds',
        'last_control_sync_at' => 'ALTER TABLE student_exam_sessions ADD COLUMN last_control_sync_at DATETIME NULL AFTER admin_paused_at',
        'last_user_agent' => 'ALTER TABLE student_exam_sessions ADD COLUMN last_user_agent VARCHAR(255) NULL AFTER last_ip_address'
    ];

    foreach ($requiredColumns as $columnName => $query) {
        if (!isset($knownColumns[$columnName])) {
            $db->query($query);
        }
    }
}

function ensureExamSessionEventsSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS exam_session_events (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            session_id INT(11) NOT NULL DEFAULT 0,
            student_name VARCHAR(120) NOT NULL,
            student_class VARCHAR(20) NOT NULL,
            assessment_id INT(11) NOT NULL DEFAULT 0,
            subject VARCHAR(80) NOT NULL,
            task_type VARCHAR(20) NOT NULL DEFAULT "exam",
            event_key VARCHAR(40) NOT NULL,
            summary VARCHAR(255) NOT NULL,
            current_index INT(11) NOT NULL DEFAULT 0,
            payload_json LONGTEXT NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_exam_session_events_session (session_id, created_at),
            INDEX idx_exam_session_events_assessment (assessment_id, subject, created_at)
        )'
    );
}

function ensureExamQuestionAnalyticsSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS exam_question_analytics (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            attempt_id INT(11) NOT NULL DEFAULT 0,
            assessment_id INT(11) NOT NULL DEFAULT 0,
            student_name VARCHAR(120) NOT NULL,
            student_class VARCHAR(20) NOT NULL,
            subject VARCHAR(80) NOT NULL,
            task_type VARCHAR(20) NOT NULL DEFAULT "exam",
            question_hash VARCHAR(64) NOT NULL,
            question_number INT(11) NOT NULL DEFAULT 0,
            question_text TEXT NOT NULL,
            selected_answer TEXT NULL,
            correct_answer TEXT NULL,
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            was_answered TINYINT(1) NOT NULL DEFAULT 0,
            was_flagged TINYINT(1) NOT NULL DEFAULT 0,
            time_spent_seconds INT(11) NOT NULL DEFAULT 0,
            completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_exam_question_analytics_assessment (assessment_id, subject, student_class),
            INDEX idx_exam_question_analytics_question (question_hash)
        )'
    );
}

/**
 * Decode stored session JSON safely
 *
 * @param string|null $value
 * @param mixed $default
 * @return mixed
 */
function studentExamSessionDecodeJson($value, $default)
{
    $decoded = json_decode((string) $value, true);
    return is_array($decoded) ? $decoded : $default;
}

/**
 * Calculate score from question/answer payloads
 *
 * @param array $questions
 * @param array $answers
 * @return int
 */
function studentExamSessionCalculateScore(array $questions, array $answers)
{
    $score = 0;

    foreach ($questions as $index => $question) {
        $selectedAnswer = trim((string) ($answers[$index] ?? ''));
        $correctAnswer = trim((string) ($question['correct_answer'] ?? ''));

        if ($selectedAnswer !== '' && strcasecmp($selectedAnswer, $correctAnswer) === 0) {
            $score++;
        }
    }

    return $score;
}

/**
 * Return a shuffled copy of a question set for per-student delivery
 *
 * @param array $questions
 * @return array
 */
function randomizeQuestionSet(array $questions)
{
    $shuffled = array_values($questions);

    if (count($shuffled) > 1) {
        shuffle($shuffled);
    }

    return $shuffled;
}

/**
 * Find the latest in-progress exam session for a student
 *
 * @param mixed $db
 * @param string $studentName
 * @param string $studentClass
 * @return array|null
 */
function studentFindActiveExamSession($db, $studentName, $studentClass)
{
    ensureStudentExamSessionsSchema($db);

    $row = $db->query(
        'SELECT *
         FROM student_exam_sessions
         WHERE student_name = :student_name
           AND student_class = :student_class
           AND task_type = :task_type
           AND status = :status
         ORDER BY updated_at DESC, id DESC
         LIMIT 1',
        [
            'student_name' => (string) $studentName,
            'student_class' => strtoupper((string) $studentClass),
            'task_type' => 'exam',
            'status' => 'in_progress'
        ]
    )->fetch();

    return $row ?: null;
}

/**
 * Check if persisted exam session can still be resumed
 *
 * @param array|null $sessionRow
 * @return bool
 */
function studentExamSessionIsResumable($sessionRow)
{
    if (!is_array($sessionRow) || empty($sessionRow)) {
        return false;
    }

    $questions = studentExamSessionDecodeJson($sessionRow['questions_json'] ?? '[]', []);
    $total = max(0, (int) ($sessionRow['total_questions'] ?? count($questions)));
    $currentIndex = (int) ($sessionRow['current_index'] ?? 0);
    $attemptLogged = (int) ($sessionRow['attempt_logged'] ?? 0) === 1;
    $status = strtolower(trim((string) ($sessionRow['status'] ?? 'in_progress')));
    $endsAt = (int) ($sessionRow['ends_at'] ?? 0);

    if ($status !== 'in_progress' || $attemptLogged || empty($questions) || $total < 1) {
        return false;
    }

    if ($currentIndex < 0 || $currentIndex >= $total) {
        return false;
    }

    if ($endsAt > 0 && time() >= $endsAt) {
        return false;
    }

    return true;
}

/**
 * Restore persisted exam session into current PHP session
 *
 * @param array $sessionRow
 * @return bool
 */
function studentRestoreExamSessionToPhpSession(array $sessionRow)
{
    $questions = studentExamSessionDecodeJson($sessionRow['questions_json'] ?? '[]', []);
    $answers = studentExamSessionDecodeJson($sessionRow['answers_json'] ?? '[]', []);
    $total = max(0, (int) ($sessionRow['total_questions'] ?? count($questions)));
    $currentIndex = (int) ($sessionRow['current_index'] ?? 0);

    if (empty($questions) || $total < 1 || $currentIndex < 0 || $currentIndex >= $total) {
        return false;
    }

    Session::set('subjects', [
        'subject' => strtolower(trim((string) ($sessionRow['subject'] ?? ''))),
        'class' => strtoupper(trim((string) ($sessionRow['student_class'] ?? 'SS3'))),
        'task' => normalizeAssessmentTask((string) ($sessionRow['task_type'] ?? 'exam')),
        'header' => trim((string) ($sessionRow['header_text'] ?? '')),
        'term' => normalizeExamTerm((string) ($sessionRow['term_key'] ?? 'first_term')),
        'assessment_id' => (int) ($sessionRow['assessment_id'] ?? 0)
    ]);

    Session::set('quiz', [
        'questions' => $questions,
        'current_index' => $currentIndex,
        'answers' => $answers,
        'flags' => studentExamSessionDecodeJson($sessionRow['flags_json'] ?? '[]', []),
        'question_times' => studentExamSessionDecodeJson($sessionRow['question_times_json'] ?? '[]', []),
        'score' => (int) ($sessionRow['score'] ?? studentExamSessionCalculateScore($questions, $answers)),
        'total' => $total,
        'started_at' => (int) ($sessionRow['started_at'] ?? 0),
        'ends_at' => (int) ($sessionRow['ends_at'] ?? 0),
        'duration_seconds' => (int) ($sessionRow['duration_seconds'] ?? 0),
        'attempt_logged' => (int) ($sessionRow['attempt_logged'] ?? 0) === 1,
        'reviewed_before_submit' => (int) ($sessionRow['reviewed_before_submit'] ?? 0) === 1,
        'last_autosaved_at' => (string) ($sessionRow['last_autosaved_at'] ?? ''),
        'resume_count' => (int) ($sessionRow['resume_count'] ?? 0),
        'timer_paused' => (int) ($sessionRow['timer_paused'] ?? 0) === 1,
        'time_bonus_seconds' => max(0, (int) ($sessionRow['time_bonus_seconds'] ?? 0)),
        'admin_paused_at' => (string) ($sessionRow['admin_paused_at'] ?? ''),
        'resume_count' => (int) ($sessionRow['resume_count'] ?? 0)
    ]);

    return true;
}

/**
 * Save or update the current in-progress exam session
 *
 * @param mixed $db
 * @param array $payload
 * @return int
 */
function studentSaveExamSession($db, array $payload)
{
    ensureStudentExamSessionsSchema($db);

    $studentName = trim((string) ($payload['student_name'] ?? ''));
    $studentClass = strtoupper(trim((string) ($payload['student_class'] ?? 'SS3')));
    $sessionId = (int) ($payload['id'] ?? 0);
    $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : [];
    $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
    $flags = is_array($payload['flags'] ?? null) ? $payload['flags'] : [];
    $questionTimes = is_array($payload['question_times'] ?? null) ? $payload['question_times'] : [];

    if ($studentName === '' || empty($questions)) {
        return 0;
    }

    $params = [
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'assessment_id' => (int) ($payload['assessment_id'] ?? 0),
        'subject' => strtolower(trim((string) ($payload['subject'] ?? ''))),
        'task_type' => normalizeAssessmentTask((string) ($payload['task_type'] ?? 'exam')),
        'term_key' => normalizeExamTerm((string) ($payload['term_key'] ?? 'first_term')),
        'header_text' => trim((string) ($payload['header_text'] ?? '')),
        'questions_json' => json_encode(array_values($questions), JSON_UNESCAPED_SLASHES),
        'answers_json' => json_encode($answers, JSON_UNESCAPED_SLASHES),
        'flags_json' => json_encode($flags, JSON_UNESCAPED_SLASHES),
        'question_times_json' => json_encode($questionTimes, JSON_UNESCAPED_SLASHES),
        'current_index' => (int) ($payload['current_index'] ?? 0),
        'score' => (int) ($payload['score'] ?? studentExamSessionCalculateScore($questions, $answers)),
        'total_questions' => max(0, (int) ($payload['total_questions'] ?? count($questions))),
        'started_at' => (int) ($payload['started_at'] ?? time()),
        'ends_at' => (int) ($payload['ends_at'] ?? 0),
        'duration_seconds' => (int) ($payload['duration_seconds'] ?? 0),
        'timer_paused' => (int) (!empty($payload['timer_paused'])),
        'time_bonus_seconds' => max(0, (int) ($payload['time_bonus_seconds'] ?? 0)),
        'admin_paused_at' => trim((string) ($payload['admin_paused_at'] ?? '')) !== '' ? (string) $payload['admin_paused_at'] : null,
        'last_control_sync_at' => trim((string) ($payload['last_control_sync_at'] ?? '')) !== '' ? (string) $payload['last_control_sync_at'] : null,
        'last_autosaved_at' => trim((string) ($payload['last_autosaved_at'] ?? '')) !== '' ? (string) $payload['last_autosaved_at'] : null,
        'last_activity_at' => trim((string) ($payload['last_activity_at'] ?? '')) !== '' ? (string) $payload['last_activity_at'] : date('Y-m-d H:i:s'),
        'attempt_logged' => (int) (!empty($payload['attempt_logged'])),
        'reviewed_before_submit' => (int) (!empty($payload['reviewed_before_submit'])),
        'resume_count' => max(0, (int) ($payload['resume_count'] ?? 0)),
        'last_ip_address' => trim((string) ($payload['last_ip_address'] ?? '')) !== '' ? (string) $payload['last_ip_address'] : null,
        'last_user_agent' => trim((string) ($payload['last_user_agent'] ?? '')) !== '' ? substr((string) $payload['last_user_agent'], 0, 255) : null,
        'status' => strtolower(trim((string) ($payload['status'] ?? 'in_progress')))
    ];
    $params['completed_at'] = $params['status'] === 'completed'
        ? date('Y-m-d H:i:s')
        : null;

    if ($sessionId <= 0) {
        $existing = studentFindActiveExamSession($db, $studentName, $studentClass);
        $sessionId = (int) ($existing['id'] ?? 0);
    }

    if ($sessionId > 0) {
        $updateParams = [
            'id' => $sessionId,
            'assessment_id' => $params['assessment_id'],
            'subject' => $params['subject'],
            'task_type' => $params['task_type'],
            'term_key' => $params['term_key'],
            'header_text' => $params['header_text'],
            'questions_json' => $params['questions_json'],
            'answers_json' => $params['answers_json'],
            'flags_json' => $params['flags_json'],
            'question_times_json' => $params['question_times_json'],
            'current_index' => $params['current_index'],
            'score' => $params['score'],
            'total_questions' => $params['total_questions'],
            'started_at' => $params['started_at'],
            'ends_at' => $params['ends_at'],
            'duration_seconds' => $params['duration_seconds'],
            'timer_paused' => $params['timer_paused'],
            'time_bonus_seconds' => $params['time_bonus_seconds'],
            'admin_paused_at' => $params['admin_paused_at'],
            'last_control_sync_at' => $params['last_control_sync_at'],
            'last_autosaved_at' => $params['last_autosaved_at'],
            'last_activity_at' => $params['last_activity_at'],
            'attempt_logged' => $params['attempt_logged'],
            'reviewed_before_submit' => $params['reviewed_before_submit'],
            'resume_count' => $params['resume_count'],
            'last_ip_address' => $params['last_ip_address'],
            'last_user_agent' => $params['last_user_agent'],
            'status' => $params['status'],
            'completed_at' => $params['completed_at']
        ];
        $db->query(
            'UPDATE student_exam_sessions
             SET assessment_id = :assessment_id,
                 subject = :subject,
                 task_type = :task_type,
                 term_key = :term_key,
                 header_text = :header_text,
                 questions_json = :questions_json,
                 answers_json = :answers_json,
                 flags_json = :flags_json,
                 question_times_json = :question_times_json,
                 current_index = :current_index,
                 score = :score,
                 total_questions = :total_questions,
                 started_at = :started_at,
                 ends_at = :ends_at,
                 duration_seconds = :duration_seconds,
                 timer_paused = :timer_paused,
                 time_bonus_seconds = :time_bonus_seconds,
                 admin_paused_at = :admin_paused_at,
                 last_control_sync_at = :last_control_sync_at,
                 last_autosaved_at = :last_autosaved_at,
                 last_activity_at = :last_activity_at,
                 attempt_logged = :attempt_logged,
                 reviewed_before_submit = :reviewed_before_submit,
                 resume_count = :resume_count,
                 last_ip_address = :last_ip_address,
                 last_user_agent = :last_user_agent,
                 status = :status,
                 completed_at = :completed_at
             WHERE id = :id
             LIMIT 1',
            $updateParams
        );

        return $sessionId;
    }

    $db->query(
        'INSERT INTO student_exam_sessions (
            student_name,
            student_class,
            assessment_id,
            subject,
            task_type,
            term_key,
            header_text,
            questions_json,
            answers_json,
            flags_json,
            question_times_json,
            current_index,
            score,
            total_questions,
            started_at,
            ends_at,
            duration_seconds,
            timer_paused,
            time_bonus_seconds,
            admin_paused_at,
            last_control_sync_at,
            last_autosaved_at,
            last_activity_at,
            attempt_logged,
            reviewed_before_submit,
            resume_count,
            last_ip_address,
            last_user_agent,
            status,
            completed_at
         ) VALUES (
            :student_name,
            :student_class,
            :assessment_id,
            :subject,
            :task_type,
            :term_key,
            :header_text,
            :questions_json,
            :answers_json,
            :flags_json,
            :question_times_json,
            :current_index,
            :score,
            :total_questions,
            :started_at,
            :ends_at,
            :duration_seconds,
            :timer_paused,
            :time_bonus_seconds,
            :admin_paused_at,
            :last_control_sync_at,
            :last_autosaved_at,
            :last_activity_at,
            :attempt_logged,
            :reviewed_before_submit,
            :resume_count,
            :last_ip_address,
            :last_user_agent,
            :status,
            :completed_at
         )',
        $params
    );

    return (int) ($db->connection->lastInsertId() ?? 0);
}

/**
 * Finalize a persisted exam session and log result once
 *
 * @param mixed $db
 * @param array $sessionRow
 * @param bool $timedOut
 * @return array
 */
function studentFinalizeExamSession($db, array $sessionRow, $timedOut = false)
{
    ensureStudentExamSessionsSchema($db);
    ensureExamAttemptsSchema($db);
    ensureExamQuestionAnalyticsSchema($db);

    $questions = studentExamSessionDecodeJson($sessionRow['questions_json'] ?? '[]', []);
    $answers = studentExamSessionDecodeJson($sessionRow['answers_json'] ?? '[]', []);
    $flags = studentExamSessionDecodeJson($sessionRow['flags_json'] ?? '[]', []);
    $questionTimes = studentExamSessionDecodeJson($sessionRow['question_times_json'] ?? '[]', []);
    $score = studentExamSessionCalculateScore($questions, $answers);
    $total = max(0, (int) ($sessionRow['total_questions'] ?? count($questions)));
    $startedAt = (int) ($sessionRow['started_at'] ?? time());
    $durationSeconds = max(0, (int) ($sessionRow['duration_seconds'] ?? 0));
    $elapsed = max(0, time() - $startedAt);

    if ($durationSeconds > 0) {
        $elapsed = min($elapsed, $durationSeconds);
    }

    $questionBreakdown = [];
    foreach ($questions as $index => $question) {
        $selectedAnswer = trim((string) ($answers[$index] ?? ''));
        $correctAnswer = trim((string) ($question['correct_answer'] ?? ''));
        $wasAnswered = $selectedAnswer !== '';
        $questionBreakdown[] = [
            'question_number' => $index + 1,
            'question_text' => trim((string) ($question['question'] ?? '')),
            'selected_answer' => $selectedAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $wasAnswered && strcasecmp($selectedAnswer, $correctAnswer) === 0,
            'was_answered' => $wasAnswered,
            'was_flagged' => !empty($flags[$index]),
            'time_spent_seconds' => max(0, (int) ($questionTimes[$index] ?? 0))
        ];
    }

    if ((int) ($sessionRow['attempt_logged'] ?? 0) !== 1) {
        $db->query(
            'INSERT INTO exam_attempts (student_name, student_class, assessment_id, subject, task_type, term_key, header_text, score, total_questions, time_spent_seconds, timed_out, question_breakdown_json, flags_json, reviewed_before_submit, completed_at)
             VALUES (:student_name, :student_class, :assessment_id, :subject, :task_type, :term_key, :header_text, :score, :total_questions, :time_spent_seconds, :timed_out, :question_breakdown_json, :flags_json, :reviewed_before_submit, NOW())',
            [
                'student_name' => (string) ($sessionRow['student_name'] ?? 'Unknown Student'),
                'student_class' => strtoupper((string) ($sessionRow['student_class'] ?? 'SS3')),
                'assessment_id' => (int) ($sessionRow['assessment_id'] ?? 0),
                'subject' => strtolower((string) ($sessionRow['subject'] ?? 'unknown')),
                'task_type' => normalizeAssessmentTask((string) ($sessionRow['task_type'] ?? 'exam')),
                'term_key' => normalizeExamTerm((string) ($sessionRow['term_key'] ?? 'first_term')),
                'header_text' => trim((string) ($sessionRow['header_text'] ?? '')),
                'score' => $score,
                'total_questions' => $total,
                'time_spent_seconds' => $elapsed,
                'timed_out' => $timedOut ? 1 : 0,
                'question_breakdown_json' => json_encode($questionBreakdown, JSON_UNESCAPED_SLASHES),
                'flags_json' => json_encode($flags, JSON_UNESCAPED_SLASHES),
                'reviewed_before_submit' => !empty($sessionRow['reviewed_before_submit']) ? 1 : 0
            ]
        );

        $attemptId = (int) ($db->connection->lastInsertId() ?? 0);
        foreach ($questionBreakdown as $questionRow) {
            $db->query(
                'INSERT INTO exam_question_analytics (attempt_id, assessment_id, student_name, student_class, subject, task_type, question_hash, question_number, question_text, selected_answer, correct_answer, is_correct, was_answered, was_flagged, time_spent_seconds, completed_at)
                 VALUES (:attempt_id, :assessment_id, :student_name, :student_class, :subject, :task_type, :question_hash, :question_number, :question_text, :selected_answer, :correct_answer, :is_correct, :was_answered, :was_flagged, :time_spent_seconds, NOW())',
                [
                    'attempt_id' => $attemptId,
                    'assessment_id' => (int) ($sessionRow['assessment_id'] ?? 0),
                    'student_name' => (string) ($sessionRow['student_name'] ?? 'Unknown Student'),
                    'student_class' => strtoupper((string) ($sessionRow['student_class'] ?? 'SS3')),
                    'subject' => strtolower((string) ($sessionRow['subject'] ?? 'unknown')),
                    'task_type' => normalizeAssessmentTask((string) ($sessionRow['task_type'] ?? 'exam')),
                    'question_hash' => sha1(strtolower((string) ($sessionRow['subject'] ?? 'unknown')) . '|' . strtolower((string) ($questionRow['question_text'] ?? '')) . '|' . strtolower((string) ($questionRow['correct_answer'] ?? ''))),
                    'question_number' => (int) ($questionRow['question_number'] ?? 0),
                    'question_text' => (string) ($questionRow['question_text'] ?? ''),
                    'selected_answer' => (string) ($questionRow['selected_answer'] ?? ''),
                    'correct_answer' => (string) ($questionRow['correct_answer'] ?? ''),
                    'is_correct' => !empty($questionRow['is_correct']) ? 1 : 0,
                    'was_answered' => !empty($questionRow['was_answered']) ? 1 : 0,
                    'was_flagged' => !empty($questionRow['was_flagged']) ? 1 : 0,
                    'time_spent_seconds' => (int) ($questionRow['time_spent_seconds'] ?? 0)
                ]
            );
        }
    }

    $sessionRow['answers_json'] = json_encode($answers, JSON_UNESCAPED_SLASHES);
    $sessionRow['score'] = $score;
    $sessionRow['total_questions'] = $total;
    $sessionRow['attempt_logged'] = 1;
    $sessionRow['status'] = 'completed';
    $sessionRow['current_index'] = $total;
    $sessionRow['completed_at'] = date('Y-m-d H:i:s');

    studentSaveExamSession($db, [
        'id' => (int) ($sessionRow['id'] ?? 0),
        'student_name' => (string) ($sessionRow['student_name'] ?? ''),
        'student_class' => (string) ($sessionRow['student_class'] ?? 'SS3'),
        'assessment_id' => (int) ($sessionRow['assessment_id'] ?? 0),
        'subject' => (string) ($sessionRow['subject'] ?? ''),
        'task_type' => (string) ($sessionRow['task_type'] ?? 'exam'),
        'term_key' => (string) ($sessionRow['term_key'] ?? 'first_term'),
        'header_text' => (string) ($sessionRow['header_text'] ?? ''),
        'questions' => $questions,
        'answers' => $answers,
        'flags' => $flags,
        'question_times' => $questionTimes,
        'current_index' => $total,
        'score' => $score,
        'total_questions' => $total,
        'started_at' => $startedAt,
        'ends_at' => (int) ($sessionRow['ends_at'] ?? 0),
        'duration_seconds' => $durationSeconds,
        'timer_paused' => (int) ($sessionRow['timer_paused'] ?? 0),
        'time_bonus_seconds' => max(0, (int) ($sessionRow['time_bonus_seconds'] ?? 0)),
        'admin_paused_at' => (string) ($sessionRow['admin_paused_at'] ?? ''),
        'last_control_sync_at' => date('Y-m-d H:i:s'),
        'last_autosaved_at' => (string) ($sessionRow['last_autosaved_at'] ?? ''),
        'last_activity_at' => date('Y-m-d H:i:s'),
        'attempt_logged' => true,
        'reviewed_before_submit' => !empty($sessionRow['reviewed_before_submit']),
        'resume_count' => (int) ($sessionRow['resume_count'] ?? 0),
        'last_ip_address' => (string) ($sessionRow['last_ip_address'] ?? ''),
        'last_user_agent' => (string) ($sessionRow['last_user_agent'] ?? ''),
        'status' => 'completed'
    ]);

    return $sessionRow;
}

/**
 * Ensure exam activation table exists
 *
 * @param mixed $db
 * @return void
 */
function ensureExamActivationSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS exam_active_subjects (
            student_class VARCHAR(20) NOT NULL,
            subject VARCHAR(80) NOT NULL,
            updated_by_admin_id INT(11) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (student_class, subject)
        )'
    );

    $columns = $db->query('DESCRIBE exam_active_subjects')->fetchAll();
    $hasUpdatedBy = false;

    foreach ($columns as $column) {
        if (strtolower((string) ($column["Field"] ?? "")) === "updated_by_admin_id") {
            $hasUpdatedBy = true;
            break;
        }
    }

    if (!$hasUpdatedBy) {
        $db->query('ALTER TABLE exam_active_subjects ADD COLUMN updated_by_admin_id INT(11) NULL AFTER subject');
    }

    $indexes = $db->query('SHOW INDEX FROM exam_active_subjects')->fetchAll();
    $primaryColumns = [];
    foreach ($indexes as $indexRow) {
        if (strtolower((string) ($indexRow['Key_name'] ?? '')) !== 'primary') {
            continue;
        }

        $seq = (int) ($indexRow['Seq_in_index'] ?? 0);
        $columnName = strtolower(trim((string) ($indexRow['Column_name'] ?? '')));
        if ($seq > 0 && $columnName !== '') {
            $primaryColumns[$seq] = $columnName;
        }
    }

    ksort($primaryColumns);
    $primaryColumns = array_values($primaryColumns);
    if ($primaryColumns !== ['student_class', 'subject']) {
        if (!empty($primaryColumns)) {
            $db->query('ALTER TABLE exam_active_subjects DROP PRIMARY KEY');
        }
        $db->query('ALTER TABLE exam_active_subjects ADD PRIMARY KEY (student_class, subject)');
    }
}

/**
 * Return active exam subjects for a class
 *
 * @param mixed $db
 * @param string|null $studentClass
 * @return string[]
 */
function examActiveSubjectsForClass($db, $studentClass)
{
    ensureExamActivationSchema($db);
    $classKey = strtoupper(trim((string) $studentClass));
    if ($classKey === '') {
        return [];
    }

    $rows = $db->query(
        'SELECT subject
         FROM exam_active_subjects
         WHERE student_class = :student_class
         ORDER BY subject ASC',
        ['student_class' => $classKey]
    )->fetchAll();

    $subjects = [];
    foreach ($rows as $row) {
        $subjectKey = strtolower(trim((string) ($row['subject'] ?? '')));
        if ($subjectKey !== '') {
            $subjects[$subjectKey] = $subjectKey;
        }
    }

    return array_values($subjects);
}

/**
 * Return active exam subject for a class (empty when not set)
 *
 * @param mixed $db
 * @param string|null $studentClass
 * @return string
 */
function examActiveSubjectForClass($db, $studentClass)
{
    $subjects = examActiveSubjectsForClass($db, $studentClass);
    return (string) ($subjects[0] ?? '');
}

/**
 * Set or clear active exam subject for a class
 *
 * @param mixed $db
 * @param string|null $studentClass
 * @param string|null $subject
 * @param int|null $adminUserId
 * @return void
 */
function examSetActiveSubject($db, $studentClass, $subject, $adminUserId = null)
{
    ensureExamActivationSchema($db);
    $classKey = strtoupper(trim((string) $studentClass));
    $subjectKey = strtolower(trim((string) $subject));
    if ($classKey === '') {
        return;
    }

    if ($subjectKey === '') {
        $db->query('DELETE FROM exam_active_subjects WHERE student_class = :student_class', [
            'student_class' => $classKey
        ]);
        return;
    }

    $db->query(
        'INSERT INTO exam_active_subjects (student_class, subject, updated_by_admin_id)
         VALUES (:student_class, :subject, :updated_by_admin_id)
         ON DUPLICATE KEY UPDATE updated_by_admin_id = VALUES(updated_by_admin_id), updated_at = NOW()',
        [
            'student_class' => $classKey,
            'subject' => $subjectKey,
            'updated_by_admin_id' => $adminUserId !== null && (int) $adminUserId > 0 ? (int) $adminUserId : null
        ]
    );
}

/**
 * Toggle a subject on or off for a class
 *
 * @param mixed $db
 * @param string|null $studentClass
 * @param string|null $subject
 * @param int|null $adminUserId
 * @return bool True when the subject is active after the toggle
 */
function examToggleActiveSubject($db, $studentClass, $subject, $adminUserId = null)
{
    ensureExamActivationSchema($db);
    $classKey = strtoupper(trim((string) $studentClass));
    $subjectKey = strtolower(trim((string) $subject));
    if ($classKey === '' || $subjectKey === '') {
        return false;
    }

    $existing = $db->query(
        'SELECT subject
         FROM exam_active_subjects
         WHERE student_class = :student_class
           AND subject = :subject
         LIMIT 1',
        [
            'student_class' => $classKey,
            'subject' => $subjectKey
        ]
    )->fetch();

    if ($existing) {
        $db->query(
            'DELETE FROM exam_active_subjects
             WHERE student_class = :student_class
               AND subject = :subject',
            [
                'student_class' => $classKey,
                'subject' => $subjectKey
            ]
        );
        return false;
    }

    examSetActiveSubject($db, $classKey, $subjectKey, $adminUserId);
    return true;
}

function studentLogExamSessionEvent($db, array $payload)
{
    ensureExamSessionEventsSchema($db);

    $studentName = trim((string) ($payload['student_name'] ?? ''));
    if ($studentName === '') {
        return;
    }

    $ipAddress = null;
    foreach ([(string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''), (string) ($_SERVER['REMOTE_ADDR'] ?? '')] as $candidate) {
        $parts = array_filter(array_map('trim', explode(',', $candidate)));
        if (!empty($parts)) {
            $ipAddress = (string) $parts[0];
            break;
        }
    }

    $db->query(
        'INSERT INTO exam_session_events (session_id, student_name, student_class, assessment_id, subject, task_type, event_key, summary, current_index, payload_json, ip_address, user_agent, created_at)
         VALUES (:session_id, :student_name, :student_class, :assessment_id, :subject, :task_type, :event_key, :summary, :current_index, :payload_json, :ip_address, :user_agent, NOW())',
        [
            'session_id' => (int) ($payload['session_id'] ?? 0),
            'student_name' => $studentName,
            'student_class' => strtoupper((string) ($payload['student_class'] ?? 'SS3')),
            'assessment_id' => (int) ($payload['assessment_id'] ?? 0),
            'subject' => strtolower((string) ($payload['subject'] ?? 'unknown')),
            'task_type' => normalizeAssessmentTask((string) ($payload['task_type'] ?? 'exam')),
            'event_key' => trim((string) ($payload['event_key'] ?? 'activity')),
            'summary' => trim((string) ($payload['summary'] ?? 'Exam activity recorded')),
            'current_index' => max(0, (int) ($payload['current_index'] ?? 0)),
            'payload_json' => empty($payload['payload']) ? null : json_encode($payload['payload'], JSON_UNESCAPED_SLASHES),
            'ip_address' => $ipAddress,
            'user_agent' => substr(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255)
        ]
    );
}

function studentFetchExamSessionEvents($db, $limit = 300)
{
    ensureExamSessionEventsSchema($db);
    $rowLimit = max(1, min(1000, (int) $limit));

    return $db->query(
        "SELECT id, session_id, student_name, student_class, assessment_id, subject, task_type, event_key, summary, current_index, payload_json, ip_address, user_agent, created_at
         FROM exam_session_events
         ORDER BY id DESC
         LIMIT {$rowLimit}"
    )->fetchAll();
}

function adminExamAnalyticsSummary($db)
{
    ensureExamAttemptsSchema($db);
    ensureExamQuestionAnalyticsSchema($db);
    ensureExamSessionEventsSchema($db);

    $overview = $db->query(
        'SELECT COUNT(*) AS total_attempts,
                AVG((score / NULLIF(total_questions, 0)) * 100) AS avg_percent,
                SUM(CASE WHEN timed_out = 1 THEN 1 ELSE 0 END) AS timed_out_total,
                SUM(CASE WHEN reviewed_before_submit = 1 THEN 1 ELSE 0 END) AS reviewed_total
         FROM exam_attempts'
    )->fetch() ?: [];

    $hardQuestions = $db->query(
        'SELECT subject, student_class, question_number, question_text,
                COUNT(*) AS attempts,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS correct_total,
                AVG(time_spent_seconds) AS avg_time_spent
         FROM exam_question_analytics
         GROUP BY assessment_id, subject, student_class, question_hash, question_number, question_text
         ORDER BY (SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) ASC, COUNT(*) DESC
         LIMIT 8'
    )->fetchAll();

    $subjectPerformance = $db->query(
        'SELECT subject, student_class,
                COUNT(*) AS attempts,
                AVG((score / NULLIF(total_questions, 0)) * 100) AS avg_percent,
                AVG(time_spent_seconds) AS avg_time_spent
         FROM exam_attempts
         GROUP BY subject, student_class
         ORDER BY subject ASC, student_class ASC'
    )->fetchAll();

    $eventCounts = $db->query(
        'SELECT event_key, COUNT(*) AS total
         FROM exam_session_events
         GROUP BY event_key
         ORDER BY total DESC, event_key ASC'
    )->fetchAll();

    return [
        'overview' => [
            'total_attempts' => (int) ($overview['total_attempts'] ?? 0),
            'avg_percent' => round((float) ($overview['avg_percent'] ?? 0), 1),
            'timed_out_total' => (int) ($overview['timed_out_total'] ?? 0),
            'reviewed_total' => (int) ($overview['reviewed_total'] ?? 0)
        ],
        'hard_questions' => $hardQuestions,
        'subject_performance' => $subjectPerformance,
        'event_counts' => $eventCounts
    ];
}

/**
 * Return allowed class labels for a subject category
 *
 * @param string|null $category
 * @return string[]
 */
function classOptionsForSubjectCategory($category = 'both')
{
    $value = strtolower(trim((string) $category));

    if ($value === 'junior') {
        return ['JSS1', 'JSS2', 'JSS3'];
    }

    if ($value === 'senior') {
        return ['SS1', 'SS2', 'SS3'];
    }

    return ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];
}

/**
 * Build answer-map metadata for exam navigation panel
 *
 * @param array $questions
 * @param array $answers
 * @param array $flags
 * @return array<int,array<string,mixed>>
 */
function buildAnsweredMap($questions, $answers = [], $flags = [])
{
    $map = [];

    foreach (($questions ?? []) as $index => $questionRow) {
        $map[] = [
            'index' => (int) $index,
            'number' => $index + 1,
            'answered' => trim((string) ($answers[$index] ?? '')) !== '',
            'flagged' => !empty($flags[$index])
        ];
    }

    return $map;
}

function studentGetGlobalTimerPause($db)
{
    try {
        $db->query(
            "CREATE TABLE IF NOT EXISTS exam_global_controls (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                control_key VARCHAR(40) NOT NULL,
                control_value VARCHAR(255) NOT NULL,
                created_by_admin_id INT(11) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                UNIQUE KEY uniq_control_key (control_key)
            )"
        );

        $row = $db->query(
            "SELECT control_value FROM exam_global_controls WHERE control_key = 'global_timer_pause' LIMIT 1"
        )->fetch();

        return $row ? (string) ($row['control_value'] ?? '0') : '0';
    } catch (Exception $e) {
        return '0';
    }
}

function isAjaxRequest()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }

    $accept = isset($_SERVER['HTTP_ACCEPT']) ? strtolower((string) $_SERVER['HTTP_ACCEPT']) : '';
    if (strpos($accept, 'application/json') !== false) {
        return true;
    }

    $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower((string) $_SERVER['CONTENT_TYPE']) : '';
    if (strpos($contentType, 'application/json') !== false) {
        return true;
    }

    return false;
}
