<?php

require basePath('Framework/Database.php');

$student = Session::get('student') ?? [];
$studentName = trim((string) ($student['name'] ?? ''));
$studentClass = strtoupper(trim((string) ($student['class'] ?? 'SS3')));
$filter = strtolower(trim((string) ($_GET['filter'] ?? 'all')));

if (!in_array($filter, ['all', 'failed', 'incomplete'], true)) {
    $filter = 'all';
}

$subjectsConfig = require basePath('config/config-db2.php');
$subjectsDb = new Database($subjectsConfig);
$subjectsDb->query(
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

$rows = $subjectsDb->query(
    'SELECT id, subject, task_type, score, total_questions, time_spent_seconds, timed_out, completed_at
     FROM exam_attempts
     WHERE student_name = :student_name AND student_class = :student_class
     ORDER BY completed_at DESC, id DESC',
    [
        'student_name' => $studentName,
        'student_class' => $studentClass
    ]
)->fetchAll();

$currentQuiz = Session::get('quiz') ?? [];
$currentSubjects = Session::get('subjects') ?? [];
$canUseSessionCorrection = !empty($currentQuiz['questions'])
    && ((bool) ($currentQuiz['attempt_logged'] ?? false) || (int) ($currentQuiz['current_index'] ?? 0) >= (int) ($currentQuiz['total'] ?? 0));
$sessionSubject = strtolower(trim((string) ($currentSubjects['subject'] ?? '')));
$sessionTask = normalizeAssessmentTask((string) ($currentSubjects['task'] ?? 'exam'));

$correctionRows = [];
foreach ($rows as $row) {
    $score = (int) ($row['score'] ?? 0);
    $total = max(1, (int) ($row['total_questions'] ?? 1));
    $timedOut = (int) ($row['timed_out'] ?? 0) === 1;
    $failed = $score < (int) ceil($total * 0.5);
    $status = $timedOut ? 'incomplete' : ($failed ? 'failed' : 'passed');

    if ($filter === 'failed' && $status !== 'failed') {
        continue;
    }
    if ($filter === 'incomplete' && $status !== 'incomplete') {
        continue;
    }
    if ($filter === 'all' && $status === 'passed') {
        continue;
    }

    $subjectKey = strtolower(trim((string) ($row['subject'] ?? '')));
    $taskKey = normalizeAssessmentTask((string) ($row['task_type'] ?? 'exam'));
    $canView = $canUseSessionCorrection && $sessionSubject === $subjectKey && $sessionTask === $taskKey;

    $correctionRows[] = [
        'id' => (int) ($row['id'] ?? 0),
        'subject' => $subjectKey,
        'task' => $taskKey,
        'score' => $score,
        'total' => $total,
        'time_spent_seconds' => (int) ($row['time_spent_seconds'] ?? 0),
        'timed_out' => $timedOut,
        'status' => $status,
        'completed_at' => (string) ($row['completed_at'] ?? ''),
        'can_view' => $canView
    ];
}

loadView('student-corrections', [
    'student' => [
        'name' => $studentName,
        'class' => $studentClass
    ],
    'filter' => $filter,
    'rows' => $correctionRows
]);
