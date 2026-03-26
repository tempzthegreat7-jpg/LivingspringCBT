<?php

require basePath('Framework/Database.php');
$config = require basePath('config/config-db2.php');

$db = new Database($config);
$studentClass = strtoupper(Session::get('student')['class'] ?? 'SS3');
ensureAssessmentConfigsSchema($db);
$taskOptions = assessmentTaskOptions();
$rows = $db->query(
    'SELECT id, subject, student_class, task_type, header_text, term_key, duration_seconds, table_name
     FROM assessment_configs
     WHERE student_class = :student_class
     ORDER BY subject ASC, task_type ASC, header_text ASC, term_key ASC, id ASC',
    ['student_class' => $studentClass]
)->fetchAll();
$tableRows = $db->query('SHOW TABLES')->fetchAll();
$existingTableMap = [];
foreach ($tableRows as $tableRow) {
    $existingTableMap[(string) array_values($tableRow)[0]] = true;
}

$assessments = [];
$availableSubjects = [];
foreach ($rows as $row) {
    $tableName = safeTableName((string) ($row['table_name'] ?? ''));

    if (!isset($existingTableMap[$tableName])) {
        continue;
    }

    $countRow = $db->query("SELECT COUNT(*) AS total FROM {$tableName}")->fetch();
    if ((int) ($countRow['total'] ?? 0) < 1) {
        continue;
    }

    $subjectKey = strtolower(trim((string) ($row['subject'] ?? '')));
    $taskKey = normalizeAssessmentTask($row['task_type'] ?? 'exam');
    $termKey = normalizeExamTerm($row['term_key'] ?? 'first_term');
    $headerText = trim((string) ($row['header_text'] ?? ''));
    $durationSeconds = max(0, (int) ($row['duration_seconds'] ?? 0));

    $availableSubjects[$subjectKey] = ucwords(str_replace('_', ' ', $subjectKey));
    $assessments[] = [
        'id' => (int) ($row['id'] ?? 0),
        'subject' => $subjectKey,
        'task' => $taskKey,
        'label' => $taskKey === 'exam'
            ? (examTermOptions()[$termKey] ?? '1st Term')
            : ($headerText !== '' ? $headerText : 'Custom'),
        'duration_seconds' => $durationSeconds
    ];
}

$hasAvailableSubjects = !empty($availableSubjects) && !empty($assessments);
$defaultSubject = array_key_first($availableSubjects) ?: '';
$activeExamSubjects = examActiveSubjectsForClass($db, $studentClass);

?>


<?php loadPartial('student-head') ?>


<div class="transition transition-1 is-active"></div>
<div class="heading">
    <a class="button button-soft question-back-btn" href="/student/dashboard">&leftarrow; Back to Dashboard</a>
</div>
<h1 class="primary-logo">Welcome, <?= Session::get('student')['name'] ?? '...' ?>!</h1>
<div class="question-mode-container">
    <p>Choose Subject &amp; Task</p>
    <form action="/student/question" method="POST" id="startTaskForm">
        <?= csrfField() ?>
        <label for="task">Task</label>
        <select name="task" id="task" class="select">
            <?php foreach ($taskOptions as $taskKey => $taskLabel): ?>
                <option value="<?= htmlspecialchars($taskKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($taskLabel, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <label for="subject">Subject:</label>
        <select name="subject" id="subject" class="select">
            <?php if (empty($availableSubjects)): ?>
                <option value="" selected disabled>No subjects available for your class</option>
            <?php else: ?>
                <?php foreach ($availableSubjects as $subjectKey => $subjectLabel): ?>
                    <option value="<?= htmlspecialchars($subjectKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach ?>
            <?php endif ?>
        </select>
        <label id="availableLabel">Available <?= htmlspecialchars((string) ($taskOptions['exam'] ?? 'Exam'), ENT_QUOTES, 'UTF-8') ?></label>
        <div class="task-options-inline" id="taskOptionsInline"></div>
        <input type="hidden" name="assessment_id" id="assessment_id" value="" />

        <div class="read-only-duration" id="readOnlyDuration">
            <p class="timer-title">Duration</p>
            <p class="timer-lock-text" id="durationText">Select an option to see duration.</p>
        </div>

        <small class="timer-hint">Duration and number of questions are set by the teacher and cannot be changed here.</small>

        <button type="submit" class="button" id="startButton" <?= $hasAvailableSubjects ? '' : 'disabled' ?>>Start &RightArrow;</button>
    </form>
