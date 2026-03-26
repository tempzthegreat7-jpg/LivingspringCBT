<?php


require basePath('Framework/Validation.php');
require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');
$config = require basePath('config/config-db2.php');
$adminConfig = require basePath('config/config-db.php');

$assignedSubjects = adminSubjectsFromStorage(Session::get('user')['assigned_subjects'] ?? '');
if (empty($assignedSubjects)) {
    // Fallback so UI still works if assignment data is missing.
    $assignedSubjects = ['english'];
}

$subject = strtolower(trim($_POST['subject'] ?? ''));
$studentClass = strtoupper(trim($_POST['student_class'] ?? ''));
$task = normalizeAssessmentTask($_POST['task'] ?? 'exam');
$headerInput = trim((string) ($_POST['header'] ?? ''));
$term = normalizeExamTerm($_POST['term'] ?? 'first_term');
$rawDurationMinutes = (int) ($_POST['duration_minutes'] ?? 0);
if (isset($_POST['duration_hours'])) {
    // Convert hours + minutes inputs to total minutes.
    $durationHours = max(0, min(8, (int) ($_POST['duration_hours'] ?? 0)));
    $durationMinutePart = max(0, min(59, $rawDurationMinutes));
    $durationMinutes = max(0, min(480, ($durationHours * 60) + $durationMinutePart));
} else {
    $durationMinutes = max(0, min(480, $rawDurationMinutes));
}
$questionLimit = max(1, min(200, (int) ($_POST['question_limit'] ?? 20)));
$termOptions = examTermOptions();
$taskOptions = assessmentTaskOptions();

$db = new Database($config);
$adminDb = new Database($adminConfig);
adminEnsureTeacherUsersSchema($adminDb);
ensureAssessmentConfigsSchema($db);
$available = adminAvailableSubjects($adminDb);
$subjectOptions = [];
foreach ($assignedSubjects as $subjectKey) {
    $subjectOptions[$subjectKey] = $available[$subjectKey] ?? ucwords(str_replace('_', ' ', $subjectKey));
}
$subjectCategories = adminAssignedSubjectCategoriesFromStorage(Session::get('user')['assigned_subject_categories'] ?? '{}', $subjectOptions);
$subjectCategory = strtolower((string) ($subjectCategories[$subject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);

$resolvedHeader = '';
$durationSeconds = null;
if ($task === 'exam') {
    // Exams use term label as header and require at least 1 minute if set.
    $resolvedHeader = $termOptions[$term] ?? '1st Term';
    if ($durationMinutes > 0) {
        $durationSeconds = max(60, $durationMinutes * 60);
    }
} else {
    // Other task types use typed header.
    $resolvedHeader = $headerInput;
    if ($durationMinutes > 0) {
        $durationSeconds = $durationMinutes * 60;
    }
}

$allowedFields = ['number', 'question', 'choice1', 'choice2', 'choice3', 'choice4', 'correct_answer'];
$newListingData = array_intersect_key($_POST, array_flip($allowedFields));
$oldInput = [];
foreach ($allowedFields as $field) {
    // Keep typed values so form can refill after validation errors.
    $oldInput[$field] = trim((string) ($_POST[$field] ?? ''));
}

$requiredFields = ['number', 'question', 'choice1', 'choice2', 'choice3', 'choice4', 'correct_answer'];

$errors = [];

foreach ($requiredFields as $field) {
    if (empty($newListingData[$field]) || !Validation::string($newListingData[$field])) {
        $errors[$field] = ucfirst($field) . ' is required!';
    }
}

if (!in_array($subject, $assignedSubjects, true)) {
    $errors['subject'] = 'You are not assigned to that subject.';
}

if (!in_array($studentClass, $allowedClasses, true)) {
    $errors['student_class'] = 'Select a valid class.';
}

if (assessmentTaskNeedsHeader($task) && $resolvedHeader === '') {
    $errors['header'] = 'Header is required for this task.';
}

if (assessmentTaskNeedsDuration($task) && $durationMinutes < 1) {
    $errors['duration_minutes'] = 'Duration is required for exam.';
}

if ($questionLimit < 1) {
    $errors['question_limit'] = 'Number of questions must be at least 1.';
}

$tableName = safeTableName(questionBankTableNameForTask($subject, $studentClass, $task, $resolvedHeader, $term));
if ($tableName === '_') {
    $errors['table'] = 'Invalid subject/class combination.';
}

if (empty($errors)) {
    // Accept answer by option number (1-4) or by full text.
    $choices = [
        '1' => trim($newListingData['choice1']),
        '2' => trim($newListingData['choice2']),
        '3' => trim($newListingData['choice3']),
        '4' => trim($newListingData['choice4']),
    ];

    $correctAnswerInput = trim($newListingData['correct_answer']);

    if (array_key_exists($correctAnswerInput, $choices)) {
        $newListingData['correct_answer'] = $choices[$correctAnswerInput];
    } else {
        $matchesChoiceText = false;

        foreach ($choices as $choiceText) {
            if (strtolower($correctAnswerInput) === strtolower($choiceText)) {
                $matchesChoiceText = true;
                break;
            }
        }

        if (!$matchesChoiceText) {
            $errors['correct_answer'] = 'Correct answer must be 1, 2, 3, 4, or the full choice text.';
        }
    }
}

$imagePath = null;
$imageInput = $_FILES['question_image'] ?? null;

if ($imageInput !== null && (int) ($imageInput['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ((int) ($imageInput['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors['question_image'] = 'Image upload failed. Please choose a valid image file.';
    } else {
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
            $errors['question_image'] = 'Image must be between 1 byte and 5MB.';
        } elseif ($tmpFile === '' || $imageMeta === false || !isset($allowedMimeMap[$mimeType])) {
            $errors['question_image'] = 'Only image files (JPG, PNG, WEBP, GIF) are allowed.';
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
                $errors['question_image'] = 'Image MIME check failed. Please upload a valid image.';
            } elseif (!$isWithinDimensions) {
                $errors['question_image'] = 'Image dimensions must be between 40x40 and 4000x4000 pixels.';
            }
        }

        if (!isset($errors['question_image'])) {
            // Save image with random file name to avoid collisions.
            $uploadDir = basePath('public/uploads/questions');

            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                $errors['question_image'] = 'Unable to prepare upload directory.';
            } else {
                $safeToken = bin2hex(random_bytes(8));
                $fileName = 'question_' . date('Ymd_His') . '_' . $safeToken . '.' . $allowedMimeMap[$mimeType];
                $destination = $uploadDir . '/' . $fileName;

                if (!move_uploaded_file($tmpFile, $destination)) {
                    $errors['question_image'] = 'Could not save image. Please try again.';
                } else {
                    $imagePath = '/uploads/questions/' . $fileName;
                }
            }
        }
    }
}

