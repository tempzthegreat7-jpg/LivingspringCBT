<?php loadPartial('teacher-head') ?>
<?php loadPartial('sidebar') ?>
<section>
    <?php loadPartial('header') ?>
    <main>
        <?php
        $subjects = function_exists('adminSubjectsLabels') ? adminSubjectsLabels(Session::get('user')['assigned_subjects'] ?? '') : [];
        $subjectsText = empty($subjects) ? 'No Subject' : implode(', ', $subjects);
        $isActive = (int) (Session::get('user')['is_active'] ?? 1) === 1;
        $teacherName = (string) (Session::get('user')['name'] ?? 'No Data!');
        $teacherRole = ucfirst((string) (Session::get('user')['role'] ?? 'teacher'));
        $teacherId = htmlspecialchars(displayUserId((int) (Session::get('user')['id'] ?? 0)), ENT_QUOTES, 'UTF-8');
        $nameParts = array_filter(preg_split('/\s+/', $teacherName));
        $profileInitials = '';
        foreach (array_slice($nameParts, 0, 2) as $namePart) {
            $profileInitials .= strtoupper(substr((string) $namePart, 0, 1));
        }
        if ($profileInitials === '') {
            $profileInitials = 'U';
        }
        ?>
        <h1 class="teacher-profile-title">Profile</h1>
        <section class="teacher-profile-shell">
            <div class="teacher-profile-hero">
                <div class="teacher-profile-avatar" aria-hidden="true"><?= htmlspecialchars($profileInitials, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="teacher-profile-intro">
                    <p class="teacher-profile-name"><?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="teacher-profile-role"><?= htmlspecialchars($teacherRole, ENT_QUOTES, 'UTF-8') ?></p>
                    <span class="teacher-profile-status <?= $isActive ? 'active' : 'inactive' ?>">
                        <?= $isActive ? 'Active Account' : 'Inactive Account' ?>
                    </span>
                </div>
            </div>

            <?php loadPartial('errors', [
                'errors' => $errors ?? []
            ]) ?>

            <div class="teacher-profile-grid">
                <article class="teacher-profile-card">
                    <p class="teacher-profile-card-title">Identity</p>
                    <div class="teacher-profile-detail-list">
                        <div class="teacher-profile-detail-item">
                            <span class="teacher-profile-detail-label">Teacher ID</span>
                            <span class="teacher-profile-detail-value"><?= $teacherId ?></span>
                        </div>
                        <div class="teacher-profile-detail-item">
                            <span class="teacher-profile-detail-label">Name</span>
                            <span class="teacher-profile-detail-value"><?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </article>

                <article class="teacher-profile-card">
                    <p class="teacher-profile-card-title">Assigned Subjects</p>
                    <?php if (empty($subjects)): ?>
                        <p class="teacher-profile-empty">No subjects assigned yet.</p>
                    <?php else: ?>
                        <div class="teacher-profile-subjects">
                            <?php foreach ($subjects as $subjectLabel): ?>
                                <span class="teacher-profile-subject-chip"><?= htmlspecialchars((string) $subjectLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <input
                        type="text"
                        class="select teacher-profile-hidden-input"
                        value="<?= htmlspecialchars($subjectsText, ENT_QUOTES, 'UTF-8') ?>"
                        name="subject"
                        readonly />
                </article>
            </div>

            <p class="teacher-profile-note">Profile details are managed by administration. To request updates, please contact the administrator.</p>
        </section>
    </main>
</section>
<?php loadPartial('end') ?>
