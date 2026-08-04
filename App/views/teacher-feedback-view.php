<?php loadPartial('teacher-head') ?>
<?php loadPartial('sidebar') ?>
<section>
    <?php loadPartial('header') ?>
    <main>
        <div class="teacher-feedback-page">
            <div class="teacher-feedback-shell">
                <div class="teacher-feedback-hero">
                    <div class="teacher-feedback-kicker"><i class="fa fa-commenting-o" aria-hidden="true"></i> Teacher feedback</div>
                    <h1>Ratings and feedback for your classroom</h1>
                    <p>See your live ratings and the approved anonymous feedback shared with you.</p>
                </div>

                <div class="teacher-feedback-tabs" role="tablist" aria-label="Feedback sections">
                    <button class="teacher-feedback-tab active" type="button" data-tab-target="ratings-panel">Ratings</button>
                    <button class="teacher-feedback-tab" type="button" data-tab-target="feedback-panel">Feedback</button>
                </div>

                <div id="ratings-panel" class="teacher-feedback-panel active">
                    <div class="teacher-feedback-card teacher-feedback-card--focus">
                        <div class="teacher-feedback-rating-summary">
                            <div class="teacher-feedback-rating-head">
                                <p class="teacher-feedback-label">Overall average</p>
                                <h2><?= number_format((float) ($overallAverage ?? 0), 1) ?> / 5</h2>
                            </div>
                            <div class="teacher-feedback-stars" aria-label="Overall rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php $isFilled = $i <= round((float) ($overallAverage ?? 0)); ?>
                                    <i class="fa <?= $isFilled ? 'fa-star' : 'fa-star-o' ?>" aria-hidden="true"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="teacher-feedback-subtext">Based on <?= (int) ($ratingCount ?? 0) ?> approved student ratings</p>
                            <button class="teacher-feedback-action" type="button" data-open-analytics-modal>View Analytics</button>
                        </div>
                    </div>
                </div>

                <div id="feedback-panel" class="teacher-feedback-panel">
                    <div class="teacher-feedback-card">
                        <div class="teacher-feedback-card-head">
                            <h3>Approved feedback</h3>
                            <p>Only approved, anonymous feedback is shown here.</p>
                        </div>
                        <?php if (!empty($feedbackRows ?? [])): ?>
                            <div class="feedback-list">
                                <?php foreach (($feedbackRows ?? []) as $feedback): ?>
                                    <article class="feedback-card">
                                        <div class="feedback-item-head">
                                            <div>
                                                <div class="feedback-item-title">Student response</div>
                                                <div class="feedback-meta-row">
                                                    <span class="feedback-meta-pill"><i class="fa fa-clock-o" aria-hidden="true"></i> <?= htmlspecialchars((string) ($feedback['submitted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="feedback-meta-pill"><i class="fa fa-lock" aria-hidden="true"></i> Anonymous</span>
                                                </div>
                                            </div>
                                            <span class="feedback-badge feedback-badge--approved">Approved</span>
                                        </div>
                                        <p class="feedback-preview"><?= nl2br(htmlspecialchars((string) (($feedback['moderated_feedback'] ?? '') ?: ($feedback['feedback_text'] ?? '')), ENT_QUOTES, 'UTF-8')) ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="feedback-empty">
                                <i class="fa fa-commenting-o" aria-hidden="true"></i>
                                <p>No approved feedback is available yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</section>
<div class="teacher-feedback-modal-overlay" id="analytics-modal" aria-hidden="true">
    <div class="teacher-feedback-modal-backdrop" data-close-analytics-modal></div>
    <div class="teacher-feedback-modal" role="dialog" aria-modal="true" aria-labelledby="analytics-modal-title">
        <button class="teacher-feedback-modal-close" type="button" data-close-analytics-modal aria-label="Close analytics">×</button>
        <div class="teacher-feedback-card-head">
            <h3 id="analytics-modal-title">Criterion breakdown</h3>
            <p>Live averages from approved student ratings</p>
        </div>
        <div class="teacher-feedback-analytics-grid">
            <div class="teacher-feedback-analytics-item">
                <span>Teaching (Explanation)</span>
                <strong><?= number_format((float) ($teachingExplanationAverage ?? 0), 1) ?>/5</strong>
            </div>
            <div class="teacher-feedback-analytics-item">
                <span>Teaching (Clarity)</span>
                <strong><?= number_format((float) ($teachingClarityAverage ?? 0), 1) ?>/5</strong>
            </div>
            <div class="teacher-feedback-analytics-item">
                <span>Punctuality to Class</span>
                <strong><?= number_format((float) ($punctualityToClassAverage ?? 0), 1) ?>/5</strong>
            </div>
            <div class="teacher-feedback-analytics-item">
                <span>Approachable</span>
                <strong><?= number_format((float) ($approachableAverage ?? 0), 1) ?>/5</strong>
            </div>
            <div class="teacher-feedback-analytics-item">
                <span>Likeable</span>
                <strong><?= number_format((float) ($likeableAverage ?? 0), 1) ?>/5</strong>
            </div>
            <div class="teacher-feedback-analytics-item">
                <span>Discipline</span>
                <strong><?= number_format((float) ($disciplineAverage ?? 0), 1) ?>/5</strong>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = Array.from(document.querySelectorAll('.teacher-feedback-tab'));
    const panels = Array.from(document.querySelectorAll('.teacher-feedback-panel'));
    const modal = document.getElementById('analytics-modal');
    const openModalButton = document.querySelector('[data-open-analytics-modal]');
    const closeModalButtons = Array.from(document.querySelectorAll('[data-close-analytics-modal]'));

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (item) {
                item.classList.remove('active');
            });
            panels.forEach(function (panel) {
                panel.classList.remove('active');
            });

            tab.classList.add('active');
            const targetId = tab.getAttribute('data-tab-target');
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });

    const openModal = function () {
        if (!modal) {
            return;
        }
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = function () {
        if (!modal) {
            return;
        }
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    };

    if (openModalButton) {
        openModalButton.addEventListener('click', openModal);
    }

    closeModalButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeModal();
        }
    });
});
</script>
<?php loadPartial('end') ?>
