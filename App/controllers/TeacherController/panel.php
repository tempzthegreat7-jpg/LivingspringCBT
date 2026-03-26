<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');
$config = require basePath('config/config-db2.php');
$adminConfig = require basePath('config/config-db.php');

$assignedSubjects = adminSubjectsFromStorage(Session::get('user')['assigned_subjects'] ?? '');
if (empty($assignedSubjects)) {
    // Fallback so dashboard can still render.
    $assignedSubjects = ['english'];
}

$db = new Database($config);
$adminDb = new Database($adminConfig);
adminEnsureTeacherUsersSchema($adminDb);
adminEnsureTeacherAlertRepliesSchema($adminDb);
ensureAssessmentConfigsSchema($db);
$tables = $db->query('SHOW TABLES')->fetchAll();
$subjectCategories = adminAssignedSubjectCategoriesFromStorage(Session::get('user')['assigned_subject_categories'] ?? '{}', array_fill_keys($assignedSubjects, true));
$subjectOptions = adminAvailableSubjects($adminDb);
$taskOptions = assessmentTaskOptions();
$subjectCards = [];
$totalQuestions = 0;
$allAccessibleClasses = [];
$totalContexts = 0;
$teacherUserId = (int) (Session::get('user')['id'] ?? 0);
$unreadAdminReplies = 0;

$existingTableMap = [];
foreach ($tables as $tableRow) {
    // Quick lookup map for table existence checks.
    $tableName = (string) array_values($tableRow)[0];
    $existingTableMap[$tableName] = true;
}

$contexts = [];
if ($teacherUserId > 0) {
    // Load all contexts for subjects assigned to this teacher.
    $subjectParams = [];
    $subjectPlaceholders = [];
    foreach ($assignedSubjects as $index => $subjectKey) {
        $paramKey = 'subject_' . $index;
        $subjectPlaceholders[] = $paramKey;
        $subjectParams[$paramKey] = $subjectKey;
    }
    $subjectFilter = '';
    if (!empty($subjectPlaceholders)) {
        $subjectFilter = 'WHERE subject IN (:' . implode(', :', $subjectPlaceholders) . ')';
    }

    $contexts = $db->query(
        'SELECT id, subject, student_class, task_type, table_name
         FROM assessment_configs
         ' . $subjectFilter . '
         ORDER BY updated_at DESC, id DESC',
        $subjectParams
    )->fetchAll();

    $unreadAdminReplies = adminCountUnreadTeacherInbox($adminDb, $teacherUserId);
}

$contextsBySubject = [];
foreach ($contexts as $contextRow) {
    // Group contexts by subject for easier aggregation below.
    $contextSubject = strtolower(trim((string) ($contextRow['subject'] ?? '')));
    if ($contextSubject === '') {
        continue;
    }
    if (!isset($contextsBySubject[$contextSubject])) {
        $contextsBySubject[$contextSubject] = [];
    }
    $contextsBySubject[$contextSubject][] = $contextRow;
}

foreach ($assignedSubjects as $subjectKey) {
    $subjectCategory = strtolower((string) ($subjectCategories[$subjectKey] ?? 'both'));
    $allowedClasses = classOptionsForSubjectCategory($subjectCategory);
    $countsByClass = [];
    $contextsByClass = [];
    $countsByTask = [];
    foreach (array_keys($taskOptions) as $taskKey) {
        $countsByTask[$taskKey] = 0;
    }
    $subjectTotal = 0;
    $subjectContextCount = 0;

    foreach ($allowedClasses as $classLabel) {
        $countsByClass[$classLabel] = 0;
        $contextsByClass[$classLabel] = 0;
        $allAccessibleClasses[$classLabel] = true;
    }

    foreach (($contextsBySubject[$subjectKey] ?? []) as $contextRow) {
        $classLabel = strtoupper(trim((string) ($contextRow['student_class'] ?? '')));
        if (!in_array($classLabel, $allowedClasses, true)) {
            continue;
        }

        $tableName = (string) ($contextRow['table_name'] ?? '');
        if ($tableName === '' || !isset($existingTableMap[$tableName])) {
            // Skip broken context rows that point to missing tables.
            continue;
        }

        $countResult = $db->query("SELECT COUNT(*) AS total FROM {$tableName}")->fetch();
        $questionCount = (int) ($countResult['total'] ?? 0);
        $taskKey = normalizeAssessmentTask($contextRow['task_type'] ?? 'exam');

        $countsByClass[$classLabel] += $questionCount;
        $contextsByClass[$classLabel] += 1;
        $countsByTask[$taskKey] += $questionCount;
        $subjectTotal += $questionCount;
        $subjectContextCount += 1;
        $totalQuestions += $questionCount;
        $totalContexts += 1;
    }

    $subjectCards[] = [
        'key' => $subjectKey,
        'label' => $subjectOptions[$subjectKey] ?? ucfirst($subjectKey),
        'total' => $subjectTotal,
        'context_count' => $subjectContextCount,
        'classes' => $countsByClass,
        'contexts_by_class' => $contextsByClass,
        'counts_by_task' => $countsByTask
    ];
}

loadView('teacher/panel', [
    'subjectCards' => $subjectCards,
    'totalQuestions' => $totalQuestions,
    'classesCount' => count($allAccessibleClasses),
    'totalContexts' => $totalContexts,
    'unreadAdminReplies' => $unreadAdminReplies
]);
