<?php loadPartial('student-panel-head') ?>
<?php loadPartial('student-sidebar') ?>
<section>
    <?php loadPartial('student-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Results</h1>
            <p>All completed tasks grouped by date.</p>
        </div>

        <?php if (!empty($groupedResults ?? [])): ?>
            <?php foreach (($groupedResults ?? []) as $dateLabel => $rows): ?>
                <div class="student-results-section">
                    <h4 class="student-result-group-title"><?= htmlspecialchars((string) $dateLabel, ENT_QUOTES, 'UTF-8') ?></h4>
                    <div class="record-table-wrap">
                        <table class="record-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Task</th>
                                    <th>Score</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($rows ?? []) as $attempt): ?>
                                    <?php
                                    $subjectLabel = ucwords(str_replace('_', ' ', (string) ($attempt['subject'] ?? '')));
                                    $taskLabel = assessmentTaskOptions()[normalizeAssessmentTask($attempt['task_type'] ?? 'exam')] ?? ucfirst((string) ($attempt['task_type'] ?? 'exam'));
                                    $score = (int) ($attempt['score'] ?? 0);
                                    $total = max(1, (int) ($attempt['total_questions'] ?? 1));
                                    $timeSpent = max(0, (int) ($attempt['time_spent_seconds'] ?? 0));
                                    $statusLabel = ((int) ($attempt['timed_out'] ?? 0) === 1) ? 'Incomplete' : 'Completed';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($taskLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= $score ?> / <?= $total ?></td>
                                        <td><?= floor($timeSpent / 60) ?>m <?= $timeSpent % 60 ?>s</td>
                                        <td><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="student-results-section">
                <p class="text">No results available yet.</p>
            </div>
        <?php endif; ?>
    </main>
</section>
<?php loadPartial('end') ?>
