<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>
<?php $activeTab = $activeTab ?? ($_GET['tab'] ?? 'create'); ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1><?= $activeTab === 'manage' ? 'Edit/Delete Users' : 'Create User' ?></h1>
            <p><?= $activeTab === 'manage' ? 'Update user details, permissions, or delete accounts.' : 'Create teacher users, mark admins, and assign privileges.' ?></p>
        </div>

        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= $message ?></div>
        <?php endif; ?>

        <?php loadPartial('errors', [
            'errors' => $errors ?? []
        ]) ?>

        <?php
        $createdUser = Session::getFlashMesssge('created_user');
        ?>

        <div class="admin-grid <?= $activeTab === 'create' ? 'create-mode' : 'manage-mode' ?>">
            <?php if ($activeTab === 'manage'): ?>
                <article class="table-card">
                    <h3>Existing Users</h3>
                    <div class="table-tools" data-table-controls="admin-teachers-manage">
                        <label class="table-tool-search">
                            <span class="sr-only">Search existing users</span>
                            <input type="search" class="select" data-table-search placeholder="Search users..." aria-label="Search existing users" />
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
                    <table id="admin-teachers-manage" data-enhance-table="1">
                        <thead>
                            <tr>
                                <th scope="col" data-sortable="1" data-sort-col="0">ID</th>
                                <th scope="col" data-sortable="1" data-sort-col="1">Name</th>
                                <th scope="col" data-sortable="1" data-sort-col="2">Role</th>
                                <th scope="col" data-sortable="1" data-sort-col="3">Status</th>
                                <th scope="col" data-sortable="1" data-sort-col="4">Live</th>
                                <th scope="col" data-sortable="1" data-sort-col="5">Subjects</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users ?? [])): ?>
                                <?php foreach ($users as $index => $row): ?>
                                    <?php
                                    $rowSubjects = function_exists('adminSubjectsFromStorage') ? adminSubjectsFromStorage($row['assigned_subjects'] ?? '') : ['english'];
                                    $rowSubjectLabels = function_exists('adminSubjectsLabels') ? adminSubjectsLabels($row['assigned_subjects'] ?? '', $availableSubjects ?? []) : $rowSubjects;
                                    $rowSubjectsText = empty($rowSubjectLabels) ? 'No Subject Assigned' : implode(', ', $rowSubjectLabels);
                                    $rowSubjectCategoryMap = function_exists('adminAssignedSubjectCategoriesFromStorage') ? adminAssignedSubjectCategoriesFromStorage($row['assigned_subject_categories'] ?? '{}', $availableSubjects ?? []) : [];
                                    $rowSubjectIndicatorLines = [];
                                    $rowJuniorSubjects = [];
                                    $rowSeniorSubjects = [];
                                    $baseSubjectKeys = ['english', 'mathematics'];
                                    foreach ($rowSubjects as $subjectKey) {
                                        $label = ($availableSubjects[$subjectKey] ?? ucwords(str_replace('_', ' ', $subjectKey)));
                                        $category = strtolower((string) ($rowSubjectCategoryMap[$subjectKey] ?? 'both'));
                                        if ($category === 'both') {
                                            $rowSubjectIndicatorLines[] = $label . ': both';
                                            $rowJuniorSubjects[] = $subjectKey;
                                            $rowSeniorSubjects[] = $subjectKey;
                                        } elseif ($category === 'senior') {
                                            $rowSubjectIndicatorLines[] = $label . ': senior';
                                            $rowSeniorSubjects[] = $subjectKey;
                                        } elseif ($category === 'junior') {
                                            $rowSubjectIndicatorLines[] = $label . ': junior';
                                            $rowJuniorSubjects[] = $subjectKey;
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars(displayUserId((int) $index + 1), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <input type="text" class="cell-input" name="name" form="update-<?= (int) $row['id'] ?>" value="<?= htmlspecialchars($row['name']) ?>" />
                                        </td>
                                        <td>
                                            <select name="role" class="cell-select" form="update-<?= (int) $row['id'] ?>">
                                                <option value="teacher" <?= strtolower($row['role']) === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                                <option value="admin" <?= strtolower($row['role']) === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </td>
                                        <td>
                                            <label class="inline-check">
                                                <input type="checkbox" name="is_active" value="1" form="update-<?= (int) $row['id'] ?>" <?= (int) ($row['is_active'] ?? 1) === 1 ? 'checked' : '' ?> />
                                                <?= (int) ($row['is_active'] ?? 1) === 1 ? 'Active' : 'Inactive' ?>
                                            </label>
                                        </td>
                                        <td>
                                            <span class="presence-pill offline" data-user-id="<?= (int) $row['id'] ?>">
                                                <span class="presence-dot" aria-hidden="true"></span>
                                                <span class="presence-text">Offline</span>
                                            </span>
                                        </td>
                                        <td>
                                            <!-- <p class="subject-text"><?= htmlspecialchars($rowSubjectsText, ENT_QUOTES, 'UTF-8') ?></p> -->
                                            <?php if (!empty($rowSubjectIndicatorLines)): ?>
                                                <div class="subject-indicators">
                                                    <?php foreach ($rowSubjectIndicatorLines as $indicatorLine): ?>
                                                        <p class="subject-indicator-line"><?= htmlspecialchars($indicatorLine, ENT_QUOTES, 'UTF-8') ?></p>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <button type="button" class="mini-btn ghost subject-overlay-btn" data-modal="subject-modal-<?= (int) $row['id'] ?>">Edit</button>

                                            <div class="subject-overlay" id="subject-modal-<?= (int) $row['id'] ?>" aria-hidden="true">
                                                <div class="subject-overlay-backdrop" data-close-modal></div>
                                                <div class="subject-overlay-card" role="dialog" aria-modal="true" aria-labelledby="subject-overlay-title-<?= (int) $row['id'] ?>">
                                                    <div class="subject-overlay-head">
                                                        <h4 id="subject-overlay-title-<?= (int) $row['id'] ?>">Assigned Subjects</h4>
                                                        <div class="subject-overlay-actions">
                                                            <button type="submit" class="mini-btn" form="update-<?= (int) $row['id'] ?>">Save</button>
                                                            <button type="button" class="mini-btn ghost" data-close-modal>Close</button>
                                                        </div>
                                                    </div>

                                                    <div class="subject-overlay-grid">
                                                        <div class="subject-overlay-section">
                                                            <h5>Junior Category</h5>
                                                            <div class="subject-checks">
                                                                <?php foreach (($juniorSubjects ?? []) as $subjectKey => $subjectLabel): ?>
                                                                    <?php $isBaseSubject = in_array($subjectKey, $baseSubjectKeys, true); ?>
                                                                    <div class="subject-chip-row" data-subject-chip data-subject-key="<?= htmlspecialchars($subjectKey, ENT_QUOTES, 'UTF-8') ?>">
                                                                        <label class="subject-chip">
                                                                            <input type="checkbox" class="category-subject-input" name="subjects_junior[]" value="<?= $subjectKey ?>" form="update-<?= (int) $row['id'] ?>" <?= in_array($subjectKey, $rowJuniorSubjects, true) ? 'checked' : '' ?> />
                                                                            <?= $subjectLabel ?>
                                                                        </label>
                                                                        <?php if (!$isBaseSubject): ?>
                                                                            <button type="button" class="chip-remove" data-remove-subject data-subject-key="<?= htmlspecialchars($subjectKey, ENT_QUOTES, 'UTF-8') ?>" title="Remove subject">&times;</button>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                        <div class="subject-overlay-section">
                                                            <h5>Senior Category</h5>
                                                            <div class="subject-checks">
                                                                <?php foreach (($seniorSubjects ?? []) as $subjectKey => $subjectLabel): ?>
                                                                    <?php $isBaseSubject = in_array($subjectKey, $baseSubjectKeys, true); ?>
                                                                    <div class="subject-chip-row" data-subject-chip data-subject-key="<?= htmlspecialchars($subjectKey, ENT_QUOTES, 'UTF-8') ?>">
                                                                        <label class="subject-chip">
                                                                            <input type="checkbox" class="category-subject-input" name="subjects_senior[]" value="<?= $subjectKey ?>" form="update-<?= (int) $row['id'] ?>" <?= in_array($subjectKey, $rowSeniorSubjects, true) ? 'checked' : '' ?> />
                                                                            <?= $subjectLabel ?>
                                                                        </label>
                                                                        <?php if (!$isBaseSubject): ?>
                                                                            <button type="button" class="chip-remove" data-remove-subject data-subject-key="<?= htmlspecialchars($subjectKey, ENT_QUOTES, 'UTF-8') ?>" title="Remove subject">&times;</button>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="inline-input">
                                                <input type="password" name="new_password" form="update-<?= (int) $row['id'] ?>" class="cell-input password-input" placeholder="New password (optional)" />
                                                <button type="button" class="mini-btn ghost" data-toggle-password>Show</button>
                                            </div>
                                            <form id="update-<?= (int) $row['id'] ?>" action="/admin/teachers/update" method="POST">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                                            </form>
                                            <button type="submit" form="update-<?= (int) $row['id'] ?>" class="mini-btn">Save</button>

                                            <form action="/admin/teachers/delete" method="POST" class="delete-form" data-warning-confirm="Delete this user?">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                                                <button type="submit" class="mini-btn danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No users available yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </article>
            <?php else: ?>
                <article class="form-card">
                    <form action="/admin/teachers" method="POST">
                        <?= csrfField() ?>
                        <label for="name">Full Name</label>
                        <input id="name" type="text" name="name" class="select" value="<?= htmlspecialchars($old['name'] ?? '') ?>" placeholder="e.g. Chika Promise" />
                        <label for="password">Password</label>
                        <div class="inline-input">
                            <input id="password" type="password" name="password" class="select password-input" placeholder="Minimum 6 characters" />
                            <button type="button" class="mini-btn ghost" data-toggle-password data-target="#password">Show</button>
                        </div>
                        <label for="role">Role</label>
                        <select id="role" name="role" class="select">
                            <option value="teacher" <?= ($old['role'] ?? 'teacher') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                            <option value="admin" <?= ($old['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>

                        <div class="privileges-card">
                            <div class="privileges-card-head">
                                <h4>Privileges</h4>
                            </div>



                            <div class="privileges-grid">
                                <label class="toggle-row">
                                    <input type="checkbox" name="can_set_questions" value="1" <?= (int) ($old['can_set_questions'] ?? 1) === 1 ? 'checked' : '' ?> />
                                    Can set questions
                                </label>

                                <label class="toggle-row">
                                    <input type="checkbox" name="is_active" value="1" <?= (int) ($old['is_active'] ?? 1) === 1 ? 'checked' : '' ?> />
                                    Account is active
                                </label>

                                <label class="toggle-row">
                                    <input type="checkbox" name="can_manage_students" value="1" <?= (int) ($old['can_manage_students'] ?? 0) === 1 ? 'checked' : '' ?> />
                                    Can manage students
                                </label>
                            </div>
                        </div>

                        <?php
                        $juniorSubjectList = $juniorSubjects ?? ['english' => 'English', 'mathematics' => 'Mathematics'];
                        $seniorSubjectList = $seniorSubjects ?? ['english' => 'English', 'mathematics' => 'Mathematics'];
                        $selectedJuniorSubjects = adminNormalizeSubjects($old['subjects_junior'] ?? [], $availableSubjects ?? []);
                        $selectedSeniorSubjects = adminNormalizeSubjects($old['subjects_senior'] ?? [], $availableSubjects ?? []);
                        $initialCategory = 'junior';
                        $initialSubject = '';

                        if (!empty($selectedJuniorSubjects)) {
                            $initialCategory = 'junior';
                            $initialSubject = (string) ($selectedJuniorSubjects[0] ?? '');
                        } elseif (!empty($selectedSeniorSubjects)) {
                            $initialCategory = 'senior';
                            $initialSubject = (string) ($selectedSeniorSubjects[0] ?? '');
                        } else {
                            $defaultKey = (string) (array_key_first($juniorSubjectList) ?? '');
                            $initialSubject = $defaultKey;
                        }
                        ?>

                        <p class="subject-label">Category</p>
                        <select id="subject-category-filter" class="select subject-filter-select">
                            <option value="junior" <?= $initialCategory === 'junior' ? 'selected' : '' ?>>Junior Category</option>
                            <option value="senior" <?= $initialCategory === 'senior' ? 'selected' : '' ?>>Senior Category</option>
                        </select>

                        <p class="subject-label">Assigned Subjects</p>
                        <div class="subject-dropdown-wrap">
                            <select
                                id="subject-dropdown"
                                class="select subject-dropdown-select"
                                data-initial-subject="<?= htmlspecialchars($initialSubject, ENT_QUOTES, 'UTF-8') ?>"
                            ></select>
                        </div>
                        <div class="subject-add-row">
                            <button type="button" class="mini-btn ghost add-subject-btn" id="add-subject-btn">Add Subject</button>
                        </div>
                        <p class="subject-dropdown-note">Choose a subject. Select "Add Subject" in the dropdown to create a new one.</p>
                        <div class="subject-checks compact" id="selected-subjects-list"></div>
                        <div id="subject-hidden-inputs"></div>

                        <button class="button" type="submit">Create User</button>
                    </form>
                </article>

                <article class="profile-card">
                    <div class="profile-card-head">
                        <p class="profile-card-title">Created Profile</p>
                        <?php if (!empty($createdUser)): ?>
                            <span class="profile-status <?= !empty($createdUser['is_active']) ? 'active' : 'inactive' ?>">
                                <?= !empty($createdUser['is_active']) ? 'Active' : 'Inactive' ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($createdUser)): ?>
                        <?php
                        $profileName = trim((string) ($createdUser['name'] ?? ''));
                        $parts = array_filter(explode(' ', $profileName));
                        $initials = '';
                        foreach (array_slice($parts, 0, 2) as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                        if ($initials === '') {
                            $initials = 'U';
                        }
                        $roleLabel = ucfirst((string) ($createdUser['role'] ?? 'teacher'));
                        $subjects = $createdUser['subjects'] ?? [];
                        $subjectCategoryLines = $createdUser['subject_categories'] ?? [];
                        $subjectLevelRows = $createdUser['subject_level_rows'] ?? [];
                        if (empty($subjectLevelRows) && !empty($subjectCategoryLines)) {
                            foreach ($subjectCategoryLines as $line) {
                                $parts = explode(':', (string) $line, 2);
                                $subjectName = trim((string) ($parts[0] ?? ''));
                                $levelRaw = strtolower(trim((string) ($parts[1] ?? 'both')));
                                $levelLabel = 'Both';
                                if ($levelRaw === 'junior') {
                                    $levelLabel = 'Junior';
                                } elseif ($levelRaw === 'senior') {
                                    $levelLabel = 'Senior';
                                }

                                if ($subjectName !== '') {
                                    $subjectLevelRows[] = [
                                        'subject' => $subjectName,
                                        'level' => $levelLabel
                                    ];
                                }
                            }
                        }
                        ?>

                        <div class="profile-hero">
                            <div class="profile-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                            <div>
                                <h4><?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></h4>
                                <p><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>

                        <div class="profile-section">
                            <p class="profile-label">Privileges</p>
                            <div class="profile-badges">
                                <span class="profile-badge <?= !empty($createdUser['can_set_questions']) ? 'on' : 'off' ?>">Set Questions</span>
                                <span class="profile-badge <?= !empty($createdUser['can_manage_students']) ? 'on' : 'off' ?>">Manage Students</span>
                                <span class="profile-badge <?= !empty($createdUser['is_active']) ? 'on' : 'off' ?>">Account Active</span>
                            </div>
                        </div>

                        <div class="profile-section">
                            <p class="profile-label">Assigned Subjects</p>
                            <?php if (empty($subjectLevelRows)): ?>
                                <p class="profile-muted">No subject assigned.</p>
                            <?php else: ?>
                                <div class="profile-subject-table-wrap">
                                    <table class="profile-subject-table">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subjectLevelRows as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string) ($row['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars((string) ($row['level'] ?? 'Both'), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (!empty($subjects)): ?>
                                    <p class="profile-muted">Total: <?= count($subjects) ?> subject<?= count($subjects) === 1 ? '' : 's' ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="profile-empty">
                            <h4>No created user yet</h4>
                            <p>Create a user to see their profile preview here. It clears automatically after you leave this page.</p>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
        </div>
    </main>
</section>

<div class="subject-modal" id="subject-modal" aria-hidden="true">
    <div class="subject-modal-backdrop" id="close-subject-modal"></div>
    <div class="subject-modal-card" role="dialog" aria-modal="true" aria-labelledby="subject-modal-title">
        <h3 id="subject-modal-title">Create Custom Subject</h3>
        <p>Add a subject to junior, senior, or both categories.</p>
        <form action="/admin/subjects/create" method="POST" id="subject-create-form">
            <?= csrfField() ?>
            <label for="subject_category">Category</label>
            <select id="subject_category" name="category" class="select" required>
                <option value="junior">Junior Category</option>
                <option value="senior">Senior Category</option>
                <option value="both">Both Categories</option>
            </select>

            <label for="subject_label">Subject Name</label>
            <input id="subject_label" type="text" name="label" class="select" placeholder="e.g. Basic Science" required />

            <div class="modal-actions">
                <button type="button" class="mini-btn ghost" id="cancel-subject-modal">Cancel</button>
                <button type="submit" class="mini-btn">Save Subject</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';
        const modal = document.getElementById('subject-modal');
        const openBtn = document.getElementById('open-subject-modal');
        const closeBackdrop = document.getElementById('close-subject-modal');
        const cancelBtn = document.getElementById('cancel-subject-modal');
        const createSubjectForm = document.getElementById('subject-create-form');
        const subjectCategoryFilter = document.getElementById('subject-category-filter');
        const subjectDropdown = document.getElementById('subject-dropdown');
        const subjectHiddenInputs = document.getElementById('subject-hidden-inputs');
        const addSubjectButton = document.getElementById('add-subject-btn');
        const selectedSubjectsList = document.getElementById('selected-subjects-list');
        const addSubjectOptionValue = '__add_subject__';
        const juniorSubjectMap = <?= json_encode($juniorSubjects ?? ['english' => 'English', 'mathematics' => 'Mathematics']) ?>;
        const seniorSubjectMap = <?= json_encode($seniorSubjects ?? ['english' => 'English', 'mathematics' => 'Mathematics']) ?>;
        const initialJuniorSubjects = <?= json_encode($selectedJuniorSubjects ?? [], JSON_UNESCAPED_SLASHES) ?>;
        const initialSeniorSubjects = <?= json_encode($selectedSeniorSubjects ?? [], JSON_UNESCAPED_SLASHES) ?>;
        let openModal = function() {};
        let closeModal = function() {};

        const toggleButtons = Array.from(document.querySelectorAll('[data-toggle-password]'));
        if (toggleButtons.length > 0) {
            toggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const targetSelector = button.getAttribute('data-target');
                    let input = null;

                    if (targetSelector) {
                        input = document.querySelector(targetSelector);
                    }

                    if (!input) {
                        const parent = button.closest('.inline-input');
                        if (parent) {
                            input = parent.querySelector('input');
                        }
                    }

                    if (!input) {
                        return;
                    }

                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    button.textContent = isPassword ? 'Hide' : 'Show';
                });
            });
        }

        if (modal && closeBackdrop && cancelBtn) {
            openModal = function() {
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            };

            closeModal = function() {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            };

            if (openBtn) {
                openBtn.addEventListener('click', openModal);
            }
            closeBackdrop.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        }

        if (subjectCategoryFilter && subjectDropdown && subjectHiddenInputs && selectedSubjectsList && addSubjectButton) {
            const selectedByCategory = {
                junior: new Set(initialJuniorSubjects),
                senior: new Set(initialSeniorSubjects)
            };
            const currentSelection = {
                junior: '',
                senior: ''
            };

            const optionsForCategory = function(category) {
                return category === 'senior' ? seniorSubjectMap : juniorSubjectMap;
            };

            const getFirstSubjectKey = function(category) {
                const subjectMap = optionsForCategory(category);
                const keys = Object.keys(subjectMap);
                return keys.length > 0 ? keys[0] : '';
            };

            const syncHiddenInput = function() {
                const category = String(subjectCategoryFilter.value || 'junior');
                subjectHiddenInputs.innerHTML = '';
                const selectedSet = selectedByCategory[category] || new Set();
                const combined = new Set(selectedSet);
                const primary = String(currentSelection[category] || '');
                if (primary) {
                    combined.add(primary);
                }

                Array.from(combined).forEach(function(subjectKey) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = category === 'senior' ? 'subjects_senior[]' : 'subjects_junior[]';
                    hidden.value = subjectKey;
                    subjectHiddenInputs.appendChild(hidden);
                });
            };

            const renderSelectedList = function() {
                const category = String(subjectCategoryFilter.value || 'junior');
                const subjectMap = optionsForCategory(category);
                const selectedSet = selectedByCategory[category] || new Set();
                const combined = new Set(selectedSet);
                const primary = String(currentSelection[category] || '');
                if (primary) {
                    combined.add(primary);
                }
                selectedSubjectsList.innerHTML = '';

                if (combined.size === 0) {
                    return;
                }

                Array.from(combined).forEach(function(subjectKey) {
                    const label = subjectMap[subjectKey] || subjectKey;
                    const chip = document.createElement('span');
                    chip.className = 'subject-chip small';
                    chip.textContent = label;

                    const row = document.createElement('span');
                    row.className = 'subject-chip-row';
                    row.appendChild(chip);
                    if (subjectKey !== primary) {
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'chip-remove';
                        removeBtn.setAttribute('aria-label', 'Remove subject');
                        removeBtn.innerHTML = '&times;';
                        removeBtn.addEventListener('click', function() {
                            selectedSet.delete(subjectKey);
                            renderSelectedList();
                            syncHiddenInput();
                        });
                        row.appendChild(removeBtn);
                    }
                    selectedSubjectsList.appendChild(row);
                });
            };

            const renderSubjectOptions = function(category) {
                const subjectMap = optionsForCategory(category);
                const keys = Object.keys(subjectMap);
                subjectDropdown.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Select subject';
                subjectDropdown.appendChild(placeholder);

                keys.forEach(function(subjectKey) {
                    const option = document.createElement('option');
                    option.value = subjectKey;
                    option.textContent = subjectMap[subjectKey];
                    subjectDropdown.appendChild(option);
                });

                const addOption = document.createElement('option');
                addOption.value = addSubjectOptionValue;
                addOption.textContent = '+ Add Subject';
                subjectDropdown.appendChild(addOption);

                const remembered = currentSelection[category] || '';
                const fallback = getFirstSubjectKey(category);
                const nextValue = remembered && Object.prototype.hasOwnProperty.call(subjectMap, remembered) ? remembered : fallback;
                subjectDropdown.value = nextValue;
                currentSelection[category] = nextValue;
                renderSelectedList();
                syncHiddenInput();
            };

            const initialCategory = String(subjectCategoryFilter.value || 'junior');
            const initialSubject = String(subjectDropdown.getAttribute('data-initial-subject') || '');
            if (initialSubject) {
                selectedByCategory[initialCategory].add(initialSubject);
            }
            currentSelection[initialCategory] = initialSubject || getFirstSubjectKey(initialCategory);

            renderSubjectOptions(initialCategory);

            subjectCategoryFilter.addEventListener('change', function() {
                const category = String(subjectCategoryFilter.value || 'junior');
                renderSubjectOptions(category);
            });

                subjectDropdown.addEventListener('change', function() {
                    const selectedValue = String(subjectDropdown.value || '');
                    const category = String(subjectCategoryFilter.value || 'junior');

                if (selectedValue === addSubjectOptionValue) {
                    subjectDropdown.value = selectedByCategory[category] || getFirstSubjectKey(category);
                    openModal();
                    return;
                }

                currentSelection[category] = selectedValue;
                renderSelectedList();
                syncHiddenInput();
            });

            addSubjectButton.addEventListener('click', function() {
                const category = String(subjectCategoryFilter.value || 'junior');
                const selectedValue = String(subjectDropdown.value || '');
                if (!selectedValue || selectedValue === addSubjectOptionValue) {
                    return;
                }
                selectedByCategory[category].add(selectedValue);
                renderSelectedList();
                syncHiddenInput();
            });

            if (createSubjectForm) {
                createSubjectForm.addEventListener('submit', function(event) {
                    event.preventDefault();

                    const formData = new FormData(createSubjectForm);
                    fetch(createSubjectForm.getAttribute('action') || '/admin/subjects/create', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-Token': csrfToken
                            },
                            body: formData
                        })
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(payload) {
                            if (!payload || !payload.ok || !payload.subject) {
                                const message = payload && payload.message ? payload.message : 'Unable to create subject.';
                                if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                                    window.AppWarning.alert(message);
                                }
                                return;
                            }

                            const subject = payload.subject;
                            const slug = String(subject.slug || '');
                            const label = String(subject.label || '');
                            const category = String(subject.category || 'both');

                            if (!slug || !label) {
                                return;
                            }

                            if (category === 'junior' || category === 'both') {
                                juniorSubjectMap[slug] = label;
                            }

                            if (category === 'senior' || category === 'both') {
                                seniorSubjectMap[slug] = label;
                            }

                            if (category === 'both') {
                                selectedByCategory.junior.add(slug);
                                selectedByCategory.senior.add(slug);
                            } else if (category === 'junior') {
                                selectedByCategory.junior.add(slug);
                            } else if (category === 'senior') {
                                selectedByCategory.senior.add(slug);
                            }

                            if (category === 'junior' || category === 'senior') {
                                subjectCategoryFilter.value = category;
                            }

                            renderSubjectOptions(String(subjectCategoryFilter.value || 'junior'));
                            syncHiddenInput();
                            closeModal();
                            createSubjectForm.reset();

                            if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                                const successMessage = payload && payload.message ? payload.message : 'Subject created successfully.';
                                window.AppWarning.alert(successMessage, {
                                    title: 'Success',
                                    variant: 'success'
                                });
                            }
                        })
                        .catch(function() {
                            if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                                window.AppWarning.alert('Unable to add subject right now.');
                            }
                        });
                });
            }
        }

        const presenceNodes = Array.from(document.querySelectorAll('.presence-pill[data-user-id]'));
        if (presenceNodes.length > 0) {
            const applyPresence = function(payload) {
                const map = payload && payload.presence ? payload.presence : {};

                presenceNodes.forEach(function(node) {
                    const userId = node.getAttribute('data-user-id');
                    const state = map[userId];
                    const textNode = node.querySelector('.presence-text');

                    if (!state || !state.active) {
                        node.classList.remove('online');
                        node.classList.add('offline');
                        if (textNode) {
                            textNode.textContent = 'Offline';
                        }
                        return;
                    }

                    if (state.online) {
                        node.classList.remove('offline');
                        node.classList.add('online');
                        if (textNode) {
                            textNode.textContent = 'Online';
                        }
                    } else {
                        node.classList.remove('online');
                        node.classList.add('offline');
                        if (textNode) {
                            textNode.textContent = 'Offline';
                        }
                    }
                });
            };

            const loadPresence = function() {
                fetch('/admin/teachers/presence', {
                        cache: 'no-store'
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(payload) {
                        applyPresence(payload);
                    })
                    .catch(function() {});
            };

            loadPresence();
            setInterval(loadPresence, 5000);
        }

        const overlayButtons = Array.from(document.querySelectorAll('.subject-overlay-btn'));
        if (overlayButtons.length > 0) {
            const overlays = Array.from(document.querySelectorAll('.subject-overlay'));
            overlays.forEach(function(overlay) {
                document.body.appendChild(overlay);
            });

            const closeOverlay = function(overlay) {
                overlay.classList.remove('open');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            };

            const openOverlay = function(overlay) {
                overlay.classList.add('open');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            };

            overlayButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const targetId = button.getAttribute('data-modal');
                    if (!targetId) {
                        return;
                    }
                    const overlay = document.getElementById(targetId);
                    if (overlay) {
                        openOverlay(overlay);
                    }
                });
            });

            document.addEventListener('click', function(event) {
                const closeTrigger = event.target.closest('[data-close-modal]');
                if (closeTrigger) {
                    const overlay = closeTrigger.closest('.subject-overlay');
                    if (overlay) {
                        closeOverlay(overlay);
                    }
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape') {
                    return;
                }
                const openOverlays = Array.from(document.querySelectorAll('.subject-overlay.open'));
                openOverlays.forEach(function(overlay) {
                    closeOverlay(overlay);
                });
            });
        }

        document.addEventListener('click', function(event) {
            const removeBtn = event.target.closest('[data-remove-subject]');
            if (!removeBtn) {
                return;
            }
            const subjectKey = removeBtn.getAttribute('data-subject-key') || '';
            if (!subjectKey) {
                return;
            }
            const proceedDelete = function() {
                fetch('/admin/subjects/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: 'subject=' + encodeURIComponent(subjectKey)
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(payload) {
                        if (!payload || !payload.ok) {
                            const message = payload && payload.message ? payload.message : 'Unable to delete subject.';
                            if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                                window.AppWarning.alert(message);
                            }
                            return;
                        }

                        const chips = Array.from(document.querySelectorAll('[data-subject-chip][data-subject-key="' + subjectKey + '"]'));
                        chips.forEach(function(chip) {
                            const input = chip.querySelector('input[type="checkbox"]');
                            if (input) {
                                input.checked = false;
                            }
                            chip.classList.add('removed');
                        });
                    })
                    .catch(function() {
                        if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                            window.AppWarning.alert('Unable to delete subject right now.');
                        }
                    });
            };

            if (window.AppWarning && typeof window.AppWarning.confirm === 'function') {
                window.AppWarning
                    .confirm('Delete this subject globally? This removes it from all users.')
                    .then(function(confirmed) {
                        if (confirmed) {
                            proceedDelete();
                        }
                    });
                return;
            }

            if (!confirm('Delete this subject globally? This removes it from all users.')) {
                return;
            }
            proceedDelete();
        });
    })();
</script>

<?php loadPartial('end') ?>
