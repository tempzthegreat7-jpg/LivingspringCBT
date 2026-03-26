<?php

require __DIR__ . '/../helpers.php';
require __DIR__ . '/../Framework/Validation.php';

$testsPassed = 0;
$testsFailed = 0;

$run = function ($name, $fn) use (&$testsPassed, &$testsFailed) {
    try {
        $fn();
        $testsPassed++;
        echo "[PASS] {$name}" . PHP_EOL;
    } catch (Exception $error) {
        $testsFailed++;
        echo "[FAIL] {$name}: " . $error->getMessage() . PHP_EOL;
    }
};

$assertTrue = function ($condition, $message) {
    if (!$condition) {
        throw new Exception($message);
    }
};

$assertSame = function ($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new Exception($message . " (expected: " . var_export($expected, true) . ", actual: " . var_export($actual, true) . ")");
    }
};

$run('normalizeExamTerm accepts valid key', function () use ($assertSame) {
    $assertSame('second_term', normalizeExamTerm('second_term'), 'Term key should be preserved');
});

$run('normalizeExamTerm falls back on invalid key', function () use ($assertSame) {
    $assertSame('first_term', normalizeExamTerm('invalid-key'), 'Invalid term should fallback');
});

$run('normalizeAssessmentTask accepts valid task', function () use ($assertSame) {
    $assertSame('classwork', normalizeAssessmentTask('classwork'), 'Task key should be preserved');
});

$run('questionBankTableNameForTask keeps mysql-safe length', function () use ($assertTrue) {
    $tableName = questionBankTableNameForTask(
        'very_long_subject_name_that_would_normally_overflow_length',
        'ss3',
        'assignment',
        'extremely descriptive custom header text for this assessment context',
        'first_term'
    );

    $assertTrue(strlen($tableName) <= 64, 'MySQL table name must be 64 chars or less');
});

$run('buildAnsweredMap marks answered and unanswered rows', function () use ($assertSame) {
    $questions = [
        ['number' => 1, 'question' => 'Q1'],
        ['number' => 2, 'question' => 'Q2'],
        ['number' => 3, 'question' => 'Q3']
    ];
    $answers = [
        0 => 'A',
        2 => 'D'
    ];
    $map = buildAnsweredMap($questions, $answers);

    $assertSame(true, $map[0]['answered'], 'First question should be answered');
    $assertSame(false, $map[1]['answered'], 'Second question should be unanswered');
    $assertSame(true, $map[2]['answered'], 'Third question should be answered');
    $assertSame(1, $map[0]['number'], 'Displayed numbers should start at one');
    $assertSame(2, $map[1]['number'], 'Displayed numbers should stay sequential');
});

$run('randomizeQuestionSet preserves all questions', function () use ($assertSame, $assertTrue) {
    $questions = [
        ['question' => 'Q1'],
        ['question' => 'Q2'],
        ['question' => 'Q3']
    ];

    $shuffled = randomizeQuestionSet($questions);
    $originalTexts = array_column($questions, 'question');
    $shuffledTexts = array_column($shuffled, 'question');

    sort($originalTexts);
    sort($shuffledTexts);

    $assertSame(count($questions), count($shuffled), 'Randomized set should keep same size');
    $assertTrue($originalTexts === $shuffledTexts, 'Randomized set should keep the same questions');
});

$run('normalizeQuizSecurityState fills expected defaults', function () use ($assertSame, $assertTrue) {
    $state = normalizeQuizSecurityState(['violation_count' => '2', 'requires_admin_unlock' => 1]);

    $assertSame(2, $state['violation_count'], 'Violation count should be normalized to integer');
    $assertTrue($state['requires_admin_unlock'] === true, 'Lock flag should be normalized to boolean true');
    $assertSame(0, $state['locked_at'], 'Missing lock time should default to zero');
    $assertSame('', $state['last_event'], 'Missing event key should default to empty string');
});

$run('Validation::string respects min and max', function () use ($assertSame) {
    $assertSame(true, Validation::string('Teacher', 2, 10), 'String should pass bounds');
    $assertSame(false, Validation::string('A', 2, 10), 'String should fail min bound');
});

echo PHP_EOL;
echo "Passed: {$testsPassed}" . PHP_EOL;
echo "Failed: {$testsFailed}" . PHP_EOL;

exit($testsFailed > 0 ? 1 : 0);
