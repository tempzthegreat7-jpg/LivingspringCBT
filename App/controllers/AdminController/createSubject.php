<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');

$config = require basePath('config/config-db.php');
$db = new Database($config);

adminEnsureTeacherUsersSchema($db);

$label = trim($_POST['label'] ?? '');
$category = adminNormalizeSubjectCategory($_POST['category'] ?? 'both');
$wantsJson = isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

$respondJson = function ($payload, $status = 200) {
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json');
    }
    echo json_encode($payload);
    exit;
};

if (!Validation::string($label, 2, 120)) {
    if ($wantsJson) {
        $respondJson(['ok' => false, 'message' => 'Subject name must be between 2 and 120 characters.'], 422);
    }
    Session::setFlashMesssge('error_message', 'Subject name must be between 2 and 120 characters.');
    redirect('/admin/teachers');
}

$baseSlug = adminSubjectSlug($label);
$slug = adminSubjectStorageKey($label, $category);
if ($baseSlug === '' || $slug === '') {
    if ($wantsJson) {
        $respondJson(['ok' => false, 'message' => 'Invalid subject name.'], 422);
    }
    Session::setFlashMesssge('error_message', 'Invalid subject name.');
    redirect('/admin/teachers');
}

// Allow same subject label in different categories, but block duplicates in the same/overlapping category.
$categoryOverlaps = function ($first, $second) {
    $a = adminNormalizeSubjectCategory($first);
    $b = adminNormalizeSubjectCategory($second);
    if ($a === 'both' || $b === 'both') {
        return true;
    }
    return $a === $b;
};

$baseCategories = [
    'english' => 'both',
    'mathematics' => 'both'
];

if (isset($baseCategories[$baseSlug]) && $categoryOverlaps($baseCategories[$baseSlug], $category)) {
    if ($wantsJson) {
        $respondJson(['ok' => false, 'message' => 'That subject already exists in this category.'], 409);
    }
    Session::setFlashMesssge('error_message', 'That subject already exists in this category.');
    redirect('/admin/teachers');
}

$existing = $db->query('SELECT label, category FROM custom_subjects')->fetchAll();
foreach ($existing as $row) {
    $existingBase = adminSubjectSlug($row['label'] ?? '');
    $existingCategory = adminNormalizeSubjectCategory($row['category'] ?? 'both');

    if ($existingBase === $baseSlug && $categoryOverlaps($existingCategory, $category)) {
        if ($wantsJson) {
            $respondJson(['ok' => false, 'message' => 'That subject already exists in this category.'], 409);
        }
        Session::setFlashMesssge('error_message', 'That subject already exists in this category.');
        redirect('/admin/teachers');
    }
}

$db->query(
    'INSERT INTO custom_subjects (slug, label, category) VALUES (:slug, :label, :category)',
    [
        'slug' => $slug,
        'label' => $label,
        'category' => $category
    ]
);
adminAuditLog(
    $db,
    'subject.create',
    'subject',
    $slug,
    'Created subject "' . $label . '"',
    [
        'category' => $category
    ]
);

if ($wantsJson) {
    $respondJson([
        'ok' => true,
        'message' => 'Subject created in ' . ucfirst($category) . ' category.',
        'subject' => [
            'slug' => $slug,
            'label' => $label,
            'category' => $category
        ]
    ]);
}

Session::setFlashMesssge('success_message', 'Subject created in ' . ucfirst($category) . ' category.');
redirect('/admin/teachers');
