<?php loadPartial('teacher-head') ?>
<?php loadPartial('sidebar') ?>
<section>
    <?php loadPartial('header') ?>
    <main>
        <?php
        $selectedSubject = strtolower($subject ?? 'english');
        $selectedClass = strtoupper($studentClass ?? 'SS3');
        $selectedTask = normalizeAssessmentTask($task ?? 'exam');
        $selectedHeader = trim((string) ($header ?? ''));
        $selectedTerm = normalizeExamTerm($term ?? 'first_term');
        $termOptions = $termOptions ?? examTermOptions();
        $taskOptions = $taskOptions ?? assessmentTaskOptions();
        $subjectCategories = $subjectCategories ?? [];
        $subjectOptions = $subjectOptions ?? ['english' => 'English'];
        $allowedClasses = $allowedClasses ?? ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];
        $questionRows = $questions ?? [];
        $availableContexts = $availableContexts ?? [];
        ?>

        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="dashboard-head">
            <h1>Check Questions</h1>
            <p>Preview, edit, and delete questions by subject and class.</p>
        </div>

        <form action="/teacher/check-question" method="GET" class="question-check-filter">
            <div class="question-check-picker-row">
                <div>
                    <label for="subject">Subject</label>
                    <select id="subject" name="subject" class="select">
                        <?php foreach ($subjectOptions as $subjectKey => $subjectLabel): ?>
                            <option value="<?= htmlspecialchars($subjectKey, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedSubject === $subjectKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="task">Task</label>
                    <select id="task" name="task" class="select">
                        <?php foreach ($taskOptions as $taskKey => $taskLabel): ?>
                            <option value="<?= htmlspecialchars($taskKey, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedTask === $taskKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($taskLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="student_class">Class</label>
                    <select id="student_class" name="student_class" class="select">
                        <?php foreach ($allowedClasses as $classLabel): ?>
                            <option value="<?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedClass === $classLabel ? 'selected' : '' ?>>
                                <?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="checkHeaderWrap">
                    <label id="checkAvailableLabel">Available <?= htmlspecialchars((string) ($taskOptions[$selectedTask] ?? ucfirst($selectedTask)), ENT_QUOTES, 'UTF-8') ?></label>
                    <div class="check-context-tabs" id="checkContextTabs">
                        <?php if ($selectedTask !== 'exam' && !empty($availableContexts)): ?>
                            <?php foreach ($availableContexts as $contextRow): ?>
                                <?php
                                $contextHeader = trim((string) ($contextRow['header'] ?? ''));
                                $isCurrentHeader = $selectedHeader === $contextHeader;
                                ?>
                                <button type="button" class="check-context-pill <?= $isCurrentHeader ? 'current' : '' ?>" data-header="<?= htmlspecialchars($contextHeader, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) ($contextRow['label'] ?? ($contextHeader !== '' ? $contextHeader : 'Custom')), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            <?php endforeach; ?>
                        <?php elseif ($selectedTask !== 'exam'): ?>
                            <p class="check-context-empty">No existing <?= htmlspecialchars((string) ($taskOptions[$selectedTask] ?? ucfirst($selectedTask)), ENT_QUOTES, 'UTF-8') ?> yet.</p>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="header" name="header" value="<?= htmlspecialchars($selectedHeader, ENT_QUOTES, 'UTF-8') ?>" />
                </div>
                <div id="checkTermWrap">
                    <label for="term">Term</label>
                    <select id="term" name="term" class="select">
                        <?php foreach ($termOptions as $termKey => $termLabel): ?>
                            <option value="<?= htmlspecialchars($termKey, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedTerm === $termKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($termLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="button" id="save">Load Subject</button>
        </form>

        <div class="question-meta">
            <p>
                Showing
                <strong><?= count($questionRows) ?></strong>
                question<?= count($questionRows) === 1 ? '' : 's' ?>
                for
                <strong><?= htmlspecialchars($subjectOptions[$selectedSubject] ?? ucfirst($selectedSubject), ENT_QUOTES, 'UTF-8') ?></strong>
                in <strong><?= htmlspecialchars($selectedClass, ENT_QUOTES, 'UTF-8') ?></strong>,
                <strong><?= htmlspecialchars($taskOptions[$selectedTask] ?? ucfirst($selectedTask), ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if ($selectedTask === 'exam'): ?>
                    <strong><?= htmlspecialchars($termOptions[$selectedTerm] ?? '1st Term', ENT_QUOTES, 'UTF-8') ?></strong>.
                <?php else: ?>
                    <strong><?= htmlspecialchars($selectedHeader !== '' ? $selectedHeader : 'Custom', ENT_QUOTES, 'UTF-8') ?></strong>.
                <?php endif; ?>
                <?php
                $durationSeconds = (int) ($durationSeconds ?? 0);
                $durationLabel = 'Not set — proceed at your pace.';
                if ($durationSeconds > 0) {
                    $totalMinutes = (int) ceil($durationSeconds / 60);
                    $hours = (int) floor($totalMinutes / 60);
                    $minutes = (int) ($totalMinutes % 60);
                    $parts = [];
                    if ($hours > 0) {
                        $parts[] = $hours . 'h';
                    }
                    if ($minutes > 0) {
                        $parts[] = $minutes . 'm';
                    }
                    if (empty($parts)) {
                        $parts[] = '1m';
                    }
                    $durationLabel = implode(' ', $parts);
                }
                ?>
                <span class="question-duration">Duration: <?= htmlspecialchars($durationLabel, ENT_QUOTES, 'UTF-8') ?></span>
            </p>
        </div>

        <div class="question-review-list">
            <?php if (!empty($questionRows)): ?>
                <?php foreach ($questionRows as $row): ?>
                    <?php
                    $payload = htmlspecialchars(json_encode([
                        'number' => (int) ($row['number'] ?? 0),
                        'question' => (string) ($row['question'] ?? ''),
                        'choice1' => (string) ($row['choice1'] ?? ''),
                        'choice2' => (string) ($row['choice2'] ?? ''),
                        'choice3' => (string) ($row['choice3'] ?? ''),
                        'choice4' => (string) ($row['choice4'] ?? ''),
                        'correct_answer' => (string) ($row['correct_answer'] ?? ''),
                        'image_path' => (string) ($row['image_path'] ?? '')
                    ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <article class="question-review-card">
                        <div class="question-review-head">
                            <p class="question-number">Question <?= (int) ($row['number'] ?? 0) ?></p>
                            <span class="correct-chip">Correct: <?= htmlspecialchars((string) ($row['correct_answer'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="question-review-text"><?= htmlspecialchars((string) ($row['question'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if (!empty($row['image_path'])): ?>
                            <div class="question-review-image-wrap">
                                <img
                                    src="<?= htmlspecialchars((string) $row['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Question image for question <?= (int) ($row['number'] ?? 0) ?>"
                                    class="question-review-image" />
                            </div>
                        <?php endif; ?>
                        <ul class="question-choice-list">
                            <li><strong>A.</strong> <?= htmlspecialchars((string) ($row['choice1'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
                            <li><strong>B.</strong> <?= htmlspecialchars((string) ($row['choice2'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
                            <li><strong>C.</strong> <?= htmlspecialchars((string) ($row['choice3'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
                            <li><strong>D.</strong> <?= htmlspecialchars((string) ($row['choice4'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
                        </ul>
                        <div class="question-actions">
                            <button type="button" class="button review-edit-button open-edit-modal" data-question="<?= $payload ?>">Edit</button>
                            <form action="/teacher/check-question/delete" method="POST" data-warning-confirm="Delete this question permanently?">
                                <?= csrfField() ?>
                                <input type="hidden" name="subject" value="<?= htmlspecialchars($selectedSubject, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="student_class" value="<?= htmlspecialchars($selectedClass, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="task" value="<?= htmlspecialchars($selectedTask, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="header" value="<?= htmlspecialchars($selectedHeader, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="number" value="<?= (int) ($row['number'] ?? 0) ?>" />
                                <button type="submit" class="button review-delete-button">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <article class="card empty-card">
                    <div class="icon">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    </div>
                    <div class="info">
                        <p class="main-text">No questions found for this class</p>
                        <p class="text">Switch class tabs or add questions first.</p>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </main>
</section>

<div class="question-modal-overlay" id="questionEditOverlay" aria-hidden="true">
    <div class="question-modal" role="dialog" aria-modal="true" aria-labelledby="questionEditTitle">
        <button type="button" class="question-modal-close" id="questionEditClose" aria-label="Close edit form">&times;</button>
        <h2 id="questionEditTitle">Edit Question</h2>
        <form action="/teacher/check-question/update" method="POST" class="question-edit-form" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="subject" value="<?= htmlspecialchars($selectedSubject, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="student_class" value="<?= htmlspecialchars($selectedClass, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="task" value="<?= htmlspecialchars($selectedTask, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="header" value="<?= htmlspecialchars($selectedHeader, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="original_number" id="edit_original_number" value="" />

            <label for="edit_number">Number</label>
            <input type="number" min="1" name="number" id="edit_number" class="select" required />

            <label for="edit_question">Question</label>
            <textarea name="question" id="edit_question" class="select question-textarea" rows="5" required></textarea>

            <div class="grid">
                <div>
                    <label for="edit_choice1">Choice 1</label>
                    <input type="text" name="choice1" id="edit_choice1" class="select" required />
                </div>
                <div>
                    <label for="edit_choice2">Choice 2</label>
                    <input type="text" name="choice2" id="edit_choice2" class="select" required />
                </div>
                <div>
                    <label for="edit_choice3">Choice 3</label>
                    <input type="text" name="choice3" id="edit_choice3" class="select" required />
                </div>
                <div>
                    <label for="edit_choice4">Choice 4</label>
                    <input type="text" name="choice4" id="edit_choice4" class="select" required />
                </div>
            </div>

            <label for="edit_correct_answer">Correct Answer</label>
            <input type="text" name="correct_answer" id="edit_correct_answer" class="select" placeholder="Use 1, 2, 3, 4 or full answer text..." required />

            <label>Question Image (optional)</label>
            <div class="image-preview-box" id="editImagePreviewBox" hidden>
                <img src="" alt="Current question image preview" id="editImagePreview" class="image-preview-thumb" />
            </div>
            <input type="file" name="question_image" id="edit_question_image" class="select" accept="image/*" />
            <label class="inline-check">
                <input type="checkbox" name="remove_image" id="edit_remove_image" value="1" />
                Remove current image
            </label>

            <button type="submit" class="button" id="save">Save Changes</button>
        </form>
    </div>
</div>

<script>
    (function() {
        const overlay = document.getElementById('questionEditOverlay');
        const closeButton = document.getElementById('questionEditClose');
        const openButtons = document.querySelectorAll('.open-edit-modal');

        const formFields = {
            original_number: document.getElementById('edit_original_number'),
            number: document.getElementById('edit_number'),
            question: document.getElementById('edit_question'),
            choice1: document.getElementById('edit_choice1'),
            choice2: document.getElementById('edit_choice2'),
            choice3: document.getElementById('edit_choice3'),
            choice4: document.getElementById('edit_choice4'),
            correct_answer: document.getElementById('edit_correct_answer')
        };
        const editImageInput = document.getElementById('edit_question_image');
        const editRemoveImage = document.getElementById('edit_remove_image');
        const editImagePreviewBox = document.getElementById('editImagePreviewBox');
        const editImagePreview = document.getElementById('editImagePreview');

        const resetEditImageState = function(imagePath) {
            if (editImageInput) {
                editImageInput.value = '';
            }
            if (editRemoveImage) {
                editRemoveImage.checked = false;
            }
            if (editImagePreviewBox && editImagePreview) {
                if (imagePath) {
                    editImagePreview.src = imagePath;
                    editImagePreviewBox.hidden = false;
                } else {
                    editImagePreview.src = '';
                    editImagePreviewBox.hidden = true;
                }
            }
        };

        const openModal = (data) => {
            formFields.original_number.value = data.number || '';
            formFields.number.value = data.number || '';
            formFields.question.value = data.question || '';
            formFields.choice1.value = data.choice1 || '';
            formFields.choice2.value = data.choice2 || '';
            formFields.choice3.value = data.choice3 || '';
            formFields.choice4.value = data.choice4 || '';
            formFields.correct_answer.value = data.correct_answer || '';
            resetEditImageState(data.image_path || '');

            overlay.classList.add('active');
            overlay.setAttribute('aria-hidden', 'false');
        };

        const closeModal = () => {
            overlay.classList.remove('active');
            overlay.setAttribute('aria-hidden', 'true');
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const raw = button.getAttribute('data-question');
                if (!raw) {
                    return;
                }

                try {
                    const data = JSON.parse(raw);
                    openModal(data);
                } catch (error) {
                    console.error('Failed to open edit modal:', error);
                }
            });
        });

        closeButton.addEventListener('click', closeModal);

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && overlay.classList.contains('active')) {
                closeModal();
            }
        });

        if (editImageInput && editImagePreviewBox && editImagePreview) {
            editImageInput.addEventListener('change', function() {
                const file = editImageInput.files && editImageInput.files[0] ? editImageInput.files[0] : null;
                if (!file) {
                    return;
                }
                if (editRemoveImage) {
                    editRemoveImage.checked = false;
                }
                const objectUrl = URL.createObjectURL(file);
                editImagePreview.src = objectUrl;
                editImagePreviewBox.hidden = false;
            });
        }

        const subjectSelect = document.getElementById('subject');
        const classSelect = document.getElementById('student_class');
        const taskSelect = document.getElementById('task');
        const headerInput = document.getElementById('header');
        const termSelect = document.getElementById('term');
        const checkHeaderWrap = document.getElementById('checkHeaderWrap');
        const checkTermWrap = document.getElementById('checkTermWrap');
        const checkContextTabs = document.getElementById('checkContextTabs');
        const checkAvailableLabel = document.getElementById('checkAvailableLabel');
        const taskLabels = <?= json_encode($taskOptions ?? [], JSON_UNESCAPED_SLASHES) ?>;
        const contextEndpoint = '/teacher/add-question/contexts';
        const subjectCategories = <?= json_encode($subjectCategories ?? [], JSON_UNESCAPED_SLASHES) ?>;
        const classMap = {
            junior: ['JSS1', 'JSS2', 'JSS3'],
            senior: ['SS1', 'SS2', 'SS3'],
            both: ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3']
        };

        const renderClassOptions = function(subjectKey) {
            if (!classSelect) return;
            const category = String(subjectCategories[subjectKey] || 'both').toLowerCase();
            const options = classMap[category] || classMap.both;
            const previous = classSelect.value;
            classSelect.innerHTML = '';

            options.forEach(function(classLabel) {
                const option = document.createElement('option');
                option.value = classLabel;
                option.textContent = classLabel;
                classSelect.appendChild(option);
            });

            if (options.indexOf(previous) !== -1) {
                classSelect.value = previous;
            }
        };

        if (subjectSelect && classSelect) {
            subjectSelect.addEventListener('change', function() {
                renderClassOptions(subjectSelect.value);
            });

            renderClassOptions(subjectSelect.value);
        }

        const toggleContext = function() {
            if (!taskSelect || !checkHeaderWrap || !checkTermWrap || !headerInput || !termSelect || !checkContextTabs || !checkAvailableLabel) {
                return;
            }

            const taskKey = String(taskSelect.value || '').toLowerCase();
            const isExam = taskKey === 'exam';
            checkHeaderWrap.style.display = isExam ? 'none' : '';
            checkTermWrap.style.display = isExam ? '' : 'none';
            headerInput.required = !isExam;
            termSelect.required = isExam;
            checkAvailableLabel.textContent = 'Available ' + String(taskLabels[taskKey] || taskKey || 'Task');
        };

        const bindContextTabClicks = function() {
            if (!checkContextTabs || !headerInput) {
                return;
            }

            checkContextTabs.querySelectorAll('[data-header]').forEach(function(node) {
                node.addEventListener('click', function() {
                    const headerValue = node.getAttribute('data-header') || '';
                    headerInput.value = headerValue;
                    checkContextTabs.querySelectorAll('.check-context-pill').forEach(function(pill) {
                        pill.classList.remove('current');
                    });
                    node.classList.add('current');
                });
            });
        };

        const renderContextTabs = function(payload) {
            if (!checkContextTabs || !headerInput || !taskSelect) {
                return;
            }

            const taskKey = String(taskSelect.value || '').toLowerCase();
            if (taskKey === 'exam') {
                return;
            }

            const contexts = Array.isArray(payload && payload.available_contexts) ? payload.available_contexts : [];
            if (contexts.length === 0) {
                headerInput.value = '';
                checkContextTabs.innerHTML = '<p class="check-context-empty">No existing ' + String(taskLabels[taskKey] || taskKey || 'task') + ' yet.</p>';
                return;
            }

            let activeHeader = String(headerInput.value || '');
            const hasActive = contexts.some(function(item) {
                return String(item && item.header ? item.header : '') === activeHeader;
            });

            if (!hasActive) {
                activeHeader = String(contexts[0] && contexts[0].header ? contexts[0].header : '');
                headerInput.value = activeHeader;
            }

            const rows = contexts.map(function(item) {
                const headerValue = String(item && item.header ? item.header : '');
                const label = String(item && item.label ? item.label : (headerValue !== '' ? headerValue : 'Custom'));
                const isCurrent = activeHeader === headerValue;
                const safeHeader = headerValue
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
                const safeLabel = label
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');

                return '<button type="button" class="check-context-pill ' + (isCurrent ? 'current' : '') + '" data-header="' + safeHeader + '">' + safeLabel + '</button>';
            });

            checkContextTabs.innerHTML = rows.join('');
            bindContextTabClicks();
        };

        const refreshContextTabs = function() {
            if (!subjectSelect || !classSelect || !taskSelect || !termSelect) {
                return;
            }

            const params = new URLSearchParams();
            params.set('subject', subjectSelect.value);
            params.set('student_class', classSelect.value);
            params.set('task', taskSelect.value);
            params.set('term', termSelect.value);
            params.set('header', headerInput ? headerInput.value : '');

            fetch(contextEndpoint + '?' + params.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Unable to load contexts');
                    }
                    return response.json();
                })
                .then(function(payload) {
                    renderContextTabs(payload || {});
                })
                .catch(function(error) {
                    console.error(error);
                });
        };

        if (taskSelect) {
            taskSelect.addEventListener('change', function() {
                toggleContext();
                refreshContextTabs();
            });
            toggleContext();
        }

        if (subjectSelect) {
            subjectSelect.addEventListener('change', refreshContextTabs);
        }

        if (classSelect) {
            classSelect.addEventListener('change', refreshContextTabs);
        }

        bindContextTabClicks();
        refreshContextTabs();
    })();
</script>
<?php loadPartial('end') ?>
