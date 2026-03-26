<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Audit Log</h1>
            <p>Recent administrative actions for accountability and troubleshooting.</p>
        </div>

        <article class="table-card">
            <div class="table-head">
                <h3>Recent Admin Actions</h3>
                <p><?= count($auditRows ?? []) ?> row<?= count($auditRows ?? []) === 1 ? '' : 's' ?></p>
            </div>

            <div class="table-tools" data-table-controls="admin-audit-log">
                <label class="table-tool-search">
                    <span class="sr-only">Search audit log</span>
                    <input type="search" class="select" data-table-search placeholder="Search actions..." aria-label="Search audit log" />
                </label>
                <label class="table-tool-size">
                    <span>Rows</span>
                    <select class="select" data-table-size aria-label="Rows per page">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                    </select>
                </label>
                <p class="table-tool-status" data-table-status aria-live="polite"></p>
                <div class="table-pagination" data-table-pagination aria-label="Table pagination"></div>
            </div>

            <table id="admin-audit-log" data-enhance-table="1">
                <thead>
                    <tr>
                        <th scope="col" data-sortable="1" data-sort-col="0">Time</th>
                        <th scope="col" data-sortable="1" data-sort-col="1">Action</th>
                        <th scope="col" data-sortable="1" data-sort-col="2">Entity</th>
                        <th scope="col" data-sortable="1" data-sort-col="3">Summary</th>
                        <th scope="col" data-sortable="1" data-sort-col="4">Admin ID</th>
                        <th scope="col" data-sortable="1" data-sort-col="5">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($auditRows ?? [])): ?>
                        <?php foreach (($auditRows ?? []) as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) date('M j, Y g:i A', strtotime((string) ($row['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['action_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?= !empty($row['entity_id']) ? (' #' . htmlspecialchars((string) $row['entity_id'], ENT_QUOTES, 'UTF-8')) : '' ?></td>
                                <td><?= htmlspecialchars((string) ($row['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) ($row['admin_user_id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($row['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No audit entries yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </article>
    </main>
</section>

<?php loadPartial('end') ?>
