<?php

require basePath('Framework/Database.php');
$config = require basePath('config/config-db2.php');

$db = new Database($config);
ensureAssessmentConfigsSchema($db);
ensureStudentExamSessionsSchema($db);

$subject = strtolower($_POST['subject'] ?? '');
$task = normalizeAssessmentTask($_POST['task'] ?? 'exam');
$assessmentId = (int) ($_POST['assessment_id'] ?? 0);
$studentName = (string) (Session::get('student')['name'] ?? '');
$studentClass = strtoupper((string) (Session::get('student')['class'] ?? 'SS3'));

if ($task === 'exam') {
    $activeExamSubjects = examActiveSubjectsForClass($db, $studentClass);
    if (empty($activeExamSubjects)) {
        redirect('/student/question-set');
    }
    if (!in_array($subject, $activeExamSubjects, true)) {
        redirect('/student/question-set');
    }
}

$configRow = $db->query(
    'SELECT id, subject, student_class, task_type, header_text, term_key, duration_seconds, question_limit, table_name
     FROM assessment_configs
     WHERE id = :id AND subject = :subject AND student_class = :student_class AND task_type = :task_type
     LIMIT 1',
    [
        'id' => $assessmentId,
        'subject' => $subject,
        'student_class' => $studentClass,
        'task_type' => $task
    ]
)->fetch();

if (!$configRow) {
    // Student must pick a valid test setup first.
    redirect('/student/question-set');
}

$tableName = safeTableName((string) ($configRow['table_name'] ?? ''));
$tableExists = $db->query('SHOW TABLES LIKE :table_name', ['table_name' => $tableName])->fetch();
if (!$tableExists) {
    // Setup exists, but its questions table is missing.
    redirect('/student/question-set');
}

$limit = max(0, (int) ($configRow['question_limit'] ?? 0));

if ($limit > 0) {
    // Use only the number of questions the teacher set.
    $limitInt = (int) $limit;
    $info = $db->query("SELECT * FROM {$tableName} LIMIT {$limitInt}")->fetchAll();
} else {
    $info = $db->query("SELECT * FROM {$tableName}")->fetchAll();
}

// inspectAndDie($info);

$questions = randomizeQuestionSet($info);
$durationSeconds = max(0, (int) ($configRow['duration_seconds'] ?? 0));
if ($task === 'exam' && $durationSeconds < 60) {
    $durationSeconds = 60;
}

if (empty($questions)) {
    // If no questions were found, go back to selection page.
    redirect('/student/question-set');
}

$startedAt = time();
$endsAt = $durationSeconds > 0 ? ($startedAt + $durationSeconds) : 0;
$term = $task === 'exam'
    ? normalizeExamTerm($configRow['term_key'] ?? 'first_term')
    : '';
$headerText = trim((string) ($configRow['header_text'] ?? ''));

// Store questions JSON for offline-first rendering
$questionsJson = json_encode($questions, JSON_UNESCAPED_SLASHES);
Session::set('quiz_questions_json', $questionsJson);
Session::set('quiz_questions_count', count($questions));

