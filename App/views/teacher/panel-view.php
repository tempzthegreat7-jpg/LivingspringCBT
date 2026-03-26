<?php loadPartial('teacher-head') ?>
<?php loadPartial('sidebar') ?>
<section>
    <?php loadPartial('header') ?>
    <main>
        <?php
        $cards = $subjectCards ?? [];
        $subjectCount = count($cards);
        $allClassesCount = (int) ($classesCount ?? 0);
        $totalContexts = (int) ($totalContexts ?? 0);
        $unreadAdminReplies = (int) ($unreadAdminReplies ?? 0);
        $taskOptions = assessmentTaskOptions();
        ?>
        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= $message ?></div>
        <?php endif; ?>
        <div class="dashboard-head">
            <h1>Dashboard</h1>
            <p>Your admin-assigned subject overview and question volume.</p>
        </div>

        <div class="dashboard-summary">
            <div class="summary-item">
                <p class="label">Assigned Subjects</p>
                <p class="value"><?= $subjectCount ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Classes</p>
                <p class="value"><?= $allClassesCount ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Question Banks</p>
                <p class="value"><?= $totalContexts ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Total Questions</p>
                <p class="value"><?= (int) ($totalQuestions ?? 0) ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Admin Messages</p>
                <p class="value" id="teacherUnreadAdminReplies"><?= $unreadAdminReplies ?></p>
                <p class="text"><a href="/teacher/notify-admin">Open Teacher Messages</a></p>
            </div>
        </div>

        <div class="cards">
            <?php if (!empty($cards)) : ?>
                <?php foreach ($cards as $card) : ?>
                    <article class="card">
                        <div class="info subject-card-info">
                            <div class="subject-card-top">
                                <div class="icon subject-card-icon">
                                    <i class="fa fa-book" aria-hidden="true"></i>
                                </div>
                                <p class="main-text subject-card-title"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text subject-card-meta">
                                    <?= (int) ($card['total'] ?? 0) ?> Question<?= (int) ($card['total'] ?? 0) === 1 ? '' : 's' ?> |
                                    <?= (int) ($card['context_count'] ?? 0) ?> Bank<?= (int) ($card['context_count'] ?? 0) === 1 ? '' : 's' ?>
                                </p>
                            </div>
                            <?php if (!empty($card['counts_by_task'])): ?>
                                <div class="text subject-task-summary">
                                    <?php
                                    $taskShortMap = [
                                        'assignment' => 'ASS',
                                        'classwork' => 'CLW',
                                        'test' => 'TST',
                                        'exam' => 'EX'
                                    ];
                                    $taskSummaryParts = [];
                                    foreach (($card['counts_by_task'] ?? []) as $taskKey => $taskCount) {
                                        $normalizedTaskKey = strtolower((string) $taskKey);
                                        $taskLabelRaw = $taskShortMap[$normalizedTaskKey] ?? (string) ($taskOptions[$taskKey] ?? ucfirst((string) $taskKey));
                                        $taskLabel = htmlspecialchars($taskLabelRaw, ENT_QUOTES, 'UTF-8');
                                        $taskValue = (int) $taskCount;
                                        $taskSummaryParts[] = $taskLabel . ': ' . $taskValue;
                                    }
                                    ?>
                                    <p><?= implode(' | ', $taskSummaryParts) ?></p>
                                </div>
                            <?php endif; ?>
                            <details class="subject-dropdown">
                                <summary>
                                    <span>View Classes</span>
                                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                                </summary>
                                <div class="subject-dropdown-list">
                                    <?php foreach (($card['classes'] ?? []) as $classLabel => $classTotal): ?>
                                        <?php $classContextCount = (int) (($card['contexts_by_class'][$classLabel] ?? 0)); ?>
                                        <a
                                            class="subject-class-link"
                                            href="/teacher/check-question?subject=<?= urlencode((string) ($card['key'] ?? 'english')) ?>&student_class=<?= urlencode((string) $classLabel) ?>&task=exam&term=first_term">
                                            <span class="subject-class-name"><?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="subject-class-count">
                                                <?= (int) $classTotal ?> Question<?= (int) $classTotal === 1 ? '' : 's' ?> |
                                                <?= $classContextCount ?> Bank<?= $classContextCount === 1 ? '' : 's' ?>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else : ?>
                <article class="card empty-card">
                    <div class="icon">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    </div>
                    <div class="info">
                        <p class="main-text">No questions available yet</p>
                        <p class="text">Use Set Questions to create your first question bank.</p>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </main>
</section>
<script>
    (function() {
        const unreadNode = document.getElementById('teacherUnreadAdminReplies');
        if (!unreadNode) {
            return;
        }

        const refreshUnread = function() {
            fetch('/teacher/messages/feed', {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(payload) {
                    if (!payload || payload.ok !== true) {
                        return;
                    }
                    const nextCount = Number(payload.unread_reply_count || 0);
                    unreadNode.textContent = String(nextCount);
                })
                .catch(function() {});
        };

        refreshUnread();
        setInterval(refreshUnread, 6000);
    })();
</script>
<?php loadPartial('end') ?>
