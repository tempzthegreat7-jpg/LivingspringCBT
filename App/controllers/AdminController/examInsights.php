<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db2.php');
$db = new Database($config);

ensureExamAttemptsSchema($db);

$selectedSubject = strtolower(trim((string) ($_GET['subject'] ?? '')));
$selectedClass = strtoupper(trim((string) ($_GET['student_class'] ?? '')));

$subjectRows = $db->query(
    'SELECT DISTINCT subject
     FROM exam_attempts
     WHERE subject IS NOT NULL AND TRIM(subject) != ""
     ORDER BY subject ASC'
)->fetchAll();

$classRows = $db->query(
    'SELECT DISTINCT student_class
     FROM exam_attempts
     WHERE student_class IS NOT NULL AND TRIM(student_class) != ""
     ORDER BY student_class ASC'
)->fetchAll();

$subjectOptions = [];
foreach ($subjectRows as $row) {
    $subjectKey = strtolower(trim((string) ($row['subject'] ?? '')));
    if ($subjectKey !== '') {
        $subjectOptions[$subjectKey] = ucwords(str_replace('_', ' ', $subjectKey));
    }
}

$classOptions = [];
foreach ($classRows as $row) {
    $classKey = strtoupper(trim((string) ($row['student_class'] ?? '')));
    if ($classKey !== '') {
        $classOptions[$classKey] = $classKey;
    }
}

if ($selectedSubject !== '' && !isset($subjectOptions[$selectedSubject])) {
    $selectedSubject = '';
}

if ($selectedClass !== '' && !isset($classOptions[$selectedClass])) {
    $selectedClass = '';
}

$where = [];
$params = [];
if ($selectedSubject !== '') {
    $where[] = 'subject = :subject';
    $params['subject'] = $selectedSubject;
}
if ($selectedClass !== '') {
    $where[] = 'student_class = :student_class';
    $params['student_class'] = $selectedClass;
}

$whereClause = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

$studentRecords = $db->query(
    "SELECT id, student_name, student_class, subject, score, total_questions, time_spent_seconds, timed_out, completed_at
     FROM exam_attempts
     {$whereClause}
     ORDER BY completed_at DESC, id DESC
     LIMIT 500",
    $params
)->fetchAll();

$performanceRows = $db->query(
    "SELECT subject, student_class,
            COUNT(*) AS attempts,
            AVG(score) AS avg_score_raw,
            AVG((score / NULLIF(total_questions, 0)) * 100) AS avg_percent,
            AVG(time_spent_seconds) AS avg_time_spent
     FROM exam_attempts
     {$whereClause}
     GROUP BY subject, student_class
     ORDER BY subject ASC, student_class ASC",
    $params
)->fetchAll();

loadView('admin/exam-insights', [
    'subjectOptions' => $subjectOptions,
    'classOptions' => $classOptions,
    'selectedSubject' => $selectedSubject,
    'selectedClass' => $selectedClass,
    'studentRecords' => $studentRecords,
    'performanceRows' => $performanceRows
]);
