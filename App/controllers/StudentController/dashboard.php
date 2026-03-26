<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$student = Session::get('student') ?? [];
$studentName = trim((string) ($student['name'] ?? ''));
$studentClass = strtoupper(trim((string) ($student['class'] ?? 'SS3')));

$quiz = Session::get('quiz');
$hasQuiz = is_array($quiz) && !empty($quiz['questions']);
$quizCurrentIndex = (int) ($quiz['current_index'] ?? 0);
$quizTotal = (int) ($quiz['total'] ?? 0);
$quizAttemptLogged = (bool) ($quiz['attempt_logged'] ?? false);
$resumeAvailable = $hasQuiz && !$quizAttemptLogged && $quizCurrentIndex >= 0 && $quizCurrentIndex < $quizTotal;
$completedQuizAvailable = $hasQuiz && ($quizAttemptLogged || $quizCurrentIndex >= $quizTotal) && $quizTotal > 0;

$subjectsConfig = require basePath('config/config-db2.php');
$subjectsDb = new Database($subjectsConfig);
ensureExamAttemptsSchema($subjectsDb);
ensureStudentExamSessionsSchema($subjectsDb);

$persistedExamSession = studentFindActiveExamSession($subjectsDb, $studentName, $studentClass);
if ($persistedExamSession && !studentExamSessionIsResumable($persistedExamSession)) {
    studentFinalizeExamSession(
        $subjectsDb,
        $persistedExamSession,
        ((int) ($persistedExamSession['ends_at'] ?? 0) > 0 && time() >= (int) ($persistedExamSession['ends_at'] ?? 0))
    );
    $persistedExamSession = null;
}

$resumeCurrentValue = $quizCurrentIndex;
$resumeTotalValue = $quizTotal;
if (!$resumeAvailable && $persistedExamSession) {
    $persistedQuestions = studentExamSessionDecodeJson($persistedExamSession['questions_json'] ?? '[]', []);
    $persistedTotal = max(0, (int) ($persistedExamSession['total_questions'] ?? count($persistedQuestions)));
    $persistedIndex = (int) ($persistedExamSession['current_index'] ?? 0);
    $resumeAvailable = studentExamSessionIsResumable($persistedExamSession);

    if ($resumeAvailable) {
        $resumeCurrentValue = $persistedIndex;
        $resumeTotalValue = $persistedTotal;
    }
}

$attemptRows = $subjectsDb->query(
    'SELECT id, subject, task_type, score, total_questions, time_spent_seconds, timed_out, completed_at
     FROM exam_attempts
     WHERE student_name = :student_name AND student_class = :student_class
     ORDER BY id DESC
     LIMIT 30',
    [
        'student_name' => $studentName,
        'student_class' => $studentClass
    ]
)->fetchAll();

$recentAttempts = array_slice($attemptRows, 0, 10);

ensureAssessmentConfigsSchema($subjectsDb);
$availableAssessmentRows = $subjectsDb->query(
    'SELECT id, subject, task_type, student_class, table_name
     FROM assessment_configs
     WHERE student_class = :student_class
     ORDER BY id DESC',
    ['student_class' => $studentClass]
)->fetchAll();
$tableRows = $subjectsDb->query('SHOW TABLES')->fetchAll();
$existingTableMap = [];
foreach ($tableRows as $tableRow) {
    $existingTableMap[(string) array_values($tableRow)[0]] = true;
}

$availableTaskRows = [];
foreach ($availableAssessmentRows as $cfg) {
    $tableName = safeTableName((string) ($cfg['table_name'] ?? ''));

    if (!isset($existingTableMap[$tableName])) {
        continue;
    }

    $countRow = $subjectsDb->query("SELECT COUNT(*) AS total FROM {$tableName}")->fetch();
    if ((int) ($countRow['total'] ?? 0) < 1) {
        continue;
    }

    $availableTaskRows[] = [
        'id' => (int) ($cfg['id'] ?? 0),
        'subject' => strtolower(trim((string) ($cfg['subject'] ?? ''))),
        'task' => normalizeAssessmentTask((string) ($cfg['task_type'] ?? 'exam'))
    ];
}

$attemptedKeys = [];
foreach ($attemptRows as $attemptRow) {
    $attemptedKeys[] = strtolower(trim((string) ($attemptRow['subject'] ?? ''))) . '|' . normalizeAssessmentTask((string) ($attemptRow['task_type'] ?? 'exam'));
}
$attemptedKeys = array_values(array_unique($attemptedKeys));

$newTask = null;
foreach ($availableTaskRows as $taskRow) {
    $key = (string) ($taskRow['subject'] ?? '') . '|' . (string) ($taskRow['task'] ?? 'exam');
    if (!in_array($key, $attemptedKeys, true)) {
        $newTask = $taskRow;
        break;
    }
}

$newTaskAvailable = $newTask !== null;
$newTaskLink = '/student/question-set';
$newTaskLabel = '';
if ($newTaskAvailable) {
    $newTaskLink = '/student/question-set?subject=' . urlencode((string) ($newTask['subject'] ?? ''))
        . '&task=' . urlencode((string) ($newTask['task'] ?? 'exam'))
        . '&assessment_id=' . urlencode((string) ((int) ($newTask['id'] ?? 0)));
    $newTaskLabel = ucwords(str_replace('_', ' ', (string) ($newTask['subject'] ?? '')))
        . ' - '
        . (assessmentTaskOptions()[(string) ($newTask['task'] ?? 'exam')] ?? ucfirst((string) ($newTask['task'] ?? 'exam')));
}

$notifications = [];
$mainConfig = require basePath('config/config-db.php');
$mainDb = new Database($mainConfig);
adminEnsureNotificationsSchema($mainDb);
$notifications = adminFetchLatestNotifications($mainDb, 6, false);

loadView('student-dashboard', [
    'student' => [
        'name' => $studentName,
        'class' => $studentClass
    ],
    'resumeAvailable' => $resumeAvailable,
    'resumeProgress' => [
        'current' => $resumeAvailable ? ($resumeCurrentValue + 1) : 1,
        'total' => $resumeTotalValue
    ],
    'completedQuizAvailable' => $completedQuizAvailable,
    'recentAttempts' => $recentAttempts,
    'newTaskAvailable' => $newTaskAvailable,
    'newTaskLink' => $newTaskLink,
    'newTaskLabel' => $newTaskLabel,
    'notifications' => $notifications
]);