</div>

<div class="task-loading-overlay" id="startTaskOverlay" aria-hidden="true">
    <div class="task-loading-card" role="status" aria-live="polite">
        <span class="task-loading-spinner" aria-hidden="true"></span>
        <p class="task-loading-title">Preparing Task</p>
        <p class="task-loading-subtitle">Please wait while your assessment environment is being set up.</p>
    </div>
</div>

<script>
    (() => {
        const assessments = <?= json_encode($assessments, JSON_UNESCAPED_SLASHES) ?>;
        const subjectSelect = document.getElementById('subject');
        const taskSelect = document.getElementById('task');
        const optionsInline = document.getElementById('taskOptionsInline');
        const availableLabel = document.getElementById('availableLabel');
        const assessmentInput = document.getElementById('assessment_id');
        const durationText = document.getElementById('durationText');
        const startButton = document.getElementById('startButton');

        if (!subjectSelect || !taskSelect || !optionsInline || !availableLabel || !assessmentInput || !durationText || !startButton) {
            return;
        }

        const taskLabels = <?= json_encode($taskOptions, JSON_UNESCAPED_SLASHES) ?>;
        const activeExamSubjects = <?= json_encode($activeExamSubjects, JSON_UNESCAPED_SLASHES) ?>;
        const subjectOptionCache = Array.from(subjectSelect.options).map((option) => ({
            value: String(option.value || ''),
            label: String(option.textContent || ''),
            disabled: option.disabled
        }));
        const activeExamSubjectSet = new Set(
            (Array.isArray(activeExamSubjects) ? activeExamSubjects : []).filter((subjectKey) =>
                subjectOptionCache.some((option) => option.value === subjectKey)
            )
        );
        const hasActiveExamSubject = activeExamSubjectSet.size > 0;
        const preload = {
            subject: <?= json_encode(strtolower(trim((string) ($_GET['subject'] ?? ''))), JSON_UNESCAPED_SLASHES) ?>,
            task: <?= json_encode((trim((string) ($_GET['task'] ?? '')) !== '' ? normalizeAssessmentTask($_GET['task']) : ''), JSON_UNESCAPED_SLASHES) ?>,
            assessmentId: <?= json_encode((int) ($_GET['assessment_id'] ?? 0), JSON_UNESCAPED_SLASHES) ?>
        };

        const formatDuration = (seconds) => {
            const total = Math.max(0, Number(seconds || 0));
            if (total <= 0) {
                return 'No time limit';
            }
            const hrs = Math.floor(total / 3600);
            const mins = Math.floor((total % 3600) / 60);
            const parts = [];
            if (hrs > 0) parts.push(`${hrs}h`);
            if (mins > 0) parts.push(`${mins}m`);
            if (parts.length === 0) parts.push('1m');
            return parts.join(' ');
        };

        const setSelected = (item, button) => {
            assessmentInput.value = String(item.id);
            durationText.textContent = formatDuration(item.duration_seconds);
            optionsInline.querySelectorAll('button').forEach((node) => node.classList.remove('current'));
            button.classList.add('current');
            startButton.disabled = false;
        };

        const applyExamSubjectLock = () => {
            const isExam = String(taskSelect.value || '').toLowerCase() === 'exam';
            if (!isExam || !hasActiveExamSubject) {
                if (isExam && !hasActiveExamSubject) {
                    subjectSelect.innerHTML = '';
                    const node = document.createElement('option');
                    node.value = '';
                    node.textContent = 'No active exam subjects';
                    node.disabled = true;
                    node.selected = true;
                    subjectSelect.appendChild(node);
                    return;
                }

                subjectSelect.innerHTML = '';
                subjectOptionCache.forEach((option) => {
                    const node = document.createElement('option');
                    node.value = option.value;
                    node.textContent = option.label;
                    node.disabled = option.disabled;
                    subjectSelect.appendChild(node);
                });
                return;
            }

            subjectSelect.innerHTML = '';
            subjectOptionCache.forEach((option) => {
                if (option.value && !activeExamSubjectSet.has(option.value)) {
                    return;
                }
                const node = document.createElement('option');
                node.value = option.value;
                node.textContent = option.label;
                node.disabled = option.disabled;
                subjectSelect.appendChild(node);
            });
        };

        const renderOptions = () => {
            const subject = String(subjectSelect.value || '').toLowerCase();
            const task = String(taskSelect.value || '').toLowerCase();
            availableLabel.textContent = `Available ${String(taskLabels[task] || task || 'Task')}`;
            optionsInline.innerHTML = '';
            assessmentInput.value = '';
            startButton.disabled = true;
            durationText.textContent = 'No option found for this task.';

            const matches = assessments.filter((row) => row.subject === subject && row.task === task);
            if (matches.length === 0) {
                return;
            }

            durationText.textContent = 'Select an option to see duration.';
            let selectedByPreload = false;
            matches.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = index === 0 ? 'current' : '';
                button.textContent = String(item.label || 'Untitled');
                button.addEventListener('click', () => setSelected(item, button));
                optionsInline.appendChild(button);

                if (preload.assessmentId > 0 && Number(item.id) === Number(preload.assessmentId)) {
                    setSelected(item, button);
                    selectedByPreload = true;
                } else if (index === 0) {
                    setSelected(item, button);
                }
            });

            if (!selectedByPreload && preload.assessmentId > 0) {
                const firstButton = optionsInline.querySelector('button');
                if (firstButton) {
                    firstButton.classList.add('current');
                }
            }
        };

        subjectSelect.addEventListener('change', renderOptions);
        taskSelect.addEventListener('change', () => {
            const previousSubject = String(subjectSelect.value || '');
            applyExamSubjectLock();
            if (previousSubject && Array.from(subjectSelect.options).some((option) => option.value === previousSubject)) {
                subjectSelect.value = previousSubject;
            } else if (hasActiveExamSubject && String(taskSelect.value || '').toLowerCase() === 'exam') {
                subjectSelect.value = String(activeExamSubjects[0] || '');
            }
            renderOptions();
        });

        const defaultSubject = <?= json_encode($defaultSubject, JSON_UNESCAPED_SLASHES) ?>;
        applyExamSubjectLock();

        const isExamTask = String(taskSelect.value || '').toLowerCase() === 'exam';
        const examLockedOff = isExamTask && !hasActiveExamSubject;

        if (preload.subject && Array.from(subjectSelect.options).some((option) => option.value === preload.subject)) {
            subjectSelect.value = preload.subject;
        } else if (hasActiveExamSubject && isExamTask) {
            subjectSelect.value = String(activeExamSubjects[0] || '');
        } else if (!examLockedOff && !subjectSelect.value && defaultSubject) {
            subjectSelect.value = defaultSubject;
        }

        if (preload.task && Array.from(taskSelect.options).some((option) => option.value === preload.task)) {
            taskSelect.value = preload.task;
        }
        renderOptions();

        const startForm = document.getElementById('startTaskForm');
        const startOverlay = document.getElementById('startTaskOverlay');
        let isSubmitting = false;

        if (startForm && startOverlay) {
            startForm.addEventListener('submit', (event) => {
                if (isSubmitting) {
                    return;
                }

                event.preventDefault();
                isSubmitting = true;
                startOverlay.classList.add('active');
                startOverlay.setAttribute('aria-hidden', 'false');

                window.requestAnimationFrame(() => {
                    startForm.submit();
                });
            });
        }
    })();
</script>

<?php loadPartial('end') ?>