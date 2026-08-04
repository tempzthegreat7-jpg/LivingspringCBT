<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>
<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="feedback-shell">
            <div class="feedback-hero">
                <div class="feedback-kicker"><i class="fa fa-commenting-o" aria-hidden="true"></i> Admin review board</div>
                <h1>Moderate and release feedback</h1>
                <p>Review anonymous submissions, edit inappropriate language, assign a teacher, and approve or reject each item before it becomes visible.</p>
            </div>

            <div class="feedback-summary-grid">
                <div class="feedback-metric-card">
                    <p class="label">Total feedback</p>
                    <p class="value"><?= count($feedbackRows ?? []) ?></p>
                </div>
                <div class="feedback-metric-card">
                    <p class="label">Pending</p>
                    <p class="value"><?= count(array_filter($feedbackRows ?? [], function ($row) { return (string) ($row['review_status'] ?? 'pending_review') === 'pending_review'; })) ?></p>
                </div>
                <div class="feedback-metric-card">
                    <p class="label">Approved</p>
                    <p class="value"><?= count(array_filter($feedbackRows ?? [], function ($row) { return (string) ($row['review_status'] ?? 'pending_review') === 'approved'; })) ?></p>
                </div>
            </div>

            <?php if ($message = Session::getFlashMessage('error_message')): ?>
                <div class="feedback-alert feedback-alert--error"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($message = Session::getFlashMessage('success_message')): ?>
                <div class="feedback-alert feedback-alert--success"><i class="fa fa-check-circle" aria-hidden="true"></i> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if (!empty($feedbackRows ?? [])): ?>
                <div class="feedback-list">
                    <?php foreach (($feedbackRows ?? []) as $feedback): ?>
                        <article class="feedback-card">
                            <div class="feedback-item-head">
                                <div>
                                    <div class="feedback-item-title">Feedback #<?= (int) ($feedback['id'] ?? 0) ?></div>
                                    <div class="feedback-meta-row">
                                        <span class="feedback-meta-pill"><i class="fa fa-user" aria-hidden="true"></i> <?= htmlspecialchars((string) (($teacherLookup[(int) ($feedback['teacher_user_id'] ?? 0)] ?? '') ?: 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="feedback-meta-pill"><i class="fa fa-clock-o" aria-hidden="true"></i> <?= htmlspecialchars((string) ($feedback['submitted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                                <span class="feedback-badge feedback-badge--<?= htmlspecialchars((string) ($feedback['review_status'] ?? 'pending_review'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(feedbackStatusLabel((string) ($feedback['review_status'] ?? 'pending_review')), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="feedback-score-grid">
                                <div class="feedback-score-pill"><strong>Teaching (Explanation):</strong> <?= (int) ($feedback['rating_teaching_explanation'] ?? 0) ?>/5</div>
                                <div class="feedback-score-pill"><strong>Teaching (Clarity):</strong> <?= (int) ($feedback['rating_teaching_clarity'] ?? 0) ?>/5</div>
                                <div class="feedback-score-pill"><strong>Punctuality to Class:</strong> <?= (int) ($feedback['rating_punctuality_to_class'] ?? 0) ?>/5</div>
                                <div class="feedback-score-pill"><strong>Approachable:</strong> <?= (int) ($feedback['rating_approachable'] ?? 0) ?>/5</div>
                                <div class="feedback-score-pill"><strong>Likeable:</strong> <?= (int) ($feedback['rating_likeable'] ?? 0) ?>/5</div>
                                <div class="feedback-score-pill"><strong>Discipline:</strong> <?= (int) ($feedback['rating_discipline'] ?? 0) ?>/5</div>
                                <div class="feedback-score-pill"><strong>Overall:</strong> <?= (int) ($feedback['rating_overall'] ?? 0) ?>/5</div>
                            </div>
                            <p class="feedback-preview" style="margin-top:12px;"><strong>Original:</strong> <?= htmlspecialchars((string) ($feedback['feedback_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="feedback-preview" style="margin-top:10px;"><strong>Moderated:</strong> <?= htmlspecialchars((string) (($feedback['moderated_feedback'] ?? '') ?: 'Not edited yet'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="feedback-preview" style="margin-top:10px;"><strong>Notes:</strong> <?= htmlspecialchars((string) (($feedback['review_notes'] ?? '') ?: 'No notes yet'), ENT_QUOTES, 'UTF-8') ?></p>

                            <form action="/admin/feedback" method="POST" class="feedback-form-grid" style="margin-top:14px;">
                                <?= csrfField() ?>
                                <input type="hidden" name="feedback_id" value="<?= (int) ($feedback['id'] ?? 0) ?>" />
                                <div class="feedback-field">
                                    <label class="feedback-label" for="teacher_user_id_<?= (int) ($feedback['id'] ?? 0) ?>">Assign teacher</label>
                                    <select id="teacher_user_id_<?= (int) ($feedback['id'] ?? 0) ?>" name="teacher_user_id" class="feedback-select">
                                        <option value="">Unassigned</option>
                                        <?php foreach (($teacherRows ?? []) as $teacher): ?>
                                            <option value="<?= (int) ($teacher['id'] ?? 0) ?>" <?= ((int) ($feedback['teacher_user_id'] ?? 0) === (int) ($teacher['id'] ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($teacher['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="feedback-field">
                                    <label class="feedback-label" for="moderated_feedback_<?= (int) ($feedback['id'] ?? 0) ?>">Moderated feedback</label>
                                    <textarea id="moderated_feedback_<?= (int) ($feedback['id'] ?? 0) ?>" name="moderated_feedback" class="feedback-textarea" rows="4"><?= htmlspecialchars((string) ($feedback['moderated_feedback'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="feedback-field">
                                    <label class="feedback-label" for="review_notes_<?= (int) ($feedback['id'] ?? 0) ?>">Review notes</label>
                                    <textarea id="review_notes_<?= (int) ($feedback['id'] ?? 0) ?>" name="review_notes" class="feedback-textarea" rows="3"><?= htmlspecialchars((string) ($feedback['review_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="feedback-field">
                                    <label class="feedback-label" for="review_status_<?= (int) ($feedback['id'] ?? 0) ?>">Status</label>
                                    <select id="review_status_<?= (int) ($feedback['id'] ?? 0) ?>" name="review_status" class="feedback-select">
                                        <option value="pending_review" <?= (string) ($feedback['review_status'] ?? 'pending_review') === 'pending_review' ? 'selected' : '' ?>>Pending Review</option>
                                        <option value="approved" <?= (string) ($feedback['review_status'] ?? 'pending_review') === 'approved' ? 'selected' : '' ?>>Approved</option>
                                        <option value="rejected" <?= (string) ($feedback['review_status'] ?? 'pending_review') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        <option value="archived" <?= (string) ($feedback['review_status'] ?? 'pending_review') === 'archived' ? 'selected' : '' ?>>Archived</option>
                                    </select>
                                </div>
                                <button type="submit" class="feedback-btn">Save review</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="feedback-empty">
                    <i class="fa fa-commenting-o" aria-hidden="true"></i>
                    <p>No feedback found yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</section>
<?php loadPartial('end') ?>
