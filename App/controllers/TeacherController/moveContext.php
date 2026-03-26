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
    $assignedSubjects = ['english'];
}
$subjectCategories = adminAssignedSubjectCategoriesFromStorage(Session::get('user')['assigned_subject_categories'] ?? '{}', array_fill_keys($assignedSubjects, true));

$contextId = (int) ($_POST['context_id'] ?? 0);
$subject = strtolower(trim((string) ($_POST['subject'] ?? '')));
$sourceClass = strtoupper(trim((string) ($_POST['student_class'] ?? '')));
$targetClass = strtoupper(trim((string) ($_POST['target_class'] ?? '')));
$task = normalizeAssessmentTask($_POST['task'] ?? 'exam');
$term = normalizeExamTerm($_POST['term'] ?? 'first_term');
$header = trim((string) ($_POST['header'] ?? ''));

if ($contextId <= 0 || !in_array($subject, $assignedSubjects, true)) {
    Session::setFlashMesssge('error_message', 'Invalid bank selected for moving.');
    redirect('/teacher/add-question');
}

$subjectCategory = strtolower((string) ($subjectCategories[$subject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);
if (!in_array($sourceClass, $allowedClasses, true) || !in_array($targetClass, $allowedClasses, true)) {
    Session::setFlashMesssge('error_message', 'Select a valid class to move this bank.');
    redirect('/teacher/add-question?subject=' . urlencode($subject));
}

if ($targetClass === $sourceClass) {
    Session::setFlashMesssge('error_message', 'Pick a different class before moving this bank.');
    $redirect = '/teacher/add-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($sourceClass) . '&task=' . urlencode($task) . '&assessment_id=' . urlencode((string) $contextId);
    if ($task === 'exam') {
        $redirect .= '&term=' . urlencode($term);
    } elseif ($header !== '') {
        $redirect .= '&header=' . urlencode($header);
    }
    redirect($redirect);
}

$contextRow = $db->query(
    'SELECT id, subject, student_class, task_type, header_text, term_key, duration_seconds, question_limit, table_name
     FROM assessment_configs
     WHERE id = :id
       AND subject = :subject
       AND student_class = :student_class
       AND task_type = :task_type
     LIMIT 1',
    [
        'id' => $contextId,
        'subject' => $subject,
        'student_class' => $sourceClass,
        'task_type' => $task
    ]
)->fetch();

if (!$contextRow) {
    Session::setFlashMesssge('error_message', 'That bank could not be found anymore.');
    redirect('/teacher/add-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($sourceClass) . '&task=' . urlencode($task));
}

$headerText = trim((string) ($contextRow['header_text'] ?? ''));
$termKey = normalizeExamTerm((string) ($contextRow['term_key'] ?? 'first_term'));
$sourceTableName = trim((string) ($contextRow['table_name'] ?? ''));
$targetTableName = questionBankTableNameForTask($subject, $targetClass, $task, $headerText, $termKey);

if ($sourceTableName === '' || $targetTableName === '_') {
    Session::setFlashMesssge('error_message', 'This bank cannot be moved right now.');
    redirect('/teacher/add-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($sourceClass) . '&task=' . urlencode($task));
}

$sourceTableExists = $db->query('SHOW TABLES LIKE :table_name', ['table_name' => $sourceTableName])->fetch();
if (!$sourceTableExists) {
    Session::setFlashMesssge('error_message', 'The bank table is missing, so the move could not continue.');
    redirect('/teacher/add-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($sourceClass) . '&task=' . urlencode($task));
}

$targetTableExists = $db->query('SHOW TABLES LIKE :table_name', ['table_name' => $targetTableName])->fetch();
$targetConfigExists = $db->query(
    'SELECT id
     FROM assessment_configs
     WHERE table_name = :table_name
     LIMIT 1',
    ['table_name' => $targetTableName]
)->fetch();

if ($targetTableExists || $targetConfigExists) {
    Session::setFlashMesssge('error_message', 'A bank already exists for that class and context.');
    $redirect = '/teacher/add-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($sourceClass) . '&task=' . urlencode($task) . '&assessment_id=' . urlencode((string) $contextId);
    if ($task === 'exam') {
        $redirect .= '&term=' . urlencode($termKey);
    } elseif ($headerText !== '') {
        $redirect .= '&header=' . urlencode($headerText);
    }
    redirect($redirect);
}

try {
    $db->connection->beginTransaction();
    $db->query("RENAME TABLE {$sourceTableName} TO {$targetTableName}");
    $db->query(
        'UPDATE assessment_configs
         SET student_class = :student_class,
             table_name = :table_name
         WHERE id = :id
         LIMIT 1',
        [
            'student_class' => $targetClass,
            'table_name' => $targetTableName,
            'id' => (int) ($contextRow['id'] ?? 0)
        ]
    );
    $db->connection->commit();
} catch (Exception $exception) {
    if ($db->connection->inTransaction()) {
        $db->connection->rollBack();
    }

    Session::setFlashMesssge('error_message', 'We could not move that bank just now. Please try again.');
    $redirect = '/teacher/add-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($sourceClass) . '&task=' . urlencode($task) . '&assessment_id=' . urlencode((string) $contextId);
    if ($task === 'exam') {
        $redirect .= '&term=' . urlencode($termKey);
    } elseif ($headerText !== '') {
        $redirect .= '&header=' . urlencode($headerText);
    }
    redirect($redirect);
}

$redirect = '/teacher/add-question?subject=' . urlencode($subject)
    . '&student_class=' . urlencode($targetClass)
    . '&task=' . urlencode($task)
    . '&assessment_id=' . urlencode((string) ((int) ($contextRow['id'] ?? 0)));

$durationMinutes = max(0, (int) ceil(max(0, (int) ($contextRow['duration_seconds'] ?? 0)) / 60));
$redirect .= '&duration_hours=' . urlencode((string) ((int) floor($durationMinutes / 60)));
$redirect .= '&duration_minutes=' . urlencode((string) ((int) ($durationMinutes % 60)));
$redirect .= '&question_limit=' . urlencode((string) max(1, min(200, (int) ($contextRow['question_limit'] ?? 20))));

if ($task === 'exam') {
    $redirect .= '&term=' . urlencode($termKey);
} elseif ($headerText !== '') {
    $redirect .= '&header=' . urlencode($headerText);
}

Session::setFlashMesssge('success_message', 'Bank moved successfully.');
redirect($redirect);
