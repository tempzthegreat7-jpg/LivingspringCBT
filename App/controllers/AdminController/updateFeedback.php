<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/feedback');
}

$mainConfig = require basePath('config/config-db.php');
$mainDb = new Database($mainConfig);
adminEnsureTeacherUsersSchema($mainDb);
adminEnsureFeedbackSchema($mainDb);

$feedbackId = (int) ($_POST['feedback_id'] ?? 0);
$action = strtolower(trim((string) ($_POST['action'] ?? 'save')));
$reviewStatus = normalizeFeedbackStatus($_POST['review_status'] ?? 'pending_review');
$moderatedFeedback = trim((string) ($_POST['moderated_feedback'] ?? ''));
$reviewNotes = trim((string) ($_POST['review_notes'] ?? ''));
$teacherId = (int) ($_POST['teacher_user_id'] ?? 0);
$adminId = (int) (Session::get('user')['id'] ?? 0);

if ($feedbackId <= 0) {
    Session::setFlashMessage('error_message', 'The selected feedback item could not be found.');
    redirect('/admin/feedback');
}

$feedbackRow = $mainDb->query('SELECT id, teacher_user_id, moderated_feedback FROM student_feedback WHERE id = :id LIMIT 1', ['id' => $feedbackId])->fetch();
if (!$feedbackRow) {
    Session::setFlashMessage('error_message', 'The selected feedback item could not be found.');
    redirect('/admin/feedback/list');
}

if ($teacherId > 0) {
    $teacherRow = $mainDb->query('SELECT id FROM teacher_users WHERE id = :id AND LOWER(role) != :admin LIMIT 1', ['id' => $teacherId, 'admin' => 'admin'])->fetch();
    if (!$teacherRow) {
        Session::setFlashMessage('error_message', 'The selected teacher could not be found.');
        redirect('/admin/feedback/list');
    }
}

if ($action === 'approve') {
    $reviewStatus = 'approved';
}

$approvedTeacherVisible = 0;
$approvedAt = null;
$approvedByAdminId = null;
if ($reviewStatus === 'approved') {
    $approvedTeacherVisible = 1;
    $approvedAt = date('Y-m-d H:i:s');
    $approvedByAdminId = $adminId;
}

$moderatedFeedbackPayload = $moderatedFeedback !== '' ? $moderatedFeedback : ((string) ($feedbackRow['moderated_feedback'] ?? '') !== '' ? (string) ($feedbackRow['moderated_feedback'] ?? '') : null);
$feedbackTextPayload = $moderatedFeedbackPayload !== null ? $moderatedFeedbackPayload : (string) ($feedbackRow['feedback_text'] ?? '');

$mainDb->query(
    'UPDATE student_feedback
     SET teacher_user_id = :teacher_user_id,
         feedback_text = :feedback_text,
         moderated_feedback = :moderated_feedback,
         review_status = :review_status,
         review_notes = :review_notes,
         reviewed_at = NOW(),
         reviewed_by_admin_id = :reviewed_by_admin_id,
         approved_at = :approved_at,
         approved_by_admin_id = :approved_by_admin_id,
         approved_teacher_visible = :approved_teacher_visible
     WHERE id = :id',
    [
        'teacher_user_id' => $teacherId > 0 ? $teacherId : (int) ($feedbackRow['teacher_user_id'] ?? 0),
        'feedback_text' => $feedbackTextPayload,
        'moderated_feedback' => $moderatedFeedbackPayload,
        'review_status' => $reviewStatus,
        'review_notes' => $reviewNotes !== '' ? $reviewNotes : null,
        'reviewed_by_admin_id' => $adminId > 0 ? $adminId : null,
        'approved_at' => $approvedAt,
        'approved_by_admin_id' => $approvedByAdminId,
        'approved_teacher_visible' => $approvedTeacherVisible,
        'id' => $feedbackId
    ]
);

Session::setFlashMessage('success_message', 'Feedback review updated successfully.');
redirect('/admin/feedback/list');
