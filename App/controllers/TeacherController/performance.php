<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db2.php');
$adminConfig = require basePath('config/config-db.php');
$db = new Database($config);
$adminDb = new Database($adminConfig);

$db->query(
    // Keep history table available for result analytics page.
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

$available = adminAvailableSubjects($adminDb);
$assignedSubjects = adminSubjectsFromStorage(Session::get('user')['assigned_subjects'] ?? '');

if (empty($assignedSubjects)) {
    // Fallback keeps selector usable.
    $assignedSubjects = ['english'];
}

$subjectOptions = [];
foreach ($assignedSubjects as $subjectKey) {
    $subjectOptions[$subjectKey] = $available[$subjectKey] ?? ucwords(str_replace('_', ' ', $subjectKey));
}
$subjectCategories = adminAssignedSubjectCategoriesFromStorage(
    Session::get('user')['assigned_subject_categories'] ?? '{}',
    $subjectOptions
);

$requestedSubject = strtolower(trim((string) ($_GET['subject'] ?? '')));
$sessionSubject = strtolower(trim((string) (Session::get('teacher_selected_subject') ?? '')));

if (in_array($requestedSubject, $assignedSubjects, true)) {
    $selectedSubject = $requestedSubject;
} elseif (in_array($sessionSubject, $assignedSubjects, true)) {
    $selectedSubject = $sessionSubject;
} else {
    // Default to first assigned subject.
    $selectedSubject = strtolower((string) ($assignedSubjects[0] ?? 'english'));
}

Session::set('teacher_selected_subject', $selectedSubject);

$subjectCategory = strtolower((string) ($subjectCategories[$selectedSubject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);
if (empty($allowedClasses)) {
    $allowedClasses = classOptionsForSubjectCategory('both');
}

$classPlaceholders = [];
$queryParams = [
    'subject' => $selectedSubject
];
foreach ($allowedClasses as $index => $classLabel) {
    $paramKey = 'class_' . $index;
    $classPlaceholders[] = ':' . $paramKey;
    $queryParams[$paramKey] = strtoupper((string) $classLabel);
}

$classFilterSql = implode(', ', $classPlaceholders);

$records = $db->query(
    'SELECT id, student_name, student_class, subject, task_type, score, total_questions, time_spent_seconds, timed_out, completed_at
     FROM exam_attempts
     WHERE subject = :subject
       AND UPPER(TRIM(student_class)) IN (' . $classFilterSql . ')
     ORDER BY completed_at DESC',
    $queryParams
)->fetchAll();

$exportFormat = strtolower(trim((string) ($_GET['export'] ?? '')));
if ($exportFormat === 'csv') {
    $fileDate = date('Ymd_His');
    $safeSubject = preg_replace('/[^a-z0-9_]+/i', '_', (string) $selectedSubject);
    $fileName = 'performance_' . strtolower((string) $safeSubject) . '_' . $fileDate . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Class', 'Subject', 'Task', 'Score', 'Total Questions', 'Percentage', 'Time Spent (seconds)', 'Timed Out', 'Completed At']);

    foreach ($records as $record) {
        $score = (int) ($record['score'] ?? 0);
        $totalQuestions = max(1, (int) ($record['total_questions'] ?? 1));
        $percentage = round(($score / $totalQuestions) * 100, 2);

        fputcsv($output, [
            (string) ($record['student_name'] ?? ''),
            (string) ($record['student_class'] ?? ''),
            (string) ($record['subject'] ?? ''),
            (string) (assessmentTaskOptions()[normalizeAssessmentTask($record['task_type'] ?? 'exam')] ?? ucfirst((string) ($record['task_type'] ?? 'exam'))),
            $score,
            (int) ($record['total_questions'] ?? 0),
            $percentage,
            (int) ($record['time_spent_seconds'] ?? 0),
            (int) ($record['timed_out'] ?? 0) === 1 ? 'Yes' : 'No',
            (string) ($record['completed_at'] ?? '')
        ]);
    }

    fclose($output);
    exit;
}

$totalAttempts = count($records);
$sumPercent = 0.0;
$passCount = 0;
$timeoutCount = 0;
$classMetricBuckets = [];
$dailyTrendMap = [];

foreach ($records as $record) {
    $score = (int) ($record['score'] ?? 0);
    $totalQuestions = max(1, (int) ($record['total_questions'] ?? 1));
    $percentage = ($score / $totalQuestions) * 100;
    $classKey = strtoupper(trim((string) ($record['student_class'] ?? 'SS3')));
    if ($classKey === '') {
        $classKey = 'SS3';
    }

    $sumPercent += $percentage;
    if ($percentage >= 50) {
        $passCount++;
    }
    if ((int) ($record['timed_out'] ?? 0) === 1) {
        $timeoutCount++;
    }

    if (!isset($classMetricBuckets[$classKey])) {
        $classMetricBuckets[$classKey] = [
            'count' => 0,
            'sum_percent' => 0.0
        ];
    }
    $classMetricBuckets[$classKey]['count']++;
    $classMetricBuckets[$classKey]['sum_percent'] += $percentage;

    $dayKey = date('Y-m-d', strtotime((string) ($record['completed_at'] ?? 'now')));
    if (!isset($dailyTrendMap[$dayKey])) {
        $dailyTrendMap[$dayKey] = [
            'count' => 0,
            'sum_percent' => 0.0
        ];
    }
    $dailyTrendMap[$dayKey]['count']++;
    $dailyTrendMap[$dayKey]['sum_percent'] += $percentage;
}

$averagePercent = $totalAttempts > 0 ? round($sumPercent / $totalAttempts, 1) : 0.0;
$passRate = $totalAttempts > 0 ? round(($passCount / $totalAttempts) * 100, 1) : 0.0;
$timeoutRate = $totalAttempts > 0 ? round(($timeoutCount / $totalAttempts) * 100, 1) : 0.0;

$classAverages = [];
foreach ($classMetricBuckets as $classKey => $metrics) {
    $classAverages[$classKey] = [
        'attempts' => (int) ($metrics['count'] ?? 0),
        'average_percent' => ($metrics['count'] ?? 0) > 0
            ? round(((float) ($metrics['sum_percent'] ?? 0.0)) / (int) $metrics['count'], 1)
            : 0.0
    ];
}
uksort($classAverages, 'strcmp');

$dailyTrend = [];
ksort($dailyTrendMap);
foreach ($dailyTrendMap as $dayKey => $metrics) {
    $dailyTrend[] = [
        'day' => $dayKey,
        'attempts' => (int) ($metrics['count'] ?? 0),
        'average_percent' => ($metrics['count'] ?? 0) > 0
            ? round(((float) ($metrics['sum_percent'] ?? 0.0)) / (int) $metrics['count'], 1)
            : 0.0
    ];
}
if (count($dailyTrend) > 7) {
    $dailyTrend = array_slice($dailyTrend, -7);
}

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$groupedRecords = [];

foreach ($records as $record) {
    // Group rows by day heading for easier reading in UI.
    $recordDate = date('Y-m-d', strtotime($record['completed_at']));
    $recordClass = strtoupper(trim((string) ($record['student_class'] ?? 'SS3')));
    if ($recordClass === '') {
        $recordClass = 'SS3';
    }

    if ($recordDate === $today) {
        $sectionTitle = 'Today';
    } elseif ($recordDate === $yesterday) {
        $sectionTitle = 'Yesterday';
    } else {
        $sectionTitle = date('l, F j, Y', strtotime($record['completed_at']));
    }

    if (!isset($groupedRecords[$sectionTitle])) {
        $groupedRecords[$sectionTitle] = [];
    }

    if (!isset($groupedRecords[$sectionTitle][$recordClass])) {
        $groupedRecords[$sectionTitle][$recordClass] = [];
    }

    $groupedRecords[$sectionTitle][$recordClass][] = $record;
}

loadView('teacher/performance', [
    'selectedSubject' => $selectedSubject,
    'subjectOptions' => $subjectOptions,
    'selectedSubjectLabel' => $subjectOptions[$selectedSubject] ?? ucwords(str_replace('_', ' ', $selectedSubject)),
    'selectedSubjectCategory' => $subjectCategory,
    'allowedClasses' => $allowedClasses,
    'totalAttempts' => $totalAttempts,
    'averagePercent' => $averagePercent,
    'passRate' => $passRate,
    'timeoutRate' => $timeoutRate,
    'classAverages' => $classAverages,
    'dailyTrend' => $dailyTrend,
    'groupedRecords' => $groupedRecords
]);
