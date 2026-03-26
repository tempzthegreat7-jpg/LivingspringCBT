<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$configAdmin = require basePath('config/config-db.php');
$configExam = require basePath('config/config-db2.php');

$dbAdmin = new Database($configAdmin);
$dbExam = new Database($configExam);

adminEnsureTeacherUsersSchema($dbAdmin);
ensureAssessmentConfigsSchema($dbExam);
ensureExamActivationSchema($dbExam);

$studentClass = adminNormalizeStudentClass($_POST['student_class'] ?? '');
$subject = strtolower(trim((string) ($_POST['subject'] ?? '')));
$adminUserId = (int) (Session::get('user')['id'] ?? 0);

if ($studentClass === '') {
    Session::setFlashMesssge('error_message', 'Invalid class selection.');
    redirect('/admin/exams');
}

if ($subject === '') {
    examSetActiveSubject($dbExam, $studentClass, '', $adminUserId > 0 ? $adminUserId : null);
    Session::setFlashMesssge('success_message', "Exam activation cleared for {$studentClass}.");
    redirect('/admin/exams');
}

$rows = $dbExam->query(
    'SELECT table_name
     FROM assessment_configs
     WHERE LOWER(task_type) = "exam"
       AND subject = :subject
       AND student_class = :student_class',
    [
        'subject' => $subject,
        'student_class' => $studentClass
    ]
)->fetchAll();

$hasQuestions = false;
foreach ($rows as $row) {
    $tableName = (string) ($row['table_name'] ?? '');
    if ($tableName === '') {
        continue;
    }
    $tableExists = $dbExam->query('SHOW TABLES LIKE :table_name', ['table_name' => $tableName])->fetch();
    if (!$tableExists) {
        continue;
    }
    $countRow = $dbExam->query("SELECT COUNT(*) AS total FROM {$tableName}")->fetch();
    if ((int) ($countRow['total'] ?? 0) > 0) {
        $hasQuestions = true;
        break;
    }
}

if (!$hasQuestions) {
    Session::setFlashMesssge('error_message', 'Selected subject has no exam questions for that class.');
    redirect('/admin/exams');
}

$isActive = examToggleActiveSubject($dbExam, $studentClass, $subject, $adminUserId > 0 ? $adminUserId : null);
adminAuditLog($dbAdmin, $isActive ? 'exam.activate' : 'exam.deactivate', 'exam_subject', $studentClass, ($isActive ? 'Activated ' : 'Deactivated ') . $subject . " exam for {$studentClass}", [
    'student_class' => $studentClass,
    'subject' => $subject,
    'is_active' => $isActive
]);

Session::setFlashMesssge(
    'success_message',
    ($isActive ? 'Activated ' : 'Deactivated ')
    . ucwords(str_replace('_', ' ', $subject))
    . " for {$studentClass}."
);
redirect('/admin/exams');
