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
        extract($data);
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
        extract($data);
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
    die(var_dump($value));
    echo '</pre>';
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
 * Redirects to pages
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
            security_json LONGTEXT NULL,
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
    $hasSecurityJson = false;

    foreach ($columns as $column) {
        if (strtolower((string) ($column['Field'] ?? '')) === 'security_json') {
            $hasSecurityJson = true;
            break;
        }
    }

    if (!$hasSecurityJson) {
        $db->query('ALTER TABLE student_exam_sessions ADD COLUMN security_json LONGTEXT NULL AFTER answers_json');
    }
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
 * Normalize quiz security state payload
 *
 * @param mixed $state
 * @return array
 */
function normalizeQuizSecurityState($state = [])
{
    $source = is_array($state) ? $state : [];

    return [
        'violation_count' => max(0, (int) ($source['violation_count'] ?? 0)),
        'requires_admin_unlock' => !empty($source['requires_admin_unlock']),
        'locked_at' => max(0, (int) ($source['locked_at'] ?? 0)),
        'last_event' => trim((string) ($source['last_event'] ?? '')),
        'last_event_label' => trim((string) ($source['last_event_label'] ?? ''))
    ];
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
    $security = normalizeQuizSecurityState(studentExamSessionDecodeJson($sessionRow['security_json'] ?? '[]', []));
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
        'security' => $security,
        'score' => (int) ($sessionRow['score'] ?? studentExamSessionCalculateScore($questions, $answers)),
        'total' => $total,
        'started_at' => (int) ($sessionRow['started_at'] ?? 0),
        'ends_at' => (int) ($sessionRow['ends_at'] ?? 0),
        'duration_seconds' => (int) ($sessionRow['duration_seconds'] ?? 0),
        'attempt_logged' => (int) ($sessionRow['attempt_logged'] ?? 0) === 1
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
    $security = normalizeQuizSecurityState($payload['security'] ?? []);

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
        'security_json' => json_encode($security, JSON_UNESCAPED_SLASHES),
        'current_index' => (int) ($payload['current_index'] ?? 0),
        'score' => (int) ($payload['score'] ?? studentExamSessionCalculateScore($questions, $answers)),
        'total_questions' => max(0, (int) ($payload['total_questions'] ?? count($questions))),
        'started_at' => (int) ($payload['started_at'] ?? time()),
        'ends_at' => (int) ($payload['ends_at'] ?? 0),
        'duration_seconds' => (int) ($payload['duration_seconds'] ?? 0),
        'attempt_logged' => (int) (!empty($payload['attempt_logged'])),
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
            'security_json' => $params['security_json'],
            'current_index' => $params['current_index'],
            'score' => $params['score'],
            'total_questions' => $params['total_questions'],
            'started_at' => $params['started_at'],
            'ends_at' => $params['ends_at'],
            'duration_seconds' => $params['duration_seconds'],
            'attempt_logged' => $params['attempt_logged'],
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
                 security_json = :security_json,
                 current_index = :current_index,
                 score = :score,
                 total_questions = :total_questions,
                 started_at = :started_at,
                 ends_at = :ends_at,
                 duration_seconds = :duration_seconds,
                 attempt_logged = :attempt_logged,
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
            security_json,
            current_index,
            score,
            total_questions,
            started_at,
            ends_at,
            duration_seconds,
            attempt_logged,
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
            :security_json,
            :current_index,
            :score,
            :total_questions,
            :started_at,
            :ends_at,
            :duration_seconds,
            :attempt_logged,
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

    $questions = studentExamSessionDecodeJson($sessionRow['questions_json'] ?? '[]', []);
    $answers = studentExamSessionDecodeJson($sessionRow['answers_json'] ?? '[]', []);
    $security = normalizeQuizSecurityState(studentExamSessionDecodeJson($sessionRow['security_json'] ?? '[]', []));
    $score = studentExamSessionCalculateScore($questions, $answers);
    $total = max(0, (int) ($sessionRow['total_questions'] ?? count($questions)));
    $startedAt = (int) ($sessionRow['started_at'] ?? time());
    $durationSeconds = max(0, (int) ($sessionRow['duration_seconds'] ?? 0));
    $elapsed = max(0, time() - $startedAt);

    if ($durationSeconds > 0) {
        $elapsed = min($elapsed, $durationSeconds);
    }

    if ((int) ($sessionRow['attempt_logged'] ?? 0) !== 1) {
        $db->query(
            'INSERT INTO exam_attempts (student_name, student_class, subject, task_type, score, total_questions, time_spent_seconds, timed_out, completed_at)
             VALUES (:student_name, :student_class, :subject, :task_type, :score, :total_questions, :time_spent_seconds, :timed_out, NOW())',
            [
                'student_name' => (string) ($sessionRow['student_name'] ?? 'Unknown Student'),
                'student_class' => strtoupper((string) ($sessionRow['student_class'] ?? 'SS3')),
                'subject' => strtolower((string) ($sessionRow['subject'] ?? 'unknown')),
                'task_type' => normalizeAssessmentTask((string) ($sessionRow['task_type'] ?? 'exam')),
                'score' => $score,
                'total_questions' => $total,
                'time_spent_seconds' => $elapsed,
                'timed_out' => $timedOut ? 1 : 0
            ]
        );
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
        'security' => $security,
        'current_index' => $total,
        'score' => $score,
        'total_questions' => $total,
        'started_at' => $startedAt,
        'ends_at' => (int) ($sessionRow['ends_at'] ?? 0),
        'duration_seconds' => $durationSeconds,
        'attempt_logged' => true,
        'status' => 'completed'
    ]);

    return $sessionRow;
}

