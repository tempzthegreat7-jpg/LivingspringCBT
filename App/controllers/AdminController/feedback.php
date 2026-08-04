<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$mainConfig = require basePath('config/config-db.php');
$mainDb = new Database($mainConfig);
adminEnsureTeacherUsersSchema($mainDb);
adminEnsureStudentUsersSchema($mainDb);
adminEnsureFeedbackSchema($mainDb);

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$currentPath = parse_url($requestUri, PHP_URL_PATH) ?: '';
$currentPath = strtolower($currentPath);

$teacherRows = $mainDb->query("SELECT id, name FROM teacher_users WHERE LOWER(role) != 'admin' ORDER BY name ASC")->fetchAll();
$teacherLookup = [];
foreach ($teacherRows as $teacherRow) {
    $teacherLookup[(int) ($teacherRow['id'] ?? 0)] = (string) ($teacherRow['name'] ?? '');
}

$feedbackRows = $mainDb->query(
    'SELECT sf.id, sf.teacher_user_id, sf.student_user_id, sf.student_identity_token, sf.rating_teaching_explanation, sf.rating_teaching_clarity, sf.rating_punctuality_to_class, sf.rating_approachable, sf.rating_likeable, sf.rating_discipline, sf.rating_overall, sf.feedback_text, sf.moderated_feedback, sf.review_status, sf.review_notes, sf.submitted_at, sf.approved_at, sf.approved_teacher_visible, tu.name AS teacher_name
     FROM student_feedback sf
     LEFT JOIN teacher_users tu ON tu.id = sf.teacher_user_id
     ORDER BY sf.submitted_at DESC'
)->fetchAll();

$teacherStats = [];
foreach ($teacherRows as $teacherRow) {
    $teacherId = (int) ($teacherRow['id'] ?? 0);
    $teacherStats[$teacherId] = [
        'teacher_id' => $teacherId,
        'teacher_name' => (string) ($teacherRow['name'] ?? ''),
        'count' => 0,
        'avg_overall' => 0.0,
        'avg_teaching_explanation' => 0.0,
        'avg_teaching_clarity' => 0.0,
        'avg_punctuality_to_class' => 0.0,
        'avg_approachable' => 0.0,
        'avg_likeable' => 0.0,
        'avg_discipline' => 0.0,
        'feedback_rows' => []
    ];
}

foreach ($feedbackRows as $feedbackRow) {
    $teacherId = (int) ($feedbackRow['teacher_user_id'] ?? 0);
    if (!isset($teacherStats[$teacherId])) {
        continue;
    }

    $teacherStats[$teacherId]['count'] += 1;
    $teacherStats[$teacherId]['avg_overall'] += (float) ($feedbackRow['rating_overall'] ?? 0);
    $teacherStats[$teacherId]['avg_teaching_explanation'] += (float) ($feedbackRow['rating_teaching_explanation'] ?? 0);
    $teacherStats[$teacherId]['avg_teaching_clarity'] += (float) ($feedbackRow['rating_teaching_clarity'] ?? 0);
    $teacherStats[$teacherId]['avg_punctuality_to_class'] += (float) ($feedbackRow['rating_punctuality_to_class'] ?? 0);
    $teacherStats[$teacherId]['avg_approachable'] += (float) ($feedbackRow['rating_approachable'] ?? 0);
    $teacherStats[$teacherId]['avg_likeable'] += (float) ($feedbackRow['rating_likeable'] ?? 0);
    $teacherStats[$teacherId]['avg_discipline'] += (float) ($feedbackRow['rating_discipline'] ?? 0);
    $teacherStats[$teacherId]['feedback_rows'][] = $feedbackRow;
}

foreach ($teacherStats as &$teacherStat) {
    $count = max(1, (int) ($teacherStat['count'] ?? 0));
    $teacherStat['avg_overall'] = round((float) ($teacherStat['avg_overall'] ?? 0) / $count, 1);
    $teacherStat['avg_teaching_explanation'] = round((float) ($teacherStat['avg_teaching_explanation'] ?? 0) / $count, 1);
    $teacherStat['avg_teaching_clarity'] = round((float) ($teacherStat['avg_teaching_clarity'] ?? 0) / $count, 1);
    $teacherStat['avg_punctuality_to_class'] = round((float) ($teacherStat['avg_punctuality_to_class'] ?? 0) / $count, 1);
    $teacherStat['avg_approachable'] = round((float) ($teacherStat['avg_approachable'] ?? 0) / $count, 1);
    $teacherStat['avg_likeable'] = round((float) ($teacherStat['avg_likeable'] ?? 0) / $count, 1);
    $teacherStat['avg_discipline'] = round((float) ($teacherStat['avg_discipline'] ?? 0) / $count, 1);
}
unset($teacherStat);

$selectedTeacherId = 0;
if ($currentPath === '/admin/feedback/analytics' || $currentPath === '/admin/feedback/list') {
    $selectedTeacherId = (int) ($_GET['teacher_id'] ?? 0);
    if ($selectedTeacherId <= 0 && !empty($teacherRows)) {
        $selectedTeacherId = (int) ($teacherRows[0]['id'] ?? 0);
    }
}

$selectedFeedbackRows = [];
if ($selectedTeacherId > 0) {
    foreach (($feedbackRows ?? []) as $feedbackRow) {
        if ((int) ($feedbackRow['teacher_user_id'] ?? 0) === $selectedTeacherId) {
            $selectedFeedbackRows[] = $feedbackRow;
        }
    }
}

$viewName = 'admin-feedback-ratings';
if ($currentPath === '/admin/feedback' || $currentPath === '/admin/feedback/') {
    $viewName = 'admin-feedback-list';
} elseif ($currentPath === '/admin/feedback/list') {
    $viewName = 'admin-feedback-list';
} elseif ($currentPath === '/admin/feedback/analytics') {
    $viewName = 'admin-feedback-analytics';
}

loadView($viewName, [
    'feedbackRows' => $feedbackRows,
    'teacherLookup' => $teacherLookup,
    'teacherRows' => $teacherRows,
    'teacherStats' => $teacherStats,
    'selectedTeacherId' => $selectedTeacherId,
    'selectedFeedbackRows' => $selectedFeedbackRows,
    'currentPath' => $currentPath,
]);
