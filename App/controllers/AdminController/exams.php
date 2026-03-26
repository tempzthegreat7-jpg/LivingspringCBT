<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$configAdmin = require basePath('config/config-db.php');
$configExam = require basePath('config/config-db2.php');

$dbAdmin = new Database($configAdmin);
$dbExam = new Database($configExam);

adminEnsureTeacherUsersSchema($dbAdmin);
ensureAssessmentConfigsSchema($dbExam);
ensureExamActivationSchema($dbExam);

$subjectLabels = adminAvailableSubjects($dbAdmin);
$classOptions = adminClassOptions();

$rows = $dbExam->query(
    'SELECT id, subject, student_class, task_type, term_key, table_name
     FROM assessment_configs
     WHERE LOWER(task_type) = "exam"
     ORDER BY student_class ASC, subject ASC, term_key ASC, id ASC'
)->fetchAll();

$examBanks = [];
$subjectsByClass = [];

foreach ($rows as $row) {
    $tableName = safeTableName((string) ($row['table_name'] ?? ''));

    $tableExists = $dbExam->query('SHOW TABLES LIKE :table_name', ['table_name' => $tableName])->fetch();
    if (!$tableExists) {
        continue;
    }

    $countRow = $dbExam->query("SELECT COUNT(*) AS total FROM {$tableName}")->fetch();
    $questionCount = (int) ($countRow['total'] ?? 0);
    if ($questionCount < 1) {
        continue;
    }

    $subjectKey = strtolower(trim((string) ($row['subject'] ?? '')));
    $classKey = strtoupper(trim((string) ($row['student_class'] ?? '')));
    if ($subjectKey === '' || $classKey === '') {
        continue;
    }

    $termKey = normalizeExamTerm($row['term_key'] ?? 'first_term');
    $termLabel = examTermOptions()[$termKey] ?? '1st Term';
    $label = $subjectLabels[$subjectKey] ?? ucwords(str_replace('_', ' ', $subjectKey));

    if (!isset($subjectsByClass[$classKey])) {
        $subjectsByClass[$classKey] = [];
    }
    $subjectsByClass[$classKey][$subjectKey] = $label;

    $examBanks[] = [
        'subject_key' => $subjectKey,
        'subject_label' => $label,
        'student_class' => $classKey,
        'term_label' => $termLabel,
        'question_count' => $questionCount
    ];
}

foreach ($subjectsByClass as $classKey => $subjectMap) {
    asort($subjectMap);
    $subjectsByClass[$classKey] = $subjectMap;
}

$activeRows = $dbExam->query('SELECT student_class, subject FROM exam_active_subjects')->fetchAll();
$activeSubjects = [];
foreach ($activeRows as $row) {
    $classKey = strtoupper(trim((string) ($row['student_class'] ?? '')));
    $subjectKey = strtolower(trim((string) ($row['subject'] ?? '')));
    if ($classKey !== '' && $subjectKey !== '') {
        if (!isset($activeSubjects[$classKey])) {
            $activeSubjects[$classKey] = [];
        }
        $activeSubjects[$classKey][$subjectKey] = $subjectKey;
    }
}

loadView('admin/exams', [
    'examBanks' => $examBanks,
    'subjectsByClass' => $subjectsByClass,
    'activeSubjects' => $activeSubjects,
    'classOptions' => $classOptions
]);