if (!empty($errors)) {
    // Reload add-question page with all state and errors.
    loadView('teacher/add', [
        'subject' => $subject,
        'studentClass' => $studentClass,
        'term' => $term,
        'termOptions' => $termOptions,
        'task' => $task,
        'taskOptions' => $taskOptions,
        'header' => $headerInput,
        'durationMinutes' => $durationMinutes,
        'questionLimit' => $questionLimit,
        'resolvedHeader' => $resolvedHeader,
        'subjectOptions' => $subjectOptions,
        'subjectCategories' => $subjectCategories,
        'allowedClasses' => $allowedClasses,
        'nextNumber' => trim($_POST['number'] ?? ''),
        'oldInput' => $oldInput,
        'errors' => $errors,
    ]);
} else {
    $newListingData['image_path'] = $imagePath;

    $fields = [];

    foreach ($newListingData as $field => $value) {
        $fields[] = $field;
    }

    $fields = implode(', ', $fields);

    $values = [];

    foreach ($newListingData as $field => $value) {
        if ($value === '') {
            $newListingData[$field] = null;
        }

        $values[] = ':' . $field;
    }

    $values = implode(', ', $values);

    $db->query("CREATE TABLE IF NOT EXISTS {$tableName} (number INT(11) PRIMARY KEY, question MEDIUMTEXT NOT NULL, choice1 MEDIUMTEXT NOT NULL, choice2 MEDIUMTEXT NOT NULL, choice3 MEDIUMTEXT NOT NULL, choice4 MEDIUMTEXT NOT NULL, correct_answer MEDIUMTEXT NOT NULL, image_path VARCHAR(255) NULL)");

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

    $configRow = $db->query('SELECT id FROM assessment_configs WHERE table_name = :table_name LIMIT 1', [
        'table_name' => $tableName
    ])->fetch();

    $configParams = [
        'teacher_user_id' => (int) (Session::get('user')['id'] ?? 0),
        'subject' => $subject,
        'student_class' => $studentClass,
        'task_type' => $task,
        'header_text' => $resolvedHeader === '' ? null : $resolvedHeader,
        'term_key' => $task === 'exam' ? $term : null,
        'duration_seconds' => $durationSeconds,
        'question_limit' => $questionLimit,
        'table_name' => $tableName
    ];

    if ($configRow) {
        // Existing context: update its settings.
        $configParams['id'] = (int) $configRow['id'];
        $updateParams = $configParams;
        unset($updateParams['teacher_user_id']);
        $db->query(
            'UPDATE assessment_configs
             SET subject = :subject,
                 student_class = :student_class,
                 task_type = :task_type,
                 header_text = :header_text,
                 term_key = :term_key,
                 duration_seconds = :duration_seconds,
                 question_limit = :question_limit,
                 table_name = :table_name
             WHERE id = :id',
            $updateParams
        );
    } else {
        // New context: create settings row.
        $db->query(
            'INSERT INTO assessment_configs (teacher_user_id, subject, student_class, task_type, header_text, term_key, duration_seconds, question_limit, table_name)
             VALUES (:teacher_user_id, :subject, :student_class, :task_type, :header_text, :term_key, :duration_seconds, :question_limit, :table_name)',
            $configParams
        );
    }

$query = "INSERT INTO {$tableName} ($fields) VALUES ({$values})";

$db->query($query, $newListingData);

Session::setFlashMesssge('success_message', 'Question added successfully');
$selectedContextId = 0;
if ($configRow) {
    $selectedContextId = (int) ($configRow['id'] ?? 0);
} else {
    $selectedContextId = (int) $db->connection->lastInsertId();
}
$redirectHours = (int) floor($durationMinutes / 60);
$redirectMinutes = (int) ($durationMinutes % 60);
$redirectQuery = 'subject=' . urlencode($subject) . '&student_class=' . urlencode($studentClass) . '&task=' . urlencode($task) . '&duration_hours=' . urlencode((string) $redirectHours) . '&duration_minutes=' . urlencode((string) $redirectMinutes) . '&question_limit=' . urlencode((string) $questionLimit);
if ($selectedContextId > 0) {
    $redirectQuery .= '&assessment_id=' . urlencode((string) $selectedContextId);
}
if ($task === 'exam') {
    $redirectQuery .= '&term=' . urlencode($term);
} else {
    $redirectQuery .= '&header=' . urlencode($headerInput);
}
    redirect('/teacher/add-question?' . $redirectQuery);
}
