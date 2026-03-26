<?php

require basePath('Framework/Database.php');
require_once basePath('App/controllers/AdminController/shared.php');
$config = require basePath('config/config-db2.php');
$adminConfig = require basePath('config/config-db.php');

$db = new Database($config);
$adminDb = new Database($adminConfig);
adminEnsureTeacherUsersSchema($adminDb);
ensureAssessmentConfigsSchema($db);
$termOptions = examTermOptions();
$taskOptions = assessmentTaskOptions();
$assignedSubjects = adminSubjectsFromStorage(Session::get('user')['assigned_subjects'] ?? '');
if (empty($assignedSubjects)) {
    // Fallback so page remains usable when assignment data is empty.
    $assignedSubjects = ['english'];
}
$available = adminAvailableSubjects($adminDb);
$subjectOptions = [];
foreach ($assignedSubjects as $subjectKey) {
    $subjectOptions[$subjectKey] = $available[$subjectKey] ?? ucwords(str_replace('_', ' ', $subjectKey));
}
$subjectCategories = adminAssignedSubjectCategoriesFromStorage(Session::get('user')['assigned_subject_categories'] ?? '{}', $subjectOptions);

$subject = strtolower(trim($_GET['subject'] ?? ($assignedSubjects[0] ?? 'english')));
$studentClass = strtoupper(trim($_GET['student_class'] ?? 'SS3'));
$task = normalizeAssessmentTask($_GET['task'] ?? 'exam');
$header = trim((string) ($_GET['header'] ?? ''));
$term = normalizeExamTerm($_GET['term'] ?? 'first_term');
$rawDurationHours = (int) ($_GET['duration_hours'] ?? 0);
$rawDurationMinutes = (int) ($_GET['duration_minutes'] ?? 0);
$hasDurationInRequest = $rawDurationHours > 0 || $rawDurationMinutes > 0;
if (isset($_GET['duration_hours'])) {
    $durationHours = max(0, min(8, $rawDurationHours));
    $durationMinutePart = max(0, min(59, $rawDurationMinutes));
    $durationMinutes = max(0, min(480, ($durationHours * 60) + $durationMinutePart));
} else {
    $durationMinutes = max(0, min(480, $rawDurationMinutes));
}
$hasQuestionLimitInRequest = isset($_GET['question_limit']);
$questionLimit = max(1, min(200, (int) ($_GET['question_limit'] ?? 20)));
$assessmentId = (int) ($_GET['assessment_id'] ?? 0);
$createContext = (int) ($_GET['create_context'] ?? 0) === 1;

if (!in_array($subject, $assignedSubjects, true)) {
    // Never allow selecting subjects outside teacher assignment.
    $subject = $assignedSubjects[0];
}

$subjectCategory = strtolower((string) ($subjectCategories[$subject] ?? 'both'));
$allowedClasses = classOptionsForSubjectCategory($subjectCategory);

if (!in_array($studentClass, $allowedClasses, true)) {
    // Keep class inside allowed category (junior/senior/both).
    $studentClass = $allowedClasses[0];
}

$count = 0;
$questionRows = [];
$resolvedHeader = '';
$durationSeconds = null;
$tableName = '';
$selectedContextId = 0;
$availableContexts = [];

$availableContexts = $db->query(
    'SELECT id, header_text, term_key, duration_seconds, question_limit, table_name, teacher_user_id
     FROM assessment_configs
     WHERE subject = :subject
       AND student_class = :student_class
       AND task_type = :task_type
     ORDER BY updated_at DESC, id DESC',
    [
        'subject' => $subject,
        'student_class' => $studentClass,
        'task_type' => $task
    ]
)->fetchAll();

if ($task === 'exam') {
    // Exams use term as context label.
    $resolvedHeader = $termOptions[$term] ?? '1st Term';
    if ($durationMinutes > 0) {
        $durationSeconds = max(60, $durationMinutes * 60);
    }
} else {
    $resolvedHeader = $header;
    if ($durationMinutes > 0) {
        $durationSeconds = $durationMinutes * 60;
    }
}

if ($task === 'exam') {
    // For exams, auto-pick matching term context if it exists.
    foreach ($availableContexts as $contextRow) {
        $contextTerm = normalizeExamTerm($contextRow['term_key'] ?? 'first_term');
        if ($contextTerm !== $term) {
            continue;
        }

        $selectedContextId = (int) ($contextRow['id'] ?? 0);

        if (!$hasDurationInRequest) {
            $contextDurationSeconds = max(0, (int) ($contextRow['duration_seconds'] ?? 0));
            $durationMinutes = $contextDurationSeconds > 0 ? (int) ceil($contextDurationSeconds / 60) : 0;
            $durationSeconds = $contextDurationSeconds > 0 ? max(60, $contextDurationSeconds) : null;
        }

        if (!$hasQuestionLimitInRequest) {
            $questionLimit = max(1, min(200, (int) ($contextRow['question_limit'] ?? $questionLimit)));
        }

        break;
    }
}