if ($task === 'exam') {
    $activeExamSession = studentFindActiveExamSession($db, $studentName, $studentClass);
    $sameAssessmentSession = $activeExamSession
        && (int) ($activeExamSession['assessment_id'] ?? 0) === $assessmentId
        && strtolower(trim((string) ($activeExamSession['subject'] ?? ''))) === $subject
        && studentExamSessionIsResumable($activeExamSession);

    if ($sameAssessmentSession && studentRestoreExamSessionToPhpSession($activeExamSession)) {
        $quiz = Session::get('quiz') ?? [];
        $resumeQuestions = is_array($quiz['questions'] ?? null) ? $quiz['questions'] : [];
        $resumeAnswers = is_array($quiz['answers'] ?? null) ? $quiz['answers'] : [];
        $resumeFlags = is_array($quiz['flags'] ?? null) ? $quiz['flags'] : [];
        $resumeTotal = (int) ($quiz['total'] ?? count($resumeQuestions));
        $resumeIndex = max(0, (int) ($quiz['current_index'] ?? 0));
        $resumeQuestion = $resumeQuestions[$resumeIndex] ?? null;
        $resumeSubjects = Session::get('subjects') ?? [];

if ($resumeQuestion) {
            $resumeQuestionsJson = json_encode($resumeQuestions, JSON_UNESCAPED_SLASHES);
            studentLogExamSessionEvent($db, [
                'session_id' => (int) ($activeExamSession['id'] ?? 0),
                'student_name' => $studentName,
                'student_class' => $studentClass,
                'assessment_id' => $assessmentId,
                'subject' => $subject,
                'task_type' => $task,
                'event_key' => 'resume_loaded',
                'summary' => 'Student reopened an in-progress exam from the start flow.',
                'current_index' => $resumeIndex
            ]);
            loadView('/questions', [
                'subject' => $resumeSubjects['subject'] ?? $subject,
                'term' => $resumeSubjects['term'] ?? $term,
                'assessment_task' => $resumeSubjects['task'] ?? $task,
                'assessment_header' => $resumeSubjects['header'] ?? $headerText,
                'number' => $resumeIndex + 1,
                'question' => $resumeQuestion['question'] ?? '',
                'image_path' => $resumeQuestion['image_path'] ?? null,
                'choice1' => $resumeQuestion['choice1'] ?? '',
                'choice2' => $resumeQuestion['choice2'] ?? '',
                'choice3' => $resumeQuestion['choice3'] ?? '',
                'choice4' => $resumeQuestion['choice4'] ?? '',
                'selected_choice' => $resumeAnswers[$resumeIndex] ?? '',
                'answered_map' => buildAnsweredMap($resumeQuestions, $resumeAnswers, $resumeFlags),
                'current_index_zero' => $resumeIndex,
                'current' => $resumeIndex + 1,
                'total' => $resumeTotal,
                'is_last' => ($resumeIndex + 1) >= $resumeTotal,
                'exam_ends_at' => (int) ($quiz['ends_at'] ?? 0),
                'exam_duration_seconds' => (int) ($quiz['duration_seconds'] ?? 0),
                'flagged_questions' => $resumeFlags,
                'last_autosaved_at' => (string) ($quiz['last_autosaved_at'] ?? ''),
                'resume_count' => (int) ($quiz['resume_count'] ?? 0),
                'questions_json' => $resumeQuestionsJson
            ]);
            return;
        }
    }
}

Session::set('subjects', [
    'subject' => $subject,
    'class' => strtoupper($studentClass),
    'task' => $task,
    'header' => $headerText,
    'term' => $term,
    'assessment_id' => $assessmentId
]);

// Save all quiz progress in session while student moves between questions.
Session::set('quiz', [
    'questions' => $questions,
    'current_index' => 0,
    'answers' => [],
    'flags' => [],
    'question_times' => [],
    'score' => 0,
    'total' => count($questions),
    'started_at' => $startedAt,
    'ends_at' => $endsAt,
    'duration_seconds' => $durationSeconds,
    'attempt_logged' => false,
    'reviewed_before_submit' => false,
    'last_autosaved_at' => '',
    'resume_count' => 0
]);

if ($task === 'exam') {
    $sessionId = studentSaveExamSession($db, [
        'student_name' => $studentName,
        'student_class' => (string) (Session::get('student')['class'] ?? $studentClass),
        'assessment_id' => $assessmentId,
        'subject' => $subject,
        'task_type' => $task,
        'term_key' => $term,
        'header_text' => $headerText,
        'questions' => $questions,
        'answers' => [],
        'flags' => [],
        'question_times' => [],
        'current_index' => 0,
        'score' => 0,
        'total_questions' => count($questions),
        'started_at' => $startedAt,
        'ends_at' => $endsAt,
        'duration_seconds' => $durationSeconds,
        'last_activity_at' => date('Y-m-d H:i:s'),
        'attempt_logged' => false,
        'status' => 'in_progress'
    ]);
    studentLogExamSessionEvent($db, [
        'session_id' => $sessionId,
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'assessment_id' => $assessmentId,
        'subject' => $subject,
        'task_type' => $task,
        'event_key' => 'exam_started',
        'summary' => 'Student started an exam.',
        'current_index' => 0
    ]);
}

loadView('/questions', [
    'subject' => $subject,
    'term' => $term,
    'assessment_task' => $task,
    'assessment_header' => $headerText,
    'number' => 1,
    'question' => $questions[0]['question'],
    'image_path' => $questions[0]['image_path'] ?? null,
    'choice1' => $questions[0]['choice1'],
    'choice2' => $questions[0]['choice2'],
    'choice3' => $questions[0]['choice3'],
    'choice4' => $questions[0]['choice4'],
    'selected_choice' => '',
    'answered_map' => buildAnsweredMap($questions, [], []),
    'current_index_zero' => 0,
    'current' => 1,
    'total' => count($questions),
    'is_last' => count($questions) === 1,
    'exam_ends_at' => $endsAt,
    'exam_duration_seconds' => $durationSeconds,
    'flagged_questions' => [],
    'last_autosaved_at' => '',
    'resume_count' => 0,
    'questions_json' => $questionsJson
]);
