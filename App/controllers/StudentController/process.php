<?php

require basePath('Framework/Database.php');
$config = require basePath('config/config-db2.php');

$db = new Database($config);
ensureExamAttemptsSchema($db);
ensureStudentExamSessionsSchema($db);

$quiz = Session::get('quiz');

if (!$quiz || empty($quiz['questions'])) {
    // No active quiz session, so send student back.
    redirect('/student/question-set');
}

$questions = $quiz['questions'];
$currentIndex = $quiz['current_index'] ?? 0;
$answers = $quiz['answers'] ?? [];
$flags = $quiz['flags'] ?? [];
$questionTimes = $quiz['question_times'] ?? [];
$total = $quiz['total'] ?? count($questions);
$endsAt = (int) ($quiz['ends_at'] ?? 0);
$durationSeconds = (int) ($quiz['duration_seconds'] ?? 0);
$student = Session::get('student') ?? [];
$exam = Session::get('subjects') ?? [];
$studentName = (string) ($student['name'] ?? 'Unknown Student');
$studentClass = (string) ($student['class'] ?? 'SS3');
$examSubject = strtolower((string) ($exam['subject'] ?? 'unknown'));
$examTask = normalizeAssessmentTask((string) ($exam['task'] ?? 'exam'));
$examAssessmentId = (int) ($exam['assessment_id'] ?? 0);
$examTerm = normalizeExamTerm((string) ($exam['term'] ?? 'first_term'));
$examHeader = (string) ($exam['header'] ?? '');
$activeExamSession = $examTask === 'exam'
    ? studentFindActiveExamSession($db, $studentName, $studentClass)
    : null;

$endsAtOverride = (int) ($_POST['ends_at_override'] ?? 0);
if ($endsAtOverride > 0 && $endsAtOverride !== $endsAt) {
    $endsAt = $endsAtOverride;
    $quiz['ends_at'] = $endsAt;
    Session::set('quiz', $quiz);
}

$globalTimerPause = $examTask === 'exam' ? studentGetGlobalTimerPause($db) : '0';
$isTimedOut = $endsAt > 0 && time() >= $endsAt && $globalTimerPause !== '1';

// Recalculate score from saved answers any time student moves.
$calculateScore = function ($allQuestions, $allAnswers) {
    $currentScore = 0;

    foreach ($allQuestions as $index => $question) {
        $selectedAnswer = trim($allAnswers[$index] ?? '');
        $correctAnswer = trim($question['correct_answer'] ?? '');

        if ($selectedAnswer !== '' && strcasecmp($selectedAnswer, $correctAnswer) === 0) {
            $currentScore++;
        }
    }

    return $currentScore;
};

$storeAttempt = function ($finalQuiz, $finalScore, $didTimeOut) use ($db) {
    // Save result once only, even if student refreshes.
    if (($finalQuiz['attempt_logged'] ?? false) === true) {
        return $finalQuiz;
    }

    $startedAt = (int) ($finalQuiz['started_at'] ?? time());
    $durationSeconds = (int) ($finalQuiz['duration_seconds'] ?? 0);
    $now = time();
    $elapsed = max(0, $now - $startedAt);

    if ($durationSeconds > 0) {
        // For timed tests, do not save time above allowed duration.
        $elapsed = min($elapsed, $durationSeconds);
    }

    $student = Session::get('student');
    $exam = Session::get('subjects');
    $taskType = normalizeAssessmentTask((string) ($exam['task'] ?? 'exam'));

    if ($taskType === 'exam') {
        return $finalQuiz;
    }

    $db->query(
        'INSERT INTO exam_attempts (student_name, student_class, assessment_id, subject, task_type, term_key, header_text, score, total_questions, time_spent_seconds, timed_out, reviewed_before_submit, completed_at)
         VALUES (:student_name, :student_class, :assessment_id, :subject, :task_type, :term_key, :header_text, :score, :total_questions, :time_spent_seconds, :timed_out, :reviewed_before_submit, NOW())',
        [
            'student_name' => (string) ($student['name'] ?? 'Unknown Student'),
            'student_class' => (string) ($student['class'] ?? 'SS3'),
            'assessment_id' => (int) ($exam['assessment_id'] ?? 0),
            'subject' => strtolower((string) ($exam['subject'] ?? 'unknown')),
            'task_type' => $taskType,
            'term_key' => normalizeExamTerm((string) ($exam['term'] ?? 'first_term')),
            'header_text' => (string) ($exam['header'] ?? ''),
            'score' => (int) $finalScore,
            'total_questions' => (int) ($finalQuiz['total'] ?? 0),
            'time_spent_seconds' => (int) $elapsed,
            'timed_out' => $didTimeOut ? 1 : 0,
            'reviewed_before_submit' => !empty($finalQuiz['reviewed_before_submit']) ? 1 : 0
        ]
    );

    $finalQuiz['attempt_logged'] = true;
    return $finalQuiz;
};

