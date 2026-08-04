<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$userSession = Session::get('user') ?? [];
$teacherId = (int) ($userSession['id'] ?? 0);
if ($teacherId <= 0) {
    redirect('/teacher/login');
}

$mainConfig = require basePath('config/config-db.php');
$mainDb = new Database($mainConfig);
adminEnsureTeacherUsersSchema($mainDb);
adminEnsureFeedbackSchema($mainDb);

$feedbackRows = $mainDb->query(
    'SELECT id, teacher_user_id, rating_teaching_explanation, rating_teaching_clarity, rating_punctuality_to_class, rating_approachable, rating_likeable, rating_discipline, rating_overall, feedback_text, moderated_feedback, review_status, submitted_at, approved_at, approved_by_admin_id, approved_teacher_visible
     FROM student_feedback
     WHERE teacher_user_id = :teacher_user_id
       AND approved_teacher_visible = 1
       AND review_status = :review_status
     ORDER BY approved_at DESC, submitted_at DESC',
    [
        'teacher_user_id' => $teacherId,
        'review_status' => 'approved'
    ]
)->fetchAll();

$approvedCount = count($feedbackRows);
$overallTotal = 0.0;
$teachingExplanationTotal = 0.0;
$teachingClarityTotal = 0.0;
$punctualityToClassTotal = 0.0;
$approachableTotal = 0.0;
$likeableTotal = 0.0;
$disciplineTotal = 0.0;
$ratingCount = 0;
foreach ($feedbackRows as $feedbackRow) {
    $ratingCount += 1;
    $overallTotal += (float) ($feedbackRow['rating_overall'] ?? 0);
    $teachingExplanationTotal += (float) ($feedbackRow['rating_teaching_explanation'] ?? 0);
    $teachingClarityTotal += (float) ($feedbackRow['rating_teaching_clarity'] ?? 0);
    $punctualityToClassTotal += (float) ($feedbackRow['rating_punctuality_to_class'] ?? 0);
    $approachableTotal += (float) ($feedbackRow['rating_approachable'] ?? 0);
    $likeableTotal += (float) ($feedbackRow['rating_likeable'] ?? 0);
    $disciplineTotal += (float) ($feedbackRow['rating_discipline'] ?? 0);
}

$overallAverage = $ratingCount > 0 ? round($overallTotal / $ratingCount, 1) : 0.0;
$teachingExplanationAverage = $ratingCount > 0 ? round($teachingExplanationTotal / $ratingCount, 1) : 0.0;
$teachingClarityAverage = $ratingCount > 0 ? round($teachingClarityTotal / $ratingCount, 1) : 0.0;
$punctualityToClassAverage = $ratingCount > 0 ? round($punctualityToClassTotal / $ratingCount, 1) : 0.0;
$approachableAverage = $ratingCount > 0 ? round($approachableTotal / $ratingCount, 1) : 0.0;
$likeableAverage = $ratingCount > 0 ? round($likeableTotal / $ratingCount, 1) : 0.0;
$disciplineAverage = $ratingCount > 0 ? round($disciplineTotal / $ratingCount, 1) : 0.0;

loadView('teacher-feedback', [
    'feedbackRows' => $feedbackRows,
    'ratingCount' => $ratingCount,
    'overallAverage' => $overallAverage,
    'teachingExplanationAverage' => $teachingExplanationAverage,
    'teachingClarityAverage' => $teachingClarityAverage,
    'punctualityToClassAverage' => $punctualityToClassAverage,
    'approachableAverage' => $approachableAverage,
    'likeableAverage' => $likeableAverage,
    'disciplineAverage' => $disciplineAverage,
    'approvedCount' => $approvedCount,
]);
