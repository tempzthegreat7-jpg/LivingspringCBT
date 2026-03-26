<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Dashboard</h1>
            <p>Overview of teacher/admin user access and permissions.</p>
        </div>

        <div class="dashboard-summary">
            <div class="summary-item">
                <p class="label">Total Users</p>
                <p class="value"><?= $totalUsers ?? 0 ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Teachers</p>
                <p class="value"><?= $teacherCount ?? 0 ?></p>
            </div>
            <div class="summary-item">
                <p class="label">Admins</p>
                <p class="value"><?= $adminCount ?? 0 ?></p>
            </div>
        </div>

        <?php
        $maintenanceState = $maintenanceMode ?? ['enabled' => false, 'message' => 'We are updating the platform. Please check back shortly.'];
        $maintenanceEnabled = !empty($maintenanceState['enabled']);
        $maintenanceMessage = (string) ($maintenanceState['message'] ?? 'We are updating the platform. Please check back shortly.');
        ?>
        <div class="table-card maintenance-card">
            <div class="maintenance-head">
                <div>
                    <h3>Maintenance Mode</h3>
                    <p>Pause the app neatly during updates. Admin pages stay open while student and teacher access waits outside.</p>
                </div>
                <span class="maintenance-badge <?= $maintenanceEnabled ? 'on' : 'off' ?>">
                    <?= $maintenanceEnabled ? 'Live Lock On' : 'App Open' ?>
                </span>
            </div>
            <form action="/admin/maintenance" method="POST" class="maintenance-form">
                <?= csrfField() ?>
                <div class="maintenance-toggle-card">
                    <label class="toggle-row maintenance-toggle-row">
                        <input type="checkbox" name="is_enabled" value="1" <?= $maintenanceEnabled ? 'checked' : '' ?> />
                        <span>
                            <strong>Enable maintenance mode</strong>
                            <small>Only admins keep access while the rest of the app pauses.</small>
                        </span>
                    </label>
                </div>
                <div class="maintenance-warning">
                    <strong>Heads up</strong>
                    <span>Once this is on, students and teachers will see the update screen until you reopen the app.</span>
                </div>
                <div class="maintenance-compose">
                    <div class="maintenance-field">
                        <label for="maintenance_message">Visitor message</label>
                        <textarea id="maintenance_message" name="message" class="select maintenance-textarea" rows="3" placeholder="We are updating the platform. Please check back shortly."><?= htmlspecialchars($maintenanceMessage, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="maintenance-preview">
                        <p class="maintenance-preview-label">Visitor preview</p>
                        <p class="maintenance-preview-text"><?= htmlspecialchars($maintenanceMessage, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                <div class="maintenance-actions">
                    <button type="submit" class="button"><?= $maintenanceEnabled ? 'Update Maintenance Mode' : 'Save Maintenance Settings' ?></button>
                </div>
            </form>
        </div>

        <div class="table-card">
            <div class="table-head">
                <h3>Recent Users</h3>
                <a href="/admin/teachers">Create/Manage users</a>
            </div>
            <div class="table-tools" data-table-controls="admin-dashboard-users">
                <label class="table-tool-search">
                    <span class="sr-only">Search recent users</span>
                    <input type="search" class="select" data-table-search placeholder="Search users..." aria-label="Search recent users" />
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

            <table id="admin-dashboard-users" data-enhance-table="1">
                <thead>
                    <tr>
                        <th scope="col" data-sortable="1" data-sort-col="0">ID</th>
                        <th scope="col" data-sortable="1" data-sort-col="1">Name</th>
                        <th scope="col" data-sortable="1" data-sort-col="2">Role</th>
                        <th scope="col" data-sortable="1" data-sort-col="3">Set Questions</th>
                        <th scope="col" data-sortable="1" data-sort-col="4">Live</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentUsers ?? [])): ?>
                        <?php foreach ($recentUsers as $index => $row): ?>
                            <tr>
                                <td><?= htmlspecialchars(displayUserId((int) $index + 1), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= ucfirst($row['role']) ?></td>
                                <td><?= (int) $row['can_set_questions'] === 1 ? 'Yes' : 'No' ?></td>
                                <td>
                                    <span class="presence-pill offline" data-user-id="<?= (int) $row['id'] ?>">
                                        <span class="presence-dot" aria-hidden="true"></span>
                                        <span class="presence-text">Offline</span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No users available yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>

<script>
    (function() {
        const presenceNodes = Array.from(document.querySelectorAll('.presence-pill[data-user-id]'));
        if (presenceNodes.length === 0) {
            return;
        }

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
    })();
</script>

<?php loadPartial('end') ?>
