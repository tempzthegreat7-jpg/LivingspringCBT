<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Exam Insights</h1>
            <p>Filter by subject and class to see each student's result, score, and time taken without switching into teacher accounts.</p>
        </div>

        <div class="table-card">
            <div class="table-head">
                <h3>Filters</h3>
                <span>Choose a subject and class to narrow the records</span>
            </div>
            <form action="/admin/exam-insights" method="GET" class="maintenance-compose">
                <div class="maintenance-field">
                    <label for="examInsightSubject">Subject</label>
                    <select name="subject" id="examInsightSubject" class="select">
                        <option value="">All Subjects</option>
                        <?php foreach (($subjectOptions ?? []) as $subjectKey => $subjectLabel): ?>
                            <option value="<?= htmlspecialchars((string) $subjectKey, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($selectedSubject ?? '') === (string) $subjectKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $subjectLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="maintenance-field">
                    <label for="examInsightClass">Class</label>
                    <select name="student_class" id="examInsightClass" class="select">
                        <option value="">All Classes</option>
                        <?php foreach (($classOptions ?? []) as $classKey => $classLabel): ?>
                            <option value="<?= htmlspecialchars((string) $classKey, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($selectedClass ?? '') === (string) $classKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="maintenance-actions">
                    <button type="submit" class="button">Apply Filter</button>
                    <a href="/admin/exam-insights" class="button exam-submit-secondary" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Clear</a>
                </div>
            </form>
        </div>

        <div class="table-card">
            <div class="table-head">
                <h3>Student Records</h3>
                <span>Individual exam results for the selected subject and class</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Score</th>
                        <th>Percent</th>
                        <th>Time Taken</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($studentRecords ?? [])): ?>
                        <?php foreach (($studentRecords ?? []) as $row): ?>
                            <?php
                            $score = (int) ($row['score'] ?? 0);
                            $totalQuestions = max(1, (int) ($row['total_questions'] ?? 0));
                            $percent = round(($score / $totalQuestions) * 100, 1);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($row['student_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['student_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($row['subject'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $score ?> / <?= (int) ($row['total_questions'] ?? 0) ?></td>
                                <td><?= $percent ?>%</td>
                                <td><?= round((float) ($row['time_spent_seconds'] ?? 0)) ?>s</td>
                                <td><?= htmlspecialchars((string) ($row['completed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No student records match the current filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <div class="table-head">
                <h3>Performance By Subject</h3>
                <span>Average score and time for the selected class and subject</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Class</th>
                        <th>Attempts</th>
                        <th>Average Score</th>
                        <th>Average Percent</th>
                        <th>Average Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($performanceRows ?? [])): ?>
                        <?php foreach (($performanceRows ?? []) as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($row['subject'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['student_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) ($row['attempts'] ?? 0) ?></td>
                                <td><?= round((float) ($row['avg_score_raw'] ?? 0), 1) ?></td>
                                <td><?= round((float) ($row['avg_percent'] ?? 0), 1) ?>%</td>
                                <td><?= round((float) ($row['avg_time_spent'] ?? 0)) ?>s</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No performance summary matches the current filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>

<?php loadPartial('end') ?>
