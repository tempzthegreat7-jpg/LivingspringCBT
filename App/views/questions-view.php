<?php loadPartial('question-head') ?>

<?php
date_default_timezone_set('America/New_York');

$isReviewMode = (bool) ($review_mode ?? false);
$termKey = normalizeExamTerm($term ?? (Session::get('subjects')['term'] ?? 'first_term'));
$termLabel = examTermOptions()[$termKey] ?? '1st Term';
$assessmentTask = normalizeAssessmentTask($assessment_task ?? (Session::get('subjects')['task'] ?? 'exam'));
$taskLabel = assessmentTaskOptions()[$assessmentTask] ?? ucfirst($assessmentTask);
$assessmentHeader = trim((string) ($assessment_header ?? (Session::get('subjects')['header'] ?? '')));
$subjectLabel = ucwords(str_replace('_', ' ', (string) $subject));
$selectedChoice = trim((string) ($selected_choice ?? ''));
$reviewCorrectChoice = trim((string) ($review_correct_choice ?? ''));
$reviewHasAnswer = (bool) ($review_has_answer ?? false);
$reviewIsCorrect = (bool) ($review_is_correct ?? false);
$reviewIndex = max(0, (int) ($review_index ?? (($current ?? 1) - 1)));
$choices = [
    'A' => (string) ($choice1 ?? ''),
    'B' => (string) ($choice2 ?? ''),
    'C' => (string) ($choice3 ?? ''),
    'D' => (string) ($choice4 ?? '')
];
?>