$selected = $_POST['choice'] ?? '';
$timeUpFlag = (int) ($_POST['time_up'] ?? 0) === 1;
$nav = $_POST['nav'] ?? 'next';
$jumpIndex = isset($_POST['jump_index']) ? (int) $_POST['jump_index'] : null;
$flagCurrent = array_key_exists('flag_current', $_POST) ? (int) $_POST['flag_current'] === 1 : null;
$reviewedBeforeSubmit = !empty($_POST['reviewed_before_submit']) || !empty($quiz['reviewed_before_submit']);
$questionTimesPayload = json_decode((string) ($_POST['question_times_json'] ?? '[]'), true);

if ($selected !== '') {
    // Save current answer before moving next/previous.
    $answers[$currentIndex] = trim($selected);
}

if ($flagCurrent !== null) {
    $flags[$currentIndex] = $flagCurrent;
}

if (is_array($questionTimesPayload)) {
    foreach ($questionTimesPayload as $index => $seconds) {
        $safeIndex = (int) $index;
        if ($safeIndex < 0) {
            continue;
        }
        $questionTimes[$safeIndex] = max(0, (int) $seconds);
    }
}

$score = $calculateScore($questions, $answers);

if ($isTimedOut || $timeUpFlag) {
    // Timer ended: finalize and show result immediately.
    $finalQuiz = [
        'questions' => $questions,
        'current_index' => $total,
        'answers' => $answers,
        'flags' => $flags,
        'question_times' => $questionTimes,
        'score' => $score,
        'total' => $total,
        'started_at' => $quiz['started_at'] ?? null,
        'ends_at' => $endsAt,
        'duration_seconds' => $durationSeconds,
        'attempt_logged' => (bool) ($quiz['attempt_logged'] ?? false),
        'reviewed_before_submit' => $reviewedBeforeSubmit,
        'last_autosaved_at' => date('Y-m-d H:i:s'),
        'resume_count' => (int) ($quiz['resume_count'] ?? 0)
    ];

    $finalQuiz = $storeAttempt($finalQuiz, $score, true);
    Session::set('quiz', $finalQuiz);

    if ($examTask === 'exam') {
        $sessionRow = $activeExamSession ?: [
            'student_name' => $studentName,
            'student_class' => $studentClass,
            'assessment_id' => $examAssessmentId,
            'subject' => $examSubject,
            'task_type' => $examTask,
            'term_key' => $examTerm,
            'header_text' => $examHeader,
            'questions_json' => json_encode($questions, JSON_UNESCAPED_SLASHES),
            'answers_json' => json_encode($answers, JSON_UNESCAPED_SLASHES),
            'started_at' => (int) ($quiz['started_at'] ?? time()),
            'ends_at' => $endsAt,
            'duration_seconds' => $durationSeconds,
            'id' => 0
        ];
        $sessionRow['answers_json'] = json_encode($answers, JSON_UNESCAPED_SLASHES);
        $sessionRow['flags_json'] = json_encode($flags, JSON_UNESCAPED_SLASHES);
        $sessionRow['question_times_json'] = json_encode($questionTimes, JSON_UNESCAPED_SLASHES);
        $sessionRow['current_index'] = $total;
        $sessionRow['score'] = $score;
        $sessionRow['total_questions'] = $total;
        $sessionRow['attempt_logged'] = (bool) ($finalQuiz['attempt_logged'] ?? false);
        $sessionRow['reviewed_before_submit'] = $reviewedBeforeSubmit;
        studentFinalizeExamSession($db, $sessionRow, true);
        $finalQuiz['attempt_logged'] = true;
        $finalQuiz['reviewed_before_submit'] = $reviewedBeforeSubmit;
        Session::set('quiz', $finalQuiz);
        studentLogExamSessionEvent($db, [
            'session_id' => (int) ($sessionRow['id'] ?? 0),
            'student_name' => $studentName,
            'student_class' => $studentClass,
            'assessment_id' => $examAssessmentId,
            'subject' => $examSubject,
            'task_type' => $examTask,
            'event_key' => 'timed_out_submit',
            'summary' => 'Exam submitted automatically because time expired.',
            'current_index' => $total
        ]);
    }

    loadView('/result', [
        'score' => $score,
        'total' => $total,
        'subject' => Session::get('subjects')['subject'] ?? '',
        'timed_out' => true
    ]);
    return;
}

