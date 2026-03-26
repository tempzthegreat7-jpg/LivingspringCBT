<?php loadPartial('student-panel-head') ?>
<?php loadPartial('student-sidebar') ?>
<section>
    <?php loadPartial('student-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Correction Tab</h1>
            <p>Filtered view of failed or incomplete subjects.</p>
        </div>

        <form method="GET" action="/student/corrections" class="performance-filter performance-filter-quiet">
            <div class="field-group">
                <label for="filter">Filter</label>
                <select id="filter" name="filter" class="select">
                    <option value="all" <?= ($filter ?? 'all') === 'all' ? 'selected' : '' ?>>Failed + Incomplete</option>
                    <option value="failed" <?= ($filter ?? '') === 'failed' ? 'selected' : '' ?>>Failed only</option>
                    <option value="incomplete" <?= ($filter ?? '') === 'incomplete' ? 'selected' : '' ?>>Incomplete only</option>
                </select>
            </div>
            <button type="submit" class="button button-soft">Apply Filter</button>
        </form>

        <div class="record-table-wrap">
            <table class="record-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Task</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows ?? [])): ?>
                        <?php foreach (($rows ?? []) as $row): ?>
                            <?php
                            $subjectLabel = ucwords(str_replace('_', ' ', (string) ($row['subject'] ?? '')));
                            $taskLabel = assessmentTaskOptions()[normalizeAssessmentTask($row['task'] ?? 'exam')] ?? ucfirst((string) ($row['task'] ?? 'exam'));
                            $statusKey = (string) ($row['status'] ?? 'failed');
                            $statusLabel = $statusKey === 'incomplete' ? 'Incomplete' : 'Failed';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($row['completed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($taskLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) ($row['score'] ?? 0) ?> / <?= (int) ($row['total'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (!empty($row['can_view'])): ?>
                                        <a class="button button-soft" href="/student/correction?index=0">View Correction</a>
                                    <?php else: ?>
                                        <button type="button" class="button button-soft" disabled>View Correction</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No failed or incomplete subjects found for this filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>
<?php loadPartial('end') ?>
