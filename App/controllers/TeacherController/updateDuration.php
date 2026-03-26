<?php

require basePath('Framework/Validation.php');
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

$subject = strtolower(trim($_POST['subject'] ?? ''));
$studentClass = strtoupper(trim($_POST['student_class'] ?? ''));
$task = normalizeAssessmentTask($_POST['task'] ?? 'exam');
$header = trim((string) ($_POST['header'] ?? ''));
$term = normalizeExamTerm($_POST['term'] ?? 'first_term');
$assessmentId = (int) ($_POST['assessment_id'] ?? 0);

if (!in_array($subject, $assignedSubjects, true)) {
    $subject = $assignedSubjects[0];
}

$subjectCategories = adminAssignedSubjectCategoriesFromStorage(Session::get('user')['assigned_subject_categories'] ?? '{}', array_fill_keys($assignedSubjects, true));
$subjectCategory = strtolower((string) ($subjectCategories[$subject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);

if (!in_array($studentClass, $allowedClasses, true)) {
    $studentClass = $allowedClasses[0];
}

$rawHours = (int) ($_POST['duration_hours'] ?? 0);
$rawMinutes = (int) ($_POST['duration_minutes'] ?? 0);
$hours = max(0, min(8, $rawHours));
$minutes = max(0, min(59, $rawMinutes));
$totalMinutes = max(0, min(480, ($hours * 60) + $minutes));
$durationSeconds = $totalMinutes > 0 ? max(60, $totalMinutes * 60) : null;

$contextRow = null;
if ($assessmentId > 0) {
    $contextRow = $db->query(
        'SELECT id FROM assessment_configs WHERE id = :id LIMIT 1',
        ['id' => $assessmentId]
    )->fetch();
}

if (!$contextRow) {
    if ($task === 'exam') {
        $contextRow = $db->query(
            'SELECT id FROM assessment_configs
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
        $contextRow = $db->query(
            'SELECT id FROM assessment_configs
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
}

if (!$contextRow) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => 'Question bank not found.']);
    exit;
}

$db->query(
    'UPDATE assessment_configs
     SET duration_seconds = :duration_seconds
     WHERE id = :id',
    [
        'duration_seconds' => $durationSeconds,
        'id' => (int) ($contextRow['id'] ?? 0)
    ]
);

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'duration_seconds' => $durationSeconds ?? 0
]);
