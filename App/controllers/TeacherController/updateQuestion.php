<?php

require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');
$config = require basePath('config/config-db2.php');
$adminConfig = require basePath('config/config-db.php');

$db = new Database($config);
$adminDb = new Database($adminConfig);
adminEnsureTeacherUsersSchema($adminDb);
$assignedSubjects = adminSubjectsFromStorage(Session::get('user')['assigned_subjects'] ?? '');
if (empty($assignedSubjects)) {
    // Fallback keeps checks consistent.
    $assignedSubjects = ['english'];
}
$subjectCategories = adminAssignedSubjectCategoriesFromStorage(Session::get('user')['assigned_subject_categories'] ?? '{}', array_fill_keys($assignedSubjects, true));

$subject = strtolower(trim($_POST['subject'] ?? ''));
$studentClass = strtoupper(trim($_POST['student_class'] ?? ''));
$task = normalizeAssessmentTask($_POST['task'] ?? 'exam');
$header = trim((string) ($_POST['header'] ?? ''));
$term = normalizeExamTerm($_POST['term'] ?? 'first_term');
$originalNumber = (int) ($_POST['original_number'] ?? 0);
$number = (int) ($_POST['number'] ?? 0);
$question = trim((string) ($_POST['question'] ?? ''));
$choice1 = trim((string) ($_POST['choice1'] ?? ''));
$choice2 = trim((string) ($_POST['choice2'] ?? ''));
$choice3 = trim((string) ($_POST['choice3'] ?? ''));
$choice4 = trim((string) ($_POST['choice4'] ?? ''));
$correctAnswer = trim((string) ($_POST['correct_answer'] ?? ''));
$removeImage = (int) ($_POST['remove_image'] ?? 0) === 1;
$redirectQuery = 'subject=' . urlencode($subject) . '&student_class=' . urlencode($studentClass) . '&task=' . urlencode($task);
if ($task === 'exam') {
    $redirectQuery .= '&term=' . urlencode($term);
} else {
    $redirectQuery .= '&header=' . urlencode($header);
}

if (!in_array($subject, $assignedSubjects, true)) {
    // Teachers can update only their assigned subjects.
    Session::setFlashMesssge('error_message', 'You are not assigned to that subject.');
    redirect('/teacher/check-question');
}

