<?php loadPartial('teacher-head') ?>
<?php loadPartial('sidebar') ?>
<section>
    <?php loadPartial('header') ?>
    <main>
        <?php
        $old = Session::getFlashMesssge('old_teacher_alert', []);
        $conversation = $conversation ?? [];
        $unreadReplyCount = (int) ($unreadReplyCount ?? 0);
        $latestActivityTs = 0;
        foreach ($conversation as $entry) {
            $ts = strtotime((string) ($entry['created_at'] ?? ''));
            if ($ts > $latestActivityTs) {
                $latestActivityTs = $ts;
            }
        }
        ?>
        <div class="dashboard-head">
            <h1>Messages</h1>
            <p>Chat with admin directly. No title required.</p>
        </div>

        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="chat-shell teacher-chat-shell">
            <div class="chat-main">
                <div class="chat-thread" id="teacherChatThread">
                    <?php if (!empty($conversation)): ?>
                        <?php foreach ($conversation as $entry): ?>
                            <?php
                            $isOutgoing = ($entry['origin_role'] ?? 'teacher') === 'teacher';
                            $timeText = date('M j, g:i A', strtotime((string) ($entry['created_at'] ?? 'now')));
                            ?>
                            <article class="chat-bubble <?= $isOutgoing ? 'outgoing' : 'incoming' ?>">
                                <p><?= nl2br(htmlspecialchars((string) ($entry['text'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                                <small><?= htmlspecialchars($timeText, ENT_QUOTES, 'UTF-8') ?></small>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="question-preview-empty">
                            <p class="main-text">No messages yet</p>
                            <p class="text">Start the conversation with admin.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="chat-summary-line">
                    <span id="teacherMessageCount"><?= count($conversation) ?> message<?= count($conversation) === 1 ? '' : 's' ?></span>
                    <span id="teacherUnreadRepliesCount"><?= $unreadReplyCount ?> unread</span>
                </div>

                <form action="/teacher/notify-admin" method="POST" class="chat-compose">
                    <?= csrfField() ?>
                    <input type="hidden" name="title" value="" />
                    <textarea
                        id="teacher_alert_message"
                        name="message"
                        class="select"
                        rows="2"
                        maxlength="2000"
                        placeholder="Type message..."
                        required><?= htmlspecialchars((string) ($old['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    <button type="submit" class="mini-btn chat-send-btn" id="save">Send</button>
                </form>
            </div>
        </div>
    </main>
</section>
<script>
    (function() {
        const unreadNode = document.getElementById('teacherUnreadRepliesCount');
        const countNode = document.getElementById('teacherMessageCount');
        const thread = document.getElementById('teacherChatThread');
        const composeBox = document.getElementById('teacher_alert_message');
        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }

        if (!unreadNode || !countNode) {
            return;
        }

        let latestActivityTs = <?= (int) $latestActivityTs ?>;
        let activityCount = <?= (int) count($conversation) ?>;

        const canAutoRefresh = function() {
            const active = document.activeElement;
            if (!active) {
                return true;
            }
            const tag = String(active.tagName || '').toLowerCase();
            return tag !== 'textarea' && tag !== 'input';
        };

        const refreshMessageStats = function() {
            fetch('/teacher/messages/feed', {
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

                    const unread = Number(payload.unread_reply_count || 0);
                    unreadNode.textContent = String(unread) + ' unread';

                    const nextActivityTs = Number(payload.latest_activity_ts || 0);
                    const nextCount = Number(payload.activity_count || 0);
                    countNode.textContent = String(nextCount) + ' message' + (nextCount === 1 ? '' : 's');

                    const changed = nextActivityTs !== latestActivityTs || nextCount !== activityCount;
                    if (changed && canAutoRefresh() && !(composeBox && composeBox.value.trim() !== '')) {
                        window.location.reload();
                    }

                    latestActivityTs = nextActivityTs;
                    activityCount = nextCount;
                })
                .catch(function() {});
        };

        refreshMessageStats();
        setInterval(refreshMessageStats, 5000);
    })();
</script>
<?php loadPartial('end') ?>