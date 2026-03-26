<?php loadPartial('teacher-head') ?>
<?php loadPartial('sidebar') ?>
<section>
    <?php loadPartial('header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Performance Records</h1>
            <p class="performance-subhead">
                <?= htmlspecialchars((string) ($selectedSubjectLabel ?? ucfirst((string) ($selectedSubject ?? 'english'))), ENT_QUOTES, 'UTF-8') ?>
                <span>•</span>
                <?= htmlspecialchars(ucfirst((string) ($selectedSubjectCategory ?? 'both')), ENT_QUOTES, 'UTF-8') ?> classes:
                <?= htmlspecialchars(implode(', ', $allowedClasses ?? []), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>

        <form action="/teacher/performance" method="GET" class="performance-filter performance-filter-quiet">
            <label for="subject">Subject</label>
            <select id="subject" name="subject" class="select">
                <?php foreach (($subjectOptions ?? []) as $subjectKey => $subjectLabel): ?>
                    <option value="<?= htmlspecialchars((string) $subjectKey, ENT_QUOTES, 'UTF-8') ?>" <?= ($selectedSubject ?? '') === $subjectKey ? 'selected' : '' ?>><?= htmlspecialchars((string) $subjectLabel, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <div class="performance-filter-actions">
                <button type="submit" class="button" id="save">Load</button>
                <a class="button button-soft" href="/teacher/performance?subject=<?= urlencode((string) ($selectedSubject ?? 'english')) ?>&export=csv">CSV</a>
                <button type="button" class="button button-soft" onclick="window.print()">Print</button>
            </div>
        </form>

        <div class="dashboard-summary">
            <div class="summary-item">
                <p class="label">Attempts</p>
                <p class="value"><?= (int) ($totalAttempts ?? 0) ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Average Score</p>
                <p class="value"><?= htmlspecialchars((string) ($averagePercent ?? 0), ENT_QUOTES, 'UTF-8') ?>%</p>
            </div>
            <div class="summary-item">
                <p class="label">Pass Rate</p>
                <p class="value"><?= htmlspecialchars((string) ($passRate ?? 0), ENT_QUOTES, 'UTF-8') ?>%</p>
            </div>
            <div class="summary-item">
                <p class="label">Auto-submit Rate</p>
                <p class="value"><?= htmlspecialchars((string) ($timeoutRate ?? 0), ENT_QUOTES, 'UTF-8') ?>%</p>
            </div>
        </div>

        <?php if (!empty($classAverages ?? []) || !empty($dailyTrend ?? [])): ?>
            <details class="analytics-collapse">
                <summary>More Analytics</summary>
                <?php if (!empty($classAverages ?? [])): ?>
                    <article class="record-section compact-section">
                        <div class="record-section-head">
                            <h2>Class Averages</h2>
                        </div>
                        <div class="record-table-wrap">
                            <table class="record-table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Attempts</th>
                                        <th>Average Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($classAverages ?? []) as $classKey => $metrics): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $classKey, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= (int) ($metrics['attempts'] ?? 0) ?></td>
                                            <td><strong><?= htmlspecialchars((string) ($metrics['average_percent'] ?? 0), ENT_QUOTES, 'UTF-8') ?>%</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
                <?php endif; ?>

                <?php if (!empty($dailyTrend ?? [])): ?>
                    <article class="record-section compact-section">
                        <div class="record-section-head">
                            <h2>Recent Daily Trend</h2>
                        </div>
                        <div class="record-table-wrap">
                            <table class="record-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Attempts</th>
                                        <th>Average Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($dailyTrend ?? []) as $trendRow): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) date('M j, Y', strtotime((string) ($trendRow['day'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= (int) ($trendRow['attempts'] ?? 0) ?></td>
                                            <td><strong><?= htmlspecialchars((string) ($trendRow['average_percent'] ?? 0), ENT_QUOTES, 'UTF-8') ?>%</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
                <?php endif; ?>
            </details>
        <?php endif; ?>

        <?php
        $formatDuration = function ($seconds) {
            $seconds = max(0, (int) $seconds);
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $remainingSeconds = $seconds % 60;

            if ($hours > 0) {
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
            }

            return sprintf('%02d:%02d', $minutes, $remainingSeconds);
        };
        ?>

        <?php if (!empty($groupedRecords ?? [])): ?>
            <?php $sectionIndex = 0; ?>
            <?php foreach ($groupedRecords as $sectionTitle => $classGroups): ?>
                <?php $sectionIndex++; ?>
                <?php
                $firstClassKey = array_key_first($classGroups);
                $firstClassItems = $firstClassKey !== null ? ($classGroups[$firstClassKey] ?? []) : [];
                $firstRowDate = !empty($firstClassItems[0]['completed_at']) ? date('l, F j, Y', strtotime($firstClassItems[0]['completed_at'])) : '';
                $dateTotal = 0;
                foreach ($classGroups as $classItems) {
                    $dateTotal += count($classItems);
                }
                $displayHeading = $sectionTitle;
                if ($sectionTitle === 'Today' || $sectionTitle === 'Yesterday') {
                    $displayHeading = $sectionTitle . ' - ' . $firstRowDate;
                }
                ?>
                <article class="record-section" style="--record-delay: <?= $sectionIndex * 60 ?>ms;">
                    <div class="record-section-head">
                        <h2><?= $displayHeading ?></h2>
                        <p><?= $dateTotal ?> test<?= $dateTotal === 1 ? '' : 's' ?></p>
                    </div>
                    <?php $classIndex = 0; ?>
                    <?php foreach ($classGroups as $classLabel => $items): ?>
                        <?php
                        $classIndex++;
                        $tableId = 'performance-table-' . $sectionIndex . '-' . $classIndex;
                        ?>
                        <div class="record-class-group">
                            <div class="record-class-head">
                                <h3>Class <?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= count($items) ?> test<?= count($items) === 1 ? '' : 's' ?></p>
                            </div>
                            <div class="table-tools" data-table-controls="<?= htmlspecialchars($tableId, ENT_QUOTES, 'UTF-8') ?>">
                                <label class="table-tool-search">
                                    <span class="sr-only">Search records for class <?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    <input type="search" class="select" data-table-search placeholder="Search class records..." aria-label="Search records for class <?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?>" />
                                </label>
                                <label class="table-tool-size">
                                    <span>Rows</span>
                                    <select class="select" data-table-size aria-label="Rows per page">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                    </select>
                                </label>
                                <p class="table-tool-status" data-table-status aria-live="polite"></p>
                                <div class="table-pagination" data-table-pagination aria-label="Table pagination"></div>
                            </div>
                            <div class="record-table-wrap">
                                <table class="record-table" id="<?= htmlspecialchars($tableId, ENT_QUOTES, 'UTF-8') ?>" data-enhance-table="1">
                                    <thead>
                                        <tr>
                                            <th scope="col" data-sortable="1" data-sort-col="0">Student</th>
                                            <th scope="col" data-sortable="1" data-sort-col="1">Class</th>
                                            <th scope="col" data-sortable="1" data-sort-col="2">Task</th>
                                            <th scope="col" data-sortable="1" data-sort-col="3">Score</th>
                                            <th scope="col" data-sortable="1" data-sort-col="4">Time Spent</th>
                                            <th scope="col" data-sortable="1" data-sort-col="5">Submitted</th>
                                            <th scope="col" data-sortable="1" data-sort-col="6">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $record): ?>
                                            <?php
                                            $score = (int) ($record['score'] ?? 0);
                                            $total = max(1, (int) ($record['total_questions'] ?? 1));
                                            $percentage = (int) round(($score / $total) * 100);
                                            ?>
                                            <tr>
                                                <td class="record-student"><?= htmlspecialchars($record['student_name'] ?? 'Unknown') ?></td>
                                                <td><?= htmlspecialchars($record['student_class'] ?? 'SS3') ?></td>
                                                <td><?= htmlspecialchars((string) (assessmentTaskOptions()[normalizeAssessmentTask($record['task_type'] ?? 'exam')] ?? ucfirst((string) ($record['task_type'] ?? 'exam'))), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><strong><?= $score ?> / <?= (int) ($record['total_questions'] ?? 0) ?></strong> <span class="muted">(<?= $percentage ?>%)</span></td>
                                                <td><?= $formatDuration($record['time_spent_seconds'] ?? 0) ?></td>
                                                <td><?= date('g:i A', strtotime($record['completed_at'])) ?></td>
                                                <td>
                                                    <?php if ((int) ($record['timed_out'] ?? 0) === 1): ?>
                                                        <span class="record-pill timeout">Auto-submit</span>
                                                    <?php else: ?>
                                                        <span class="record-pill completed">Completed</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <article class="card empty-card">
                <div class="icon">
                    <i class="fa fa-line-chart" aria-hidden="true"></i>
                </div>
                <div class="info">
                    <p class="main-text">No records yet for this subject</p>
                    <p class="text">Completed student exams for this subject will appear here.</p>
                </div>
            </article>
        <?php endif; ?>
    </main>
</section>
<?php loadPartial('end') ?>
