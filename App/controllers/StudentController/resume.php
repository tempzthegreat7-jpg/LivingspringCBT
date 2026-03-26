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

    $quiz = Session::get('quiz');
}

$questions = $quiz['questions'];
$answers = $quiz['answers'] ?? [];
$security = normalizeQuizSecurityState($quiz['security'] ?? []);
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
    'security_state' => $security,
    'answered_map' => buildAnsweredMap($questions, $answers),
    'current_index_zero' => $currentIndex,
    'current' => $currentIndex + 1,
    'total' => $total,
    'is_last' => ($currentIndex + 1) >= $total,
    'exam_ends_at' => (int) ($quiz['ends_at'] ?? 0),
    'exam_duration_seconds' => (int) ($quiz['duration_seconds'] ?? 0)
]);
