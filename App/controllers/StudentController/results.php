<?php

require basePath('Framework/Database.php');

$student = Session::get('student') ?? [];
$studentName = trim((string) ($student['name'] ?? ''));
$studentClass = strtoupper(trim((string) ($student['class'] ?? 'SS3')));

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
     ORDER BY completed_at DESC, id DESC
     LIMIT 60',
    [
        'student_name' => $studentName,
        'student_class' => $studentClass
    ]
)->fetchAll();

$groupedResults = [];
foreach ($rows as $attemptRow) {
    $completedAt = (string) ($attemptRow['completed_at'] ?? '');
    $bucket = 'Unknown Date';
    if ($completedAt !== '') {
        $bucket = date('l, F j, Y', strtotime($completedAt));
    }
    if (!isset($groupedResults[$bucket])) {
        $groupedResults[$bucket] = [];
    }
    $groupedResults[$bucket][] = $attemptRow;
}

loadView('student-results', [
    'student' => [
        'name' => $studentName,
        'class' => $studentClass
    ],
    'groupedResults' => $groupedResults
]);
