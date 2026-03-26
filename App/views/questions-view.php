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
$securityState = normalizeQuizSecurityState($security_state ?? []);
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
                    $classes = 'answer-map-item';
                    if ($isAnswered) {
                        $classes .= ' answered';
                    }
                    if ($itemIndex === $activeIndex) {
                        $classes .= ' active';
                    }
                    ?>
                    <button
                        type="button"
                        class="<?= htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') ?>"
                        data-jump-index="<?= $itemIndex ?>"
                        aria-label="Question <?= (int) ($item['number'] ?? ($itemIndex + 1)) ?><?= $isAnswered ? ', answered' : ', not answered' ?>"
                        aria-current="<?= $itemIndex === $activeIndex ? 'step' : 'false' ?>">
                        <?= (int) ($item['number'] ?? ($itemIndex + 1)) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <p class="answer-map-hint">Click any number to jump. Green means saved.</p>
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
                <div class="mini-calculator-head">
                    <div>
                        <p class="mini-calculator-kicker">Quick Tool</p>
                        <h2>Calculator</h2>
                    </div>
                    <button
                        type="button"
                        class="mini-calculator-close"
                        id="calculatorClose"
                        aria-label="Close calculator">
                        x
                    </button>
                </div>

                <p class="mini-calculator-note">Supports brackets, trig in degrees, pi, square root, log, powers, and implied multiplication like 2(3+4).</p>

                <label class="sr-only" for="calculatorDisplay">Calculator display</label>
                <input
                    type="text"
                    class="mini-calculator-display"
                    id="calculatorDisplay"
                    value=""
                    readonly
                    inputmode="none"
                    placeholder="0" />

                <div class="calculator-function-wrap">
                    <button
                        type="button"
                        class="calculator-function-toggle"
                        id="calculatorFunctionToggle"
                        aria-expanded="false"
                        aria-controls="calculatorFunctionPanel">
                        Functions
                    </button>

                    <div class="calculator-function-panel" id="calculatorFunctionPanel" hidden>
                        <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="sin(">sin</button>
                        <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="cos(">cos</button>
                        <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="tan(">tan</button>
                        <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="sqrt(">sqrt</button>
                        <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="log(">log</button>
                        <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="^">x^y</button>
                        <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="pi">pi</button>
                        <button type="button" class="calculator-key calculator-key-soft calculator-key-function" data-calc-value="%">%</button>
                    </div>
                </div>

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
                    <button type="button" class="calculator-key calculator-key-soft" data-calc-action="sign">+/-</button>
                    <button type="button" class="calculator-key calculator-key-operator" data-calc-value="+">+</button>
                    <button type="button" class="calculator-key calculator-key-equals" data-calc-action="equals">=</button>
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

    <div
        class="exam-security-overlay <?= !empty($securityState['requires_admin_unlock']) ? 'active' : '' ?>"
        id="securityOverlay"
        aria-hidden="<?= !empty($securityState['requires_admin_unlock']) ? 'false' : 'true' ?>"
        data-locked="<?= !empty($securityState['requires_admin_unlock']) ? '1' : '0' ?>"
        data-violation-count="<?= (int) ($securityState['violation_count'] ?? 0) ?>">
        <div class="exam-security-card">
            <p class="exam-security-kicker">Security Lock</p>
            <h2>Admin approval required</h2>
            <p class="exam-security-message" id="securityMessage">
                <?php if (!empty($securityState['requires_admin_unlock'])): ?>
                    <?= htmlspecialchars((string) (($securityState['last_event_label'] ?? '') !== '' ? ucfirst((string) $securityState['last_event_label']) . '. ' : ''), ENT_QUOTES, 'UTF-8') ?>An admin password is required before this exam can continue.
                <?php else: ?>
                    Leaving the exam screen triggers a lock.
                <?php endif; ?>
            </p>
            <p class="exam-security-count" id="securityCount">Violations: <?= (int) ($securityState['violation_count'] ?? 0) ?></p>
            <div class="exam-security-pill-row">
                <span class="exam-security-pill">Exam paused</span>
                <span class="exam-security-pill exam-security-pill-muted">Admin unlock required</span>
            </div>

            <form id="securityUnlockForm" class="exam-security-form">
                <label for="securityAdminPassword">Admin Password</label>
                <input id="securityAdminPassword" type="password" class="exam-security-input" autocomplete="off" placeholder="Enter admin password" />
                <button type="submit" class="button exam-security-submit">Unlock Exam</button>
                <p class="exam-security-feedback" id="securityFeedback" aria-live="polite"></p>
            </form>
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
            const answerMapGrid = document.getElementById('answerMapGrid');
            const calculatorAnchor = document.getElementById('calculatorAnchor');
            const calculatorToggle = document.getElementById('calculatorToggle');
            const calculatorPanel = document.getElementById('calculatorPanel');
            const calculatorClose = document.getElementById('calculatorClose');
            const calculatorDisplay = document.getElementById('calculatorDisplay');
            const calculatorFunctionToggle = document.getElementById('calculatorFunctionToggle');
            const calculatorFunctionPanel = document.getElementById('calculatorFunctionPanel');
            const securityOverlay = document.getElementById('securityOverlay');
            const securityMessage = document.getElementById('securityMessage');
            const securityCount = document.getElementById('securityCount');
            const securityFeedback = document.getElementById('securityFeedback');
            const securityUnlockForm = document.getElementById('securityUnlockForm');
            const securityAdminPassword = document.getElementById('securityAdminPassword');
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';

            if (!timer || !timerValue || !timerProgress || !form || !timeUpField || !jumpIndexField || !markingOverlay) return;

            const endTime = Number(timer.dataset.endTime || 0);
            const serverTime = Number(timer.dataset.serverTime || 0);
            const duration = Number(timer.dataset.duration || 0);
            const isLastQuestion = String(form.dataset.isLast || '0') === '1';
            let isSubmittingFinal = false;
            let securityLocked = !!securityOverlay && String(securityOverlay.dataset.locked || '0') === '1';
            let violationRequestInFlight = false;
            let suppressSecurityMonitoring = false;
            let pageIsUnloading = false;

            const showMarkingOverlay = () => {
                markingOverlay.classList.add('active');
                markingOverlay.setAttribute('aria-hidden', 'false');
            };

            const setSecurityLocked = (locked) => {
                securityLocked = locked;
                if (!securityOverlay) {
                    return;
                }

                securityOverlay.classList.toggle('active', locked);
                securityOverlay.setAttribute('aria-hidden', locked ? 'false' : 'true');
                securityOverlay.dataset.locked = locked ? '1' : '0';

                if (locked && securityAdminPassword) {
                    window.setTimeout(() => {
                        securityAdminPassword.focus();
                    }, 30);
                } else if (!locked && securityAdminPassword) {
                    securityAdminPassword.value = '';
                }
            };

            const updateSecurityCopy = (message, violationCount) => {
                if (securityMessage && message) {
                    securityMessage.textContent = message;
                }

                if (securityCount && Number.isFinite(violationCount)) {
                    securityCount.textContent = `Violations: ${violationCount}`;
                }
            };

            const postSecurityAction = async (payload) => {
                const body = new URLSearchParams();
                Object.entries(payload).forEach(([key, value]) => {
                    body.append(key, String(value));
                });

                const response = await fetch('/student/exam-security', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: body.toString()
                });

                const payloadJson = await response.json().catch(() => ({}));
                if (!response.ok || payloadJson.ok === false) {
                    throw new Error(String(payloadJson.message || 'Unable to complete security action.'));
                }

                return payloadJson;
            };

            const reportExitViolation = async (eventType) => {
                if (securityLocked || violationRequestInFlight || isSubmittingFinal || suppressSecurityMonitoring || pageIsUnloading) {
                    return;
                }

                violationRequestInFlight = true;
                setSecurityLocked(true);
                updateSecurityCopy('Exam locked. Admin password is required to continue.', Number(securityOverlay ? securityOverlay.dataset.violationCount || 0 : 0));
                if (securityFeedback) {
                    securityFeedback.textContent = '';
                }

                try {
                    const payload = await postSecurityAction({
                        action: 'report_violation',
                        event_type: eventType
                    });
                    if (securityOverlay) {
                        securityOverlay.dataset.violationCount = String(payload.violation_count || 0);
                    }
                    updateSecurityCopy(String(payload.message || 'Exam locked.'), Number(payload.violation_count || 0));
                } catch (error) {
                    updateSecurityCopy(error.message || 'Exam locked.', Number(securityOverlay ? securityOverlay.dataset.violationCount || 0 : 0));
                } finally {
                    violationRequestInFlight = false;
                }
            };

            if (securityOverlay && securityLocked) {
                updateSecurityCopy(
                    String(securityMessage ? securityMessage.textContent : 'Exam locked. Admin password is required to continue.'),
                    Number(securityOverlay.dataset.violationCount || 0)
                );
            }

            const submitWithMarkingDelay = (force = false) => {
                if (isSubmittingFinal || (securityLocked && !force)) {
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

            form.addEventListener('submit', (event) => {
                if (isSubmittingFinal || securityLocked) {
                    return;
                }

                const submitter = event.submitter || null;
                const navAction = submitter ? String(submitter.value || '') : '';
                const shouldMark = isLastQuestion && navAction === 'next';

                 suppressSecurityMonitoring = true;

                if (!shouldMark) {
                    return;
                }

                event.preventDefault();
                submitWithMarkingDelay();
            });

            if (answerMapGrid) {
                answerMapGrid.addEventListener('click', (event) => {
                    if (securityLocked) {
                        return;
                    }

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
                    suppressSecurityMonitoring = true;
                    nextButton.click();
                });
            }

            if (securityUnlockForm && securityAdminPassword) {
                securityUnlockForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const password = String(securityAdminPassword.value || '').trim();
                    if (securityFeedback) {
                        securityFeedback.textContent = '';
                    }

                    if (!password) {
                        if (securityFeedback) {
                            securityFeedback.textContent = 'Enter the admin password.';
                        }
                        securityAdminPassword.focus();
                        return;
                    }

                    try {
                        const payload = await postSecurityAction({
                            action: 'admin_unlock',
                            password
                        });
                        setSecurityLocked(false);
                        if (securityFeedback) {
                            securityFeedback.textContent = String(payload.message || 'Exam unlocked.');
                        }
                    } catch (error) {
                        if (securityFeedback) {
                            securityFeedback.textContent = error.message || 'Unlock failed.';
                        }
                        securityAdminPassword.focus();
                    }
                });
            }

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    reportExitViolation('visibility_hidden');
                }
            });

            window.addEventListener('blur', () => {
                reportExitViolation('window_blur');
            });

            window.addEventListener('beforeunload', () => {
                pageIsUnloading = true;
                suppressSecurityMonitoring = true;
            });

            document.addEventListener('contextmenu', (event) => {
                event.preventDefault();
            });

            ['copy', 'cut', 'paste'].forEach((eventName) => {
                document.addEventListener(eventName, (event) => {
                    event.preventDefault();
                });
            });

            if (calculatorAnchor && calculatorToggle && calculatorPanel && calculatorClose && calculatorDisplay) {
                let calculatorOpen = false;
                let functionPanelOpen = false;
                let resultLocked = false;

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

                    const anchorRect = calculatorAnchor.getBoundingClientRect();
                    const panelWidth = Math.min(360, window.innerWidth - 24);
                    const left = Math.min(
                        Math.max(12, anchorRect.right - panelWidth),
                        Math.max(12, window.innerWidth - panelWidth - 12)
                    );
                    const top = Math.min(
                        Math.max(12, anchorRect.bottom + 10),
                        Math.max(12, window.innerHeight - calculatorPanel.offsetHeight - 12)
                    );

                    calculatorPanel.style.left = `${left}px`;
                    calculatorPanel.style.top = `${top}px`;
                    calculatorPanel.style.width = `${panelWidth}px`;
                };

                const appendToDisplay = (value) => {
                    if (calculatorDisplay.value === 'Error') {
                        calculatorDisplay.value = '';
                    }

                    if (resultLocked) {
                        if (!/^[+\-*/%^]$/.test(value)) {
                            return;
                        }

                        calculatorDisplay.value = `${calculatorDisplay.value}${value}`;
                        resultLocked = false;
                        return;
                    }

                    calculatorDisplay.value = `${calculatorDisplay.value}${value}`;
                };

                const setFunctionPanelOpen = (open) => {
                    if (!calculatorFunctionToggle || !calculatorFunctionPanel) {
                        return;
                    }

                    functionPanelOpen = open;
                    calculatorFunctionToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    calculatorFunctionPanel.hidden = !open;
                    calculatorFunctionToggle.textContent = open ? 'Hide Functions' : 'Functions';
                    window.setTimeout(positionCalculator, 0);
                };

                const tokenizeExpression = (raw) => {
                    const tokens = [];
                    const source = String(raw || '').replace(/\s+/g, '');
                    const tokenPattern = /sin|cos|tan|sqrt|log|pi|\d*\.\d+|\d+|[()+\-*/%^]/g;
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
                const isLeadingToken = (token) => /^(?:\d*\.\d+|\d+|pi|\(|sin|cos|tan|sqrt|log)$/.test(token);

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

                        if (token === 'cos') {
                            expression += 'degCos';
                            return;
                        }

                        if (token === 'tan') {
                            expression += 'degTan';
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
                        const degCos = (value) => Math.cos((Number(value) * Math.PI) / 180);
                        const degTan = (value) => Math.tan((Number(value) * Math.PI) / 180);
                        const result = Function('degSin', 'degCos', 'degTan', `"use strict"; return (${expression});`)(
                            degSin,
                            degCos,
                            degTan
                        );

                        if (!Number.isFinite(result)) {
                            throw new Error('Invalid result');
                        }

                        const roundedResult = Math.abs(result) < 1e-12 ? 0 : Number(result.toFixed(10));
                        calculatorDisplay.value = String(roundedResult);
                        resultLocked = true;
                    } catch (error) {
                        calculatorDisplay.value = 'Error';
                        resultLocked = true;
                    }
                };

                calculatorToggle.addEventListener('click', () => {
                    setCalculatorOpen(!calculatorOpen);
                    positionCalculator();
                });

                calculatorClose.addEventListener('click', () => {
                    setCalculatorOpen(false);
                });

                if (calculatorFunctionToggle && calculatorFunctionPanel) {
                    calculatorFunctionToggle.addEventListener('click', () => {
                        setFunctionPanelOpen(!functionPanelOpen);
                    });
                }

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
                        if (resultLocked) {
                            return;
                        }
                        calculatorDisplay.value = calculatorDisplay.value.slice(0, -1);
                        return;
                    }

                    if (action === 'sign') {
                        if (resultLocked) {
                            return;
                        }
                        if (!calculatorDisplay.value || calculatorDisplay.value === 'Error') {
                            calculatorDisplay.value = '-';
                            return;
                        }

                        calculatorDisplay.value = calculatorDisplay.value.startsWith('-')
                            ? calculatorDisplay.value.slice(1)
                            : `-${calculatorDisplay.value}`;
                        return;
                    }

                    if (action === 'equals') {
                        evaluateExpression();
                        return;
                    }

                    appendToDisplay(value);
                });

                window.addEventListener('resize', positionCalculator);
                window.addEventListener('scroll', positionCalculator, { passive: true });
            }
        })();
    </script>
<?php endif; ?>

<?php loadPartial('end') ?>
