<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <?php
        $directOld = Session::getFlashMesssge('old_teacher_direct_message', []);
        $selectedTeacherId = (int) ($selectedTeacherId ?? 0);
        $latestAlertId = (int) ($latestAlertId ?? 0);
        $conversation = $conversation ?? [];
        $latestActivityTs = 0;
        foreach ($conversation as $entry) {
            $ts = strtotime((string) ($entry['created_at'] ?? ''));
            if ($ts > $latestActivityTs) {
                $latestActivityTs = $ts;
            }
        }
        ?>
        <div class="dashboard-head">
            <h1>Teacher Messages</h1>
            <p>Direct chat with teachers. No title required.</p>
        </div>

        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="chat-shell" id="adminTeacherMessagesRoot" data-latest-alert-id="<?= $latestAlertId ?>">
            <aside class="chat-sidebar">
                <h3>Teachers</h3>
                <div class="chat-sidebar-list">
                    <?php if (!empty($teachers ?? [])): ?>
                        <?php foreach (($teachers ?? []) as $teacher): ?>
                            <?php $teacherId = (int) ($teacher['id'] ?? 0); ?>
                            <a
                                href="/admin/teacher-messages?teacher=<?= $teacherId ?>"
                                class="chat-sidebar-item <?= $teacherId === $selectedTeacherId ? 'active' : '' ?>">
                                <?= htmlspecialchars((string) ($teacher['name'] ?? 'Unknown Teacher'), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text">No teacher accounts found.</p>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="chat-main">
                <?php if ($selectedTeacherId > 0): ?>
                    <div class="chat-thread" id="adminChatThread">
                        <?php if (!empty($conversation)): ?>
                            <?php foreach ($conversation as $entry): ?>
                                <?php
                                $isOutgoing = ($entry['origin_role'] ?? 'teacher') === 'admin';
                                $timeText = date('M j, g:i A', strtotime((string) ($entry['created_at'] ?? 'now')));
                                ?>
                                <article class="chat-bubble <?= $isOutgoing ? 'outgoing' : 'incoming' ?>">
                                    <p><?= nl2br(htmlspecialchars((string) ($entry['text'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                                    <small><?= htmlspecialchars($timeText, ENT_QUOTES, 'UTF-8') ?></small>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="profile-empty">
                                <h4>No messages yet</h4>
                                <p>Start chatting with this teacher.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="/admin/teacher-messages/send" method="POST" class="chat-compose">
                        <?= csrfField() ?>
                        <input type="hidden" name="teacher_user_id" value="<?= $selectedTeacherId ?>" />
                        <input type="hidden" name="title" value="" />
                        <textarea
                            name="message"
                            class="select"
                            rows="2"
                            maxlength="2000"
                            placeholder="Type message..."
                            required><?= htmlspecialchars((string) ($directOld['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        <button type="submit" class="mini-btn chat-send-btn">Send</button>
                    </form>
                <?php else: ?>
                    <div class="profile-empty">
                        <h4>Select a teacher</h4>
                        <p>Choose a teacher from the left to open chat.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</section>

<script>
    (function() {
        const root = document.getElementById('adminTeacherMessagesRoot');
        const chatThread = document.getElementById('adminChatThread');
        if (!root) {
            return;
        }

        if (chatThread) {
            chatThread.scrollTop = chatThread.scrollHeight;
        }

        let latestId = Number(root.getAttribute('data-latest-alert-id') || 0);
        let latestActivityTs = <?= (int) $latestActivityTs ?>;
        let totalCount = <?= (int) count($conversation) ?>;
        const params = new URLSearchParams(window.location.search || '');
        const selectedTeacher = Number(params.get('teacher') || 0);

        const canAutoRefresh = function() {
            const active = document.activeElement;
            if (!active) {
                return true;
            }
            const tag = String(active.tagName || '').toLowerCase();
            return tag !== 'textarea' && tag !== 'input';
        };

        const poll = function() {
            const query = selectedTeacher > 0 ? ('?teacher=' + encodeURIComponent(String(selectedTeacher))) : '';
            fetch('/admin/teacher-messages/feed' + query, {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(payload) {
                    if (!payload || payload.ok !== true) {
                        return;
                    }

                    const nextLatest = Number(payload.latest_alert_id || 0);
                    const nextTs = Number(payload.latest_activity_ts || 0);
                    const nextTotal = Number(payload.total || 0);
                    const changed = nextLatest !== latestId || nextTs !== latestActivityTs || nextTotal !== totalCount;
                    if (changed && canAutoRefresh()) {
                        window.location.reload();
                    }

                    latestId = nextLatest;
                    latestActivityTs = nextTs;
                    totalCount = nextTotal;
                })
                .catch(function() {});
        };

        setInterval(poll, 5000);
    })();
</script>

<?php loadPartial('end') ?>
