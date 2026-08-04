<?php

require_once basePath('App/controllers/AdminController/shared.php');

$password = trim((string) ($_POST['access_password'] ?? ''));

if ($password !== adminStudentLoginLogPassword()) {
    Session::setFlashMesssge('error_message', 'Incorrect log access password.');
    redirect('/student/login-log');
}

Session::set('student_login_log_unlocked_until', time() + (15 * 60));
Session::setFlashMesssge('success_message', 'Login log unlocked for 15 minutes.');

redirect('/student/login-log');
