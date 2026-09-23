<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>
<?php $activeTab = $_GET['tab'] ?? 'create'; ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1><?= $activeTab === 'manage' ? 'Manage Student Login' : 'Create Student Login' ?></h1>
            <p><?= $activeTab === 'manage' ? 'Update class, status, and password for student access.' : 'Create student credentials used on the names login page.' ?></p>
            <p>
                <a href="/admin/students?tab=create" class="mini-btn <?= $activeTab === 'create' ? '' : 'ghost' ?>">Create</a>
                <a href="/admin/students?tab=manage" class="mini-btn <?= $activeTab === 'manage' ? '' : 'ghost' ?>">Manage</a>
            </p>
        </div>

        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php loadPartial('errors', ['errors' => $errors ?? []]) ?>

        <?php $createdLogin = Session::getFlashMesssge('created_student_login'); ?>

        <div class="admin-grid <?= $activeTab === 'create' ? 'create-mode' : 'manage-mode' ?>">
            <?php if ($activeTab === 'manage'): ?>
                <article class="table-card">
                    <h3>Student Credentials</h3>
                    <div class="class-lock-panel">
                        <div class="class-lock-head">
                            <div>
                                <h4>Class Access Lock</h4>
                                <p>Lock a class to keep every student in that class out of the student dashboard.</p>
                            </div>
                        </div>
                        <div class="class-lock-grid">
                            <?php foreach (($classOptions ?? []) as $classLabel): ?>
                                <?php $isClassLocked = !empty(($classLocks ?? [])[$classLabel]); ?>
                                <form action="/admin/students/class-lock" method="POST" class="class-lock-card">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="student_class" value="<?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="is_locked" value="<?= $isClassLocked ? '0' : '1' ?>" />
                                    <div class="class-lock-card-top">
                                        <span class="class-lock-name"><?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="class-lock-badge <?= $isClassLocked ? 'locked' : 'open' ?>">
                                            <?= $isClassLocked ? 'Locked' : 'Open' ?>
                                        </span>
                                    </div>
                                    <button type="submit" class="mini-btn <?= $isClassLocked ? 'ghost' : 'class-lock-btn' ?>">
                                        <?= $isClassLocked ? 'Unlock Class' : 'Lock Class' ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="table-tools" data-table-controls="admin-students-manage">
                        <label class="table-tool-search">
                            <span class="sr-only">Search student login records</span>
                            <input type="search" class="select" data-table-search placeholder="Search students..." aria-label="Search student login records" />
                        </label>
                        <label class="table-tool-filter class-filter-tool">
                            <span>Class</span>
                            <select class="select" data-table-filter="studentClass" aria-label="Filter student login records by class">
                                <option value="">All Classes</option>
                                <?php foreach (($classOptions ?? []) as $classLabel): ?>
                                    <option value="<?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
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

                    <table id="admin-students-manage" data-enhance-table="1">
                        <thead>
                            <tr>
                                <th scope="col" data-sortable="1" data-sort-col="0">ID</th>
                                <th scope="col" data-sortable="1" data-sort-col="1">Student Name</th>
                                <th scope="col" data-sortable="1" data-sort-col="2">Class</th>
                                <th scope="col" data-sortable="1" data-sort-col="3">Status</th>
                                <th scope="col" data-sortable="1" data-sort-col="4">Lock</th>
                                <th scope="col" data-sortable="1" data-sort-col="5">Current Password</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students ?? [])): ?>
                                <?php foreach (($students ?? []) as $index => $row): ?>
                                    <?php
                                    $isStudentLocked = (int) ($row['is_locked'] ?? 0) === 1;
                                    ?>
                                    <tr data-student-class="<?= htmlspecialchars(strtoupper((string) ($row['student_class'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                        <td><?= htmlspecialchars(displayUserId((int) $index + 1), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <input type="text" class="cell-input" name="student_name" form="update-student-<?= (int) $row['id'] ?>" value="<?= htmlspecialchars((string) ($row['student_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                        </td>
                                        <td>
                                            <select class="cell-select" name="student_class" form="update-student-<?= (int) $row['id'] ?>">
                                                <?php foreach (($classOptions ?? []) as $classLabel): ?>
                                                    <option value="<?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?>" <?= strtoupper((string) ($row['student_class'] ?? '')) === (string) $classLabel ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <label class="inline-check">
                                                <input type="checkbox" name="is_active" value="1" form="update-student-<?= (int) $row['id'] ?>" <?= (int) ($row['is_active'] ?? 1) === 1 ? 'checked' : '' ?> />
                                                <?= (int) ($row['is_active'] ?? 1) === 1 ? 'Active' : 'Inactive' ?>
                                            </label>
                                            <?php if ($isStudentLocked): ?>
                                                <span class="status-pill locked" style="margin-left: 8px; font-size: 0.7rem;">
                                                    <i class="fa fa-lock" aria-hidden="true"></i> Locked
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form action="/admin/students/lock" method="POST" class="inline-form student-lock-form" data-warning-confirm="<?= $isStudentLocked ? 'Unlock this student?' : 'Lock this student?' ?>">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                                                <input type="hidden" name="is_locked" value="<?= $isStudentLocked ? '0' : '1' ?>" />
                                                <button type="submit" class="mini-btn <?= $isStudentLocked ? 'ghost' : '' ?>" title="<?= $isStudentLocked ? 'Unlock student' : 'Lock student' ?>">
                                                    <i class="fa <?= $isStudentLocked ? 'fa-unlock' : 'fa-lock' ?>" aria-hidden="true"></i>
                                                    <span class="btn-text"><?= $isStudentLocked ? 'Unlock' : 'Lock' ?></span>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars((string) ($row['display_password'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                                        </td>
                                        <td>
                                            <input type="password" name="new_password" form="update-student-<?= (int) $row['id'] ?>" class="cell-input password-input" placeholder="New password (optional)" />
                                            <form id="update-student-<?= (int) $row['id'] ?>" action="/admin/students/update" method="POST">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                                            </form>
                                            <button type="submit" class="mini-btn" form="update-student-<?= (int) $row['id'] ?>">Save</button>

                                            <form action="/admin/students/delete" method="POST" class="delete-form" data-warning-confirm="Delete this student login?">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                                                <button type="submit" class="mini-btn danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No student login records yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </article>
            <?php else: ?>
                <article class="form-card">
                    <form action="/admin/students" method="POST">
                        <?= csrfField() ?>
                        <label for="student_name">Student Name</label>
                        <input id="student_name" type="text" name="student_name" class="select" value="<?= htmlspecialchars((string) ($old['student_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Peace Daniel" required />

                        <label for="student_class">Class</label>
                        <select id="student_class" name="student_class" class="select" required>
                            <?php foreach (($classOptions ?? []) as $classLabel): ?>
                                <option value="<?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?>" <?= strtoupper((string) ($old['student_class'] ?? 'SS3')) === (string) $classLabel ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $classLabel, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="password">Password (optional)</label>
                        <input id="password" type="text" name="password" class="select" placeholder="Leave blank to auto-generate" />

                        <label class="toggle-row">
                            <input type="checkbox" name="is_active" value="1" <?= (int) ($old['is_active'] ?? 1) === 1 ? 'checked' : '' ?> />
                            Login is active
                        </label>

                        <button class="button">Create Student Login</button>
                    </form>
                </article>

                <article class="table-card">
                    <h3>Latest Created Credential</h3>
                    <?php if (!empty($createdLogin ?? [])): ?>
                        <div class="profile-view">
                            <h4><?= htmlspecialchars((string) ($createdLogin['student_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h4>
                            <p><strong>Class:</strong> <?= htmlspecialchars((string) ($createdLogin['student_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>Password:</strong> <code><?= htmlspecialchars((string) ($createdLogin['password'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></p>
                        </div>
                    <?php else: ?>
                        <p class="text">Create a student login and the latest credential appears here.</p>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
        </div>
    </main>
</section>

<?php if ($activeTab === 'manage'): ?>
    <script>
        (() => {
            const filterSelect = document.querySelector("[data-table-controls='admin-students-manage'] [data-table-filter='studentClass']");
            const table = document.getElementById('admin-students-manage');

            if (!filterSelect || !table || !table.tBodies || !table.tBodies[0]) {
                return;
            }

            const tbody = table.tBodies[0];
            const rows = Array.from(tbody.rows).filter((row) => row.querySelector('td'));
            const statusNode = document.querySelector("[data-table-controls='admin-students-manage'] [data-table-status]");

            const getRowClassValue = (row) => {
                const classSelect = row.querySelector("select[name='student_class']");
                if (classSelect) {
                    const currentValue = String(classSelect.value || '').trim().toUpperCase();
                    row.dataset.studentClass = currentValue;
                    return currentValue;
                }

                const dataValue = String((row.dataset && row.dataset.studentClass) || '').trim().toUpperCase();
                return dataValue;
            };

            const applyClassFilter = () => {
                const selectedClass = String(filterSelect.value || '').trim().toUpperCase();
                let visibleCount = 0;

                rows.forEach((row) => {
                    const rowClass = getRowClassValue(row);
                    const shouldShow = selectedClass === '' || rowClass === selectedClass;
                    row.hidden = !shouldShow;
                    if (shouldShow) {
                        visibleCount += 1;
                    }
                });

                if (statusNode) {
                    statusNode.textContent = selectedClass === ''
                        ? `Showing 1-${visibleCount} of ${visibleCount}`
                        : `${selectedClass}: ${visibleCount} record${visibleCount === 1 ? '' : 's'}`;
                }
            };

            filterSelect.addEventListener('change', applyClassFilter);
            filterSelect.addEventListener('input', applyClassFilter);

            rows.forEach((row) => {
                const classSelect = row.querySelector("select[name='student_class']");
                if (!classSelect) {
                    return;
                }

                classSelect.addEventListener('change', () => {
                    row.dataset.studentClass = String(classSelect.value || '').trim().toUpperCase();
                    applyClassFilter();
                });
            });

            applyClassFilter();
        })();
    </script>
<?php endif; ?>

<?php loadPartial('end') ?>
