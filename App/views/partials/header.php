<?php
$role = strtolower(Session::get('user')['role'] ?? 'teacher');
$displayName = trim((string) (Session::get('user')['name'] ?? 'No Data'));
$nameParts = array_filter(preg_split('/\s+/', $displayName));
$profileInitials = '';
foreach (array_slice($nameParts, 0, 2) as $namePart) {
    $profileInitials .= strtoupper(substr((string) $namePart, 0, 1));
}
if ($profileInitials === '') {
    $profileInitials = 'U';
}
$subjectsText = 'No Subject';
if (function_exists('adminSubjectsLabels')) {
    $subjects = adminSubjectsLabels(Session::get('user')['assigned_subjects'] ?? '');
    if (!empty($subjects)) {
        $subjectsText = implode(', ', $subjects);
    }
}

$notificationRows = [];
$notificationCount = 0;
$notificationTypeOptions = function_exists('adminNotificationTypeOptions') ? adminNotificationTypeOptions() : [];
$notificationUserId = (int) (Session::get('user')['id'] ?? 0);

if (function_exists('adminEnsureNotificationsSchema') && function_exists('adminFetchLatestNotifications') && function_exists('adminGroupNotificationsByDate')) {
    require_once basePath('Framework/Database.php');
    try {
        $adminConfig = require basePath('config/config-db.php');
        $adminDb = new Database($adminConfig);
        adminEnsureNotificationsSchema($adminDb);
        $notificationRows = adminFetchLatestNotifications($adminDb, 120, false);
        $notificationCount = count($notificationRows);
    } catch (Exception $exception) {
        $notificationRows = [];
        $notificationCount = 0;
    }
}
?>
<header>
    <h2>Livingspring CBT</h2>
    <div class="header-actions">
        <button
            type="button"
            class="notification-toggle-btn icon-only"
            id="openTeacherNotifications"
            aria-label="Open notifications"
            title="Notifications"
            aria-haspopup="dialog"
            aria-controls="teacherNotificationOverlay">
            <i class="fa fa-bell" aria-hidden="true"></i>
            <strong id="teacherNotificationBadge" <?= $notificationCount > 0 ? '' : 'hidden' ?>><?= $notificationCount > 99 ? '99+' : $notificationCount ?></strong>
        </button>
        <div class="profile">
            <div class="header-avatar" aria-hidden="true"><?= htmlspecialchars($profileInitials, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="profile-bio">
                <p class="name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="role"><?= ucfirst($role) ?> | <?= $subjectsText ?></p>
            </div>
        </div>
    </div>
</header>

<div class="teacher-notification-overlay" id="teacherNotificationOverlay" aria-hidden="true">
    <div class="teacher-notification-backdrop" data-close-teacher-notifications></div>
    <div class="teacher-notification-card" role="dialog" aria-modal="true" aria-labelledby="teacherNotificationTitle">
        <div class="teacher-notification-head">
            <h3 id="teacherNotificationTitle">Notifications</h3>
            <button type="button" class="teacher-notification-close" data-close-teacher-notifications aria-label="Close notifications">&times;</button>
        </div>
        <p class="teacher-notification-subtitle">Updates from Admins</p>
        <p class="teacher-notification-system-note" id="teacherNotificationSystemNote" hidden></p>
        <div class="teacher-notification-scroll" id="teacherNotificationScroll"></div>
    </div>
</div>

<script>
    (function() {
        const openButton = document.getElementById('openTeacherNotifications');
        const overlay = document.getElementById('teacherNotificationOverlay');
        const listHost = document.getElementById('teacherNotificationScroll');
        const systemNote = document.getElementById('teacherNotificationSystemNote');
        const badge = document.getElementById('teacherNotificationBadge');
        const closeNodes = document.querySelectorAll('[data-close-teacher-notifications]');
        const notificationUserId = <?= (int) $notificationUserId ?>;
        const notificationTypeOptions = <?= json_encode($notificationTypeOptions ?? [], JSON_UNESCAPED_SLASHES) ?>;
        const initialRows = <?= json_encode(array_values($notificationRows), JSON_UNESCAPED_SLASHES) ?>;
        const readStorageKey = 'teacher_notification_last_read_' + String(notificationUserId || 'guest');
        const feedEndpoint = '/teacher/notifications/feed';
        const state = {
            rows: Array.isArray(initialRows) ? initialRows : [],
            times: [],
            visibleIds: []
        };

        if (!openButton || !overlay || !listHost || !systemNote || !badge || closeNodes.length === 0) {
            return;
        }

        const getLastReadTs = function() {
            try {
                const raw = window.localStorage.getItem(readStorageKey);
                const parsed = Number(raw || 0);
                return Number.isFinite(parsed) ? parsed : 0;
            } catch (error) {
                return 0;
            }
        };

        const parseDateValue = function(value) {
            const raw = String(value || '').trim();
            if (!raw) {
                return null;
            }

            const normalized = raw.replace(' ', 'T');
            let date = new Date(normalized);
            if (!Number.isNaN(date.getTime())) {
                return date;
            }

            const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
            if (!match) {
                return null;
            }

            date = new Date(
                Number(match[1]),
                Number(match[2]) - 1,
                Number(match[3]),
                Number(match[4]),
                Number(match[5]),
                Number(match[6] || 0)
            );
            return Number.isNaN(date.getTime()) ? null : date;
        };

        const toTimestamp = function(value) {
            const date = parseDateValue(value);
            if (!date) {
                return 0;
            }
            return Math.floor(date.getTime() / 1000);
        };

        const rebuildTimes = function() {
            state.times = (state.rows || []).map(function(row) {
                return toTimestamp(row && row.created_at ? row.created_at : '');
            }).filter(function(ts) {
                return ts > 0;
            });
            state.visibleIds = (state.rows || []).map(function(row) {
                return Number(row && row.id ? row.id : 0);
            }).filter(function(id) {
                return id > 0;
            });
        };

        const getLatestNotificationTs = function() {
            if (!Array.isArray(state.times) || state.times.length === 0) {
                return 0;
            }
            return Math.max.apply(null, state.times);
        };

        const getUnreadCount = function(lastReadTs) {
            if (!Array.isArray(state.times) || state.times.length === 0) {
                return 0;
            }
            return state.times.reduce(function(total, timestamp) {
                return total + (Number(timestamp) > lastReadTs ? 1 : 0);
            }, 0);
        };

        const renderUnreadBadge = function() {
            const unread = getUnreadCount(getLastReadTs());
            if (unread > 0) {
                badge.textContent = unread > 99 ? '99+' : String(unread);
                badge.hidden = false;
            } else {
                badge.hidden = true;
            }
        };

        const markAllRead = function() {
            const latestTs = getLatestNotificationTs();
            if (latestTs <= 0) {
                renderUnreadBadge();
                return;
            }
            try {
                window.localStorage.setItem(readStorageKey, String(latestTs));
            } catch (error) {}
            renderUnreadBadge();
        };

        const showSystemNote = function(text) {
            if (!text) {
                systemNote.hidden = true;
                systemNote.textContent = '';
                return;
            }

            systemNote.textContent = text;
            systemNote.hidden = false;
            window.setTimeout(function() {
                systemNote.hidden = true;
                systemNote.textContent = '';
            }, 4500);
        };

        const escapeHtml = function(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const formatHeading = function(dateKey) {
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const yesterday = new Date(today.getTime() - 86400000);
            const target = new Date(dateKey + 'T00:00:00');
            const fullDate = target.toLocaleDateString(undefined, {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            if (target.getTime() === today.getTime()) {
                return 'Today - ' + fullDate;
            }
            if (target.getTime() === yesterday.getTime()) {
                return 'Yesterday - ' + fullDate;
            }
            return fullDate;
        };

        const formatTime = function(value) {
            const date = parseDateValue(value);
            if (!date) {
                return '';
            }
            return date.toLocaleTimeString([], {
                hour: 'numeric',
                minute: '2-digit'
            });
        };

        const renderNotificationList = function() {
            if (!Array.isArray(state.rows) || state.rows.length === 0) {
                listHost.innerHTML = '<div class="teacher-notification-empty"><p class="main-text">No notifications yet</p><p class="text">Admin messages and meeting updates will appear here.</p></div>';
                return;
            }

            const grouped = {};
            state.rows.forEach(function(row) {
                const date = parseDateValue(row && row.created_at ? row.created_at : '');
                if (!date) {
                    return;
                }
                const key = [
                    String(date.getFullYear()),
                    String(date.getMonth() + 1).padStart(2, '0'),
                    String(date.getDate()).padStart(2, '0')
                ].join('-');
                if (!grouped[key]) {
                    grouped[key] = [];
                }
                grouped[key].push(row);
            });

            const dateKeys = Object.keys(grouped).sort(function(a, b) {
                return a > b ? -1 : 1;
            });

            const sections = dateKeys.map(function(key) {
                const items = grouped[key] || [];
                const itemRows = items.map(function(row) {
                    const typeKeyRaw = String(row && row.type ? row.type : 'general').toLowerCase();
                    const typeKey = ['important', 'meeting', 'general'].indexOf(typeKeyRaw) !== -1 ? typeKeyRaw : 'general';
                    const typeLabel = String(notificationTypeOptions[typeKey] || typeKey.charAt(0).toUpperCase() + typeKey.slice(1));
                    const messageHtml = '<p>' + escapeHtml(String(row && row.message ? row.message : '')).replace(/\n/g, '<br>') + '</p>';
                    const editedAt = row && row.edited_at ? String(row.edited_at) : '';
                    const editedTs = toTimestamp(editedAt);
                    const editedHtml = editedTs > 0 ?
                        '<small class="teacher-notification-edited">Edited on ' + escapeHtml(formatHeading([
                            String(parseDateValue(editedAt).getFullYear()),
                            String(parseDateValue(editedAt).getMonth() + 1).padStart(2, '0'),
                            String(parseDateValue(editedAt).getDate()).padStart(2, '0')
                        ].join('-'))) + ' at ' + escapeHtml(formatTime(editedAt)) + '</small>' :
                        '';

                    return '<article class="teacher-notification-item">' +
                        '<div class="teacher-notification-item-head">' +
                        '<h5>' + escapeHtml(String(row && row.title ? row.title : '')) + '</h5>' +
                        '<span class="teacher-notification-pill ' + escapeHtml(typeKey) + '">' + escapeHtml(typeLabel) + '</span>' +
                        '</div>' +
                        messageHtml +
                        editedHtml +
                        '<small>' + escapeHtml(formatTime(row && row.created_at ? row.created_at : '')) + '</small>' +
                        '</article>';
                }).join('');

                return '<section class="teacher-notification-group">' +
                    '<div class="teacher-notification-group-head">' +
                    '<h4>' + escapeHtml(formatHeading(key)) + '</h4>' +
                    '<span>' + String(items.length) + ' item' + (items.length === 1 ? '' : 's') + '</span>' +
                    '</div>' +
                    '<div class="teacher-notification-list">' + itemRows + '</div>' +
                    '</section>';
            }).join('');

            listHost.innerHTML = sections;
        };

        const refreshFeed = function() {
            fetch(feedEndpoint, {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(payload) {
                    if (!payload || payload.ok !== true || !Array.isArray(payload.notifications)) {
                        return;
                    }
                    const previousIds = (state.visibleIds || []).slice();
                    state.rows = payload.notifications;
                    rebuildTimes();
                    renderNotificationList();

                    const currentIds = (state.visibleIds || []).slice();
                    const removedCount = previousIds.filter(function(id) {
                        return currentIds.indexOf(id) === -1;
                    }).length;

                    if (removedCount > 0) {
                        showSystemNote(removedCount === 1 ? 'A notification was removed.' : (String(removedCount) + ' notifications were removed.'));
                    }

                    if (overlay.classList.contains('active')) {
                        markAllRead();
                    } else {
                        renderUnreadBadge();
                    }
                })
                .catch(function() {});
        };

        const openOverlay = function() {
            overlay.classList.add('active');
            overlay.setAttribute('aria-hidden', 'false');
            markAllRead();
        };

        const closeOverlay = function() {
            overlay.classList.remove('active');
            overlay.setAttribute('aria-hidden', 'true');
        };

        openButton.addEventListener('click', openOverlay);
        closeNodes.forEach(function(node) {
            node.addEventListener('click', closeOverlay);
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && overlay.classList.contains('active')) {
                closeOverlay();
            }
        });

        rebuildTimes();
        renderNotificationList();
        renderUnreadBadge();
        setInterval(refreshFeed, 10000);
    })();
</script>
