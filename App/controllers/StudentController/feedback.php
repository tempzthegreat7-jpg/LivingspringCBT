<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$studentSession = Session::get('student') ?? [];
$studentId = (int) ($studentSession['id'] ?? 0);
$studentName = trim((string) ($studentSession['name'] ?? ''));
$studentClass = strtoupper(trim((string) ($studentSession['class'] ?? 'SS3')));

if ($studentId <= 0 || $studentName === '') {
    redirect('/student/names');
}

$mainConfig = require basePath('config/config-db.php');
$mainDb = new Database($mainConfig);
adminEnsureTeacherUsersSchema($mainDb);
adminEnsureStudentUsersSchema($mainDb);
adminEnsureFeedbackSchema($mainDb);

$teacherRows = $mainDb->query(
    "SELECT id, name, role, assigned_subjects FROM teacher_users WHERE LOWER(role) != 'admin' AND is_active = 1 ORDER BY name ASC"
)->fetchAll();

$feedbackRows = $mainDb->query(
    'SELECT id, teacher_user_id, rating_teaching_explanation, rating_teaching_clarity, rating_punctuality_to_class, rating_approachable, rating_likeable, rating_discipline, rating_overall, feedback_text, review_status, submitted_at, moderated_feedback, approved_teacher_visible
     FROM student_feedback
     WHERE student_user_id = :student_user_id
     ORDER BY id DESC
     LIMIT 20',
    ['student_user_id' => $studentId]
)->fetchAll();

$teacherLookup = [];
foreach ($teacherRows as $teacherRow) {
    $teacherLookup[(int) ($teacherRow['id'] ?? 0)] = $teacherRow;
}

loadView('student-feedback', [
    'student' => ['name' => $studentName, 'class' => $studentClass],
    'teachers' => $teacherRows,
    'teacherLookup' => $teacherLookup,
    'feedbackRows' => $feedbackRows
]);
