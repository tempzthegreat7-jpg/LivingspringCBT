<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');
$config = require basePath('config/config-db2.php');
$adminConfig = require basePath('config/config-db.php');

$db = new Database($config);
$adminDb = new Database($adminConfig);
adminEnsureTeacherUsersSchema($adminDb);
$assignedSubjects = adminSubjectsFromStorage(Session::get('user')['assigned_subjects'] ?? '');
if (empty($assignedSubjects)) {
    // Fallback keeps checks stable.
    $assignedSubjects = ['english'];
}
$subjectCategories = adminAssignedSubjectCategoriesFromStorage(Session::get('user')['assigned_subject_categories'] ?? '{}', array_fill_keys($assignedSubjects, true));

$subject = strtolower(trim($_POST['subject'] ?? ''));
$studentClass = strtoupper(trim($_POST['student_class'] ?? ''));
$task = normalizeAssessmentTask($_POST['task'] ?? 'exam');
$header = trim((string) ($_POST['header'] ?? ''));
$term = normalizeExamTerm($_POST['term'] ?? 'first_term');
$number = (int) ($_POST['number'] ?? 0);
$redirectQuery = 'subject=' . urlencode($subject) . '&student_class=' . urlencode($studentClass) . '&task=' . urlencode($task);
if ($task === 'exam') {
    $redirectQuery .= '&term=' . urlencode($term);
} else {
    $redirectQuery .= '&header=' . urlencode($header);
}

if (!in_array($subject, $assignedSubjects, true)) {
    // Teachers can delete only within assigned subjects.
    Session::setFlashMesssge('error_message', 'You are not assigned to that subject.');
    redirect('/teacher/check-question');
}

$subjectCategory = strtolower((string) ($subjectCategories[$subject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);

if (!in_array($studentClass, $allowedClasses, true)) {
    // Block classes outside allowed category.
    Session::setFlashMesssge('error_message', 'Invalid class selected.');
    redirect('/teacher/check-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($allowedClasses[0]) . '&task=' . urlencode($task) . ($task === 'exam' ? '&term=' . urlencode($term) : '&header=' . urlencode($header)));
}

if ($number < 1) {
    Session::setFlashMesssge('error_message', 'Invalid question selected.');
    redirect('/teacher/check-question?' . $redirectQuery);
}

$resolvedHeader = $task === 'exam' ? '' : $header;
$tableName = questionBankTableNameForTask($subject, $studentClass, $task, $resolvedHeader, $term);
$tables = $db->query('SHOW TABLES')->fetchAll();
$hasTable = false;

foreach ($tables as $table) {
    if (in_array($tableName, $table, true)) {
        $hasTable = true;
        break;
    }
}

if (!$hasTable) {
    // Stop when selected question bank table is missing.
    Session::setFlashMesssge('error_message', 'Question bank not found for that class.');
    redirect('/teacher/check-question?' . $redirectQuery);
}

$existingQuestion = $db->query(
    "SELECT number FROM {$tableName} WHERE number = :number LIMIT 1",
    ['number' => $number]
)->fetch();

if (!$existingQuestion) {
    Session::setFlashMesssge('error_message', 'The selected question no longer exists.');
    redirect('/teacher/check-question?' . $redirectQuery);
}

$db->query(
    "DELETE FROM {$tableName} WHERE number = :number",
    ['number' => $number]
);

// Keep numbering contiguous after deletion.
$db->query(
    "UPDATE {$tableName} SET number = number - 1 WHERE number > :number ORDER BY number ASC",
    ['number' => $number]
);

Session::setFlashMesssge('success_message', 'Question deleted successfully.');
redirect('/teacher/check-question?' . $redirectQuery);
