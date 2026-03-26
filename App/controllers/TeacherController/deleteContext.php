<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db2.php');
$adminConfig = require basePath('config/config-db.php');

$db = new Database($config);
$adminDb = new Database($adminConfig);
adminEnsureTeacherUsersSchema($adminDb);
ensureAssessmentConfigsSchema($db);

$assignedSubjects = adminSubjectsFromStorage(Session::get('user')['assigned_subjects'] ?? '');
if (empty($assignedSubjects)) {
    // Fallback so validation checks can still run.
    $assignedSubjects = ['english'];
}
$subjectCategories = adminAssignedSubjectCategoriesFromStorage(Session::get('user')['assigned_subject_categories'] ?? '{}', array_fill_keys($assignedSubjects, true));

$contextId = (int) ($_POST['context_id'] ?? 0);
$contextIds = $_POST['context_ids'] ?? [];
if (!is_array($contextIds)) {
    $contextIds = [];
}
$normalizedContextIds = [];
foreach ($contextIds as $rawContextId) {
    $candidate = (int) $rawContextId;
    if ($candidate > 0) {
        $normalizedContextIds[] = $candidate;
    }
}
if ($contextId > 0) {
    $normalizedContextIds[] = $contextId;
}
$normalizedContextIds = array_values(array_unique($normalizedContextIds));
$subject = strtolower(trim((string) ($_POST['subject'] ?? '')));
$studentClass = strtoupper(trim((string) ($_POST['student_class'] ?? '')));
$task = normalizeAssessmentTask($_POST['task'] ?? 'assignment');
$term = normalizeExamTerm($_POST['term'] ?? 'first_term');
$header = trim((string) ($_POST['header'] ?? ''));

if (empty($normalizedContextIds) || !in_array($subject, $assignedSubjects, true)) {
    // Block invalid id or unassigned subject.
    Session::setFlashMesssge('error_message', 'Invalid context selected.');
    redirect('/teacher/add-question');
}

$subjectCategory = strtolower((string) ($subjectCategories[$subject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);
if (!in_array($studentClass, $allowedClasses, true)) {
    // Block class outside teacher allowed category.
    Session::setFlashMesssge('error_message', 'Invalid class selected.');
    redirect('/teacher/add-question?subject=' . urlencode($subject));
}

$deletedCount = 0;
$teacherUserId = (int) (Session::get('user')['id'] ?? 0);

foreach ($normalizedContextIds as $selectedContextId) {
    $row = $db->query(
        'SELECT id, table_name
         FROM assessment_configs
         WHERE id = :id
           AND subject = :subject
           AND student_class = :student_class
           AND task_type = :task
         LIMIT 1',
        [
            'id' => $selectedContextId,
            'subject' => $subject,
            'student_class' => $studentClass,
            'task' => $task
        ]
    )->fetch();

    if (!$row) {
        continue;
    }

    $tableName = (string) ($row['table_name'] ?? '');
    if ($tableName !== '') {
        // Remove context question table before deleting metadata row.
        $tableName = safeTableName($tableName);
        $db->query("DROP TABLE IF EXISTS {$tableName}");
    }

    $db->query('DELETE FROM assessment_configs WHERE id = :id LIMIT 1', [
        'id' => (int) ($row['id'] ?? 0)
    ]);
    $deletedCount++;
}

if ($deletedCount < 1) {
    Session::setFlashMesssge('error_message', 'Selected bank(s) no longer exist.');
    $redirect = '/teacher/add-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($studentClass) . '&task=' . urlencode($task);
    if ($task === 'exam') {
        $redirect .= '&term=' . urlencode($term);
    } elseif ($header !== '') {
        $redirect .= '&header=' . urlencode($header);
    }
    redirect($redirect);
}

Session::setFlashMesssge('success_message', $deletedCount === 1 ? 'Bank deleted successfully.' : ($deletedCount . ' banks deleted successfully.'));
$redirect = '/teacher/add-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($studentClass) . '&task=' . urlencode($task);
if ($task === 'exam') {
    $redirect .= '&term=' . urlencode($term);
} elseif ($header !== '') {
    $redirect .= '&header=' . urlencode($header);
}
redirect($redirect);
