<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Exam Question Banks</h1>
            <p>All available exam question banks by class and subject.</p>
        </div>

        <p class="subject-text"><a href="/admin/exams">Back to Exam Activation</a></p>

        <?php if (empty($examBanks ?? [])): ?>
            <article class="table-card">
                <div class="table-head">
                    <h3>Available Exam Question Banks</h3>
                </div>
                <p class="subject-text">No exam question banks found yet.</p>
            </article>
        <?php else: ?>
            <?php
            $banksByClass = [];
            foreach ($examBanks as $bank) {
                $classKey = strtoupper((string) ($bank['student_class'] ?? ''));
                if ($classKey === '') {
                    continue;
                }
                if (!isset($banksByClass[$classKey])) {
                    $banksByClass[$classKey] = [];
                }
                $banksByClass[$classKey][] = $bank;
            }
            ksort($banksByClass);
            ?>
            <?php foreach ($banksByClass as $classKey => $banks): ?>
                <article class="table-card" style="margin-bottom: 12px;">
                    <div class="table-head">
                        <h3><?= htmlspecialchars($classKey, ENT_QUOTES, 'UTF-8') ?> Exam Banks</h3>
                    </div>
                    <table class="exam-activation-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Term</th>
                                <th>Questions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banks as $bank): ?>
                                <tr>
                                    <td><?= htmlspecialchars($bank['subject_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($bank['term_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) ($bank['question_count'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</section>

<?php loadPartial('end') ?>
