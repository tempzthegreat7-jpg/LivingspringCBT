<?php loadPartial('student-panel-head') ?>
<?php loadPartial('student-sidebar') ?>
<?php
$successMessage = Session::getFlashMesssge('success_message');
$errorMessage = Session::getFlashMesssge('error_message');
$studentName = (string) ($student['name'] ?? 'Student');
$studentClass = (string) ($student['class'] ?? 'SS3');
$loginCount = count($loginRows ?? []);
?>
<section>
    <?php loadPartial('student-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Login Log</h1>
            <p>Review your recent successful sign-ins with time, class, IP address, and device details.</p>
        </div>

        <?php if ($successMessage): ?>
            <div class="errors"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="errors"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($isUnlocked): ?>
            <article class="table-card student-log-table-card">
                <div class="table-head">
                    <div>
                        <p class="student-log-label">Protected</p>
                        <h3>Login History</h3>
                        <p class="student-log-meta-line"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">•</span> <?= htmlspecialchars($studentClass, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <p><?= $loginCount ?> row<?= $loginCount === 1 ? '' : 's' ?></p>
                </div>
                <div class="table-tools" data-table-controls="student-login-log">
                    <label class="table-tool-search">
                        <span class="sr-only">Search your login log</span>
                        <input type="search" class="select" data-table-search placeholder="Search your login history..." aria-label="Search your login log" />
                    </label>
                    <label class="table-tool-size">
                        <span>Rows</span>
                        <select class="select" data-table-size aria-label="Rows per page">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                    <p class="table-tool-status" data-table-status aria-live="polite"></p>
                    <div class="table-pagination" data-table-pagination aria-label="Table pagination"></div>
                </div>

                <div class="record-table-wrap">
                    <table class="record-table" id="student-login-log" data-enhance-table="1">
                        <thead>
                            <tr>
                                <th scope="col" data-sortable="1" data-sort-col="0">Time</th>
                                <th scope="col" data-sortable="1" data-sort-col="1">Class</th>
                                <th scope="col" data-sortable="1" data-sort-col="2">IP</th>
                                <th scope="col" data-sortable="1" data-sort-col="3">Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($loginRows ?? [])): ?>
                                <?php foreach (($loginRows ?? []) as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) date('M j, Y g:i A', strtotime((string) ($row['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($row['student_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($row['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="student-log-device"><?= htmlspecialchars((string) ($row['user_agent'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No login entries found for this student yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        <?php else: ?>
            <article class="table-card student-log-locked-card">
                <div class="table-head">
                    <div>
                        <p class="student-log-label">Protected</p>
                        <h3>Login History</h3>
                        <p class="student-log-meta-line"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">•</span> <?= htmlspecialchars($studentClass, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                <p class="student-log-locked-copy">This page is protected. Use the button below to unlock it with the access password.</p>
                <button type="button" class="button button-soft student-log-open-btn" id="openStudentLogOverlay">Unlock Log</button>
            </article>
        <?php endif; ?>
    </main>
</section>

<?php if (!$isUnlocked): ?>
    <div class="student-log-overlay" id="studentLogOverlay" aria-hidden="true">
        <div class="student-log-overlay-backdrop" id="studentLogOverlayBackdrop"></div>
        <div class="student-log-overlay-card" role="dialog" aria-modal="true" aria-labelledby="studentLogOverlayTitle">
            <button type="button" class="student-log-overlay-close" id="closeStudentLogOverlay" aria-label="Close login log password prompt">&times;</button>
            <p class="student-log-kicker">Protected</p>
            <h3 id="studentLogOverlayTitle">Unlock Student Login Log</h3>
            <p class="student-log-overlay-text">Enter the access password to view your sign-in history in this panel.</p>
            <form action="/student/login-log/unlock" method="POST" class="student-log-overlay-form">
                <?= csrfField() ?>
                <label>
                    <span class="sr-only">Login log access password</span>
                    <input type="password" name="access_password" class="select" id="studentLogPasswordInput" placeholder="Access password" required />
                </label>
                <div class="student-log-overlay-actions">
                    <button type="submit" class="button">Unlock Now</button>
                    <button type="button" class="button button-soft" id="cancelStudentLogOverlay">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const overlay = document.getElementById('studentLogOverlay');
            const openBtn = document.getElementById('openStudentLogOverlay');
            const closeBtn = document.getElementById('closeStudentLogOverlay');
            const cancelBtn = document.getElementById('cancelStudentLogOverlay');
            const backdrop = document.getElementById('studentLogOverlayBackdrop');
            const passwordInput = document.getElementById('studentLogPasswordInput');

            if (!overlay || !openBtn || !closeBtn || !cancelBtn || !backdrop || !passwordInput) {
                return;
            }

            const openOverlay = () => {
                overlay.classList.add('open');
                overlay.setAttribute('aria-hidden', 'false');
                window.setTimeout(() => {
                    passwordInput.focus();
                }, 40);
            };

            const closeOverlay = () => {
                overlay.classList.remove('open');
                overlay.setAttribute('aria-hidden', 'true');
                passwordInput.value = '';
            };

            openBtn.addEventListener('click', openOverlay);
            closeBtn.addEventListener('click', closeOverlay);
            cancelBtn.addEventListener('click', closeOverlay);
            backdrop.addEventListener('click', closeOverlay);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && overlay.classList.contains('open')) {
                    closeOverlay();
                }
            });
        })();
    </script>
<?php endif; ?>
<?php loadPartial('end') ?>
