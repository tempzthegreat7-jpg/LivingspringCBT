<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Student Login Log</h1>
            <p>Successful student sign-ins with time, class, IP address, and device details.</p>
        </div>

        <article class="table-card">
            <div class="table-head">
                <h3>Recent Student Logins</h3>
                <p><?= count($loginRows ?? []) ?> row<?= count($loginRows ?? []) === 1 ? '' : 's' ?></p>
            </div>

            <div class="table-tools" data-table-controls="admin-student-login-log">
                <label class="table-tool-search">
                    <span class="sr-only">Search student login log</span>
                    <input type="search" class="select" data-table-search placeholder="Search student logins..." aria-label="Search student login log" />
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

            <table id="admin-student-login-log" data-enhance-table="1">
                <thead>
                    <tr>
                        <th scope="col" data-sortable="1" data-sort-col="0">Time</th>
                        <th scope="col" data-sortable="1" data-sort-col="1">Student</th>
                        <th scope="col" data-sortable="1" data-sort-col="2">Class</th>
                        <th scope="col" data-sortable="1" data-sort-col="3">IP</th>
                        <th scope="col" data-sortable="1" data-sort-col="4">Device</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($loginRows ?? [])): ?>
                        <?php foreach (($loginRows ?? []) as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) date('M j, Y g:i A', strtotime((string) ($row['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['student_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['student_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['user_agent'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No student login entries yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </article>
    </main>
</section>

<?php loadPartial('end') ?>