$subjectCategory = strtolower((string) ($subjectCategories[$subject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);

if (!in_array($studentClass, $allowedClasses, true)) {
    // Reject classes outside allowed category.
    Session::setFlashMesssge('error_message', 'Invalid class selected.');
    redirect('/teacher/check-question?subject=' . urlencode($subject) . '&student_class=' . urlencode($allowedClasses[0]) . '&task=' . urlencode($task) . ($task === 'exam' ? '&term=' . urlencode($term) : '&header=' . urlencode($header)));
}

$resolvedHeader = $task === 'exam' ? '' : $header;
$tableName = questionBankTableNameForTask($subject, $studentClass, $task, $resolvedHeader, $term);
$tables = $db->query('SHOW TABLES')->fetchAll();
$hasTable = false;

foreach ($tables as $table) {
    if (in_array($tableName, $table, true)) {
        $hasTable = true;
        break;
    }
}

if (!$hasTable) {
    // Stop if target question table does not exist.
    Session::setFlashMesssge('error_message', 'Question bank not found for that class.');
    redirect('/teacher/check-question?' . $redirectQuery);
}

$columnRows = $db->query("SHOW COLUMNS FROM {$tableName}")->fetchAll();
$hasImagePathColumn = false;

foreach ($columnRows as $columnRow) {
    if (strtolower((string) ($columnRow['Field'] ?? '')) === 'image_path') {
        $hasImagePathColumn = true;
        break;
    }
}

if (!$hasImagePathColumn) {
    // Keep older tables compatible with image uploads.
    $db->query("ALTER TABLE {$tableName} ADD COLUMN image_path VARCHAR(255) NULL AFTER correct_answer");
}

if ($originalNumber < 1 || $number < 1) {
    Session::setFlashMesssge('error_message', 'Question number must be 1 or greater.');
    redirect('/teacher/check-question?' . $redirectQuery);
}

$requiredStrings = [
    'Question' => $question,
    'Choice 1' => $choice1,
    'Choice 2' => $choice2,
    'Choice 3' => $choice3,
    'Choice 4' => $choice4,
    'Correct answer' => $correctAnswer
];

foreach ($requiredStrings as $label => $value) {
    // Validate required text fields.
    if (!Validation::string($value)) {
        Session::setFlashMesssge('error_message', $label . ' is required.');
        redirect('/teacher/check-question?' . $redirectQuery);
    }
}

$existingQuestion = $db->query(
    "SELECT number, image_path FROM {$tableName} WHERE number = :number LIMIT 1",
    ['number' => $originalNumber]
)->fetch();

if (!$existingQuestion) {
    Session::setFlashMesssge('error_message', 'The selected question no longer exists.');
    redirect('/teacher/check-question?' . $redirectQuery);
}

if ($number !== $originalNumber) {
    // If number changed, ensure new number is still unique.
    $duplicate = $db->query(
        "SELECT number FROM {$tableName} WHERE number = :number LIMIT 1",
        ['number' => $number]
    )->fetch();

    if ($duplicate) {
        Session::setFlashMesssge('error_message', 'Question number already exists in this class.');
        redirect('/teacher/check-question?' . $redirectQuery);
    }
}

$choices = [
    '1' => $choice1,
    '2' => $choice2,
    '3' => $choice3,
    '4' => $choice4
];

if (array_key_exists($correctAnswer, $choices)) {
    // Accept option number (1-4) and map to its choice text.
    $correctAnswer = $choices[$correctAnswer];
} else {
    // Also allow direct choice text if it matches one option.
    $matchesChoiceText = false;
    foreach ($choices as $choiceText) {
        if (strtolower($correctAnswer) === strtolower($choiceText)) {
            $matchesChoiceText = true;
            $correctAnswer = $choiceText;
            break;
        }
    }

    if (!$matchesChoiceText) {
        Session::setFlashMesssge('error_message', 'Correct answer must be 1, 2, 3, 4, or match a choice text.');
        redirect('/teacher/check-question?' . $redirectQuery);
    }
}

$uploadedImagePath = null;
$imageInput = $_FILES['question_image'] ?? null;

if ($imageInput !== null && (int) ($imageInput['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ((int) ($imageInput['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        Session::setFlashMesssge('error_message', 'Image upload failed. Please choose a valid image file.');
        redirect('/teacher/check-question?' . $redirectQuery);
    }

    $tmpFile = (string) ($imageInput['tmp_name'] ?? '');
    $imageMeta = @getimagesize($tmpFile);
    $mimeType = strtolower((string) ($imageMeta['mime'] ?? ''));
    $fileSize = (int) ($imageInput['size'] ?? 0);
    $maxUploadSizeBytes = 5 * 1024 * 1024;
    $minWidth = 40;
    $minHeight = 40;
    $maxWidth = 4000;
    $maxHeight = 4000;

    $allowedMimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    if ($fileSize <= 0 || $fileSize > $maxUploadSizeBytes) {
        Session::setFlashMesssge('error_message', 'Image must be between 1 byte and 5MB.');
        redirect('/teacher/check-question?' . $redirectQuery);
    } elseif ($tmpFile === '' || $imageMeta === false || !isset($allowedMimeMap[$mimeType])) {
        Session::setFlashMesssge('error_message', 'Only image files (JPG, PNG, WEBP, GIF) are allowed.');
        redirect('/teacher/check-question?' . $redirectQuery);
    } else {
        $detectedMime = '';
        if (function_exists('finfo_open')) {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($fileInfo !== false) {
                $detectedMime = strtolower((string) finfo_file($fileInfo, $tmpFile));
                finfo_close($fileInfo);
            }
        }

        $width = (int) ($imageMeta[0] ?? 0);
        $height = (int) ($imageMeta[1] ?? 0);
        $isValidMimePair = $detectedMime === '' || $detectedMime === $mimeType;
        $isWithinDimensions = $width >= $minWidth
            && $height >= $minHeight
            && $width <= $maxWidth
            && $height <= $maxHeight;

        if (!$isValidMimePair) {
            Session::setFlashMesssge('error_message', 'Image MIME check failed. Please upload a valid image.');
            redirect('/teacher/check-question?' . $redirectQuery);
        } elseif (!$isWithinDimensions) {
            Session::setFlashMesssge('error_message', 'Image dimensions must be between 40x40 and 4000x4000 pixels.');
            redirect('/teacher/check-question?' . $redirectQuery);
        }

        $uploadDir = basePath('public/uploads/questions');
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            Session::setFlashMesssge('error_message', 'Unable to prepare upload directory.');
            redirect('/teacher/check-question?' . $redirectQuery);
        }

        $safeToken = bin2hex(random_bytes(8));
        $fileName = 'question_' . date('Ymd_His') . '_' . $safeToken . '.' . $allowedMimeMap[$mimeType];
        $destination = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmpFile, $destination)) {
            Session::setFlashMesssge('error_message', 'Could not save image. Please try again.');
            redirect('/teacher/check-question?' . $redirectQuery);
        }

        $uploadedImagePath = '/uploads/questions/' . $fileName;
    }
}

$existingImagePath = trim((string) ($existingQuestion['image_path'] ?? ''));
$nextImagePath = $existingImagePath;
if ($removeImage) {
    $nextImagePath = '';
}
if ($uploadedImagePath !== null) {
    $nextImagePath = $uploadedImagePath;
}

$db->query(
    "UPDATE {$tableName}
     SET number = :number,
         question = :question,
         choice1 = :choice1,
         choice2 = :choice2,
         choice3 = :choice3,
         choice4 = :choice4,
         correct_answer = :correct_answer,
         image_path = :image_path
     WHERE number = :original_number",
    [
        'number' => $number,
        'question' => $question,
        'choice1' => $choice1,
        'choice2' => $choice2,
        'choice3' => $choice3,
        'choice4' => $choice4,
        'correct_answer' => $correctAnswer,
        'image_path' => $nextImagePath !== '' ? $nextImagePath : null,
        'original_number' => $originalNumber
    ]
);

if (($removeImage || $uploadedImagePath !== null) && $existingImagePath !== '') {
    if (strpos($existingImagePath, '/uploads/questions/') === 0) {
        $existingFile = basePath('public' . $existingImagePath);
        if (is_file($existingFile)) {
            @unlink($existingFile);
        }
    }
}

Session::setFlashMesssge('success_message', 'Question updated successfully.');
redirect('/teacher/check-question?' . $redirectQuery);
