<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>
<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="feedback-shell">
            <div class="feedback-hero">
                <div class="feedback-kicker"><i class="fa fa-bar-chart" aria-hidden="true"></i> Analytics</div>
                <h1>Detailed teacher rating analytics</h1>
                <p>Every criterion reflects the live average from all submitted student ratings for the selected teacher.</p>
            </div>

            <div class="feedback-grid feedback-grid--split">
                <aside class="feedback-side-panel">
                    <h2 class="feedback-side-title">Teachers</h2>
                    <div class="feedback-side-list">
                        <?php foreach (($teacherRows ?? []) as $teacher): ?>
                            <?php $teacherId = (int) ($teacher['id'] ?? 0); $isActive = $selectedTeacherId === $teacherId; ?>
                            <a class="feedback-side-item <?= $isActive ? 'active' : '' ?>" href="/admin/feedback/analytics?teacher_id=<?= $teacherId ?>">
                                <span><?= htmlspecialchars((string) ($teacher['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <small><?= (int) (($teacherStats[$teacherId]['count'] ?? 0)) ?> ratings</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <div class="feedback-content-panel">
                    <?php if ($selectedTeacherId > 0): ?>
                        <?php $stats = $teacherStats[$selectedTeacherId] ?? null; ?>
                        <div class="feedback-panel-head">
                            <h2><?= htmlspecialchars((string) (($teacherLookup[$selectedTeacherId] ?? '') ?: 'Selected teacher'), ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><?= (int) ($stats['count'] ?? 0) ?> student ratings • Live averages</p>
                        </div>

                        <div class="feedback-summary-grid">
                            <div class="feedback-metric-card">
                                <p class="label">Overall</p>
                                <p class="value"><?= number_format((float) ($stats['avg_overall'] ?? 0), 1) ?>/5</p>
                            </div>
                            <div class="feedback-metric-card">
                                <p class="label">Teaching (Explanation)</p>
                                <p class="value"><?= number_format((float) ($stats['avg_teaching_explanation'] ?? 0), 1) ?>/5</p>
                            </div>
                            <div class="feedback-metric-card">
                                <p class="label">Teaching (Clarity)</p>
                                <p class="value"><?= number_format((float) ($stats['avg_teaching_clarity'] ?? 0), 1) ?>/5</p>
                            </div>
                        </div>

                        <div class="feedback-summary-grid">
                            <div class="feedback-metric-card">
                                <p class="label">Punctuality to Class</p>
                                <p class="value"><?= number_format((float) ($stats['avg_punctuality_to_class'] ?? 0), 1) ?>/5</p>
                            </div>
                            <div class="feedback-metric-card">
                                <p class="label">Approachable</p>
                                <p class="value"><?= number_format((float) ($stats['avg_approachable'] ?? 0), 1) ?>/5</p>
                            </div>
                            <div class="feedback-metric-card">
                                <p class="label">Likeable</p>
                                <p class="value"><?= number_format((float) ($stats['avg_likeable'] ?? 0), 1) ?>/5</p>
                            </div>
                        </div>

                        <div class="feedback-summary-grid">
                            <div class="feedback-metric-card">
                                <p class="label">Discipline</p>
                                <p class="value"><?= number_format((float) ($stats['avg_discipline'] ?? 0), 1) ?>/5</p>
                            </div>
                            <div class="feedback-metric-card">
                                <p class="label">Submission count</p>
                                <p class="value"><?= (int) ($stats['count'] ?? 0) ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="feedback-empty"><i class="fa fa-bar-chart" aria-hidden="true"></i><p>Select a teacher to see analytics.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</section>
<?php loadPartial('end') ?>
