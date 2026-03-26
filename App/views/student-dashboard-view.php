<?php loadPartial('student-panel-head') ?>
<?php loadPartial('student-sidebar') ?>
<section>
    <?php loadPartial('student-header') ?>
    <main>
        <?php
        $studentName = (string) (($student['name'] ?? '') ?: 'Student');
        $studentClass = (string) (($student['class'] ?? '') ?: 'SS3');
        $resumeCurrent = (int) ($resumeProgress['current'] ?? 1);
        $resumeTotal = (int) ($resumeProgress['total'] ?? 0);
        $announcementCount = count($notifications ?? []);
        ?>

        <div class="dashboard-head">
            <h1>Dashboard</h1>
            <p>Student workspace for available tasks, corrections, announcements, and results.</p>
        </div>

        <div class="dashboard-summary">
            <div class="summary-item">
                <p class="label">Student</p>
                <p class="value"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Class</p>
                <p class="value"><?= htmlspecialchars($studentClass, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Resume Status</p>
                <p class="value"><?= $resumeAvailable ? ($resumeCurrent . ' / ' . $resumeTotal) : 'None' ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Announcements</p>
                <p class="value"><?= (int) $announcementCount ?></p>
            </div>
        </div>

        <div class="cards">
            <article class="card">
                <div class="icon">
                    <i class="fa fa-play-circle-o" aria-hidden="true"></i>
                </div>
                <div class="info">
                    <p class="main-text">Start / Resume</p>
                    <?php if ($resumeAvailable): ?>
                        <p class="text"><a href="/student/resume">Resume current assessment</a></p>
                        <p class="text"><a href="/student/question-set?fresh=1">Start fresh assessment</a></p>
                    <?php else: ?>
                        <p class="text"><a href="/student/question-set">Start new assessment</a></p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="card">
                <div class="icon">
                    <i class="fa fa-bolt" aria-hidden="true"></i>
                </div>
                <div class="info">
                    <p class="main-text">New Task Availability</p>
                    <?php if (($newTaskAvailable ?? false) === true): ?>
                        <p class="text">New task available: <?= htmlspecialchars((string) ($newTaskLabel ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text"><a href="<?= htmlspecialchars((string) ($newTaskLink ?? '/student/question-set'), ENT_QUOTES, 'UTF-8') ?>">Open preloaded task</a></p>
                    <?php else: ?>
                        <p class="text">No new task detected right now.</p>
                        <p class="text"><a href="/student/question-set">Open task selection</a></p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="card empty-card">
                <div class="icon">
                    <i class="fa fa-bullhorn" aria-hidden="true"></i>
                </div>
                <div class="info">
                    <p class="main-text">Announcements</p>
                    <?php if (!empty($notifications ?? [])): ?>
                        <?php foreach (($notifications ?? []) as $item): ?>
                            <p class="text"><strong><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string) ($item['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text">No announcements right now.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="card">
                <div class="icon">
                    <i class="fa fa-check-square-o" aria-hidden="true"></i>
                </div>
                <div class="info">
                    <p class="main-text">Correction</p>
                    <p class="text">See failed or incomplete subjects.</p>
                    <p class="text"><a href="/student/corrections">Open correction tab</a></p>
                </div>
            </article>
            <article class="card">
                <div class="icon">
                    <i class="fa fa-line-chart" aria-hidden="true"></i>
                </div>
                <div class="info">
                    <p class="main-text">Results</p>
                    <p class="text">Open your full result history grouped by date.</p>
                    <p class="text"><a href="/student/results">Open Results Page</a></p>
                </div>
            </article>
        </div>
    </main>
</section>
<?php loadPartial('end') ?>