if ($nav === 'jump' && $jumpIndex !== null) {
    if ($jumpIndex >= 0 && $jumpIndex < $total) {
        $currentIndex = $jumpIndex;
    }
} elseif ($nav === 'previous') {
    if ($currentIndex > 0) {
        $currentIndex--;
    }
} else {
    if ($currentIndex < $total) {
        $currentIndex++;
    }
}

// Persist latest quiz state after navigation.
$updatedQuiz = [
    'questions' => $questions,
    'current_index' => $currentIndex,
    'answers' => $answers,
    'flags' => $flags,
    'question_times' => $questionTimes,
    'score' => $score,
    'total' => $total,
    'started_at' => $quiz['started_at'] ?? null,
    'ends_at' => $endsAt,
    'duration_seconds' => $durationSeconds,
    'attempt_logged' => (bool) ($quiz['attempt_logged'] ?? false),
    'reviewed_before_submit' => $reviewedBeforeSubmit,
    'last_autosaved_at' => date('Y-m-d H:i:s'),
    'resume_count' => (int) ($quiz['resume_count'] ?? 0)
];
Session::set('quiz', $updatedQuiz);

$savedExamSessionId = (int) ($activeExamSession['id'] ?? 0);
if ($examTask === 'exam') {
    $savedExamSessionId = studentSaveExamSession($db, [
        'id' => $savedExamSessionId,
        'student_name' => $studentName,
        'student_class' => $studentClass,
        'assessment_id' => $examAssessmentId,
        'subject' => $examSubject,
        'task_type' => $examTask,
        'term_key' => $examTerm,
        'header_text' => $examHeader,
        'questions' => $questions,
        'answers' => $answers,
        'flags' => $flags,
        'question_times' => $questionTimes,
        'current_index' => $currentIndex,
        'score' => $score,
        'total_questions' => $total,
        'started_at' => (int) ($quiz['started_at'] ?? time()),
        'ends_at' => $endsAt,
        'duration_seconds' => $durationSeconds,
        'last_autosaved_at' => date('Y-m-d H:i:s'),
        'last_activity_at' => date('Y-m-d H:i:s'),
        'attempt_logged' => (bool) ($quiz['attempt_logged'] ?? false),
        'reviewed_before_submit' => $reviewedBeforeSubmit,
        'resume_count' => (int) ($quiz['resume_count'] ?? 0),
        'status' => 'in_progress'
    ]);
}

