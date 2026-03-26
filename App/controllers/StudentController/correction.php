<?php

$quiz = Session::get('quiz');

if (!$quiz || empty($quiz['questions'])) {
    // Correction page needs a completed quiz in session.
    redirect('/student/corrections');
}

$questions = $quiz['questions'];
$answers = $quiz['answers'] ?? [];
$total = (int) ($quiz['total'] ?? count($questions));
$currentIndex = (int) ($_GET['index'] ?? 0);
$completed = (bool) ($quiz['attempt_logged'] ?? false) || (int) ($quiz['current_index'] ?? 0) >= $total;

if (!$completed || $total < 1) {
    // Block correction until quiz is done.
    redirect('/student/corrections');
}

// Keep correction index within valid range.
if ($currentIndex < 0) {
    $currentIndex = 0;
}
if ($currentIndex >= $total) {
    $currentIndex = $total - 1;
}

$question = $questions[$currentIndex];
$selectedChoice = trim((string) ($answers[$currentIndex] ?? ''));
$correctChoice = trim((string) ($question['correct_answer'] ?? ''));
$isCorrect = $selectedChoice !== '' && strcasecmp($selectedChoice, $correctChoice) === 0;
$subjects = Session::get('subjects') ?? [];

loadView('/questions', [
    'subject' => $subjects['subject'] ?? '',
    'term' => $subjects['term'] ?? 'first_term',
    'assessment_task' => $subjects['task'] ?? 'exam',
    'assessment_header' => $subjects['header'] ?? '',
    'number' => $currentIndex + 1,
    'question' => $question['question'],
    'image_path' => $question['image_path'] ?? null,
    'choice1' => $question['choice1'],
    'choice2' => $question['choice2'],
    'choice3' => $question['choice3'],
    'choice4' => $question['choice4'],
    'selected_choice' => $selectedChoice,
    'current' => $currentIndex + 1,
    'total' => $total,
    'is_last' => ($currentIndex + 1) >= $total,
    'exam_ends_at' => 0,
    'exam_duration_seconds' => 0,
    'review_mode' => true,
    'review_correct_choice' => $correctChoice,
    'review_is_correct' => $isCorrect,
    'review_has_answer' => $selectedChoice !== '',
    'review_index' => $currentIndex
]);
