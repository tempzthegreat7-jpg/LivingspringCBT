<?php

$router->get('/', 'HomeController@welcome');
$router->get('/roles', 'HomeController@role');


$router->get('/student/names', 'StudentController@name')->only('guest');
$router->get('/student/dashboard', 'StudentController@dashboard')->only('student');
$router->get('/student/question-set', 'StudentController@setting')->only('student');
$router->get('/student/resume', 'StudentController@resume')->only('student');
$router->get('/student/results', 'StudentController@results')->only('student');
$router->get('/student/login-log', 'StudentController@loginLog')->only('student');
$router->get('/student/corrections', 'StudentController@corrections')->only('student');
$router->get('/student/correction', 'StudentController@correction')->only('student');
$router->get('/student/feedback', 'StudentController@feedback')->only('student');
$router->post('/student/feedback', 'StudentController@submitFeedback')->only('student');
$router->post('/student/session/ping', 'StudentController@sessionPing')->only('student');


$router->get('/teacher/subject', 'TeacherController@subject')->only('guest');
$router->get('/teacher/login', 'TeacherController@login')->only('guest');
$router->get('/teacher', 'TeacherController@panel')->only('teacher');
$router->get('/teacher/profile', 'TeacherController@profile')->only('teacher');
$router->get('/teacher/add-question', 'TeacherController@questions')->only('teacher_question');
$router->get('/teacher/add-question/contexts', 'TeacherController@contextList')->only('teacher_question');
$router->get('/teacher/check-question', 'TeacherController@check')->only('teacher_question');
$router->get('/teacher/performance', 'TeacherController@performance')->only('teacher');
$router->get('/teacher/feedback', 'TeacherController@feedback')->only('teacher');
$router->get('/teacher/notify-admin', 'TeacherController@notifyAdmin')->only('teacher');
$router->get('/teacher/notifications/feed', 'TeacherController@notificationsFeed')->only('teacher');
$router->get('/teacher/messages/feed', 'TeacherController@messagesFeed')->only('teacher');

$router->get('/admin', 'AdminController@index');
$router->get('/admin/login', 'AdminController@login')->only('admin_guest');
$router->post('/admin/login', 'AdminController@authenticate')->only('admin_guest');
$router->get('/admin/dashboard', 'AdminController@dashboard')->only('admin');
$router->get('/admin/teachers', 'AdminController@teachers')->only('admin');
$router->get('/admin/students', 'AdminController@students')->only('admin');
$router->get('/admin/notifications', 'AdminController@notifications')->only('admin');
$router->get('/admin/teacher-messages', 'AdminController@teacherMessages')->only('admin');
$router->get('/admin/teacher-messages/feed', 'AdminController@teacherMessagesFeed')->only('admin');
$router->get('/admin/feedback', 'AdminController@feedback')->only('admin');
$router->post('/admin/feedback', 'AdminController@updateFeedback')->only('admin');
$router->get('/admin/feedback/ratings', 'AdminController@feedback')->only('admin');
$router->get('/admin/feedback/list', 'AdminController@feedback')->only('admin');
$router->get('/admin/feedback/analytics', 'AdminController@feedback')->only('admin');
$router->get('/admin/teacher-alerts/feed', 'AdminController@teacherAlertsFeed')->only('admin');
$router->get('/admin/audit', 'AdminController@audit')->only('admin');
$router->get('/admin/student-logins', 'AdminController@studentLogins')->only('admin');
$router->get('/admin/exam-insights', 'AdminController@examInsights')->only('admin');
$router->get('/admin/exams', 'AdminController@exams')->only('admin');
$router->get('/admin/exams/banks', 'AdminController@examBanks')->only('admin');
$router->get('/admin/master-controls', 'AdminController@masterControls')->only('admin');
$router->post('/admin/master-controls/action', 'AdminController@masterControlsAction')->only('admin');
$router->get('/admin/master-controls/presence', 'AdminController@masterControlsPresence')->only('admin');
$router->get('/admin/stream', 'AdminController@stream');
$router->get('/admin/future-plans', 'AdminController@futurePlans')->only('admin');
$router->post('/admin/future-plans', 'AdminController@createFuturePlan')->only('admin');
$router->post('/admin/future-plans/resolve', 'AdminController@resolveFuturePlan')->only('admin');
$router->post('/admin/teachers', 'AdminController@createTeacher')->only('admin');
$router->post('/admin/teachers/update', 'AdminController@updateTeacher')->only('admin');
$router->post('/admin/teachers/delete', 'AdminController@deleteTeacher')->only('admin');
$router->post('/admin/students', 'AdminController@createStudent')->only('admin');
$router->post('/admin/students/class-lock', 'AdminController@updateStudentClassLock')->only('admin');
$router->post('/admin/students/update', 'AdminController@updateStudent')->only('admin');
$router->post('/admin/students/delete', 'AdminController@deleteStudent')->only('admin');
$router->post('/admin/notifications', 'AdminController@createNotification')->only('admin');
$router->post('/admin/notifications/update', 'AdminController@updateNotification')->only('admin');
$router->post('/admin/notifications/delete', 'AdminController@deleteNotification')->only('admin');
$router->post('/admin/teacher-alerts/resolve', 'AdminController@resolveTeacherAlert')->only('admin');
$router->post('/admin/teacher-messages/reply', 'AdminController@replyTeacherMessage')->only('admin');
$router->post('/admin/teacher-messages/send', 'AdminController@sendTeacherMessage')->only('admin');
$router->post('/admin/exams/activate', 'AdminController@activateExam')->only('admin');
$router->post('/admin/maintenance', 'AdminController@updateMaintenanceMode')->only('admin');
$router->post('/admin/subjects/create', 'AdminController@createSubject')->only('admin');
$router->post('/admin/subjects/delete', 'AdminController@deleteSubject')->only('admin');

$router->post('/process', 'StudentController@process')->only('student');
$router->post('/student/names', 'StudentController@register')->only('guest');
$router->post('/student/login-log/unlock', 'StudentController@unlockLoginLog')->only('student');
$router->post('/student/question', 'StudentController@start')->only('student');



$router->post('/teacher/login', 'TeacherController@authenticate')->only('guest');
$router->post('/teacher/add-question', 'TeacherController@add')->only('teacher_question');
$router->post('/teacher/add-question/context-move', 'TeacherController@moveContext')->only('teacher_question');
$router->post('/teacher/add-question/context-delete', 'TeacherController@deleteContext')->only('teacher_question');
$router->post('/teacher/add-question/duration', 'TeacherController@updateDuration')->only('teacher_question');
$router->post('/teacher/check-question/update', 'TeacherController@updateQuestion')->only('teacher_question');
$router->post('/teacher/check-question/delete', 'TeacherController@deleteQuestion')->only('teacher_question');
$router->post('/teacher/notify-admin', 'TeacherController@sendAdminNotification')->only('teacher');
$router->post('/presence/ping', 'TeacherController@presencePing')->only('teacher');
$router->get('/admin/teachers/presence', 'AdminController@presence')->only('admin');
$router->get('/logout', 'TeacherController@logout');