$canLoadContext = !assessmentTaskNeedsHeader($task) || $resolvedHeader !== '';
$shouldPersistContext = false;

if ($task !== 'exam' && $resolvedHeader !== '' && !$hasDurationInRequest) {
    foreach ($availableContexts as $contextRow) {
        $contextHeader = trim((string) ($contextRow['header_text'] ?? ''));
        if ($contextHeader !== $resolvedHeader) {
            continue;
        }

        $contextDurationSeconds = max(0, (int) ($contextRow['duration_seconds'] ?? 0));
        $durationMinutes = $contextDurationSeconds > 0 ? (int) ceil($contextDurationSeconds / 60) : 0;
        $durationSeconds = $contextDurationSeconds > 0 ? $contextDurationSeconds : null;

        if (!$hasQuestionLimitInRequest) {
            $questionLimit = max(1, min(200, (int) ($contextRow['question_limit'] ?? $questionLimit)));
        }
        break;
    }
}

if ($task !== 'exam' && $assessmentId > 0) {
    // For non-exam tasks, explicit context id selects saved header/settings.
    foreach ($availableContexts as $contextRow) {
        if ((int) ($contextRow['id'] ?? 0) === $assessmentId) {
            $selectedContextId = $assessmentId;
            $resolvedHeader = trim((string) ($contextRow['header_text'] ?? ''));
            $durationSeconds = (int) ($contextRow['duration_seconds'] ?? 0);
            $durationMinutes = $durationSeconds > 0 ? (int) ceil($durationSeconds / 60) : 0;
            $questionLimit = max(1, min(200, (int) ($contextRow['question_limit'] ?? $questionLimit)));
            $canLoadContext = $resolvedHeader !== '';
            break;
        }
    }
}

if ($task === 'exam') {
    // Exams should only be created when the teacher explicitly clicks Create,
    // or loaded when a matching saved context already exists.
    $shouldPersistContext = $selectedContextId > 0 || $createContext;
} else {
    $shouldPersistContext = $selectedContextId > 0 || $createContext;
}

if ($canLoadContext && $shouldPersistContext) {
    $tableName = safeTableName(questionBankTableNameForTask($subject, $studentClass, $task, $resolvedHeader, $term));

    if ($tableName !== '_') {
        // Ensure question table exists for this context before preview.
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
            // Keep old tables compatible with image questions.
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
            // Context already exists, update saved settings.
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
            $selectedContextId = (int) $configRow['id'];
        } else {
            // No context row yet, create a new one.
            $db->query(
                'INSERT INTO assessment_configs (teacher_user_id, subject, student_class, task_type, header_text, term_key, duration_seconds, question_limit, table_name)
                 VALUES (:teacher_user_id, :subject, :student_class, :task_type, :header_text, :term_key, :duration_seconds, :question_limit, :table_name)',
                $configParams
            );
            $selectedContextId = (int) $db->connection->lastInsertId();
        }

        $availableContexts = $db->query(
            'SELECT id, header_text, term_key, duration_seconds, question_limit, table_name, teacher_user_id
             FROM assessment_configs
             WHERE subject = :subject
               AND student_class = :student_class
               AND task_type = :task_type
             ORDER BY updated_at DESC, id DESC',
            [
                'subject' => $subject,
                'student_class' => $studentClass,
                'task_type' => $task
            ]
        )->fetchAll();

        $countResult = $db->query("SELECT COUNT(*) AS total FROM {$tableName}")->fetch();
        $count = (int) ($countResult['total'] ?? 0);
        $questionRows = $db->query("SELECT number, question, choice1, choice2, choice3, choice4, correct_answer, image_path FROM {$tableName} ORDER BY number ASC")->fetchAll();
    }
}

$showPreview = $canLoadContext && (isset($_GET['subject']) || isset($_GET['student_class']) || isset($_GET['term']) || isset($_GET['task']) || isset($_GET['header']) || $selectedContextId > 0);
$availableContexts = array_map(function ($row) {
    $row['can_delete'] = true;
    return $row;
}, $availableContexts);

loadView('teacher/add', [
    'subject' => $subject,
    'studentClass' => $studentClass,
    'term' => $term,
    'termOptions' => $termOptions,
    'task' => $task,
    'taskOptions' => $taskOptions,
    'header' => $header,
    'durationMinutes' => $durationMinutes,
    'questionLimit' => $questionLimit,
    'resolvedHeader' => $resolvedHeader,
    'selectedContextId' => $selectedContextId,
    'availableContexts' => $availableContexts,
    'nextNumber' => $count + 1,
    'subjectOptions' => $subjectOptions,
    'subjectCategories' => $subjectCategories,
    'allowedClasses' => $allowedClasses,
    'questions' => $questionRows,
    'showPreview' => $showPreview
]);
