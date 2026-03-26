<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);
adminEnsureTeacherAlertsSchema($db);

$teacherUserId = (int) (Session::get('user')['id'] ?? 0);
$title = trim((string) ($_POST['title'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($teacherUserId <= 0) {
    Session::setFlashMesssge('error_message', 'Unable to send request right now.');
    redirect('/teacher/notify-admin');
}

$errors = [];
if (!Validation::string($message, 1, 2000)) {
    $errors[] = 'Message must be between 1 and 2000 characters.';
}

if (!empty($errors)) {
    Session::setFlashMesssge('error_message', implode(' ', $errors));
    Session::setFlashMesssge('old_teacher_alert', [
        'message' => $message
    ]);
    redirect('/teacher/notify-admin');
}

adminCreateTeacherAlert($db, $teacherUserId, $title, $message);
Session::setFlashMesssge('success_message', 'Your message has been sent to admin.');
redirect('/teacher/notify-admin');