/**
 * Ensure exam security event log exists
 *
 * @param mixed $db
 * @return void
 */
function ensureExamSecurityEventsSchema($db)
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS exam_security_events (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(120) NOT NULL,
            student_class VARCHAR(20) NOT NULL,
            assessment_id INT(11) NOT NULL DEFAULT 0,
            subject VARCHAR(80) NOT NULL,
            task_type VARCHAR(20) NOT NULL DEFAULT "exam",
            event_type VARCHAR(40) NOT NULL,
            details_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_exam_security_student (student_name, student_class, created_at),
            KEY idx_exam_security_assessment (assessment_id, subject, created_at)
        )'
    );
}

/**
 * Log exam security event for review
 *
 * @param mixed $db
 * @param array $payload
 * @return void
 */
function logExamSecurityEvent($db, array $payload)
{
    ensureExamSecurityEventsSchema($db);

    $db->query(
        'INSERT INTO exam_security_events (student_name, student_class, assessment_id, subject, task_type, event_type, details_json, created_at)
         VALUES (:student_name, :student_class, :assessment_id, :subject, :task_type, :event_type, :details_json, NOW())',
        [
            'student_name' => trim((string) ($payload['student_name'] ?? 'Unknown Student')),
            'student_class' => strtoupper(trim((string) ($payload['student_class'] ?? 'SS3'))),
            'assessment_id' => (int) ($payload['assessment_id'] ?? 0),
            'subject' => strtolower(trim((string) ($payload['subject'] ?? ''))),
            'task_type' => normalizeAssessmentTask((string) ($payload['task_type'] ?? 'exam')),
            'event_type' => substr(trim((string) ($payload['event_type'] ?? 'unknown')), 0, 40),
            'details_json' => empty($payload['details']) ? null : json_encode($payload['details'], JSON_UNESCAPED_SLASHES)
        ]
    );
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
 * @return array<int,array{index:int,number:int,answered:bool}>
 */
function buildAnsweredMap($questions, $answers = [])
{
    $map = [];

    foreach (($questions ?? []) as $index => $questionRow) {
        $map[] = [
            'index' => (int) $index,
            'number' => $index + 1,
            'answered' => trim((string) ($answers[$index] ?? '')) !== ''
        ];
    }

    return $map;
}
