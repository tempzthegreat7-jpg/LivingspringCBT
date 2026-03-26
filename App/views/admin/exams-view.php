<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Exam Activation</h1>
            <p>Toggle one or more active exam subjects per class. Other tasks remain unchanged.</p>
        </div>

        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="admin-grid create-mode">
            <article class="form-card">
                <h3>Activate Exam Subject</h3>
                <p class="subject-text">Use the same toggles to switch each subject on or off for a class.</p>
                <p class="subject-text"><a href="/admin/exams/banks">View Exam Question Banks</a></p>

                <?php foreach (($classOptions ?? []) as $classKey): ?>
                    <?php
                    $classLabel = strtoupper((string) $classKey);
                    $subjects = $subjectsByClass[$classLabel] ?? [];
                    $activeSubjectMap = $activeSubjects[$classLabel] ?? [];
                    ?>
                    <div class="table-card" style="margin-bottom: 12px;">
                        <div class="table-head" style="margin-bottom: 8px;">
                            <h4><?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?></h4>
                        </div>
                        <?php if (empty($subjects)): ?>
                            <p class="subject-text">No exam banks for this class yet.</p>
                        <?php else: ?>
                            <div class="exam-toggle-wrap">
                                <?php foreach ($subjects as $subjectKey => $subjectLabel): ?>
                                    <?php $isActive = !empty($activeSubjectMap[$subjectKey]); ?>
                                    <form action="/admin/exams/activate" method="POST" class="inline-form">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="student_class" value="<?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="subject" value="<?= htmlspecialchars($subjectKey, ENT_QUOTES, 'UTF-8') ?>" />
                                        <button type="submit" class="exam-toggle-chip <?= $isActive ? 'active' : '' ?>">
                                            <span class="exam-status-dot <?= $isActive ? 'active' : 'inactive' ?>" aria-hidden="true"></span>
                                            <?= htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                                <form action="/admin/exams/activate" method="POST" class="inline-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="student_class" value="<?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="subject" value="" />
                                    <button type="submit" class="exam-toggle-chip clear">Clear Lock</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </article>
        </div>
    </main>
</section>
<?php loadPartial('end') ?>
