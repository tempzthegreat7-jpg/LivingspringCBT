<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>
<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="feedback-shell">
            <div class="feedback-hero">
                <div class="feedback-kicker"><i class="fa fa-star" aria-hidden="true"></i> Teacher ratings</div>
                <h1>Live teacher rating overview</h1>
                <p>Each rating is calculated from the latest student submissions and updates automatically as new feedback arrives.</p>
            </div>

            <div class="feedback-summary-grid">
                <div class="feedback-metric-card">
                    <p class="label">Teachers</p>
                    <p class="value"><?= count($teacherRows ?? []) ?></p>
                </div>
                <div class="feedback-metric-card">
                    <p class="label">Ratings submitted</p>
                    <p class="value"><?= count($feedbackRows ?? []) ?></p>
                </div>
                <div class="feedback-metric-card">
                    <p class="label">Average overall</p>
                    <p class="value"><?= number_format((float) (array_sum(array_column($teacherStats ?? [], 'avg_overall')) / max(1, count($teacherStats ?? []))), 1) ?>/5</p>
                </div>
            </div>

            <?php if (!empty($teacherRows ?? [])): ?>
                <div class="feedback-list">
                    <?php foreach (($teacherRows ?? []) as $teacher): ?>
                        <?php $teacherId = (int) ($teacher['id'] ?? 0); $stats = $teacherStats[$teacherId] ?? null; ?>
                        <article class="feedback-card">
                            <div class="feedback-item-head">
                                <div>
                                    <div class="feedback-item-title"><?= htmlspecialchars((string) ($teacher['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="feedback-meta-row">
                                        <span class="feedback-meta-pill"><i class="fa fa-star" aria-hidden="true"></i> <?= number_format((float) ($stats['avg_overall'] ?? 0), 1) ?>/5</span>
                                        <span class="feedback-meta-pill"><i class="fa fa-users" aria-hidden="true"></i> <?= (int) ($stats['count'] ?? 0) ?> ratings</span>
                                    </div>
                                </div>
                                <a class="feedback-btn" href="/admin/feedback/analytics?teacher_id=<?= (int) $teacherId ?>">View Analytics</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="feedback-empty"><i class="fa fa-star" aria-hidden="true"></i><p>No teachers found.</p></div>
            <?php endif; ?>
        </div>
    </main>
</section>
<?php loadPartial('end') ?>
