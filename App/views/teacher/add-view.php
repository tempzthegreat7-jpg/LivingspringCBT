<?php loadPartial('teacher-head') ?>
<?php loadPartial('sidebar') ?>
<section>
    <?php loadPartial('header') ?>
    <main>
        <?php
        $selectedSubject = strtolower($subject ?? 'english');
        $selectedClass = strtoupper($studentClass ?? 'SS3');
        $selectedTask = normalizeAssessmentTask($task ?? 'exam');
        $selectedHeaderInput = trim((string) ($header ?? ''));
        $selectedTerm = normalizeExamTerm($term ?? 'first_term');
        $selectedDurationMinutes = max(0, (int) ($durationMinutes ?? 0));
        $selectedDurationHours = (int) floor($selectedDurationMinutes / 60);
        $selectedDurationMinutePart = (int) ($selectedDurationMinutes % 60);
        $selectedQuestionLimit = max(1, min(200, (int) ($questionLimit ?? 20)));
        $termOptions = $termOptions ?? examTermOptions();
        $taskOptions = $taskOptions ?? assessmentTaskOptions();
        $subjectCategories = $subjectCategories ?? [];
        $subjectOptions = $subjectOptions ?? ['english' => 'English'];
        $allowedClasses = $allowedClasses ?? ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];
        $questionRows = $questions ?? [];
        $showPreview = $showPreview ?? false;
        $selectedContextId = (int) ($selectedContextId ?? 0);
        $availableContexts = $availableContexts ?? [];
        $oldInput = $oldInput ?? [];
        $formNumber = (string) ($oldInput['number'] ?? ($nextNumber ?? ''));
        $formQuestion = (string) ($oldInput['question'] ?? '');
        $formChoice1 = (string) ($oldInput['choice1'] ?? '');
        $formChoice2 = (string) ($oldInput['choice2'] ?? '');
        $formChoice3 = (string) ($oldInput['choice3'] ?? '');
        $formChoice4 = (string) ($oldInput['choice4'] ?? '');
        $formCorrectAnswer = (string) ($oldInput['correct_answer'] ?? '');
        $contextTitle = $selectedTask === 'exam'
            ? ($termOptions[$selectedTerm] ?? '1st Term')
            : (($resolvedHeader ?? $selectedHeaderInput) !== '' ? (string) ($resolvedHeader ?? $selectedHeaderInput) : 'Custom');
        ?>
        <h1><?= $subjectOptions[$selectedSubject] ?? ucfirst($selectedSubject) ?> | <?= $selectedClass ?> (<?= htmlspecialchars($taskOptions[$selectedTask] ?? ucfirst($selectedTask), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($contextTitle, ENT_QUOTES, 'UTF-8') ?>) Questions</h1>
        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= $message ?></div>
        <?php endif; ?>
        <form action="/teacher/add-question" method="GET">
            <div class="grid">
                <div>
                    <label for="subject">Subject</label>
                    <select id="subject" name="subject" class="select">
                        <?php foreach ($subjectOptions as $subjectKey => $subjectLabel): ?>
                            <option value="<?= $subjectKey ?>" <?= $selectedSubject === $subjectKey ? 'selected' : '' ?>><?= $subjectLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="student_class">Class</label>
                    <select id="student_class" name="student_class" class="select">
                        <?php foreach ($allowedClasses as $classLabel): ?>
                            <option value="<?= $classLabel ?>" <?= $selectedClass === $classLabel ? 'selected' : '' ?>><?= $classLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="question-settings-grid">
                <div>
                    <label for="task">Task</label>
                    <select id="task" name="task" class="select">
                        <?php foreach ($taskOptions as $taskKey => $taskLabel): ?>
                            <option value="<?= htmlspecialchars((string) $taskKey, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedTask === $taskKey ? 'selected' : '' ?>><?= htmlspecialchars((string) $taskLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="headerTextWrap">
                    <label for="header">Header</label>
                    <input type="text" id="header" name="header" class="select" value="<?= htmlspecialchars($selectedHeaderInput, ENT_QUOTES, 'UTF-8') ?>" placeholder="Enter heading..." />
                </div>
                <div id="headerTermWrap">
                    <label for="term">Term</label>
                    <select id="term" name="term" class="select">
                        <?php foreach ($termOptions as $termKey => $termLabel): ?>
                            <option value="<?= htmlspecialchars($termKey, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedTerm === $termKey ? 'selected' : '' ?>><?= htmlspecialchars($termLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="durationWrap">
                    <label>Duration</label>
                    <div class="duration-pill-row">
                        <span class="duration-pill" id="durationPill">Not set — proceed at your pace.</span>
                        <button type="button" class="mini-btn ghost duration-edit-btn" id="durationEditBtn">Edit</button>
                    </div>
                    <div class="duration-edit-panel" id="durationEditPanel" hidden>
                        <div class="duration-row">
                            <input type="number" id="duration_hours" name="duration_hours" class="select duration-input" min="0" max="8" value="<?= (int) $selectedDurationHours ?>" />
                            <input type="number" id="duration_minutes" name="duration_minutes" class="select duration-input" min="0" max="59" value="<?= (int) $selectedDurationMinutePart ?>" />
                        </div>
                        <div class="duration-edit-actions">
                            <button type="button" class="mini-btn ghost" id="durationCancelBtn">Cancel</button>
                            <button type="button" class="mini-btn" id="durationSaveBtn">Save</button>
                        </div>
                    </div>
                    <label for="question_limit">Number of Questions</label>
                    <input type="number" id="question_limit" name="question_limit" class="select duration-input" min="1" max="200" value="<?= (int) $selectedQuestionLimit ?>" required />
                </div>
            </div>
            <input type="hidden" id="assessment_id" name="assessment_id" value="<?= $selectedContextId ?>" />
            <input type="hidden" id="create_context" name="create_context" value="0" />
            <div class="question-config-actions">
                <button type="submit" class="button" id="save">Load Question Bank</button>
                <button type="submit" class="mini-btn task-create-btn" id="createTaskButton">Create</button>
            </div>
        </form>
        <form
            action="/teacher/add-question/context-delete"
            method="POST"
            id="deleteExamBankWrap"
            data-warning-confirm="Delete selected exam bank(s) and all their questions?">
            <?= csrfField() ?>
            <input type="hidden" id="delete_exam_subject" name="subject" value="<?= htmlspecialchars($selectedSubject, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" id="delete_exam_class" name="student_class" value="<?= htmlspecialchars($selectedClass, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" id="delete_exam_task" name="task" value="<?= htmlspecialchars($selectedTask, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" id="delete_exam_term" name="term" value="<?= htmlspecialchars($selectedTerm, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" id="delete_exam_header" name="header" value="<?= htmlspecialchars((string) ($resolvedHeader ?? $selectedHeaderInput), ENT_QUOTES, 'UTF-8') ?>" />
            <?php if (!empty($availableContexts)): ?>
                <div class="question-context-list">
                    <?php foreach ($availableContexts as $contextRow): ?>
                        <?php
                        $contextId = (int) ($contextRow['id'] ?? 0);
                        $contextLabel = $termOptions[normalizeExamTerm($contextRow['term_key'] ?? 'first_term')] ?? '1st Term';
                        $isChecked = $selectedContextId > 0 && $selectedContextId === $contextId;
                        ?>
                        <label class="context-pill-row">
                            <span class="mini-btn ghost"><?= htmlspecialchars($contextLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="inline-check">
                                <input type="checkbox" name="context_ids[]" value="<?= $contextId ?>" <?= $isChecked ? 'checked' : '' ?> />
                                Select
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text">No exam bank available to delete.</p>
            <?php endif; ?>
            <button type="submit" class="mini-btn ghost" id="deleteExamBankButton" <?= $selectedTask === 'exam' ? '' : 'style="display:none;"' ?> <?= $selectedContextId > 0 ? '' : 'disabled' ?>>
                Delete Bank
            </button>
        </form>

        <div class="question-context-list" id="existingContextsBox">
            <?php if (!empty($availableContexts)): ?>
                <?php foreach ($availableContexts as $contextRow): ?>
                    <?php
                    $contextId = (int) ($contextRow['id'] ?? 0);
                    $contextLabel = trim((string) ($contextRow['header_text'] ?? ''));
                    $contextDurationSeconds = max(0, (int) ($contextRow['duration_seconds'] ?? 0));
                    $contextDurationMinutes = $contextDurationSeconds > 0 ? (int) ceil($contextDurationSeconds / 60) : $selectedDurationMinutes;
                    $contextDurationHours = (int) floor($contextDurationMinutes / 60);
                    $contextDurationMinutePart = (int) ($contextDurationMinutes % 60);
                    $contextQuestionLimit = max(1, min(200, (int) ($contextRow['question_limit'] ?? $selectedQuestionLimit)));
                    if ($selectedTask === 'exam') {
                        $contextLabel = $termOptions[normalizeExamTerm($contextRow['term_key'] ?? 'first_term')] ?? '1st Term';
                    }
                    if ($contextLabel === '') {
                        $contextLabel = 'Custom';
                    }
                    $isCurrentContext = $selectedContextId === $contextId;
                    $openUrl = '/teacher/add-question?subject=' . urlencode($selectedSubject)
                        . '&student_class=' . urlencode($selectedClass)
                        . '&task=' . urlencode($selectedTask)
                        . '&assessment_id=' . urlencode((string) $contextId)
                        . '&duration_hours=' . urlencode((string) $contextDurationHours)
                        . '&duration_minutes=' . urlencode((string) $contextDurationMinutePart)
                        . '&question_limit=' . urlencode((string) $contextQuestionLimit)
                        . ($selectedTask === 'exam'
                            ? '&term=' . urlencode($selectedTerm)
                            : '&header=' . urlencode((string) ($contextRow['header_text'] ?? '')));
                    ?>
                    <div class="context-pill-row">
                        <a href="<?= htmlspecialchars($openUrl, ENT_QUOTES, 'UTF-8') ?>" class="mini-btn <?= $isCurrentContext ? 'current' : 'ghost' ?>">
                            <?= htmlspecialchars($contextLabel, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <form action="/teacher/add-question/context-delete" method="POST" data-warning-confirm="Delete this context and all its questions?">
                            <?= csrfField() ?>
                            <input type="hidden" name="context_id" value="<?= $contextId ?>" />
                            <input type="hidden" name="subject" value="<?= htmlspecialchars($selectedSubject, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="student_class" value="<?= htmlspecialchars($selectedClass, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="task" value="<?= htmlspecialchars($selectedTask, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="header" value="<?= htmlspecialchars((string) ($contextRow['header_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                            <button type="submit" class="mini-btn ghost">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text">No existing <?= htmlspecialchars($taskOptions[$selectedTask] ?? ucfirst($selectedTask), ENT_QUOTES, 'UTF-8') ?> yet.</p>
            <?php endif; ?>
        </div>

        <div class="context-action-row <?= $selectedContextId > 0 ? 'is-active' : '' ?>" id="moveContextWrap">
            <button
                type="button"
                class="mini-btn move-context-toggle-btn"
                id="moveContextToggle"
                <?= $selectedContextId > 0 ? '' : 'disabled' ?>>
                <i class="fa fa-exchange" aria-hidden="true"></i>
                Move Bank
            </button>
            <form
                action="/teacher/add-question/context-move"
                method="POST"
                class="move-context-panel"
                id="moveContextPanel">
                <?= csrfField() ?>
                <input type="hidden" name="context_id" id="move_context_id" value="<?= (int) $selectedContextId ?>" />
                <input type="hidden" name="subject" id="move_context_subject" value="<?= htmlspecialchars($selectedSubject, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="student_class" id="move_context_class" value="<?= htmlspecialchars($selectedClass, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="task" id="move_context_task" value="<?= htmlspecialchars($selectedTask, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="term" id="move_context_term" value="<?= htmlspecialchars($selectedTerm, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="header" id="move_context_header" value="<?= htmlspecialchars((string) ($resolvedHeader ?? $selectedHeaderInput), ENT_QUOTES, 'UTF-8') ?>" />
                <label for="move_target_class" class="move-context-label">Move this bank to</label>
                <select name="target_class" id="move_target_class" class="select move-context-select" <?= $selectedContextId > 0 ? '' : 'disabled' ?>>
                    <?php foreach ($allowedClasses as $classLabel): ?>
                        <option value="<?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedClass === $classLabel ? 'selected' : '' ?>>
                            <?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="move-context-actions">
                    <button type="button" class="mini-btn ghost" id="moveContextCancel">Cancel</button>
                    <button type="submit" class="mini-btn move-context-submit" id="moveContextSubmit" <?= $selectedContextId > 0 ? '' : 'disabled' ?>>
                        Move
                    </button>
                </div>
            </form>
        </div>

        <div class="question-bank-grid">
            <div class="question-form-card">
                <form action="/teacher/add-question" method="POST" enctype="multipart/form-data" id="questionForm" data-add-action="/teacher/add-question" data-edit-action="/teacher/check-question/update">
                    <?= csrfField() ?>
                    <?php loadPartial('errors', [
                        'errors' => $errors ?? []
                    ]) ?>
                    <input type="hidden" name="subject" value="<?= $selectedSubject ?>" />
                    <input type="hidden" name="student_class" value="<?= $selectedClass ?>" />
                    <input type="hidden" name="task" value="<?= htmlspecialchars($selectedTask, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="header" value="<?= htmlspecialchars((string) ($resolvedHeader ?? $selectedHeaderInput), ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="duration_hours" value="<?= (int) $selectedDurationHours ?>" />
                    <input type="hidden" name="duration_minutes" value="<?= (int) $selectedDurationMinutePart ?>" />
                    <input type="hidden" name="question_limit" value="<?= (int) $selectedQuestionLimit ?>" />
                    <input type="hidden" name="assessment_id" value="<?= (int) $selectedContextId ?>" />
                    <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="original_number" id="form_original_number" value="" />
                    <div>
                        <label for="">Number</label>
                        <div class="question-nav-row">
                            <button type="button" class="mini-btn ghost question-nav-btn" id="questionNavPrev" aria-label="Previous question">&larr;</button>
                            <span class="question-nav-status" id="questionNavStatus">Question</span>
                            <button type="button" class="mini-btn ghost question-nav-btn" id="questionNavNext" aria-label="Next question">&rarr;</button>
                        </div>
                        <input type="number" class="select" name="number" value="<?= htmlspecialchars($formNumber, ENT_QUOTES, 'UTF-8') ?>" />
                    </div>
                    <div>
                        <label for="">Question:</label>
                        <div class="question-field">
                            <textarea class="select question-textarea" name="question" rows="5" placeholder="Enter Question..."><?= htmlspecialchars($formQuestion, ENT_QUOTES, 'UTF-8') ?></textarea>
                            <button type="button" class="image-import-trigger question-import-btn" id="openImagePicker" aria-label="Import question image" title="Import question image">
                                Import Image
                            </button>
                        </div>
                    </div>
                    <div class="grid">
                        <div>
                            <label for="">Choice 1:</label>
                            <input type="text" class="select" name="choice1" id="" placeholder="1st choice..." value="<?= htmlspecialchars($formChoice1, ENT_QUOTES, 'UTF-8') ?>" />
                        </div>
                        <div>
                            <label for="">Choice 2:</label>
                            <input type="text" class="select" name="choice2" id="" placeholder="2nd choice..." value="<?= htmlspecialchars($formChoice2, ENT_QUOTES, 'UTF-8') ?>" />
                        </div>
                        <div>
                            <label for="">Choice 3:</label>
                            <input type="text" class="select" name="choice3" id="" placeholder="3rd choice..." value="<?= htmlspecialchars($formChoice3, ENT_QUOTES, 'UTF-8') ?>" />
                        </div>
                        <div>
                            <label for="">Choice 4:</label>
                            <input type="text" class="select" name="choice4" id="" placeholder="4th choice..." value="<?= htmlspecialchars($formChoice4, ENT_QUOTES, 'UTF-8') ?>" />
                        </div>
                    </div>
                    <label for="">Correct Answer:</label>
                    <input type="text" class="select" name="correct_answer" id="" placeholder="Use 1, 2, 3, 4 or full answer text..." value="<?= htmlspecialchars($formCorrectAnswer, ENT_QUOTES, 'UTF-8') ?>" />

                    <div class="image-import-box">
                        <p class="image-import-note" id="selectedImageName">No image selected.</p>
                        <div class="image-preview-box" id="formImagePreviewBox" hidden>
                            <img src="" alt="Selected question image preview" id="formImagePreview" class="image-preview-thumb" />
                        </div>
                    </div>

                    <input type="file" name="question_image" id="questionImageInput" accept="image/*" hidden />
                    <button type="submit" class="button" id="add">Add Question</button>

                    <div class="image-overlay" id="imageOverlay" aria-hidden="true">
                        <div class="image-overlay-card" role="dialog" aria-modal="true" aria-labelledby="imageOverlayTitle">
                            <h2 id="imageOverlayTitle">Attach Question Image</h2>
                            <p class="image-overlay-subtitle">Select an image file for this question (JPG, PNG, WEBP, or GIF).</p>
                            <div class="image-overlay-actions">
                                <button type="button" class="mini-overlay-btn ghost" id="chooseImageButton">Choose Image</button>
                                <button type="button" class="mini-overlay-btn" id="confirmImageButton" disabled>Confirm</button>
                            </div>
                            <p class="image-overlay-file" id="overlaySelectedFile">No file selected.</p>
                            <div class="image-preview-box" id="overlayImagePreviewBox" hidden>
                                <img src="" alt="Selected question image preview" id="overlayImagePreview" class="image-preview-thumb" />
                            </div>
                            <button type="button" class="image-overlay-close" id="closeImageOverlay">Close</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="question-preview-card">
                <div class="question-preview-head">
                    <h2>Question Bank Preview</h2>
                    <?php if ($showPreview): ?>
                        <span><?= count($questionRows) ?> question<?= count($questionRows) === 1 ? '' : 's' ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($showPreview): ?>
                    <div class="question-review-list question-preview-list">
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
                                <article class="question-review-card compact">
                                    <div class="question-review-head">
                                        <p class="question-number">Q<?= (int) ($row['number'] ?? 0) ?></p>
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
                                    <div class="question-actions">
                                        <button type="button" class="button review-edit-button open-edit-modal" data-question="<?= $payload ?>">Edit</button>
                                        <form action="/teacher/check-question/delete" method="POST" data-warning-confirm="Delete this question permanently?">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="subject" value="<?= htmlspecialchars($selectedSubject, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="student_class" value="<?= htmlspecialchars($selectedClass, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="task" value="<?= htmlspecialchars($selectedTask, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="header" value="<?= htmlspecialchars((string) ($resolvedHeader ?? $selectedHeaderInput), ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="number" value="<?= (int) ($row['number'] ?? 0) ?>" />
                                            <button type="submit" class="button review-delete-button">Delete</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="question-preview-empty">
                                <p class="main-text">No questions yet</p>
                                <p class="text">Add questions to see them here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="question-preview-empty">
                        <p class="main-text">Load a question bank</p>
                        <p class="text">Click “Load Question Bank” to preview and edit questions.</p>
                    </div>
                <?php endif; ?>
            </div>
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
            <input type="hidden" name="header" value="<?= htmlspecialchars((string) ($resolvedHeader ?? $selectedHeaderInput), ENT_QUOTES, 'UTF-8') ?>" />
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
        const openButton = document.getElementById('openImagePicker');
        const imageOverlay = document.getElementById('imageOverlay');
        const closeButton = document.getElementById('closeImageOverlay');
        const chooseButton = document.getElementById('chooseImageButton');
        const confirmButton = document.getElementById('confirmImageButton');
        const fileInput = document.getElementById('questionImageInput');
        const selectedImageName = document.getElementById('selectedImageName');
        const overlaySelectedFile = document.getElementById('overlaySelectedFile');
        const formImagePreviewBox = document.getElementById('formImagePreviewBox');
        const formImagePreview = document.getElementById('formImagePreview');
        const overlayImagePreviewBox = document.getElementById('overlayImagePreviewBox');
        const overlayImagePreview = document.getElementById('overlayImagePreview');

        if (!openButton || !imageOverlay || !closeButton || !chooseButton || !confirmButton || !fileInput || !selectedImageName || !overlaySelectedFile || !formImagePreviewBox || !formImagePreview || !overlayImagePreviewBox || !overlayImagePreview) {
            return;
        }

        const clearPreview = function() {
            formImagePreviewBox.hidden = true;
            overlayImagePreviewBox.hidden = true;
            formImagePreview.src = '';
            overlayImagePreview.src = '';
        };

        const setPreview = function(file) {
            const objectUrl = URL.createObjectURL(file);
            formImagePreview.src = objectUrl;
            overlayImagePreview.src = objectUrl;
            formImagePreviewBox.hidden = false;
            overlayImagePreviewBox.hidden = false;
        };

        const openOverlay = function() {
            imageOverlay.classList.add('active');
            imageOverlay.setAttribute('aria-hidden', 'false');
        };

        const closeOverlay = function() {
            imageOverlay.classList.remove('active');
            imageOverlay.setAttribute('aria-hidden', 'true');
        };

        const refreshSelectedFile = function() {
            const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            if (!file) {
                confirmButton.disabled = true;
                overlaySelectedFile.textContent = 'No file selected.';
                selectedImageName.textContent = 'No image selected.';
                clearPreview();
                return;
            }

            const isImageFile = file.type && file.type.toLowerCase().startsWith('image/');
            if (!isImageFile) {
                fileInput.value = '';
                confirmButton.disabled = true;
                overlaySelectedFile.textContent = 'Invalid file type. Please choose an image.';
                selectedImageName.textContent = 'No image selected.';
                clearPreview();
                return;
            }

            confirmButton.disabled = false;
            overlaySelectedFile.textContent = file.name;
            selectedImageName.textContent = 'Selected image: ' + file.name;
            setPreview(file);
        };

        openButton.addEventListener('click', openOverlay);
        closeButton.addEventListener('click', closeOverlay);
        chooseButton.addEventListener('click', function() {
            fileInput.click();
        });
        fileInput.addEventListener('change', refreshSelectedFile);
        confirmButton.addEventListener('click', function() {
            if (!confirmButton.disabled) {
                closeOverlay();
            }
        });

        imageOverlay.addEventListener('click', function(event) {
            if (event.target === imageOverlay) {
                closeOverlay();
            }
        });

        const questionEditOverlay = document.getElementById('questionEditOverlay');
        const questionEditClose = document.getElementById('questionEditClose');
        const editButtons = document.querySelectorAll('.open-edit-modal');

        const editFields = {
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

        const openEditModal = function(data) {
            if (!questionEditOverlay) {
                return;
            }
            editFields.original_number.value = data.number || '';
            editFields.number.value = data.number || '';
            editFields.question.value = data.question || '';
            editFields.choice1.value = data.choice1 || '';
            editFields.choice2.value = data.choice2 || '';
            editFields.choice3.value = data.choice3 || '';
            editFields.choice4.value = data.choice4 || '';
            editFields.correct_answer.value = data.correct_answer || '';
            resetEditImageState(data.image_path || '');

            questionEditOverlay.classList.add('active');
            questionEditOverlay.setAttribute('aria-hidden', 'false');
        };

        const closeEditModal = function() {
            if (!questionEditOverlay) {
                return;
            }
            questionEditOverlay.classList.remove('active');
            questionEditOverlay.setAttribute('aria-hidden', 'true');
        };

        editButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const raw = button.getAttribute('data-question');
                if (!raw) {
                    return;
                }
                try {
                    const data = JSON.parse(raw);
                    openEditModal(data);
                } catch (error) {
                    console.error('Failed to open edit modal:', error);
                }
            });
        });

        if (questionEditClose) {
            questionEditClose.addEventListener('click', closeEditModal);
        }

        if (questionEditOverlay) {
            questionEditOverlay.addEventListener('click', function(event) {
                if (event.target === questionEditOverlay) {
                    closeEditModal();
                }
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && questionEditOverlay && questionEditOverlay.classList.contains('active')) {
                closeEditModal();
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
        const durationHoursInput = document.getElementById('duration_hours');
        const durationMinutesInput = document.getElementById('duration_minutes');
        const questionLimitInput = document.getElementById('question_limit');
        const durationPill = document.getElementById('durationPill');
        const durationEditBtn = document.getElementById('durationEditBtn');
        const durationEditPanel = document.getElementById('durationEditPanel');
        const durationSaveBtn = document.getElementById('durationSaveBtn');
        const durationCancelBtn = document.getElementById('durationCancelBtn');
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';
        const questionForm = document.getElementById('questionForm');
        const questionNavPrev = document.getElementById('questionNavPrev');
        const questionNavNext = document.getElementById('questionNavNext');
        const questionNavStatus = document.getElementById('questionNavStatus');
        const formOriginalNumber = document.getElementById('form_original_number');
        const formNumberInput = document.querySelector("input[name='number']");
        const formQuestionInput = document.querySelector("textarea[name='question']");
        const formChoice1Input = document.querySelector("input[name='choice1']");
        const formChoice2Input = document.querySelector("input[name='choice2']");
        const formChoice3Input = document.querySelector("input[name='choice3']");
        const formChoice4Input = document.querySelector("input[name='choice4']");
        const formCorrectInput = document.querySelector("input[name='correct_answer']");
        const headerTextWrap = document.getElementById('headerTextWrap');
        const headerTermWrap = document.getElementById('headerTermWrap');
        const saveButton = document.getElementById('save');
        const createTaskButton = document.getElementById('createTaskButton');
        const createContextField = document.getElementById('create_context');
        const assessmentIdField = document.getElementById('assessment_id');
        const existingContextsBox = document.getElementById('existingContextsBox');
        const deleteExamBankWrap = document.getElementById('deleteExamBankWrap');
        const deleteExamBankButton = document.getElementById('deleteExamBankButton');
        const deleteExamSubject = document.getElementById('delete_exam_subject');
        const deleteExamClass = document.getElementById('delete_exam_class');
        const deleteExamTask = document.getElementById('delete_exam_task');
        const deleteExamTerm = document.getElementById('delete_exam_term');
        const deleteExamHeader = document.getElementById('delete_exam_header');
        const moveContextWrap = document.getElementById('moveContextWrap');
        const moveContextToggle = document.getElementById('moveContextToggle');
        const moveContextPanel = document.getElementById('moveContextPanel');
        const moveContextCancel = document.getElementById('moveContextCancel');
        const moveContextSubmit = document.getElementById('moveContextSubmit');
        const moveContextId = document.getElementById('move_context_id');
        const moveContextSubject = document.getElementById('move_context_subject');
        const moveContextClass = document.getElementById('move_context_class');
        const moveContextTask = document.getElementById('move_context_task');
        const moveContextTerm = document.getElementById('move_context_term');
        const moveContextHeader = document.getElementById('move_context_header');
        const moveTargetClass = document.getElementById('move_target_class');
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

        const escapeHtml = function(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const buildContextMarkup = function(data) {
            if (!existingContextsBox) {
                return;
            }

            const contexts = Array.isArray(data.available_contexts) ? data.available_contexts : [];
            if (!contexts.length) {
                existingContextsBox.innerHTML = '<p class="text">' + escapeHtml(data.empty_text || 'No available context yet.') + '</p>';
                return;
            }

            const safeSubject = escapeHtml(String(data.subject || (subjectSelect ? subjectSelect.value : '')));
            const safeClass = escapeHtml(String(data.student_class || (classSelect ? classSelect.value : '')));
            const safeTask = escapeHtml(String(data.task || (taskSelect ? taskSelect.value : 'exam')));
            const safeTerm = escapeHtml(String(data.term || (termSelect ? termSelect.value : 'first_term')));

            const rows = contexts.map(function(context) {
                const buttonClass = context.is_current ? 'mini-btn current' : 'mini-btn ghost';
                const safeLabel = escapeHtml(context.label || 'Custom');
                const safeOpenUrl = escapeHtml(context.open_url || '#');
                const safeContextId = escapeHtml(context.id || '');
                const safeHeader = escapeHtml(context.header || '');

                const deleteMarkup = '' +
                    '  <form action="/teacher/add-question/context-delete" method="POST" data-warning-confirm="Delete this context and all its questions?">' +
                    '    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>" />' +
                    '    <input type="hidden" name="context_id" value="' + safeContextId + '" />' +
                    '    <input type="hidden" name="subject" value="' + safeSubject + '" />' +
                    '    <input type="hidden" name="student_class" value="' + safeClass + '" />' +
                    '    <input type="hidden" name="task" value="' + safeTask + '" />' +
                    '    <input type="hidden" name="term" value="' + safeTerm + '" />' +
                    '    <input type="hidden" name="header" value="' + safeHeader + '" />' +
                    '    <button type="submit" class="mini-btn ghost">Delete</button>' +
                    '  </form>';

                return '' +
                    '<div class="context-pill-row">' +
                    '  <a href="' + safeOpenUrl + '" class="' + buttonClass + '">' + safeLabel + '</a>' +
                    deleteMarkup +
                    '</div>';
            });

            existingContextsBox.innerHTML = rows.join('');
        };

        let refreshContextsDebounce = null;
        let refreshRequestCounter = 0;
        const refreshAvailableContexts = function() {
            if (!subjectSelect || !classSelect || !taskSelect || !existingContextsBox) {
                return;
            }

            const params = new URLSearchParams();
            params.set('subject', subjectSelect.value);
            params.set('student_class', classSelect.value);
            params.set('task', taskSelect.value);
            params.set('term', termSelect ? termSelect.value : 'first_term');
            params.set('header', headerInput ? headerInput.value : '');
            params.set('duration_hours', durationHoursInput ? durationHoursInput.value : '0');
            params.set('duration_minutes', durationMinutesInput ? durationMinutesInput.value : '0');
            params.set('question_limit', questionLimitInput ? questionLimitInput.value : '20');
            params.set('assessment_id', assessmentIdField ? assessmentIdField.value : '');

            const requestId = ++refreshRequestCounter;
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
                .then(function(data) {
                    if (requestId !== refreshRequestCounter) {
                        return;
                    }
                    buildContextMarkup(data || {});
                })
                .catch(function(error) {
                    console.error(error);
                });
        };

        const queueContextRefresh = function(delay) {
            clearTimeout(refreshContextsDebounce);
            refreshContextsDebounce = setTimeout(function() {
                refreshAvailableContexts();
            }, delay || 0);
        };

        const closeMoveContextPanel = function() {
            if (!moveContextWrap || !moveContextPanel) {
                return;
            }
            moveContextWrap.classList.remove('is-open');
        };

        const updateMoveContextState = function() {
            if (!moveContextWrap || !moveContextToggle || !moveContextPanel || !moveContextId || !moveTargetClass) {
                return;
            }

            const hasSelectedContext = !!(assessmentIdField && String(assessmentIdField.value || '').trim() !== '');
            moveContextWrap.classList.toggle('is-active', hasSelectedContext);
            moveContextToggle.disabled = !hasSelectedContext;
            moveTargetClass.disabled = !hasSelectedContext;
            if (moveContextSubmit) {
                moveContextSubmit.disabled = !hasSelectedContext;
            }

            if (moveContextSubject && subjectSelect) {
                moveContextSubject.value = subjectSelect.value || '';
            }
            if (moveContextClass && classSelect) {
                moveContextClass.value = classSelect.value || '';
            }
            if (moveContextTask && taskSelect) {
                moveContextTask.value = taskSelect.value || 'exam';
            }
            if (moveContextTerm && termSelect) {
                moveContextTerm.value = termSelect.value || 'first_term';
            }
            if (moveContextHeader && headerInput) {
                moveContextHeader.value = headerInput.value || '';
            }
            if (moveContextId && assessmentIdField) {
                moveContextId.value = assessmentIdField.value || '';
            }

            const currentClass = classSelect ? String(classSelect.value || '') : '';
            Array.from(moveTargetClass.options).forEach(function(option) {
                option.disabled = currentClass !== '' && option.value === currentClass;
            });

            if (!hasSelectedContext) {
                closeMoveContextPanel();
                return;
            }

            if (moveTargetClass.value === currentClass) {
                const fallbackOption = Array.from(moveTargetClass.options).find(function(option) {
                    return !option.disabled;
                });
                if (fallbackOption) {
                    moveTargetClass.value = fallbackOption.value;
                }
            }
        };

        if (subjectSelect && classSelect) {
            subjectSelect.addEventListener('change', function() {
                renderClassOptions(subjectSelect.value);
                if (assessmentIdField) {
                    assessmentIdField.value = '';
                }
                updateDeleteBankState();
                updateMoveContextState();
                queueContextRefresh(0);
            });

            renderClassOptions(subjectSelect.value);
        }

        if (classSelect) {
            classSelect.addEventListener('change', function() {
                if (assessmentIdField) {
                    assessmentIdField.value = '';
                }
                updateDeleteBankState();
                updateMoveContextState();
                queueContextRefresh(0);
            });
        }

        const toggleTaskFields = function() {
            if (!taskSelect || !headerTextWrap || !headerTermWrap || !headerInput || !termSelect || !durationHoursInput || !durationMinutesInput) {
                return;
            }

            const task = String(taskSelect.value || 'exam').toLowerCase();
            const isExam = task === 'exam';

            headerTextWrap.style.display = isExam ? 'none' : '';
            headerTermWrap.style.display = isExam ? '' : 'none';
            headerInput.required = !isExam;
            termSelect.required = isExam;
            durationHoursInput.required = isExam;
            durationMinutesInput.required = isExam;

            if (saveButton) {
                saveButton.style.display = isExam ? '' : 'none';
            }

            if (createTaskButton) {
                let label = 'Create Context';
                if (task === 'assignment') label = 'Create Assignment';
                if (task === 'classwork') label = 'Create Classwork';
                if (task === 'test') label = 'Create Test';
                if (task === 'others') label = 'Create Others';
                createTaskButton.textContent = label;
                createTaskButton.style.display = isExam ? 'none' : '';
            }

            if (existingContextsBox) {
                existingContextsBox.style.display = isExam ? 'none' : '';
            }

            if (deleteExamBankWrap) {
                deleteExamBankWrap.style.display = isExam ? '' : 'none';
            }
        };

        const formatDurationLabel = function(hoursValue, minutesValue) {
            const hours = Math.max(0, Math.min(8, Number(hoursValue || 0)));
            const minutes = Math.max(0, Math.min(59, Number(minutesValue || 0)));
            if (hours === 0 && minutes === 0) {
                return 'Not set — proceed at your pace.';
            }
            const parts = [];
            if (hours > 0) {
                parts.push(hours + 'h');
            }
            if (minutes > 0) {
                parts.push(minutes + 'm');
            }
            return parts.join(' ');
        };

        const syncDurationPill = function() {
            if (!durationPill || !durationHoursInput || !durationMinutesInput) {
                return;
            }
            durationPill.textContent = formatDurationLabel(durationHoursInput.value, durationMinutesInput.value);
        };

        const questionNavItems = <?= json_encode($questionRows ?? [], JSON_UNESCAPED_SLASHES) ?>;
        const nextQuestionNumber = <?= json_encode((int) ($nextNumber ?? 1), JSON_UNESCAPED_SLASHES) ?>;
        const navQuestions = Array.isArray(questionNavItems)
            ? questionNavItems.map(function(item) {
                return {
                    number: item.number || '',
                    question: item.question || '',
                    choice1: item.choice1 || '',
                    choice2: item.choice2 || '',
                    choice3: item.choice3 || '',
                    choice4: item.choice4 || '',
                    correct_answer: item.correct_answer || '',
                    image_path: item.image_path || ''
                };
            })
            : [];
        let questionNavIndex = -1;

        const setFormMode = function(mode, originalNumber) {
            if (!questionForm) return;
            const addAction = questionForm.getAttribute('data-add-action') || '/teacher/add-question';
            const editAction = questionForm.getAttribute('data-edit-action') || '/teacher/check-question/update';
            if (mode === 'edit') {
                questionForm.setAttribute('action', editAction);
                if (formOriginalNumber) {
                    formOriginalNumber.value = String(originalNumber || '');
                }
                if (document.getElementById('add')) {
                    document.getElementById('add').textContent = 'Save Changes';
                }
            } else {
                questionForm.setAttribute('action', addAction);
                if (formOriginalNumber) {
                    formOriginalNumber.value = '';
                }
                if (document.getElementById('add')) {
                    document.getElementById('add').textContent = 'Add Question';
                }
            }
        };

        const updateNavState = function() {
            if (!questionNavStatus) return;
            const total = navQuestions.length;
            if (total === 0 || questionNavIndex < 0) {
                questionNavStatus.textContent = 'Question';
            } else {
                questionNavStatus.textContent = 'Question ' + String(questionNavIndex + 1) + ' of ' + String(total);
            }

            if (questionNavPrev) {
                questionNavPrev.disabled = total === 0 || questionNavIndex <= 0;
            }
            if (questionNavNext) {
                questionNavNext.disabled = total === 0 ? false : questionNavIndex > total - 1;
            }
        };

        const loadQuestionIntoForm = function(index) {
            if (!navQuestions[index]) {
                return;
            }
            const item = navQuestions[index];
            questionNavIndex = index;
            if (formNumberInput) formNumberInput.value = item.number || '';
            if (formQuestionInput) formQuestionInput.value = item.question || '';
            if (formChoice1Input) formChoice1Input.value = item.choice1 || '';
            if (formChoice2Input) formChoice2Input.value = item.choice2 || '';
            if (formChoice3Input) formChoice3Input.value = item.choice3 || '';
            if (formChoice4Input) formChoice4Input.value = item.choice4 || '';
            if (formCorrectInput) formCorrectInput.value = item.correct_answer || '';
            setFormMode('edit', item.number || '');

            if (typeof resetEditImageState === 'function') {
                // no-op in this scope
            }
            if (typeof window !== 'undefined') {
                // update image preview for add form if available
                const formPreviewBox = document.getElementById('formImagePreviewBox');
                const formPreview = document.getElementById('formImagePreview');
                const selectedImageName = document.getElementById('selectedImageName');
                if (formPreviewBox && formPreview) {
                    if (item.image_path) {
                        formPreview.src = item.image_path;
                        formPreviewBox.hidden = false;
                        if (selectedImageName) {
                            selectedImageName.textContent = 'Current image attached.';
                        }
                    } else {
                        formPreview.src = '';
                        formPreviewBox.hidden = true;
                        if (selectedImageName) {
                            selectedImageName.textContent = 'No image selected.';
                        }
                    }
                }
            }
            updateNavState();
        };

        const resetToNewQuestion = function() {
            questionNavIndex = -1;
            if (formNumberInput) formNumberInput.value = nextQuestionNumber ? String(nextQuestionNumber) : '';
            if (formQuestionInput) formQuestionInput.value = '';
            if (formChoice1Input) formChoice1Input.value = '';
            if (formChoice2Input) formChoice2Input.value = '';
            if (formChoice3Input) formChoice3Input.value = '';
            if (formChoice4Input) formChoice4Input.value = '';
            if (formCorrectInput) formCorrectInput.value = '';
            setFormMode('add');

            const formPreviewBox = document.getElementById('formImagePreviewBox');
            const formPreview = document.getElementById('formImagePreview');
            const selectedImageName = document.getElementById('selectedImageName');
            if (formPreviewBox && formPreview) {
                formPreview.src = '';
                formPreviewBox.hidden = true;
            }
            if (selectedImageName) {
                selectedImageName.textContent = 'No image selected.';
            }
            updateNavState();
        };

        const closeDurationPanel = function() {
            if (!durationEditPanel) return;
            durationEditPanel.hidden = true;
        };

        const openDurationPanel = function() {
            if (!durationEditPanel) return;
            durationEditPanel.hidden = false;
        };

        const updateDeleteBankState = function() {
            if (!deleteExamBankWrap || !deleteExamBankButton) {
                return;
            }

            if (deleteExamSubject && subjectSelect) {
                deleteExamSubject.value = subjectSelect.value || '';
            }
            if (deleteExamClass && classSelect) {
                deleteExamClass.value = classSelect.value || '';
            }
            if (deleteExamTask && taskSelect) {
                deleteExamTask.value = taskSelect.value || 'exam';
            }
            if (deleteExamTerm && termSelect) {
                deleteExamTerm.value = termSelect.value || 'first_term';
            }
            if (deleteExamHeader && headerInput) {
                deleteExamHeader.value = headerInput.value || '';
            }

            const task = String(taskSelect && taskSelect.value ? taskSelect.value : 'exam').toLowerCase();
            const isExam = task === 'exam';
            const selectedDeleteChecks = deleteExamBankWrap.querySelectorAll('input[name="context_ids[]"]:checked');
            deleteExamBankButton.disabled = !(isExam && selectedDeleteChecks.length > 0);
        };

        if (deleteExamBankWrap) {
            deleteExamBankWrap.addEventListener('change', function(event) {
                const target = event.target;
                if (target && target.matches('input[name="context_ids[]"]')) {
                    updateDeleteBankState();
                }
            });
        }

        if (createTaskButton && createContextField && assessmentIdField) {
            createTaskButton.addEventListener('click', function() {
                createContextField.value = '1';
                assessmentIdField.value = '';
                updateDeleteBankState();
                updateMoveContextState();
            });
        }

        if (saveButton && createContextField) {
            saveButton.addEventListener('click', function() {
                createContextField.value = '0';
            });
        };

        if (taskSelect) {
            taskSelect.addEventListener('change', function() {
                if (assessmentIdField) {
                    assessmentIdField.value = '';
                }
                toggleTaskFields();
                updateDeleteBankState();
                updateMoveContextState();
                queueContextRefresh(0);
            });
            toggleTaskFields();
            updateDeleteBankState();
            updateMoveContextState();
        }

        if (headerInput) {
            headerInput.addEventListener('input', function() {
                if (assessmentIdField) {
                    assessmentIdField.value = '';
                }
                updateDeleteBankState();
                updateMoveContextState();
                queueContextRefresh(250);
            });
        }

        if (termSelect) {
            termSelect.addEventListener('change', function() {
                if (assessmentIdField) {
                    assessmentIdField.value = '';
                }
                updateDeleteBankState();
                updateMoveContextState();
                queueContextRefresh(0);
            });
        }

        if (durationHoursInput) {
            durationHoursInput.addEventListener('input', function() {
                if (assessmentIdField) {
                    assessmentIdField.value = '';
                }
                updateDeleteBankState();
                updateMoveContextState();
                queueContextRefresh(250);
                syncDurationPill();
            });
        }

        if (durationMinutesInput) {
            durationMinutesInput.addEventListener('input', function() {
                if (assessmentIdField) {
                    assessmentIdField.value = '';
                }
                updateDeleteBankState();
                updateMoveContextState();
                queueContextRefresh(250);
                syncDurationPill();
            });
        }

        if (durationEditBtn && durationEditPanel) {
            durationEditBtn.addEventListener('click', function() {
                if (durationEditPanel.hidden) {
                    openDurationPanel();
                } else {
                    closeDurationPanel();
                }
            });
        }

        if (durationSaveBtn) {
            durationSaveBtn.addEventListener('click', function() {
                if (!durationHoursInput || !durationMinutesInput) {
                    return;
                }

                const formData = new FormData();
                formData.append('subject', subjectSelect ? subjectSelect.value : '');
                formData.append('student_class', classSelect ? classSelect.value : '');
                formData.append('task', taskSelect ? taskSelect.value : 'exam');
                formData.append('header', headerInput ? headerInput.value : '');
                formData.append('term', termSelect ? termSelect.value : 'first_term');
                formData.append('assessment_id', assessmentIdField ? assessmentIdField.value : '');
                formData.append('duration_hours', String(durationHoursInput.value || 0));
                formData.append('duration_minutes', String(durationMinutesInput.value || 0));

                durationSaveBtn.disabled = true;
                fetch('/teacher/add-question/duration', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: formData
                })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(payload) {
                        if (!payload || payload.ok !== true) {
                            durationSaveBtn.disabled = false;
                            return;
                        }
                        syncDurationPill();
                        closeDurationPanel();
                    })
                    .catch(function() {
                        durationSaveBtn.disabled = false;
                    })
                    .finally(function() {
                        durationSaveBtn.disabled = false;
                    });
            });
        }

        if (durationCancelBtn) {
            durationCancelBtn.addEventListener('click', function() {
                closeDurationPanel();
            });
        }

        if (questionLimitInput) {
            questionLimitInput.addEventListener('input', function() {
                if (assessmentIdField) {
                    assessmentIdField.value = '';
                }
                updateDeleteBankState();
                updateMoveContextState();
                queueContextRefresh(250);
            });
        }

        if (moveContextToggle) {
            moveContextToggle.addEventListener('click', function() {
                if (moveContextToggle.disabled || !moveContextWrap) {
                    return;
                }
                moveContextWrap.classList.toggle('is-open');
            });
        }

        if (moveContextCancel) {
            moveContextCancel.addEventListener('click', function() {
                closeMoveContextPanel();
            });
        }

        syncDurationPill();
        updateNavState();
        updateMoveContextState();

        if (questionNavPrev) {
            questionNavPrev.addEventListener('click', function() {
                if (navQuestions.length === 0) return;
                const nextIndex = questionNavIndex <= 0 ? 0 : questionNavIndex - 1;
                loadQuestionIntoForm(nextIndex);
            });
        }

        if (questionNavNext) {
            questionNavNext.addEventListener('click', function() {
                const total = navQuestions.length;
                if (total === 0) {
                    return;
                }
                const nextIndex = questionNavIndex < 0 ? 0 : questionNavIndex + 1;
                if (nextIndex >= total) {
                    resetToNewQuestion();
                    return;
                }
                loadQuestionIntoForm(nextIndex);
            });
        }

        resetToNewQuestion();
    })();
</script>
<?php loadPartial('end') ?>
