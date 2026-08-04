<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>
<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="feedback-shell">
            <div class="feedback-hero">
                <div class="feedback-kicker"><i class="fa fa-commenting-o" aria-hidden="true"></i> Anonymous feedback</div>
                <h1>Student feedback for each teacher</h1>
                <p>Written feedback is shown anonymously and ordered from newest to oldest.</p>
            </div>

            <div class="feedback-grid feedback-grid--split">
                <aside class="feedback-side-panel">
                    <h2 class="feedback-side-title">Teachers</h2>
                    <div class="feedback-side-list">
                        <?php foreach (($teacherRows ?? []) as $teacher): ?>
                            <?php $teacherId = (int) ($teacher['id'] ?? 0); $isActive = $selectedTeacherId === $teacherId; ?>
                            <a class="feedback-side-item <?= $isActive ? 'active' : '' ?>" href="/admin/feedback/list?teacher_id=<?= $teacherId ?>">
                                <span><?= htmlspecialchars((string) ($teacher['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <small><?= (int) (($teacherStats[$teacherId]['count'] ?? 0)) ?> feedback</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <div class="feedback-content-panel">
                    <?php if ($selectedTeacherId > 0): ?>
                        <?php $teacherName = htmlspecialchars((string) (($teacherLookup[$selectedTeacherId] ?? '') ?: 'Selected teacher'), ENT_QUOTES, 'UTF-8'); ?>
                        <div class="feedback-panel-head">
                            <h2><?= $teacherName ?></h2>
                            <p>Anonymous submissions only</p>
                        </div>

                        <?php if (!empty($selectedFeedbackRows)): ?>
                            <div class="feedback-list">
                                <?php foreach (($selectedFeedbackRows ?? []) as $feedback): ?>
                                    <?php $statusKey = normalizeFeedbackStatus((string) ($feedback['review_status'] ?? 'pending_review')); $isApproved = $statusKey === 'approved'; $editorText = (string) (($feedback['moderated_feedback'] ?? '') !== '' ? ($feedback['moderated_feedback'] ?? '') : ($feedback['feedback_text'] ?? '')); ?>
                                    <article class="feedback-card">
                                        <div class="feedback-item-head">
                                            <div>
                                                <div class="feedback-item-title">Student response</div>
                                                <div class="feedback-meta-row">
                                                    <span class="feedback-meta-pill"><i class="fa fa-clock-o" aria-hidden="true"></i> <?= htmlspecialchars((string) ($feedback['submitted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="feedback-meta-pill"><i class="fa fa-lock" aria-hidden="true"></i> Anonymous</span>
                                                    <span class="feedback-status-chip feedback-status-chip--<?= $isApproved ? 'approved' : 'pending' ?>"><?= $isApproved ? 'Approved' : 'Pending' ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="feedback-preview"><?= htmlspecialchars((string) ($feedback['feedback_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>

                                        <form class="feedback-moderation-form" method="POST" action="/admin/feedback">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="feedback_id" value="<?= (int) ($feedback['id'] ?? 0) ?>" />
                                            <input type="hidden" name="review_status" value="<?= htmlspecialchars((string) ($feedback['review_status'] ?? 'pending_review'), ENT_QUOTES, 'UTF-8') ?>" />
                                            <div class="feedback-card-actions">
                                                <button type="button" class="feedback-action-btn feedback-action-btn--secondary" data-edit-toggle="editor-<?= (int) ($feedback['id'] ?? 0) ?>">Edit</button>
                                                <?php if ($isApproved): ?>
                                                    <span class="feedback-action-badge">Approved</span>
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="approve" class="feedback-action-btn feedback-action-btn--primary">Approve</button>
                                                <?php endif; ?>
                                            </div>

                                            <div class="feedback-editor" id="editor-<?= (int) ($feedback['id'] ?? 0) ?>">
                                                <label class="feedback-label" for="moderated_feedback_<?= (int) ($feedback['id'] ?? 0) ?>">Moderated feedback</label>
                                                <textarea id="moderated_feedback_<?= (int) ($feedback['id'] ?? 0) ?>" name="moderated_feedback" class="feedback-textarea" rows="4"><?= htmlspecialchars($editorText, ENT_QUOTES, 'UTF-8') ?></textarea>
                                                <div class="feedback-editor-actions">
                                                    <button type="submit" name="action" value="save" class="feedback-action-btn feedback-action-btn--primary">Save</button>
                                                    <button type="button" class="feedback-action-btn feedback-action-btn--ghost" data-cancel-editor="editor-<?= (int) ($feedback['id'] ?? 0) ?>">Cancel</button>
                                                </div>
                                            </div>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="feedback-empty"><i class="fa fa-commenting-o" aria-hidden="true"></i><p>No feedback has been submitted for this teacher yet.</p></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="feedback-empty"><i class="fa fa-commenting-o" aria-hidden="true"></i><p>Select a teacher to view feedback.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-edit-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = button.getAttribute('data-edit-toggle');
            const editor = document.getElementById(targetId);
            if (!editor) {
                return;
            }
            editor.classList.add('active');
            const textarea = editor.querySelector('textarea');
            if (textarea) {
                textarea.focus();
            }
        });
    });

    document.querySelectorAll('[data-cancel-editor]').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = button.getAttribute('data-cancel-editor');
            const editor = document.getElementById(targetId);
            if (!editor) {
                return;
            }
            editor.classList.remove('active');
        });
    });
});
</script>
<?php loadPartial('end') ?>
