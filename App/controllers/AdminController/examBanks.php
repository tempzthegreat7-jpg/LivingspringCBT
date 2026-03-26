<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$configAdmin = require basePath('config/config-db.php');
$configExam = require basePath('config/config-db2.php');

$dbAdmin = new Database($configAdmin);
$dbExam = new Database($configExam);

adminEnsureTeacherUsersSchema($dbAdmin);
ensureAssessmentConfigsSchema($dbExam);

$subjectLabels = adminAvailableSubjects($dbAdmin);

$rows = $dbExam->query(
    'SELECT id, subject, student_class, task_type, term_key, table_name
     FROM assessment_configs
     WHERE LOWER(task_type) = "exam"
     ORDER BY student_class ASC, subject ASC, term_key ASC, id ASC'
)->fetchAll();

$examBanks = [];

foreach ($rows as $row) {
    $tableName = (string) ($row['table_name'] ?? '');
    if ($tableName === '') {
        continue;
    }

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

    $examBanks[] = [
        'subject_key' => $subjectKey,
        'subject_label' => $label,
        'student_class' => $classKey,
        'term_label' => $termLabel,
        'question_count' => $questionCount
    ];
}

loadView('admin/exams-banks', [
    'examBanks' => $examBanks
]);