<div class="transition transition-1 is-active"></div>
<div class="exam-shell">
    <div class="heading">
        <?php if ($isReviewMode): ?>
            <a class="leave-room-link" href="/student/question-set">&leftarrow; Exit Correction</a>
        <?php else: ?>
            <a class="leave-room-link" href="/logout">&leftarrow; Leave Room</a>
        <?php endif; ?>

        <div class="info info-compact">
            <span class="meta-chip"><span class="meta-label">Name:</span><span class="meta-value"><?= htmlspecialchars((string) (Session::get('student')['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></span>
            <span class="meta-chip"><span class="meta-label">Subject:</span><span class="meta-value"><?= htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8') ?></span></span>
            <span class="meta-chip"><span class="meta-label">Class:</span><span class="meta-value"><?= htmlspecialchars((string) (Session::get('student')['class'] ?? 'SS3'), ENT_QUOTES, 'UTF-8') ?></span></span>
            <span class="meta-chip"><span class="meta-label">Task:</span><span class="meta-value"><?= htmlspecialchars($taskLabel, ENT_QUOTES, 'UTF-8') ?></span></span>
            <?php if ($assessmentTask === 'exam'): ?>
                <span class="meta-chip"><span class="meta-label">Term:</span><span class="meta-value"><?= htmlspecialchars($termLabel, ENT_QUOTES, 'UTF-8') ?></span></span>
            <?php elseif ($assessmentHeader !== ''): ?>
                <span class="meta-chip"><span class="meta-label">Header:</span><span class="meta-value"><?= htmlspecialchars($assessmentHeader, ENT_QUOTES, 'UTF-8') ?></span></span>
            <?php endif; ?>
            <?php if ($isReviewMode): ?>
                <span class="meta-chip"><span class="meta-label">Mode:</span><span class="meta-value">Correction</span></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$isReviewMode): ?>
        <div
            class="exam-timer"
            id="examTimer"
            data-end-time="<?= (int) ($exam_ends_at ?? 0) ?>"
            data-server-time="<?= time() ?>"
            data-duration="<?= (int) ($exam_duration_seconds ?? 0) ?>">
            <p class="timer-kicker"><?= (int) ($exam_duration_seconds ?? 0) > 0 ? 'Countdown' : 'No Timer' ?></p>
            <p class="timer-value" id="timerValue"><?= (int) ($exam_duration_seconds ?? 0) > 0 ? '--:--' : 'No limit' ?></p>
            <div class="timer-bar">
                <span class="timer-progress" id="timerProgress"></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$isReviewMode): ?>
        <?php
        $answerMapRows = is_array($answered_map ?? null) ? $answered_map : [];
        $activeIndex = max(0, (int) ($current_index_zero ?? (($current ?? 1) - 1)));
        ?>
        <aside class="answer-map" aria-label="Saved answer map">
            <div class="answer-map-head">
                <h2>Saved Answers</h2>
                <p><?= (int) ($current ?? 1) ?> / <?= (int) ($total ?? 0) ?></p>
            </div>
            <div class="answer-map-grid" id="answerMapGrid">
                <?php foreach ($answerMapRows as $item): ?>
                    <?php
                    $itemIndex = (int) ($item['index'] ?? 0);
                    $isAnswered = (bool) ($item['answered'] ?? false);
                    $isFlagged = (bool) ($item['flagged'] ?? false);
                    $classes = 'answer-map-item';
                    if ($isAnswered) {
                        $classes .= ' answered';
                    }
                    if ($isFlagged) {
                        $classes .= ' flagged';
                    }
                    if ($itemIndex === $activeIndex) {
                        $classes .= ' active';
                    }
                    ?>
                    <button
                        type="button"
                        class="<?= htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') ?>"
                        data-jump-index="<?= $itemIndex ?>"
                        aria-label="Question <?= (int) ($item['number'] ?? ($itemIndex + 1)) ?><?= $isAnswered ? ', answered' : ', not answered' ?><?= $isFlagged ? ', flagged' : '' ?>"
                        aria-current="<?= $itemIndex === $activeIndex ? 'step' : 'false' ?>">
                        <?= (int) ($item['number'] ?? ($itemIndex + 1)) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="answer-map-legend">
                <span><i class="answer-map-dot answered"></i> Saved</span>
                <span><i class="answer-map-dot flagged"></i> Flagged</span>
                <span><i class="answer-map-dot active"></i> Current</span>
            </div>
            <p class="answer-map-hint">Click any number to jump. Green means saved, amber means flagged.</p>
        </aside>
    <?php endif; ?>

    <div class="questions-container exam-paper">
        <?php if (!$isReviewMode): ?>
            <div class="exam-tool-row" id="calculatorAnchor">
                <button
                    type="button"
                    class="mini-calculator-toggle"
                    id="calculatorToggle"
                    aria-expanded="false"
                    aria-controls="calculatorPanel">
                    <span class="mini-calculator-toggle-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <rect x="5" y="3" width="14" height="18" rx="3"></rect>
                            <rect x="8" y="6" width="8" height="3" rx="1"></rect>
                            <path d="M8 12h2"></path>
                            <path d="M14 12h2"></path>
                            <path d="M8 16h2"></path>
                            <path d="M14 16h2"></path>
                        </svg>
                    </span>
                    <span class="sr-only">Open calculator</span>
                </button>
            </div>

            <section class="mini-calculator-panel" id="calculatorPanel" aria-label="Mini calculator" hidden>
                <div class="mini-calculator-head" id="calculatorDragHandle">
                    <div>
                        <p class="mini-calculator-kicker">Quick Tool</p>
                        <h2>Calculator</h2>
                    </div>
                    <div class="mini-calculator-head-actions">
                        <button type="button" class="mini-calculator-control" id="calculatorMinimize" aria-label="Minimize calculator">-</button>
                        <button type="button" class="mini-calculator-close" id="calculatorClose" aria-label="Close calculator">x</button>
                    </div>
                </div>

                <div class="mini-calculator-body" id="calculatorBody">
                    <div class="calculator-shell">
                        <div class="calculator-stage">
                            <p class="mini-calculator-note">Keyboard supported. Trig uses degrees and inverse trig returns degrees.</p>

                            <div class="calculator-display-card">
                                <label class="sr-only" for="calculatorDisplay">Calculator display</label>
                                <input type="text" class="mini-calculator-display" id="calculatorDisplay" value="" inputmode="decimal" autocomplete="off" spellcheck="false" placeholder="0" />
                                <div class="calculator-display-meta">
                                    <p class="calculator-memory-status" id="calculatorMemoryStatus" aria-live="polite">Memory: 0</p>
                                    <p class="calculator-display-hint">Type numbers or use the keys</p>
                                </div>
                            </div>

                            <div class="calculator-tool-stack">
                                <div class="calculator-mode-switch" role="tablist" aria-label="Calculator mode">
                                    <button type="button" class="calculator-mode-btn active" data-mode="basic" aria-selected="true">Basic</button>
                                    <button type="button" class="calculator-mode-btn" data-mode="scientific" aria-selected="false">Sci</button>
                                </div>

                                <div class="calculator-quick-tools">
                                    <button type="button" class="calculator-section-toggle" id="calculatorMemoryToggle" aria-expanded="false" aria-controls="calculatorMemoryPanel">Memory</button>
                                    <button type="button" class="calculator-section-toggle" id="calculatorHistoryToggle" aria-expanded="false" aria-controls="calculatorHistoryCard">History</button>
                                </div>
                            </div>

                            <div class="calculator-drawer-stack">
                                <div class="calculator-memory-row" id="calculatorMemoryPanel" aria-label="Calculator memory controls" hidden>
                                    <button type="button" class="calculator-key calculator-key-soft" data-calc-action="memory-clear">MC</button>
                                    <button type="button" class="calculator-key calculator-key-soft" data-calc-action="memory-recall">MR</button>
                                    <button type="button" class="calculator-key calculator-key-soft" data-calc-action="memory-add">M+</button>
                                    <button type="button" class="calculator-key calculator-key-soft" data-calc-action="memory-subtract">M-</button>
                                </div>

                                <section class="calculator-history-card" id="calculatorHistoryCard" aria-label="Recent calculations" hidden>
                                    <div class="calculator-history-head">
                                        <p>History</p>
                                        <button type="button" class="calculator-history-clear" data-calc-action="history-clear">Clear</button>
                                    </div>
                                    <div class="calculator-history-list" id="calculatorHistoryList">
                                        <p class="calculator-history-empty">No calculations yet.</p>
                                    </div>
                                </section>

                                <div class="calculator-scientific-panel" id="calculatorScientificPanel" hidden>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="sin(">sin</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="cos(">cos</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="tan(">tan</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="sqrt(">sqrt</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="asin(">sin^-1</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="acos(">cos^-1</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="atan(">tan^-1</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="log(">log</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="pi">pi</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="^">x^y</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="%">%</button>
                                    <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-action="sign">+/-</button>
                                </div>
                            </div>
                        </div>

                        <div class="calculator-pad-card">
                            <div class="mini-calculator-grid" aria-label="Calculator keys">
                                <button type="button" class="calculator-key calculator-key-soft" data-calc-action="clear">C</button>
                                <button type="button" class="calculator-key calculator-key-soft" data-calc-action="backspace">DEL</button>
                                <button type="button" class="calculator-key calculator-key-soft" data-calc-value="(">(</button>
                                <button type="button" class="calculator-key calculator-key-soft" data-calc-value=")">)</button>
                                <button type="button" class="calculator-key" data-calc-value="7">7</button>
                                <button type="button" class="calculator-key" data-calc-value="8">8</button>
                                <button type="button" class="calculator-key" data-calc-value="9">9</button>
                                <button type="button" class="calculator-key calculator-key-operator" data-calc-value="/">/</button>
                                <button type="button" class="calculator-key" data-calc-value="4">4</button>
                                <button type="button" class="calculator-key" data-calc-value="5">5</button>
                                <button type="button" class="calculator-key" data-calc-value="6">6</button>
                                <button type="button" class="calculator-key calculator-key-operator" data-calc-value="*">*</button>
                                <button type="button" class="calculator-key" data-calc-value="1">1</button>
                                <button type="button" class="calculator-key" data-calc-value="2">2</button>
                                <button type="button" class="calculator-key" data-calc-value="3">3</button>
                                <button type="button" class="calculator-key calculator-key-operator" data-calc-value="-">-</button>
                                <button type="button" class="calculator-key" data-calc-value="0">0</button>
                                <button type="button" class="calculator-key" data-calc-value=".">.</button>
                                <button type="button" class="calculator-key calculator-key-soft" data-calc-value="%">%</button>
                                <button type="button" class="calculator-key calculator-key-operator" data-calc-value="+">+</button>
                                <button type="button" class="calculator-key calculator-key-equals" data-calc-action="equals">=</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <p class="exam-question-number">Question <?= htmlspecialchars((string) $number, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="question"><?= htmlspecialchars((string) $question, ENT_QUOTES, 'UTF-8') ?>?</p>
        <?php if (!empty($image_path ?? null)): ?>
            <div class="question-image-wrap">
                <img src="<?= htmlspecialchars((string) $image_path, ENT_QUOTES, 'UTF-8') ?>" alt="Question visual" class="question-image" />
            </div>
        <?php endif; ?>

        <?php if ($isReviewMode): ?>
            <?php if (!$reviewHasAnswer): ?>
                <p class="review-summary review-summary-neutral">No option was selected for this question.</p>
            <?php elseif ($reviewIsCorrect): ?>
                <p class="review-summary review-summary-correct">Your selected answer is correct.</p>
            <?php else: ?>
                <p class="review-summary review-summary-wrong">Your selected answer is wrong. The correct answer is highlighted below.</p>
            <?php endif; ?>

            <?php foreach ($choices as $letter => $optionText): ?>
                <?php
                $normalizedOption = trim((string) $optionText);
                $isSelectedOption = $selectedChoice !== '' && strcasecmp($selectedChoice, $normalizedOption) === 0;
                $isCorrectOption = $reviewCorrectChoice !== '' && strcasecmp($reviewCorrectChoice, $normalizedOption) === 0;
                $optionClasses = 'choice option-label option-readonly';
                if ($isSelectedOption) {
                    $optionClasses .= ' option-selected';
                }
                if ($isCorrectOption) {
                    $optionClasses .= ' option-correct';
                }
                if ($isSelectedOption && !$isCorrectOption) {
                    $optionClasses .= ' option-wrong';
                }
                ?>
                <div class="<?= $optionClasses ?>">
                    <span class="option-letter"><?= htmlspecialchars($letter, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="option-text"><?= htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($isSelectedOption && $isCorrectOption): ?>
                        <span class="option-state option-state-correct">Picked: Correct</span>
                    <?php elseif ($isSelectedOption && !$isCorrectOption): ?>
                        <span class="option-state option-state-wrong">Picked: Wrong</span>
                    <?php elseif ($isCorrectOption): ?>
                        <span class="option-state option-state-correct">Correct Answer</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="buttons">
                <?php if (($current ?? 1) > 1): ?>
                    <a class="button" id="previous" href="/student/correction?index=<?= max(0, $reviewIndex - 1) ?>">Previous</a>
                <?php endif; ?>
                <?php if (($current ?? 1) < ($total ?? 1)): ?>
                    <a class="button" id="next" href="/student/correction?index=<?= $reviewIndex + 1 ?>">Next</a>
                <?php else: ?>
                    <a class="button button-primary" href="/student/question-set">Finish Review</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form action="/process" method="POST" id="examForm" data-is-last="<?= ($is_last ?? false) ? '1' : '0' ?>">
                <?= csrfField() ?>
                <input type="hidden" name="time_up" value="0" id="timeUpField" />
                <input type="hidden" name="jump_index" value="" id="jumpIndexField" />
                <input type="hidden" name="flag_current" value="<?= !empty(($flagged_questions ?? [])[$activeIndex]) ? '1' : '0' ?>" id="flagCurrentField" />
                <input type="hidden" name="reviewed_before_submit" value="0" id="reviewedBeforeSubmitField" />
                <input type="hidden" name="question_times_json" value="[]" id="questionTimesField" />
                <input type="hidden" name="ends_at_override" value="" id="endsAtOverrideField" />

                <div class="exam-action-strip">
                    <button type="button" class="exam-flag-toggle <?= !empty(($flagged_questions ?? [])[$activeIndex]) ? 'active' : '' ?>" id="flagToggle" aria-pressed="<?= !empty(($flagged_questions ?? [])[$activeIndex]) ? 'true' : 'false' ?>">
                        <?= !empty(($flagged_questions ?? [])[$activeIndex]) ? 'Flagged for Review' : 'Flag for Review' ?>
                    </button>
                    <div class="autosave-status" id="autosaveStatus" aria-live="polite">
                        <?= trim((string) ($last_autosaved_at ?? '')) !== '' ? ('Saved ' . htmlspecialchars((string) $last_autosaved_at, ENT_QUOTES, 'UTF-8')) : 'Autosave ready' ?>
                    </div>
                </div>
                <?php foreach ($choices as $letter => $optionText): ?>
                    <?php
                    $choiceId = 'choice' . strtolower($letter);
                    $isChecked = strcasecmp($selectedChoice, trim((string) $optionText)) === 0;
                    ?>
                    <label class="choice option-label" for="<?= htmlspecialchars($choiceId, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="radio" name="choice" id="<?= htmlspecialchars($choiceId, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8') ?>" <?= $isChecked ? 'checked' : '' ?> required />
                        <span class="option-letter"><?= htmlspecialchars($letter, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="option-text"><?= htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>

                <div class="buttons">
                    <?php if (($current ?? 1) > 1) : ?>
                        <button class="button" id="previous" name="nav" value="previous" formnovalidate>Previous</button>
                    <?php endif; ?>
                    <button class="button button-soft" type="button" id="reviewAnswersButton">Review Answers</button>
                    <button class="button" id="next" name="nav" value="next">
                        <?= ($is_last ?? false) ? ('Submit ' . htmlspecialchars($taskLabel, ENT_QUOTES, 'UTF-8')) : 'Save and Next &rightarrow;' ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isReviewMode): ?>
    <div class="task-loading-overlay" id="markingOverlay" aria-hidden="true">
        <div class="task-loading-card" role="status" aria-live="polite">
            <span class="task-loading-spinner" aria-hidden="true"></span>
            <p class="task-loading-title">Marking Test</p>
            <p class="task-loading-subtitle">Please wait while your responses are being processed.</p>
        </div>
    </div>

    <div class="exam-submit-overlay" id="finalSubmitOverlay" aria-hidden="true">
        <div class="exam-submit-card" role="dialog" aria-modal="true" aria-labelledby="finalSubmitTitle">
            <p class="exam-submit-kicker">Final Step</p>
            <h2 id="finalSubmitTitle">Submit exam now?</h2>
            <p class="exam-submit-copy">You are about to end this exam. After submission, you will not be able to return and change any answer.</p>
            <div class="exam-submit-note">
                <span class="exam-submit-note-badge" aria-hidden="true">!</span>
                <p>Use <strong>Go Back</strong> if you meant to keep working, or confirm only when you are truly finished.</p>
            </div>
            <div class="exam-submit-actions">
                <button type="button" class="button exam-submit-secondary" id="finalSubmitCancel">Go Back</button>
                <button type="button" class="button exam-submit-primary" id="finalSubmitConfirm">Submit Exam</button>
            </div>
        </div>
    </div>

    <div class="exam-submit-overlay" id="reviewAnswersOverlay" aria-hidden="true">
        <div class="exam-submit-card review-answers-card" role="dialog" aria-modal="true" aria-labelledby="reviewAnswersTitle">
            <p class="exam-submit-kicker">Review Answers</p>
            <h2 id="reviewAnswersTitle">Check your paper before you finish</h2>
            <p class="exam-submit-copy">Use this panel to jump quickly to unanswered or flagged questions before final submission.</p>
            <div class="review-answers-summary" id="reviewAnswersSummary"></div>
            <div class="review-answers-grid" id="reviewAnswersGrid"></div>
            <div class="exam-submit-actions">
                <button type="button" class="button exam-submit-secondary" id="reviewAnswersClose">Keep Working</button>
                <button type="button" class="button exam-submit-primary" id="reviewAnswersSubmit">Submit Exam</button>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php if (!$isReviewMode): ?>
    <script>
        (() => {
            const timer = document.getElementById('examTimer');
            const timerValue = document.getElementById('timerValue');
            const timerProgress = document.getElementById('timerProgress');
            const form = document.getElementById('examForm');
            const timeUpField = document.getElementById('timeUpField');
            const jumpIndexField = document.getElementById('jumpIndexField');
            const markingOverlay = document.getElementById('markingOverlay');
            const finalSubmitOverlay = document.getElementById('finalSubmitOverlay');
            const finalSubmitCancel = document.getElementById('finalSubmitCancel');
            const finalSubmitConfirm = document.getElementById('finalSubmitConfirm');
            const reviewAnswersOverlay = document.getElementById('reviewAnswersOverlay');
            const reviewAnswersButton = document.getElementById('reviewAnswersButton');
            const reviewAnswersClose = document.getElementById('reviewAnswersClose');
            const reviewAnswersSubmit = document.getElementById('reviewAnswersSubmit');
            const reviewAnswersGrid = document.getElementById('reviewAnswersGrid');
            const reviewAnswersSummary = document.getElementById('reviewAnswersSummary');
            const answerMapGrid = document.getElementById('answerMapGrid');
            const optionInputs = Array.from(form.querySelectorAll('input[type="radio"][name="choice"]'));
            const flagToggle = document.getElementById('flagToggle');
            const flagCurrentField = document.getElementById('flagCurrentField');
            const reviewedBeforeSubmitField = document.getElementById('reviewedBeforeSubmitField');
            const questionTimesField = document.getElementById('questionTimesField');
            const autosaveStatus = document.getElementById('autosaveStatus');
            const previousButton = document.getElementById('previous');
            const nextButton = document.getElementById('next');
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';
            const calculatorAnchor = document.getElementById('calculatorAnchor');
            const calculatorToggle = document.getElementById('calculatorToggle');
            const calculatorPanel = document.getElementById('calculatorPanel');
            const calculatorClose = document.getElementById('calculatorClose');
            const calculatorDisplay = document.getElementById('calculatorDisplay');
            const calculatorDragHandle = document.getElementById('calculatorDragHandle');
            const calculatorBody = document.getElementById('calculatorBody');
            const calculatorMinimize = document.getElementById('calculatorMinimize');
            const calculatorScientificPanel = document.getElementById('calculatorScientificPanel');
            const calculatorModeButtons = Array.from(document.querySelectorAll('.calculator-mode-btn'));
            const calculatorMemoryToggle = document.getElementById('calculatorMemoryToggle');
            const calculatorHistoryToggle = document.getElementById('calculatorHistoryToggle');
            const calculatorMemoryPanel = document.getElementById('calculatorMemoryPanel');
            const calculatorHistoryCard = document.getElementById('calculatorHistoryCard');
            const calculatorHistoryList = document.getElementById('calculatorHistoryList');
            const calculatorMemoryStatus = document.getElementById('calculatorMemoryStatus');

            if (!timer || !timerValue || !timerProgress || !form || !timeUpField || !jumpIndexField || !markingOverlay) return;

            const endTime = Number(timer.dataset.endTime || 0);
            const serverTime = Number(timer.dataset.serverTime || 0);
            const duration = Number(timer.dataset.duration || 0);
            const isLastQuestion = String(form.dataset.isLast || '0') === '1';
            const activeQuestionIndex = Math.max(0, Number(<?= json_encode((int) ($current_index_zero ?? 0), JSON_UNESCAPED_SLASHES) ?>));
            const totalQuestions = Math.max(0, Number(<?= json_encode((int) ($total ?? 0), JSON_UNESCAPED_SLASHES) ?>));
            const flaggedState = <?= json_encode(array_values(array_map(static function ($value) { return !empty($value); }, (array) ($flagged_questions ?? []))), JSON_UNESCAPED_SLASHES) ?>;
            const questionTimes = <?= json_encode((array) (Session::get('quiz')['question_times'] ?? []), JSON_UNESCAPED_SLASHES) ?>;
            let isSubmittingFinal = false;
            let finalSubmitConfirmed = false;
            let autosaveTimer = null;
            let autosaveInFlight = false;
            let lastQuestionTick = Math.floor(Date.now() / 1000);

            while (flaggedState.length < totalQuestions) {
                flaggedState.push(false);
            }

            const setAutosaveMessage = (message, tone = '') => {
                if (!autosaveStatus) {
                    return;
                }
                autosaveStatus.textContent = message;
                autosaveStatus.classList.remove('saving', 'saved', 'error');
                if (tone !== '') {
                    autosaveStatus.classList.add(tone);
                }
            };

            const captureQuestionTimeDelta = () => {
                const clientNow = Math.floor(Date.now() / 1000);
                const delta = Math.max(0, clientNow - lastQuestionTick);
                if (delta > 0) {
                    questionTimes[activeQuestionIndex] = (questionTimes[activeQuestionIndex] || 0) + delta;
                    lastQuestionTick = clientNow;
                }
            };

            const showMarkingOverlay = () => {
                markingOverlay.classList.add('active');
                markingOverlay.setAttribute('aria-hidden', 'false');
            };

            const setFinalSubmitOverlay = (open) => {
                if (!finalSubmitOverlay) {
                    return;
                }

                finalSubmitOverlay.classList.toggle('active', open);
                finalSubmitOverlay.setAttribute('aria-hidden', open ? 'false' : 'true');

                if (open) {
                    document.body.classList.add('modal-open');
                    window.setTimeout(() => {
                        if (finalSubmitConfirm instanceof HTMLElement) {
                            finalSubmitConfirm.focus();
                        }
                    }, 20);
                    return;
                }

                document.body.classList.remove('modal-open');
                if (nextButton instanceof HTMLElement) {
                    nextButton.focus();
                }
            };

            const setReviewAnswersOverlay = (open) => {
                if (!reviewAnswersOverlay) {
                    return;
                }

                reviewAnswersOverlay.classList.toggle('active', open);
                reviewAnswersOverlay.setAttribute('aria-hidden', open ? 'false' : 'true');
                document.body.classList.toggle('modal-open', open || (finalSubmitOverlay && finalSubmitOverlay.classList.contains('active')));

                if (open) {
                    reviewedBeforeSubmitField.value = '1';
                    renderReviewAnswers();
                    setAutosaveMessage('Saving review state...', 'saving');
                    queueAutosave('review_opened');
                }
            };

            const currentChoiceValue = () => {
                const checked = optionInputs.find((input) => input.checked);
                return checked ? String(checked.value || '') : '';
            };

            const answeredCount = () => optionInputs.reduce((count, input) => {
                if (input.checked) {
                    return count + 1;
                }
                return count;
            }, 0);

            const buildQuestionTimesPayload = () => {
                questionTimes[activeQuestionIndex] = Math.max(0, (questionTimes[activeQuestionIndex] || 0));
                questionTimesField.value = JSON.stringify(questionTimes);
                return questionTimes;
            };

            const queueAutosave = (action = 'autosave') => {
                if (autosaveTimer) {
                    window.clearTimeout(autosaveTimer);
                }

                autosaveTimer = window.setTimeout(() => {
                    if (autosaveInFlight) {
                        return;
                    }

                    autosaveInFlight = true;
                    setAutosaveMessage('Saving...', 'saving');
                    buildQuestionTimesPayload();

                    fetch('/student/session/ping', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                    body: JSON.stringify({
                        _token: csrfToken,
                        current_index: activeQuestionIndex,
                        selected_choice: currentChoiceValue(),
                        flagged: !!flaggedState[activeQuestionIndex],
                        reviewed_before_submit: reviewedBeforeSubmitField.value === '1',
                        question_times: questionTimes,
                        time_bonus_seconds: (window.__cbtTimeBonusSeconds || 0),
                        action
                    })
                    }).then((response) => response.json())
                        .then((payload) => {
                            if (!payload || payload.ok !== true) {
                                throw new Error('Autosave failed');
                            }
                            setAutosaveMessage('Saved just now', 'saved');
                        })
                        .catch(() => {
                            setAutosaveMessage('Autosave pending...', 'error');
                        })
                        .finally(() => {
                            autosaveInFlight = false;
                        });
                }, action === 'autosave' ? 320 : 120);
            };

            const updateMapVisualState = () => {
                if (!answerMapGrid) {
                    return;
                }
                const answeredLookup = new Set();
                optionInputs.forEach((input) => {
                    if (input.checked) {
                        answeredLookup.add(activeQuestionIndex);
                    }
                });

                answerMapGrid.querySelectorAll('[data-jump-index]').forEach((node) => {
                    const itemIndex = Number(node.getAttribute('data-jump-index') || -1);
                    node.classList.toggle('active', itemIndex === activeQuestionIndex);
                    if (itemIndex === activeQuestionIndex) {
                        node.classList.toggle('answered', currentChoiceValue() !== '');
                        node.classList.toggle('flagged', !!flaggedState[itemIndex]);
                    }
                });
            };

            const renderReviewAnswers = () => {
                if (!reviewAnswersGrid || !reviewAnswersSummary) {
                    return;
                }

                const items = [];
                let answeredTotal = 0;
                let flaggedTotal = 0;

                for (let index = 0; index < totalQuestions; index += 1) {
                    const mapButton = answerMapGrid ? answerMapGrid.querySelector(`[data-jump-index="${index}"]`) : null;
                    const isAnswered = index === activeQuestionIndex ? currentChoiceValue() !== '' : !!(mapButton && mapButton.classList.contains('answered'));
                    const isFlagged = !!flaggedState[index];
                    if (isAnswered) {
                        answeredTotal += 1;
                    }
                    if (isFlagged) {
                        flaggedTotal += 1;
                    }
                    items.push(`
                        <button type="button" class="review-answer-chip ${isAnswered ? 'answered' : 'unanswered'} ${isFlagged ? 'flagged' : ''} ${index === activeQuestionIndex ? 'active' : ''}" data-review-jump="${index}">
                            <span>Q${index + 1}</span>
                            <small>${isAnswered ? 'Saved' : 'Pending'}${isFlagged ? ' - Flagged' : ''}</small>
                        </button>
                    `);
                }

                reviewAnswersSummary.innerHTML = `
                    <div class="review-answer-stat"><strong>${answeredTotal}</strong><span>Saved</span></div>
                    <div class="review-answer-stat"><strong>${Math.max(0, totalQuestions - answeredTotal)}</strong><span>Pending</span></div>
                    <div class="review-answer-stat"><strong>${flaggedTotal}</strong><span>Flagged</span></div>
                `;
                reviewAnswersGrid.innerHTML = items.join('');
            };

            const submitWithMarkingDelay = (force = false) => {
                if (isSubmittingFinal) {
                    return;
                }
                isSubmittingFinal = true;
                showMarkingOverlay();
                window.setTimeout(() => {
                    form.submit();
                }, 180);
            };

            if (endTime <= 0 || duration <= 0) {
                timerProgress.style.width = '0%';
            } else {
                const initialClientNow = Math.floor(Date.now() / 1000);
                const serverOffsetSeconds = serverTime > 0 ? (serverTime - initialClientNow) : 0;

                const formatTime = (secondsLeft) => {
                    const safe = Math.max(0, secondsLeft);
                    const hours = Math.floor(safe / 3600);
                    const minutes = Math.floor((safe % 3600) / 60);
                    const seconds = safe % 60;

                    if (hours > 0) {
                        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    }

                    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                };

                const submitExpiredQuiz = () => {
                    timeUpField.value = '1';
                    submitWithMarkingDelay(true);
                };

                let intervalId = null;

                const tick = () => {
                    const now = Math.floor(Date.now() / 1000) + serverOffsetSeconds;
                    const remaining = endTime - now;
                    const elapsed = Math.min(duration, Math.max(0, duration - remaining));
                    const percent = Math.max(0, Math.min(100, (elapsed / duration) * 100));
                    captureQuestionTimeDelta();

                    timerValue.textContent = formatTime(remaining);
                    timerProgress.style.width = `${percent}%`;

                    if (remaining <= 60) {
                        timer.classList.add('danger');
                    }

                    if (remaining <= 0) {
                        timer.classList.add('expired');
                        if (intervalId) {
                            clearInterval(intervalId);
                        }
                        submitExpiredQuiz();
                    }
                };

                tick();
                intervalId = setInterval(tick, 1000);
            }

            if (duration <= 0) {
                window.setInterval(captureQuestionTimeDelta, 1000);
            }

            const flashSelectedOption = (input) => {
                if (!(input instanceof HTMLInputElement)) {
                    return;
                }

                optionInputs.forEach((radio) => {
                    const optionCard = radio.closest('.option-label');
                    if (optionCard) {
                        optionCard.classList.toggle('option-selected-live', radio === input && radio.checked);
                        optionCard.classList.remove('option-confirming');
                    }
                });

                const selectedCard = input.closest('.option-label');
                if (!selectedCard || !input.checked) {
                    return;
                }

                void selectedCard.offsetWidth;
                selectedCard.classList.add('option-confirming');
            };

            optionInputs.forEach((input) => {
                if (input.checked) {
                    flashSelectedOption(input);
                }

                input.addEventListener('change', () => {
                    flashSelectedOption(input);
                    updateMapVisualState();
                    renderReviewAnswers();
                    queueAutosave('autosave');
                });
            });

            form.addEventListener('submit', (event) => {
                if (isSubmittingFinal) {
                    return;
                }

                const submitter = event.submitter || null;
                const navAction = submitter ? String(submitter.value || '') : '';
                const shouldMark = isLastQuestion && navAction === 'next';
                const shouldConfirmFinal = shouldMark && timeUpField.value !== '1' && !finalSubmitConfirmed;
                buildQuestionTimesPayload();

                if (shouldConfirmFinal) {
                    event.preventDefault();
                    setFinalSubmitOverlay(true);
                    return;
                }

                if (!shouldMark) {
                    return;
                }

                event.preventDefault();
                submitWithMarkingDelay();
            });

            if (answerMapGrid) {
                answerMapGrid.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-jump-index]');
                    if (!button) {
                        return;
                    }

                    const nextIndex = Number(button.getAttribute('data-jump-index') || -1);
                    if (Number.isNaN(nextIndex) || nextIndex < 0) {
                        return;
                    }

                    jumpIndexField.value = String(nextIndex);
                    const nextButton = document.createElement('button');
                    nextButton.type = 'submit';
                    nextButton.name = 'nav';
                    nextButton.value = 'jump';
                    nextButton.setAttribute('formnovalidate', 'formnovalidate');
                    nextButton.hidden = true;
                    form.appendChild(nextButton);
                    nextButton.click();
                });
            }

            document.addEventListener('contextmenu', (event) => {
                event.preventDefault();
            });

            ['copy', 'cut', 'paste'].forEach((eventName) => {
                document.addEventListener(eventName, (event) => {
                    event.preventDefault();
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) {
                    return;
                }

                if (finalSubmitOverlay && finalSubmitOverlay.classList.contains('active')) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        setFinalSubmitOverlay(false);
                    }
                    return;
                }

                if (reviewAnswersOverlay && reviewAnswersOverlay.classList.contains('active')) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        setReviewAnswersOverlay(false);
                    }
                    return;
                }

                const target = event.target;
                if (target instanceof HTMLElement) {
                    if (target.closest('#calculatorPanel')) {
                        return;
                    }

                    const tagName = target.tagName;
                    const inputType = target instanceof HTMLInputElement ? String(target.type || '').toLowerCase() : '';
                    const isTextEntryControl =
                        target.isContentEditable ||
                        tagName === 'TEXTAREA' ||
                        tagName === 'SELECT' ||
                        (tagName === 'INPUT' && !['radio', 'checkbox', 'button', 'submit'].includes(inputType));

                    if (isTextEntryControl) {
                        return;
                    }
                }

                const pressedKey = String(event.key || '').toUpperCase();
                if (pressedKey === 'ARROWLEFT' || pressedKey === 'P') {
                    if (previousButton instanceof HTMLElement) {
                        event.preventDefault();
                        previousButton.click();
                    }
                    return;
                }

                if (pressedKey === 'ARROWRIGHT' || pressedKey === 'N' || pressedKey === 'ENTER') {
                    if (nextButton instanceof HTMLElement) {
                        event.preventDefault();
                        nextButton.click();
                    }
                    return;
                }

                if (!['A', 'B', 'C', 'D'].includes(pressedKey)) {
                    return;
                }

                const shortcutInput = document.getElementById(`choice${pressedKey.toLowerCase()}`);
                if (!(shortcutInput instanceof HTMLInputElement) || shortcutInput.disabled) {
                    return;
                }

                event.preventDefault();
                shortcutInput.checked = true;
                shortcutInput.dispatchEvent(new Event('change', { bubbles: true }));
                shortcutInput.focus();
            });

            if (finalSubmitCancel instanceof HTMLButtonElement) {
                finalSubmitCancel.addEventListener('click', () => {
                    finalSubmitConfirmed = false;
                    setFinalSubmitOverlay(false);
                });
            }

            if (finalSubmitConfirm instanceof HTMLButtonElement) {
                finalSubmitConfirm.addEventListener('click', () => {
                    finalSubmitConfirmed = true;
                    reviewedBeforeSubmitField.value = '1';
                    setFinalSubmitOverlay(false);
                    if (nextButton instanceof HTMLElement) {
                        nextButton.click();
                    }
                });
            }

            if (finalSubmitOverlay) {
                finalSubmitOverlay.addEventListener('click', (event) => {
                    if (event.target === finalSubmitOverlay) {
                        finalSubmitConfirmed = false;
                        setFinalSubmitOverlay(false);
                    }
                });
            }

            if (reviewAnswersButton instanceof HTMLButtonElement) {
                reviewAnswersButton.addEventListener('click', () => {
                    setReviewAnswersOverlay(true);
                });
            }

            if (reviewAnswersClose instanceof HTMLButtonElement) {
                reviewAnswersClose.addEventListener('click', () => {
                    setReviewAnswersOverlay(false);
                });
            }

            if (reviewAnswersSubmit instanceof HTMLButtonElement) {
                reviewAnswersSubmit.addEventListener('click', () => {
                    reviewedBeforeSubmitField.value = '1';
                    finalSubmitConfirmed = true;
                    setReviewAnswersOverlay(false);
                    if (nextButton instanceof HTMLElement) {
                        nextButton.click();
                    }
                });
            }

            if (reviewAnswersOverlay) {
                reviewAnswersOverlay.addEventListener('click', (event) => {
                    if (event.target === reviewAnswersOverlay) {
                        setReviewAnswersOverlay(false);
                    }
                });
            }

            if (reviewAnswersGrid) {
                reviewAnswersGrid.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-review-jump]');
                    if (!button) {
                        return;
                    }

                    const nextIndex = Number(button.getAttribute('data-review-jump') || -1);
                    if (Number.isNaN(nextIndex) || nextIndex < 0) {
                        return;
                    }

                    setReviewAnswersOverlay(false);
                    jumpIndexField.value = String(nextIndex);
                    const jumpButton = document.createElement('button');
                    jumpButton.type = 'submit';
                    jumpButton.name = 'nav';
                    jumpButton.value = 'jump';
                    jumpButton.setAttribute('formnovalidate', 'formnovalidate');
                    jumpButton.hidden = true;
                    form.appendChild(jumpButton);
                    jumpButton.click();
                });
            }

            if (flagToggle instanceof HTMLButtonElement && flagCurrentField) {
                flagToggle.addEventListener('click', () => {
                    const nextState = !(flagCurrentField.value === '1');
                    flagCurrentField.value = nextState ? '1' : '0';
                    flaggedState[activeQuestionIndex] = nextState;
                    flagToggle.classList.toggle('active', nextState);
                    flagToggle.setAttribute('aria-pressed', nextState ? 'true' : 'false');
                    flagToggle.textContent = nextState ? 'Flagged for Review' : 'Flag for Review';
                    updateMapVisualState();
                    renderReviewAnswers();
                    queueAutosave('flag_toggled');
                });
            }

            window.addEventListener('beforeunload', () => {
                buildQuestionTimesPayload();
            });

            buildQuestionTimesPayload();
            renderReviewAnswers();
            updateMapVisualState();

            if (calculatorAnchor && calculatorToggle && calculatorPanel && calculatorClose && calculatorDisplay && calculatorBody && calculatorMinimize && calculatorScientificPanel && calculatorHistoryList && calculatorMemoryStatus && calculatorMemoryToggle && calculatorHistoryToggle && calculatorMemoryPanel && calculatorHistoryCard) {
                let calculatorOpen = false;
                let calculatorMinimized = false;
                let resultLocked = false;
                let calculatorMode = 'basic';
                let calculatorMemory = 0;
                let calculatorHistory = [];
                let memoryPanelOpen = false;
                let historyPanelOpen = false;
                let userPosition = null;
                let dragState = null;

                const setCalculatorOpen = (open) => {
                    calculatorOpen = open;
                    calculatorPanel.classList.toggle('is-open', open);
                    calculatorToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    calculatorPanel.hidden = !open;
                    calculatorToggle.classList.toggle('is-active', open);
                };

                const positionCalculator = () => {
                    if (!calculatorOpen) {
                        return;
                    }

                    const panelWidth = Math.min(380, window.innerWidth - 24);
                    let left;
                    let top;

                    if (userPosition) {
                        left = Math.min(Math.max(12, userPosition.left), Math.max(12, window.innerWidth - panelWidth - 12));
                        top = Math.min(Math.max(12, userPosition.top), Math.max(12, window.innerHeight - calculatorPanel.offsetHeight - 12));
                    } else {
                        const anchorRect = calculatorAnchor.getBoundingClientRect();
                        left = Math.min(
                            Math.max(12, anchorRect.right - panelWidth),
                            Math.max(12, window.innerWidth - panelWidth - 12)
                        );
                        top = Math.min(
                            Math.max(12, anchorRect.bottom + 10),
                            Math.max(12, window.innerHeight - calculatorPanel.offsetHeight - 12)
                        );
                    }

                    calculatorPanel.style.left = `${left}px`;
                    calculatorPanel.style.top = `${top}px`;
                    calculatorPanel.style.width = `${panelWidth}px`;
                };

                const setCalculatorMinimized = (minimized) => {
                    calculatorMinimized = minimized;
                    calculatorPanel.classList.toggle('is-minimized', minimized);
                    calculatorBody.hidden = minimized;
                    calculatorMinimize.textContent = minimized ? '+' : '-';
                    calculatorMinimize.setAttribute('aria-label', minimized ? 'Restore calculator' : 'Minimize calculator');
                    window.setTimeout(positionCalculator, 0);
                };

                const setCalculatorMode = (mode) => {
                    calculatorMode = mode === 'scientific' ? 'scientific' : 'basic';
                    calculatorScientificPanel.hidden = calculatorMode !== 'scientific';
                    calculatorModeButtons.forEach((button) => {
                        const active = button.getAttribute('data-mode') === calculatorMode;
                        button.classList.toggle('active', active);
                        button.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    window.setTimeout(positionCalculator, 0);
                };

                const setMemoryPanelOpen = (open) => {
                    memoryPanelOpen = !!open;
                    calculatorMemoryPanel.hidden = !memoryPanelOpen;
                    calculatorMemoryToggle.setAttribute('aria-expanded', memoryPanelOpen ? 'true' : 'false');
                    window.setTimeout(positionCalculator, 0);
                };

                const setHistoryPanelOpen = (open) => {
                    historyPanelOpen = !!open;
                    calculatorHistoryCard.hidden = !historyPanelOpen;
                    calculatorHistoryToggle.setAttribute('aria-expanded', historyPanelOpen ? 'true' : 'false');
                    window.setTimeout(positionCalculator, 0);
                };

                const renderMemory = () => {
                    calculatorMemoryStatus.textContent = `Memory: ${Number(calculatorMemory.toFixed(10))}`;
                };

                const renderHistory = () => {
                    if (calculatorHistory.length === 0) {
                        calculatorHistoryList.innerHTML = '<p class="calculator-history-empty">No calculations yet.</p>';
                        return;
                    }

                    calculatorHistoryList.innerHTML = calculatorHistory.map((entry, index) => `
                        <button type="button" class="calculator-history-item" data-history-index="${index}">
                            <span class="calculator-history-expression">${entry.expression}</span>
                            <span class="calculator-history-result">${entry.result}</span>
                        </button>
                    `).join('');
                };

                const addHistoryEntry = (expression, result) => {
                    const cleanExpression = String(expression || '').trim();
                    if (cleanExpression === '') {
                        return;
                    }

                    calculatorHistory.unshift({
                        expression: cleanExpression,
                        result: String(result)
                    });
                    calculatorHistory = calculatorHistory.slice(0, 6);
                    renderHistory();
                };

                const appendToDisplay = (value) => {
                    if (calculatorDisplay.value === 'Error') {
                        calculatorDisplay.value = '';
                    }

                    if (resultLocked) {
                        if (/^[+\-*/%^]$/.test(value)) {
                            calculatorDisplay.value = `${calculatorDisplay.value}${value}`;
                            resultLocked = false;
                            return;
                        }

                        calculatorDisplay.value = '';
                        resultLocked = false;
                    }

                    calculatorDisplay.value = `${calculatorDisplay.value}${value}`;
                };

                const sanitizeTypedExpression = (raw) => String(raw || '').replace(/[^0-9+\-*/().%^ ]+/g, '');

                const tokenizeExpression = (raw) => {
                    const tokens = [];
                    const source = String(raw || '').replace(/\s+/g, '');
                    const tokenPattern = /asin|acos|atan|sin|cos|tan|sqrt|log|pi|\d*\.\d+|\d+|[()+\-*/%^]/g;
                    let match;

                    while ((match = tokenPattern.exec(source)) !== null) {
                        tokens.push(match[0]);
                    }

                    if (tokens.join('') !== source) {
                        throw new Error('Invalid token');
                    }

                    return tokens;
                };

                const isValueToken = (token) => /^(?:\d*\.\d+|\d+|pi|\))$/.test(token);
                const isLeadingToken = (token) => /^(?:\d*\.\d+|\d+|pi|\(|asin|acos|atan|sin|cos|tan|sqrt|log)$/.test(token);

                const buildExpression = (tokens) => {
                    let expression = '';

                    tokens.forEach((token, index) => {
                        const previous = index > 0 ? tokens[index - 1] : '';
                        const needsImplicitMultiply = previous && isValueToken(previous) && isLeadingToken(token);
                        if (needsImplicitMultiply) {
                            expression += '*';
                        }

                        if (token === 'pi') {
                            expression += 'Math.PI';
                            return;
                        }

                        if (token === 'sin') {
                            expression += 'degSin';
                            return;
                        }

                        if (token === 'asin') {
                            expression += 'degAsin';
                            return;
                        }

                        if (token === 'cos') {
                            expression += 'degCos';
                            return;
                        }

                        if (token === 'acos') {
                            expression += 'degAcos';
                            return;
                        }

                        if (token === 'tan') {
                            expression += 'degTan';
                            return;
                        }

                        if (token === 'atan') {
                            expression += 'degAtan';
                            return;
                        }

                        if (token === 'sqrt') {
                            expression += 'Math.sqrt';
                            return;
                        }

                        if (token === 'log') {
                            expression += 'Math.log10';
                            return;
                        }

                        if (token === '^') {
                            expression += '**';
                            return;
                        }

                        expression += token;
                    });

                    return expression;
                };

                const evaluateExpression = () => {
                    const raw = String(calculatorDisplay.value || '');
                    if (!raw.trim()) {
                        calculatorDisplay.value = '';
                        return;
                    }

                    try {
                        const tokens = tokenizeExpression(raw);
                        const expression = buildExpression(tokens);
                        const degSin = (value) => Math.sin((Number(value) * Math.PI) / 180);
                        const degAsin = (value) => (Math.asin(Number(value)) * 180) / Math.PI;
                        const degCos = (value) => Math.cos((Number(value) * Math.PI) / 180);
                        const degAcos = (value) => (Math.acos(Number(value)) * 180) / Math.PI;
                        const degTan = (value) => Math.tan((Number(value) * Math.PI) / 180);
                        const degAtan = (value) => (Math.atan(Number(value)) * 180) / Math.PI;
                        const result = Function('degSin', 'degAsin', 'degCos', 'degAcos', 'degTan', 'degAtan', `"use strict"; return (${expression});`)(
                            degSin,
                            degAsin,
                            degCos,
                            degAcos,
                            degTan,
                            degAtan
                        );

                        if (!Number.isFinite(result)) {
                            throw new Error('Invalid result');
                        }

                        const roundedResult = Math.abs(result) < 1e-12 ? 0 : Number(result.toFixed(10));
                        calculatorDisplay.value = String(roundedResult);
                        addHistoryEntry(raw, roundedResult);
                        resultLocked = true;
                    } catch (error) {
                        calculatorDisplay.value = 'Error';
                        resultLocked = true;
                    }
                };

                const readCurrentNumber = () => {
                    const value = Number(calculatorDisplay.value || 0);
                    return Number.isFinite(value) ? value : 0;
                };

                calculatorToggle.addEventListener('click', () => {
                    const nextOpen = !calculatorOpen;
                    setCalculatorOpen(nextOpen);
                    if (nextOpen) {
                        setCalculatorMinimized(false);
                        positionCalculator();
                        window.setTimeout(() => calculatorDisplay.focus(), 30);
                    }
                });

                calculatorClose.addEventListener('click', () => {
                    setCalculatorOpen(false);
                });

                calculatorMinimize.addEventListener('click', () => {
                    setCalculatorMinimized(!calculatorMinimized);
                });

                calculatorModeButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        setCalculatorMode(String(button.getAttribute('data-mode') || 'basic'));
                    });
                });

                calculatorMemoryToggle.addEventListener('click', () => {
                    setMemoryPanelOpen(!memoryPanelOpen);
                });

                calculatorHistoryToggle.addEventListener('click', () => {
                    setHistoryPanelOpen(!historyPanelOpen);
                });

                document.addEventListener('click', (event) => {
                    if (!calculatorOpen) {
                        return;
                    }

                    if (calculatorPanel.contains(event.target) || calculatorAnchor.contains(event.target)) {
                        return;
                    }

                    setCalculatorOpen(false);
                });

                calculatorPanel.addEventListener('click', (event) => {
                    const key = event.target.closest('[data-calc-value], [data-calc-action]');
                    if (!key) {
                        return;
                    }

                    const action = String(key.getAttribute('data-calc-action') || '');
                    const value = String(key.getAttribute('data-calc-value') || '');

                    if (action === 'clear') {
                        calculatorDisplay.value = '';
                        resultLocked = false;
                        return;
                    }

                    if (action === 'backspace') {
                        calculatorDisplay.value = calculatorDisplay.value.slice(0, -1);
                        resultLocked = false;
                        return;
                    }

                    if (action === 'sign') {
                        if (!calculatorDisplay.value || calculatorDisplay.value === 'Error') {
                            calculatorDisplay.value = '-';
                            resultLocked = false;
                            return;
                        }

                        calculatorDisplay.value = calculatorDisplay.value.startsWith('-')
                            ? calculatorDisplay.value.slice(1)
                            : `-${calculatorDisplay.value}`;
                        resultLocked = false;
                        return;
                    }

                    if (action === 'equals') {
                        evaluateExpression();
                        return;
                    }

                    if (action === 'memory-clear') {
                        calculatorMemory = 0;
                        renderMemory();
                        return;
                    }

                    if (action === 'memory-recall') {
                        appendToDisplay(String(Number(calculatorMemory.toFixed(10))));
                        return;
                    }

                    if (action === 'memory-add') {
                        calculatorMemory += readCurrentNumber();
                        renderMemory();
                        return;
                    }

                    if (action === 'memory-subtract') {
                        calculatorMemory -= readCurrentNumber();
                        renderMemory();
                        return;
                    }

                    if (action === 'history-clear') {
                        calculatorHistory = [];
                        renderHistory();
                        return;
                    }

                    appendToDisplay(value);
                });

                calculatorHistoryList.addEventListener('click', (event) => {
                    const historyButton = event.target.closest('[data-history-index]');
                    if (!historyButton) {
                        return;
                    }

                    const historyIndex = Number(historyButton.getAttribute('data-history-index') || -1);
                    const entry = calculatorHistory[historyIndex] || null;
                    if (!entry) {
                        return;
                    }

                    calculatorDisplay.value = String(entry.result || '');
                    resultLocked = true;
                    calculatorDisplay.focus();
                });

                calculatorDisplay.addEventListener('keydown', (event) => {
                    const allowedControlKeys = [
                        'Backspace',
                        'Delete',
                        'ArrowLeft',
                        'ArrowRight',
                        'ArrowUp',
                        'ArrowDown',
                        'Home',
                        'End',
                        'Tab',
                        'Enter'
                    ];

                    if (event.key === 'Enter') {
                        event.preventDefault();
                        evaluateExpression();
                        return;
                    }

                    if (allowedControlKeys.includes(event.key)) {
                        return;
                    }

                    if (event.ctrlKey || event.metaKey || event.altKey) {
                        return;
                    }

                    if (!/^[0-9+\-*/().%^ ]$/.test(event.key)) {
                        event.preventDefault();
                    }
                });

                calculatorDisplay.addEventListener('input', () => {
                    const sanitized = sanitizeTypedExpression(calculatorDisplay.value);
                    if (calculatorDisplay.value !== sanitized) {
                        calculatorDisplay.value = sanitized;
                    }
                    resultLocked = false;
                });

                calculatorDisplay.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const pasted = event.clipboardData ? event.clipboardData.getData('text') : '';
                    const sanitized = sanitizeTypedExpression(pasted);
                    if (sanitized === '') {
                        return;
                    }

                    const start = calculatorDisplay.selectionStart ?? calculatorDisplay.value.length;
                    const end = calculatorDisplay.selectionEnd ?? calculatorDisplay.value.length;
                    const currentValue = calculatorDisplay.value;
                    calculatorDisplay.value = `${currentValue.slice(0, start)}${sanitized}${currentValue.slice(end)}`;
                    const nextCursor = start + sanitized.length;
                    calculatorDisplay.setSelectionRange(nextCursor, nextCursor);
                    resultLocked = false;
                });

                document.addEventListener('keydown', (event) => {
                    if (!calculatorOpen) {
                        return;
                    }

                    const target = event.target;
                    const isTypingTarget = target instanceof HTMLElement && (
                        target.tagName === 'INPUT' ||
                        target.tagName === 'TEXTAREA' ||
                        target.isContentEditable
                    );

                    if (event.key === 'Escape') {
                        if (calculatorMinimized) {
                            setCalculatorMinimized(false);
                        } else {
                            setCalculatorOpen(false);
                        }
                        return;
                    }

                    if (isTypingTarget && target !== calculatorDisplay) {
                        return;
                    }

                    if (/^[0-9+\-*/().%^]$/.test(event.key) && target !== calculatorDisplay) {
                        event.preventDefault();
                        appendToDisplay(event.key);
                        return;
                    }

                    if (event.key === 'Backspace' && target !== calculatorDisplay) {
                        event.preventDefault();
                        calculatorDisplay.value = calculatorDisplay.value.slice(0, -1);
                        resultLocked = false;
                        return;
                    }

                    if (event.key === 'Enter' && target !== calculatorDisplay) {
                        event.preventDefault();
                        evaluateExpression();
                    }
                });

                if (calculatorDragHandle) {
                    calculatorDragHandle.addEventListener('pointerdown', (event) => {
                        if (event.target.closest('button')) {
                            return;
                        }

                        dragState = {
                            startX: event.clientX,
                            startY: event.clientY,
                            originLeft: calculatorPanel.offsetLeft,
                            originTop: calculatorPanel.offsetTop
                        };
                        calculatorPanel.classList.add('is-dragging');
                        calculatorDragHandle.setPointerCapture(event.pointerId);
                    });

                    calculatorDragHandle.addEventListener('pointermove', (event) => {
                        if (!dragState) {
                            return;
                        }

                        userPosition = {
                            left: dragState.originLeft + (event.clientX - dragState.startX),
                            top: dragState.originTop + (event.clientY - dragState.startY)
                        };
                        positionCalculator();
                    });

                    const stopDrag = () => {
                        dragState = null;
                        calculatorPanel.classList.remove('is-dragging');
                    };

                    calculatorDragHandle.addEventListener('pointerup', stopDrag);
                    calculatorDragHandle.addEventListener('pointercancel', stopDrag);
                }

                renderMemory();
                renderHistory();
                setMemoryPanelOpen(false);
                setHistoryPanelOpen(false);
                setCalculatorMode('basic');
                window.addEventListener('resize', positionCalculator);
                window.addEventListener('scroll', positionCalculator, { passive: true });
            }
        })();
    </script>
    <script>
        window.__cbtControlRole = 'student';
        window.__cbtControlIdentifier = '<?= htmlspecialchars((string) (Session::get('student')['name'] ?? '') . '|' . (string) (Session::get('student')['class'] ?? 'SS3'), ENT_QUOTES, 'UTF-8') ?>';
    </script>
    <script src="/control-stream.js" defer></script>
    <div class="cbt-admin-overlay" id="cbt-admin-overlay" aria-hidden="true" style="display:none;">
        <div class="cbt-admin-overlay-backdrop"></div>
        <div class="cbt-admin-overlay-card" role="dialog" aria-modal="true">
            <div class="cbt-admin-overlay-head">
                <span class="cbt-admin-overlay-icon" aria-hidden="true">&#9888;</span>
                <h2 class="cbt-admin-overlay-title">Administrator Notice</h2>
            </div>
            <p class="cbt-admin-overlay-message"></p>
            <div class="cbt-admin-overlay-actions">
                <span class="cbt-admin-overlay-countdown"></span>
            </div>
        </div>
    </div>
    <style>
        .cbt-admin-overlay{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.55);padding:16px;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .24s ease,visibility .24s ease}
        .cbt-admin-overlay.active{opacity:1;visibility:visible;pointer-events:auto}
        .cbt-admin-overlay-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.55)}
        .cbt-admin-overlay-card{position:relative;width:min(420px,100%);background:#fff;border-radius:16px;padding:22px 20px;box-shadow:0 20px 40px rgba(15,23,42,.26);border:1px solid #e2e8f0;opacity:0;transform:translateY(14px) scale(.98);transition:opacity .24s ease,transform .24s ease}
        .cbt-admin-overlay.active .cbt-admin-overlay-card{opacity:1;transform:translateY(0) scale(1)}
        .cbt-admin-overlay-head{display:flex;align-items:center;gap:12px;margin-bottom:12px}
        .cbt-admin-overlay-icon{width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;background:#fee2e2;color:#991b1b;font-weight:800;font-size:15px}
        .cbt-admin-overlay-title{font-size:1.05rem;font-weight:800;color:#172033;margin:0}
        .cbt-admin-overlay-message{margin:0 0 16px;color:#334155;line-height:1.5}
        .cbt-admin-overlay-actions{display:flex;justify-content:flex-end;gap:10px}
        .cbt-admin-overlay-countdown{font-size:.85rem;color:#64748b;font-weight:700}
        .timer-bonus{display:inline-block;margin-left:8px;font-size:.8rem;font-weight:700;color:#166534;background:#dcfce7;padding:2px 8px;border-radius:999px}
        #examTimer.admin-paused{opacity:.85}
        #examTimer.admin-paused .timer-progress{background:#f59e0b}
    </style>
<?php endif; ?>

<?php loadPartial('end') ?>
