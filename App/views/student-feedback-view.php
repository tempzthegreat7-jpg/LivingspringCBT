<?php loadPartial('student-panel-head') ?>
<?php loadPartial('student-sidebar') ?>
<section>
    <?php loadPartial('student-header') ?>
    <main>
        <div class="feedback-shell">
            <div class="feedback-hero">
                <div class="feedback-kicker"><i class="fa fa-commenting-o" aria-hidden="true"></i>Feedback</div>
                <h1>Share your experience with your teachers</h1>
                <p>Your feedback helps improve teaching and the overall classroom experience. Please be constructive and respectful when sharing your thoughts..</p>
            </div>

            <div class="feedback-summary-grid">
                <div class="feedback-metric-card">
                    <p class="label">Teachers available</p>
                    <p class="value"><?= count($teachers ?? []) ?></p>
                </div>
                <div class="feedback-metric-card">
                    <p class="label">Submissions</p>
                    <p class="value"><?= count($feedbackRows ?? []) ?></p>
                </div>
                <div class="feedback-metric-card">
                    <p class="label">Review state</p>
                    <p class="value">Pending review</p>
                </div>
            </div>

            <?php if ($message = Session::getFlashMessage('error_message')): ?>
                <div class="feedback-alert feedback-alert--error"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($message = Session::getFlashMessage('success_message')): ?>
                <div class="feedback-alert feedback-alert--success"><i class="fa fa-check-circle" aria-hidden="true"></i> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="feedback-grid">
                <article class="feedback-card">
                    <h2>Submit anonymous feedback</h2>
                    <p class="subtext">Choose a teacher, rate your experience, and share your thoughts to help improve the learning experience</p>
                    <form action="/student/feedback" method="POST" class="feedback-form-grid">
                        <?= csrfField() ?>
                        <div class="feedback-field">
                            <label class="feedback-label" for="teacher_id">Select teacher</label>
                            <select id="teacher_id" name="teacher_id" class="feedback-select" required>
                                <option value="">Choose a teacher</option>
                                <?php foreach (($teachers ?? []) as $teacher): ?>
                                    <option value="<?= (int) ($teacher['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($teacher['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php $ratingFields = [
                            'rating_teaching_explanation' => 'Teaching (Explanation)',
                            'rating_teaching_clarity' => 'Teaching (Clarity)',
                            'rating_punctuality_to_class' => 'Punctuality to Class',
                            'rating_approachable' => 'Approachable',
                            'rating_likeable' => 'Likeable',
                            'rating_discipline' => 'Discipline',
                            'rating_overall' => 'Overall experience'
                        ]; ?>

                        <?php foreach ($ratingFields as $field => $label): ?>
                            <div class="feedback-field">
                                <label class="feedback-label" for="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
                                <div class="feedback-star-group" role="radiogroup" aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> rating">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <button type="button" class="feedback-star" data-rating-target="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>" data-rating-value="<?= $star ?>" aria-label="<?= $star ?> out of 5">
                                            <i class="fa fa-star" aria-hidden="true"></i>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                                <select id="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>" class="feedback-select" required>
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <option value="<?= $star ?>"><?= $star ?> Star<?= $star === 1 ? '' : 's' ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>

                        <div class="feedback-field">
                            <label class="feedback-label" for="feedback_text">Written feedback</label>
                            <textarea id="feedback_text" name="feedback_text" class="feedback-textarea" rows="7" maxlength="4000" placeholder="Tell us what went well or what needs attention..." required></textarea>
                        </div>

                        <button type="submit" class="feedback-btn">Submit feedback</button>
                    </form>
                </article>

                <article class="feedback-card">
                    <h3>Your recent submissions</h3>
                    <p class="subtext">Track the status of your submitted feedback.</p>
                    <?php if (!empty($feedbackRows ?? [])): ?>
                        <div class="feedback-list">
                            <?php foreach (($feedbackRows ?? []) as $feedback): ?>
                                <?php $teacherName = (string) (($teacherLookup[(int) ($feedback['teacher_user_id'] ?? 0)] ?? [])['name'] ?? 'Teacher'); ?>
                                <div class="feedback-item">
                                    <div class="feedback-item-head">
                                        <div>
                                            <div class="feedback-item-title"><?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="feedback-meta-row">
                                                <span class="feedback-meta-pill"><i class="fa fa-clock-o" aria-hidden="true"></i> <?= htmlspecialchars((string) ($feedback['submitted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </div>
                                        <span class="feedback-badge feedback-badge--<?= htmlspecialchars((string) ($feedback['review_status'] ?? 'pending_review'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(feedbackStatusLabel((string) ($feedback['review_status'] ?? 'pending_review')), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <p class="feedback-preview"><?= htmlspecialchars((string) ($feedback['feedback_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="feedback-empty">
                            <i class="fa fa-commenting-o" aria-hidden="true"></i>
                            <p>No feedback submitted yet.</p>
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        </div>
    </main>
</section>
<script>
    (function() {
        document.querySelectorAll('.feedback-star-group').forEach(function(group) {
            const select = group.closest('.feedback-field').querySelector('select.feedback-select');
            const buttons = Array.from(group.querySelectorAll('.feedback-star'));
            const update = function(value) {
                buttons.forEach(function(button, index) {
                    const isActive = (index + 1) <= value;
                    button.classList.toggle('is-active', isActive);
                });
                if (select) {
                    select.value = String(value);
                }
            };
            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    update(Number(button.getAttribute('data-rating-value') || 0));
                });
            });
            if (select) {
                select.addEventListener('change', function() {
                    update(Number(select.value || 0));
                });
            }
        });
    })();
</script>
<?php loadPartial('end') ?>
