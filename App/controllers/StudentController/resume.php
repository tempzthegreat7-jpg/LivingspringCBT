<?php

require basePath('Framework/Database.php');

$quiz = Session::get('quiz');

$hasSessionQuiz = $quiz && !empty($quiz['questions']);
if (!$hasSessionQuiz) {
    $student = Session::get('student') ?? [];
    $studentName = trim((string) ($student['name'] ?? ''));
    $studentClass = strtoupper(trim((string) ($student['class'] ?? 'SS3')));

    if ($studentName === '') {
        redirect('/student/dashboard');
    }

    $config = require basePath('config/config-db2.php');
    $db = new Database($config);
    ensureStudentExamSessionsSchema($db);
    ensureExamAttemptsSchema($db);

    $persistedExamSession = studentFindActiveExamSession($db, $studentName, $studentClass);
    if (!$persistedExamSession) {
        redirect('/student/dashboard');
    }

    if (!studentExamSessionIsResumable($persistedExamSession)) {
        studentFinalizeExamSession(
            $db,
            $persistedExamSession,
            ((int) ($persistedExamSession['ends_at'] ?? 0) > 0 && time() >= (int) ($persistedExamSession['ends_at'] ?? 0))
        );
        redirect('/student/dashboard');
    }

    if (!studentRestoreExamSessionToPhpSession($persistedExamSession)) {
        redirect('/student/dashboard');
    }

    $restoredQuiz = Session::get('quiz') ?? [];
    $restoredQuiz['resume_count'] = (int) ($restoredQuiz['resume_count'] ?? 0) + 1;
    Session::set('quiz', $restoredQuiz);

    studentSaveExamSession($db, [
        'id' => (int) ($persistedExamSession['id'] ?? 0),
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'assessment_id' => (int) ($persistedExamSession['assessment_id'] ?? 0),
        'subject' => (string) ($persistedExamSession['subject'] ?? ''),
        'task_type' => (string) ($persistedExamSession['task_type'] ?? 'exam'),
        'term_key' => (string) ($persistedExamSession['term_key'] ?? 'first_term'),
        'header_text' => (string) ($persistedExamSession['header_text'] ?? ''),
        'questions' => is_array($restoredQuiz['questions'] ?? null) ? $restoredQuiz['questions'] : [],
        'answers' => is_array($restoredQuiz['answers'] ?? null) ? $restoredQuiz['answers'] : [],
        'flags' => is_array($restoredQuiz['flags'] ?? null) ? $restoredQuiz['flags'] : [],
        'question_times' => is_array($restoredQuiz['question_times'] ?? null) ? $restoredQuiz['question_times'] : [],
        'current_index' => (int) ($restoredQuiz['current_index'] ?? 0),
        'score' => (int) ($restoredQuiz['score'] ?? 0),
        'total_questions' => (int) ($restoredQuiz['total'] ?? 0),
        'started_at' => (int) ($restoredQuiz['started_at'] ?? 0),
        'ends_at' => (int) ($restoredQuiz['ends_at'] ?? 0),
        'duration_seconds' => (int) ($restoredQuiz['duration_seconds'] ?? 0),
        'last_autosaved_at' => (string) ($restoredQuiz['last_autosaved_at'] ?? ''),
        'last_activity_at' => date('Y-m-d H:i:s'),
        'attempt_logged' => !empty($restoredQuiz['attempt_logged']),
        'reviewed_before_submit' => !empty($restoredQuiz['reviewed_before_submit']),
        'resume_count' => (int) ($restoredQuiz['resume_count'] ?? 0),
        'status' => 'in_progress'
    ]);
    studentLogExamSessionEvent($db, [
        'session_id' => (int) ($persistedExamSession['id'] ?? 0),
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'assessment_id' => (int) ($persistedExamSession['assessment_id'] ?? 0),
        'subject' => (string) ($persistedExamSession['subject'] ?? ''),
        'task_type' => (string) ($persistedExamSession['task_type'] ?? 'exam'),
        'event_key' => 'resume_opened',
        'summary' => 'Student resumed an in-progress exam.',
        'current_index' => (int) ($restoredQuiz['current_index'] ?? 0)
    ]);

    $quiz = Session::get('quiz');
}

$questions = $quiz['questions'];
$answers = $quiz['answers'] ?? [];
$flags = $quiz['flags'] ?? [];
$total = (int) ($quiz['total'] ?? count($questions));
$currentIndex = (int) ($quiz['current_index'] ?? 0);
$attemptLogged = (bool) ($quiz['attempt_logged'] ?? false);

if ($attemptLogged || $currentIndex >= $total || $currentIndex < 0) {
    redirect('/student/dashboard');
}

$row = $questions[$currentIndex] ?? null;
if (!$row) {
    redirect('/student/dashboard');
}

$subjects = Session::get('subjects') ?? [];

loadView('/questions', [
    'subject' => $subjects['subject'] ?? '',
    'term' => $subjects['term'] ?? 'first_term',
    'assessment_task' => $subjects['task'] ?? 'exam',
    'assessment_header' => $subjects['header'] ?? '',
    'number' => $currentIndex + 1,
    'question' => $row['question'] ?? '',
    'image_path' => $row['image_path'] ?? null,
    'choice1' => $row['choice1'] ?? '',
    'choice2' => $row['choice2'] ?? '',
    'choice3' => $row['choice3'] ?? '',
    'choice4' => $row['choice4'] ?? '',
    'selected_choice' => $answers[$currentIndex] ?? '',
    'answered_map' => buildAnsweredMap($questions, $answers, $flags),
    'current_index_zero' => $currentIndex,
    'current' => $currentIndex + 1,
    'total' => $total,
    'is_last' => ($currentIndex + 1) >= $total,
    'exam_ends_at' => (int) ($quiz['ends_at'] ?? 0),
    'exam_duration_seconds' => (int) ($quiz['duration_seconds'] ?? 0),
    'flagged_questions' => $flags,
    'last_autosaved_at' => (string) ($quiz['last_autosaved_at'] ?? ''),
    'resume_count' => (int) ($quiz['resume_count'] ?? 0)
]);
