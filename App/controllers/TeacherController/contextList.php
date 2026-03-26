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
    // Fallback keeps endpoint stable when assignment data is missing.
    $assignedSubjects = ['english'];
}

$available = adminAvailableSubjects($adminDb);
$subjectOptions = [];
foreach ($assignedSubjects as $subjectKey) {
    $subjectOptions[$subjectKey] = $available[$subjectKey] ?? ucwords(str_replace('_', ' ', $subjectKey));
}

$subjectCategories = adminAssignedSubjectCategoriesFromStorage(
    Session::get('user')['assigned_subject_categories'] ?? '{}',
    $subjectOptions
);

$subject = strtolower(trim((string) ($_GET['subject'] ?? ($assignedSubjects[0] ?? 'english'))));
$studentClass = strtoupper(trim((string) ($_GET['student_class'] ?? 'SS3')));
$task = normalizeAssessmentTask($_GET['task'] ?? 'exam');
$term = normalizeExamTerm($_GET['term'] ?? 'first_term');
$header = trim((string) ($_GET['header'] ?? ''));
$rawDurationMinutes = (int) ($_GET['duration_minutes'] ?? 0);
if (isset($_GET['duration_hours'])) {
    $durationHours = max(0, min(8, (int) ($_GET['duration_hours'] ?? 0)));
    $durationMinutePart = max(0, min(59, $rawDurationMinutes));
    $durationMinutes = max(0, min(480, ($durationHours * 60) + $durationMinutePart));
} else {
    $durationMinutes = max(0, min(480, $rawDurationMinutes));
}
$questionLimit = max(1, min(200, (int) ($_GET['question_limit'] ?? 20)));
$assessmentId = (int) ($_GET['assessment_id'] ?? 0);

if (!in_array($subject, $assignedSubjects, true)) {
    // Never return contexts for unassigned subjects.
    $subject = $assignedSubjects[0];
}

$subjectCategory = strtolower((string) ($subjectCategories[$subject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);
if (!in_array($studentClass, $allowedClasses, true)) {
    // Clamp class to allowed range for this subject category.
    $studentClass = $allowedClasses[0];
}

$contexts = $db->query(
    'SELECT id, header_text, term_key, duration_seconds, question_limit, teacher_user_id
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

$mapped = [];
foreach ($contexts as $row) {
    // Build each context row used by frontend dynamic list.
    $contextId = (int) ($row['id'] ?? 0);
    $headerText = trim((string) ($row['header_text'] ?? ''));
    $termKey = normalizeExamTerm($row['term_key'] ?? 'first_term');
    $label = $task === 'exam'
        ? ($termOptions[$termKey] ?? '1st Term')
        : ($headerText !== '' ? $headerText : 'Custom');

    $rowDurationSeconds = max(0, (int) ($row['duration_seconds'] ?? 0));
    $rowDurationMinutes = $rowDurationSeconds > 0 ? (int) ceil($rowDurationSeconds / 60) : $durationMinutes;
    $durationHours = (int) floor($rowDurationMinutes / 60);
    $durationMinutePart = (int) ($rowDurationMinutes % 60);
    $rowQuestionLimit = max(1, min(200, (int) ($row['question_limit'] ?? $questionLimit)));
    $openUrl = '/teacher/add-question?subject=' . urlencode($subject)
        . '&student_class=' . urlencode($studentClass)
        . '&task=' . urlencode($task)
        . '&assessment_id=' . urlencode((string) $contextId)
        . '&duration_hours=' . urlencode((string) $durationHours)
        . '&duration_minutes=' . urlencode((string) $durationMinutePart)
        . '&question_limit=' . urlencode((string) $rowQuestionLimit)
        . ($task === 'exam'
            ? '&term=' . urlencode($term)
            : '&header=' . urlencode($headerText));

    $mapped[] = [
        'id' => $contextId,
        'label' => $label,
        'header' => $headerText,
        'open_url' => $openUrl,
        'is_current' => $assessmentId > 0 && $assessmentId === $contextId,
        'can_delete' => true
    ];
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'subject' => $subject,
    'student_class' => $studentClass,
    'task' => $task,
    'term' => $term,
    'header' => $header,
    'available_contexts' => $mapped,
    'empty_text' => 'No existing ' . ($taskOptions[$task] ?? ucfirst($task)) . ' yet.'
]);