if ($currentIndex >= $total) {
    // Student reached end, so finalize attempt.
    $finalQuiz = Session::get('quiz');
    $finalQuiz = $storeAttempt($finalQuiz, $score, false);
    Session::set('quiz', $finalQuiz);

    if ($examTask === 'exam') {
        $sessionRow = $activeExamSession ?: [
            'student_name' => $studentName,
            'student_class' => $studentClass,
            'assessment_id' => $examAssessmentId,
            'subject' => $examSubject,
            'task_type' => $examTask,
            'term_key' => $examTerm,
            'header_text' => $examHeader,
            'questions_json' => json_encode($questions, JSON_UNESCAPED_SLASHES),
            'answers_json' => json_encode($answers, JSON_UNESCAPED_SLASHES),
            'started_at' => (int) ($quiz['started_at'] ?? time()),
            'ends_at' => $endsAt,
            'duration_seconds' => $durationSeconds,
            'id' => 0
        ];
        $sessionRow['answers_json'] = json_encode($answers, JSON_UNESCAPED_SLASHES);
        $sessionRow['flags_json'] = json_encode($flags, JSON_UNESCAPED_SLASHES);
        $sessionRow['question_times_json'] = json_encode($questionTimes, JSON_UNESCAPED_SLASHES);
        $sessionRow['current_index'] = $total;
        $sessionRow['score'] = $score;
        $sessionRow['total_questions'] = $total;
        $sessionRow['attempt_logged'] = (bool) ($finalQuiz['attempt_logged'] ?? false);
        $sessionRow['reviewed_before_submit'] = $reviewedBeforeSubmit;
        $sessionRow['id'] = $savedExamSessionId;
        studentFinalizeExamSession($db, $sessionRow, false);
        $finalQuiz['attempt_logged'] = true;
        $finalQuiz['reviewed_before_submit'] = $reviewedBeforeSubmit;
        Session::set('quiz', $finalQuiz);
        studentLogExamSessionEvent($db, [
            'session_id' => (int) ($sessionRow['id'] ?? 0),
            'student_name' => $studentName,
            'student_class' => $studentClass,
            'assessment_id' => $examAssessmentId,
            'subject' => $examSubject,
            'task_type' => $examTask,
            'event_key' => 'submitted',
            'summary' => 'Student submitted the exam.',
            'current_index' => $total,
            'payload' => [
                'reviewed_before_submit' => $reviewedBeforeSubmit,
                'flagged_count' => count(array_filter($flags))
            ]
        ]);
    }

    loadView('/result', [
        'score' => $score,
        'total' => $total,
        'subject' => Session::get('subjects')['subject'] ?? ''
    ]);
    return;
}

$nextQuestion = $questions[$currentIndex];

loadView('/questions', [
    'subject' => Session::get('subjects')['subject'] ?? '',
    'term' => Session::get('subjects')['term'] ?? 'first_term',
    'assessment_task' => Session::get('subjects')['task'] ?? 'exam',
    'assessment_header' => Session::get('subjects')['header'] ?? '',
    'number' => $currentIndex + 1,
    'question' => $nextQuestion['question'],
    'image_path' => $nextQuestion['image_path'] ?? null,
    'choice1' => $nextQuestion['choice1'],
    'choice2' => $nextQuestion['choice2'],
    'choice3' => $nextQuestion['choice3'],
    'choice4' => $nextQuestion['choice4'],
    'selected_choice' => $answers[$currentIndex] ?? '',
    'answered_map' => buildAnsweredMap($questions, $answers, $flags),
    'current_index_zero' => $currentIndex,
    'current' => $currentIndex + 1,
    'total' => $total,
    'is_last' => ($currentIndex + 1) >= $total,
    'exam_ends_at' => $endsAt,
    'exam_duration_seconds' => $durationSeconds,
    'flagged_questions' => $flags,
    'last_autosaved_at' => (string) ($updatedQuiz['last_autosaved_at'] ?? ''),
    'resume_count' => (int) ($updatedQuiz['resume_count'] ?? 0)
]);
