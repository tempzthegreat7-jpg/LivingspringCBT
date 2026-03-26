<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');
$config = require basePath('config/config-db2.php');
$adminConfig = require basePath('config/config-db.php');

$db = new Database($config);
$adminDb = new Database($adminConfig);
adminEnsureTeacherUsersSchema($adminDb);
ensureAssessmentConfigsSchema($db);
$termOptions = examTermOptions();
$taskOptions = assessmentTaskOptions();
$assignedSubjects = adminSubjectsFromStorage(Session::get('user')['assigned_subjects'] ?? '');
if (empty($assignedSubjects)) {
    // Fallback so page still loads if assignment data is empty.
    $assignedSubjects = ['english'];
}

$available = adminAvailableSubjects($adminDb);
$subjectOptions = [];
foreach ($assignedSubjects as $subjectKey) {
    $subjectOptions[$subjectKey] = $available[$subjectKey] ?? ucwords(str_replace('_', ' ', $subjectKey));
}
$subjectCategories = adminAssignedSubjectCategoriesFromStorage(Session::get('user')['assigned_subject_categories'] ?? '{}', $subjectOptions);

$subject = strtolower(trim($_GET['subject'] ?? ($assignedSubjects[0] ?? 'english')));
$studentClass = strtoupper(trim($_GET['student_class'] ?? 'SS3'));
$task = normalizeAssessmentTask($_GET['task'] ?? 'exam');
$header = trim((string) ($_GET['header'] ?? ''));
$term = normalizeExamTerm($_GET['term'] ?? 'first_term');

if (!in_array($subject, $assignedSubjects, true)) {
    // Keep selected subject inside assigned list.
    $subject = $assignedSubjects[0];
}

$subjectCategory = strtolower((string) ($subjectCategories[$subject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);

if (!in_array($studentClass, $allowedClasses, true)) {
    // Keep selected class inside allowed category.
    $studentClass = $allowedClasses[0];
}

$availableContextRows = $db->query(
    'SELECT id, header_text, term_key
     FROM assessment_configs
     WHERE subject = :subject
       AND student_class = :student_class
       AND task_type = :task_type
     ORDER BY updated_at DESC, id DESC',
    [
        'subject' => $subject,
        'student_class' => $studentClass,
        'task_type' => $task
    ]
)->fetchAll();

$availableContexts = [];
foreach ($availableContextRows as $row) {
    // Build simple labels for context tabs.
    $contextHeader = trim((string) ($row['header_text'] ?? ''));
    $termKey = normalizeExamTerm($row['term_key'] ?? 'first_term');

    $availableContexts[] = [
        'id' => (int) ($row['id'] ?? 0),
        'label' => $task === 'exam'
            ? ($termOptions[$termKey] ?? '1st Term')
            : ($contextHeader !== '' ? $contextHeader : 'Custom'),
        'header' => $contextHeader
    ];
}

if ($task !== 'exam' && $header === '' && !empty($availableContexts)) {
    // Default to first saved header when none is provided.
    $header = (string) ($availableContexts[0]['header'] ?? '');
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

$questions = [];
if ($hasTable) {
    $columnRows = $db->query("SHOW COLUMNS FROM {$tableName}")->fetchAll();
    $hasImagePathColumn = false;

    foreach ($columnRows as $columnRow) {
        if (strtolower((string) ($columnRow['Field'] ?? '')) === 'image_path') {
            $hasImagePathColumn = true;
            break;
        }
    }

    if ($hasImagePathColumn) {
        // Load questions with image path when available.
        $questions = $db->query("SELECT number, question, choice1, choice2, choice3, choice4, correct_answer, image_path FROM {$tableName} ORDER BY number ASC")->fetchAll();
    } else {
        // Keep old tables compatible by returning a null image path.
        $questions = $db->query("SELECT number, question, choice1, choice2, choice3, choice4, correct_answer, NULL AS image_path FROM {$tableName} ORDER BY number ASC")->fetchAll();
    }
}

$durationSeconds = 0;
$durationRow = null;
if ($task === 'exam') {
    $durationRow = $db->query(
        'SELECT duration_seconds
         FROM assessment_configs
         WHERE subject = :subject
           AND student_class = :student_class
           AND task_type = :task_type
           AND term_key = :term_key
         ORDER BY updated_at DESC, id DESC
         LIMIT 1',
        [
            'subject' => $subject,
            'student_class' => $studentClass,
            'task_type' => $task,
            'term_key' => $term
        ]
    )->fetch();
} else {
    $durationRow = $db->query(
        'SELECT duration_seconds
         FROM assessment_configs
         WHERE subject = :subject
           AND student_class = :student_class
           AND task_type = :task_type
           AND header_text = :header_text
         ORDER BY updated_at DESC, id DESC
         LIMIT 1',
        [
            'subject' => $subject,
            'student_class' => $studentClass,
            'task_type' => $task,
            'header_text' => $header
        ]
    )->fetch();
}

$durationSeconds = max(0, (int) ($durationRow['duration_seconds'] ?? 0));

loadView('teacher/check-question', [
    'subject' => $subject,
    'studentClass' => $studentClass,
    'task' => $task,
    'header' => $header,
    'term' => $term,
    'termOptions' => $termOptions,
    'taskOptions' => $taskOptions,
    'allowedClasses' => $allowedClasses,
    'subjectCategories' => $subjectCategories,
    'subjectOptions' => $subjectOptions,
    'questions' => $questions,
    'availableContexts' => $availableContexts,
    'durationSeconds' => $durationSeconds
]);
