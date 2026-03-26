<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <?php
        $old = Session::getFlashMesssge('old_notification', []);
        $selectedType = adminNormalizeNotificationType($old['type'] ?? 'important');
        ?>
        <div class="dashboard-head">
            <h1>Notifications</h1>
            <p>Broadcast updates to teachers. Use Teacher Messages for direct conversations.</p>
        </div>

        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="admin-grid create-mode notification-grid">
            <article class="form-card">
                <h3>Publish Notification</h3>
                <form action="/admin/notifications" method="POST">
                    <?= csrfField() ?>
                    <label for="notification_title">Title</label>
                    <input
                        id="notification_title"
                        type="text"
                        name="title"
                        class="select"
                        value="<?= htmlspecialchars((string) ($old['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. Staff Meeting at 2:00 PM"
                        maxlength="160"
                        required />

                    <label for="notification_type">Type</label>
                    <select id="notification_type" name="type" class="select" required>
                        <?php foreach (($notificationTypes ?? []) as $typeKey => $typeLabel): ?>
                            <option value="<?= htmlspecialchars((string) $typeKey, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedType === $typeKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $typeLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="notification_message">Message</label>
                    <textarea
                        id="notification_message"
                        name="message"
                        class="select notification-textarea"
                        rows="6"
                        maxlength="2000"
                        placeholder="Type the full message for teachers..."
                        required><?= htmlspecialchars((string) ($old['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

                    <button class="button" type="submit">Send Notification</button>
                </form>
            </article>

            <article class="table-card notification-feed-card">
                <div class="table-head">
                    <h3>Recent Notifications</h3>
                    <p><?= count($notifications ?? []) ?> item<?= count($notifications ?? []) === 1 ? '' : 's' ?></p>
                </div>
                <div class="notification-feed-list">
                    <?php if (!empty($notifications ?? [])): ?>
                        <?php foreach (($notifications ?? []) as $row): ?>
                            <?php
                            $typeKey = adminNormalizeNotificationType($row['type'] ?? 'general');
                            $typeLabel = ($notificationTypes[$typeKey] ?? ucfirst($typeKey));
                            ?>
                            <article class="notification-card">
                                <div class="notification-card-head">
                                    <h4><?= htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h4>
                                    <div class="notification-card-actions">
                                        <span class="notification-tag <?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string) $typeLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <button type="button" class="mini-btn ghost notification-edit-toggle" data-target="edit-notification-<?= (int) ($row['id'] ?? 0) ?>">Edit</button>
                                        <form action="/admin/notifications/delete" method="POST" data-warning-confirm="Delete this notification?">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>" />
                                            <button type="submit" class="mini-btn danger notification-delete-btn">Delete</button>
                                        </form>
                                    </div>
                                </div>
                                <p><?= nl2br(htmlspecialchars((string) ($row['message'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                                <small><?= date('M j, Y - g:i A', strtotime((string) ($row['created_at'] ?? 'now'))) ?></small>
                                <div class="notification-edit-panel" id="edit-notification-<?= (int) ($row['id'] ?? 0) ?>" hidden>
                                    <form action="/admin/notifications/update" method="POST">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>" />
                                        <label for="edit_title_<?= (int) ($row['id'] ?? 0) ?>">Title</label>
                                        <input id="edit_title_<?= (int) ($row['id'] ?? 0) ?>" type="text" name="title" class="select" value="<?= htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="160" required />
                                        <label for="edit_type_<?= (int) ($row['id'] ?? 0) ?>">Type</label>
                                        <select id="edit_type_<?= (int) ($row['id'] ?? 0) ?>" name="type" class="select" required>
                                            <?php foreach (($notificationTypes ?? []) as $editTypeKey => $editTypeLabel): ?>
                                                <option value="<?= htmlspecialchars((string) $editTypeKey, ENT_QUOTES, 'UTF-8') ?>" <?= $typeKey === $editTypeKey ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) $editTypeLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="edit_message_<?= (int) ($row['id'] ?? 0) ?>">Message</label>
                                        <textarea id="edit_message_<?= (int) ($row['id'] ?? 0) ?>" name="message" class="select notification-textarea compact" rows="4" maxlength="2000" required><?= htmlspecialchars((string) ($row['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                        <div class="notification-edit-actions">
                                            <button type="button" class="mini-btn ghost notification-edit-cancel" data-target="edit-notification-<?= (int) ($row['id'] ?? 0) ?>">Cancel</button>
                                            <button type="submit" class="mini-btn">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="profile-empty">
                            <h4>No notifications yet</h4>
                            <p>Published messages will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </div>

    </main>
</section>

<script>
    (function() {
        const toggleButtons = Array.from(document.querySelectorAll('.notification-edit-toggle'));
        const cancelButtons = Array.from(document.querySelectorAll('.notification-edit-cancel'));

        const togglePanel = function(targetId, forceOpen) {
            if (!targetId) return;
            const panel = document.getElementById(targetId);
            if (!panel) return;
            const open = typeof forceOpen === 'boolean' ? forceOpen : panel.hasAttribute('hidden');
            if (open) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', 'hidden');
            }
        };

        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                togglePanel(button.getAttribute('data-target'));
            });
        });

        cancelButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                togglePanel(button.getAttribute('data-target'), false);
            });
        });
    })();
</script>

<?php loadPartial('end') ?>
