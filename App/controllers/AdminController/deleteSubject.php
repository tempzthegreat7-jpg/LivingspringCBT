<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureSubjectsSchema($db);

$rawSubject = trim((string) ($_POST['subject'] ?? ''));
$slug = adminSubjectSlug($rawSubject);
$baseSubjects = ['english', 'mathematics'];

$respondJson = function ($payload, $status = 200) {
    // Helper for AJAX calls from subject chips UI.
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json');
    }
    echo json_encode($payload);
    exit;
};

$wantsJson = isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

if ($slug === '') {
    if ($wantsJson) {
        $respondJson(['ok' => false, 'message' => 'Invalid subject selected.'], 400);
    }
    Session::setFlashMesssge('error_message', 'Invalid subject selected.');
    redirect('/admin/teachers');
}

if (in_array($slug, $baseSubjects, true)) {
    // Core subjects are permanent.
    if ($wantsJson) {
        $respondJson(['ok' => false, 'message' => 'Base subjects cannot be deleted.'], 400);
    }
    Session::setFlashMesssge('error_message', 'Base subjects cannot be deleted.');
    redirect('/admin/teachers');
}

$subjectRow = $db->query('SELECT id, slug FROM custom_subjects WHERE slug = :slug LIMIT 1', [
    'slug' => $slug
])->fetch();

if (!$subjectRow) {
    if ($wantsJson) {
        $respondJson(['ok' => false, 'message' => 'Subject not found.'], 404);
    }
    Session::setFlashMesssge('error_message', 'Subject not found.');
    redirect('/admin/teachers');
}

// Remove custom subject row.
$db->query('DELETE FROM custom_subjects WHERE slug = :slug', [
    'slug' => $slug
]);
adminAuditLog(
    $db,
    'subject.delete',
    'subject',
    $slug,
    'Deleted subject "' . $slug . '"'
);

$availableSubjects = adminAvailableSubjects($db);
$users = $db->query('SELECT id, assigned_subjects, assigned_subject_categories FROM teacher_users')->fetchAll();

foreach ($users as $user) {
    // Re-save each user's subject list after removal.
    $subjects = adminSubjectsFromStorage($user['assigned_subjects'] ?? '', $availableSubjects);
    $categories = adminAssignedSubjectCategoriesFromStorage($user['assigned_subject_categories'] ?? '{}', $availableSubjects);

    $db->query(
        'UPDATE teacher_users SET assigned_subjects = :subjects, assigned_subject_categories = :categories WHERE id = :id',
        [
            'subjects' => adminSubjectsToStorage($subjects, $availableSubjects),
            'categories' => adminAssignedSubjectCategoriesToStorage($categories, $availableSubjects),
            'id' => (int) ($user['id'] ?? 0)
        ]
    );
}

if ($wantsJson) {
    $respondJson(['ok' => true, 'slug' => $slug]);
}

Session::setFlashMesssge('success_message', 'Subject deleted successfully.');
redirect('/admin/teachers');
