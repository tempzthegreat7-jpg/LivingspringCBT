<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/student/feedback');
}

$studentSession = Session::get('student') ?? [];
$studentId = (int) ($studentSession['id'] ?? 0);
if ($studentId <= 0) {
    redirect('/student/names');
}

$teacherId = (int) ($_POST['teacher_id'] ?? 0);
$ratings = [
    'teaching_explanation' => max(1, min(5, (int) ($_POST['rating_teaching_explanation'] ?? 0))),
    'teaching_clarity' => max(1, min(5, (int) ($_POST['rating_teaching_clarity'] ?? 0))),
    'punctuality_to_class' => max(1, min(5, (int) ($_POST['rating_punctuality_to_class'] ?? 0))),
    'approachable' => max(1, min(5, (int) ($_POST['rating_approachable'] ?? 0))),
    'likeable' => max(1, min(5, (int) ($_POST['rating_likeable'] ?? 0))),
    'discipline' => max(1, min(5, (int) ($_POST['rating_discipline'] ?? 0))),
    'overall' => max(1, min(5, (int) ($_POST['rating_overall'] ?? 0)))
];
$feedbackText = trim((string) ($_POST['feedback_text'] ?? ''));

if ($teacherId <= 0 || $feedbackText === '') {
    Session::setFlashMessage('error_message', 'Please choose a teacher and write a feedback message.');
    redirect('/student/feedback');
}

if (mb_strlen($feedbackText) > 4000) {
    Session::setFlashMessage('error_message', 'Feedback must be shorter than 4000 characters.');
    redirect('/student/feedback');
}

$mainConfig = require basePath('config/config-db.php');
$mainDb = new Database($mainConfig);
adminEnsureTeacherUsersSchema($mainDb);
adminEnsureStudentUsersSchema($mainDb);
adminEnsureFeedbackSchema($mainDb);

$teacherRow = $mainDb->query('SELECT id FROM teacher_users WHERE id = :id AND LOWER(role) != :admin AND is_active = 1 LIMIT 1', [
    'id' => $teacherId,
    'admin' => 'admin'
])->fetch();

if (!$teacherRow) {
    Session::setFlashMessage('error_message', 'The selected teacher could not be found.');
    redirect('/student/feedback');
}

$token = bin2hex(random_bytes(12));
$mainDb->query(
    'INSERT INTO student_feedback (
        teacher_user_id,
        student_user_id,
        student_identity_token,
        rating_teaching_explanation,
        rating_teaching_clarity,
        rating_punctuality_to_class,
        rating_approachable,
        rating_likeable,
        rating_discipline,
        rating_overall,
        feedback_text,
        review_status,
        submitted_at,
        approved_teacher_visible
    ) VALUES (
        :teacher_user_id,
        :student_user_id,
        :student_identity_token,
        :rating_teaching_explanation,
        :rating_teaching_clarity,
        :rating_punctuality_to_class,
        :rating_approachable,
        :rating_likeable,
        :rating_discipline,
        :rating_overall,
        :feedback_text,
        :review_status,
        NOW(),
        0
    )',
    [
        'teacher_user_id' => $teacherId,
        'student_user_id' => $studentId,
        'student_identity_token' => $token,
        'rating_teaching_explanation' => $ratings['teaching_explanation'],
        'rating_teaching_clarity' => $ratings['teaching_clarity'],
        'rating_punctuality_to_class' => $ratings['punctuality_to_class'],
        'rating_approachable' => $ratings['approachable'],
        'rating_likeable' => $ratings['likeable'],
        'rating_discipline' => $ratings['discipline'],
        'rating_overall' => $ratings['overall'],
        'feedback_text' => $feedbackText,
        'review_status' => 'pending_review'
    ]
);

Session::setFlashMessage('success_message', 'Your feedback has been submitted anonymously and is now awaiting review.');
redirect('/student/feedback');
