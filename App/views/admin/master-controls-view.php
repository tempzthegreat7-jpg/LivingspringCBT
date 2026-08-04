<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Master Controls</h1>
            <p>Real-time exam session management. Actions are pushed instantly to connected student and teacher clients.</p>
        </div>

        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="master-controls-grid">
            <article class="control-card timer-controls">
                <div class="control-card-head">
                    <h3>Timer Controls</h3>
                    <?php if ((bool) ($globalTimerPaused ?? false)): ?>
                        <span class="control-badge paused">Paused</span>
                    <?php endif; ?>
                </div>
                <div class="control-card-body">
                    <form action="/admin/master-controls/action" method="POST" class="control-form" data-confirm="Pause all active exam timers?">
                        <?= csrfField() ?>
                        <input type="hidden" name="command_type" value="pause_timer" />
                        <input type="hidden" name="target_scope" value="all" />
                        <input type="hidden" name="payload" value='{}' />
                        <button type="submit" class="mini-btn <?= (bool) ($globalTimerPaused ?? false) ? 'ghost' : '' ?>" <?= (bool) ($globalTimerPaused ?? false) ? 'disabled' : '' ?>>
                            <?= (bool) ($globalTimerPaused ?? false) ? 'Timers Paused' : 'Pause All Timers' ?>
                        </button>
                    </form>

                    <form action="/admin/master-controls/action" method="POST" class="control-form" data-confirm="Resume all active exam timers?">
                        <?= csrfField() ?>
                        <input type="hidden" name="command_type" value="resume_timer" />
                        <input type="hidden" name="target_scope" value="all" />
                        <input type="hidden" name="payload" value='{}' />
                        <button type="submit" class="mini-btn" <?= !(bool) ($globalTimerPaused ?? false) ? 'disabled' : '' ?>>
                            Resume All Timers
                        </button>
                    </form>

                    <form action="/admin/master-controls/action" method="POST" class="control-form" id="add-time-form" data-confirm="Add time to all active exam timers?">
                        <?= csrfField() ?>
                        <input type="hidden" name="command_type" value="add_time" />
                        <input type="hidden" name="target_scope" value="all" />
                        <div class="add-time-row">
                            <select name="seconds" class="select" required>
                                <option value="60">+1 minute</option>
                                <option value="300">+5 minutes</option>
                                <option value="600">+10 minutes</option>
                                <option value="900">+15 minutes</option>
                                <option value="1800">+30 minutes</option>
                            </select>
                            <button type="submit" class="mini-btn">Add Time</button>
                        </div>
                    </form>

                    <?php if ((int) ($globalTimeBonus ?? 0) > 0): ?>
                        <p class="control-meta">Global bonus applied: +<?= (int) ($globalTimeBonus ?? 0) ?> seconds</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="control-card session-controls">
                <div class="control-card-head">
                    <h3>Session Controls</h3>
                </div>
                <div class="control-card-body">
                    <form action="/admin/master-controls/action" method="POST" class="control-form" data-confirm="Force-submit ALL active exams? This cannot be undone.">
                        <?= csrfField() ?>
                        <input type="hidden" name="command_type" value="force_submit" />
                        <input type="hidden" name="target_scope" value="all" />
                        <input type="hidden" name="payload" value='{}' />
                        <button type="submit" class="mini-btn danger">Force Submit All Exams</button>
                    </form>

                    <form action="/admin/master-controls/action" method="POST" class="control-form" data-confirm="Terminate ALL active student and teacher sessions?">
                        <?= csrfField() ?>
                        <input type="hidden" name="command_type" value="terminate_session" />
                        <input type="hidden" name="target_scope" value="all" />
                        <input type="hidden" name="payload" value='{}' />
                        <button type="submit" class="mini-btn danger">Terminate All Sessions</button>
                    </form>
                </div>
            </article>

            <article class="control-card targeted-controls">
                <div class="control-card-head">
                    <h3>Targeted Actions</h3>
                </div>
                <div class="control-card-body">
                    <form action="/admin/master-controls/action" method="POST" class="control-form" id="targeted-form" data-confirm="Apply action to targeted sessions?">
                        <?= csrfField() ?>
                        <div class="targeted-row">
                            <select name="target_scope" class="select" required>
                                <option value="all">All Sessions</option>
                                <?php foreach (($classOptions ?? []) as $classLabel): ?>
                                    <option value="class:<?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?>">
                                        Class: <?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="command_type" class="select" required>
                                <option value="force_submit">Force Submit</option>
                                <option value="terminate_session">Terminate</option>
                                <option value="pause_timer">Pause Timer</option>
                                <option value="resume_timer">Resume Timer</option>
                                <option value="add_time">Add Time</option>
                            </select>
                            <select name="seconds" class="select" id="targeted-seconds">
                                <option value="60">+1 min</option>
                                <option value="300">+5 min</option>
                                <option value="600">+10 min</option>
                                <option value="900">+15 min</option>
                                <option value="1800">+30 min</option>
                            </select>
                        </div>
                        <input type="hidden" name="payload" id="targeted-payload" value='{"seconds": 300}' />
                        <button type="submit" class="mini-btn">Execute Targeted Action</button>
                    </form>
                </div>
            </article>

            <article class="table-card live-monitor">
                <div class="table-head">
                    <h3>Live Exam Monitor</h3>
                    <p><?= count($activeExamSessions ?? []) ?> active session<?= count($activeExamSessions ?? []) === 1 ? '' : 's' ?></p>
                </div>
                <div class="table-tools" data-table-controls="master-controls-monitor">
                    <label class="table-tool-search">
                        <span class="sr-only">Search active sessions</span>
                        <input type="search" class="select" data-table-search placeholder="Search sessions..." aria-label="Search active sessions" />
                    </label>
                    <p class="table-tool-status" data-table-status aria-live="polite"></p>
                    <div class="table-pagination" data-table-pagination aria-label="Table pagination"></div>
                </div>
                <table id="master-controls-monitor" data-enhance-table="1">
                    <thead>
                        <tr>
                            <th scope="col" data-sortable="1" data-sort-col="0">Student</th>
                            <th scope="col" data-sortable="1" data-sort-col="1">Class</th>
                            <th scope="col" data-sortable="1" data-sort-col="2">Subject</th>
                            <th scope="col" data-sortable="1" data-sort-col="3">Status</th>
                            <th scope="col" data-sortable="1" data-sort-col="4">Timer</th>
                            <th scope="col" data-sortable="1" data-sort-col="5">Question</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($activeExamSessions ?? [])): ?>
                            <?php foreach (($activeExamSessions ?? []) as $session): ?>
                                <?php
                                    $isPaused = (int) ($session['timer_paused'] ?? 0) === 1;
                                    $bonus = max(0, (int) ($session['time_bonus_seconds'] ?? 0));
                                    $endsAt = (int) ($session['ends_at'] ?? 0);
                                    $duration = max(0, (int) ($session['duration_seconds'] ?? 0));
                                    $currentIndex = (int) ($session['current_index'] ?? 0);
                                    $total = max(0, (int) ($session['total_questions'] ?? 0));
                                    $remaining = $endsAt > 0 ? max(0, $endsAt - time()) : 0;
                                    $totalSeconds = max(0, $remaining + $bonus);
                                    if ($totalSeconds > 0) {
                                        $hours = floor($totalSeconds / 3600);
                                        $minutes = floor(($totalSeconds % 3600) / 60);
                                        $seconds = $totalSeconds % 60;
                                        $timerDisplay = $hours > 0
                                            ? str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT)
                                            : str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT);
                                    } else {
                                        $timerDisplay = 'No limit';
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($session['student_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($session['student_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($session['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="control-status-pill <?= $isPaused ? 'paused' : 'active' ?>">
                                            <?= $isPaused ? 'Paused' : 'Active' ?>
                                        </span>
                                        <?php if ($bonus > 0): ?>
                                            <span class="control-badge bonus">+<?= $bonus ?>s</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($timerDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= ($currentIndex + 1) ?> / <?= $total ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No active exam sessions right now.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </article>
        </div>
    </main>
</section>

<div id="master-controls-toast" class="mc-toast" aria-live="polite" aria-atomic="true" style="display:none;"></div>

<script>
    (function() {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';
        const monitorTable = document.getElementById('master-controls-monitor');
        const tbody = monitorTable ? (monitorTable.tBodies && monitorTable.tBodies[0] ? monitorTable.tBodies[0] : null) : null;

        window.__adminServerTime = <?= time() ?>;

        const showToast = function(message, type) {
            const toast = document.getElementById('master-controls-toast');
            if (!toast) {
                return;
            }

            toast.textContent = String(message || '');
            toast.className = 'mc-toast ' + (type === 'error' ? 'error' : 'success');
            toast.style.display = 'block';

            window.clearTimeout(toast._timeout);
            toast._timeout = window.setTimeout(function() {
                toast.style.display = 'none';
                toast.className = 'mc-toast';
            }, 4000);
        };

        const formatTime = function(seconds) {
            const safe = Math.max(0, seconds);
            const hours = Math.floor(safe / 3600);
            const minutes = Math.floor((safe % 3600) / 60);
            const secs = safe % 60;

            if (hours > 0) {
                return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            }

            return String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        };

        const buildRowKey = function(name, studentClass) {
            return String(name || '').trim() + '|' + String(studentClass || '').trim();
        };

        const getServerOffset = function() {
            const serverTime = Math.max(0, parseInt((window.__adminServerTime || 0), 10));
            const clientNow = Math.floor(Date.now() / 1000);
            return serverTime > 0 ? (serverTime - clientNow) : 0;
        };

        const tickMonitorTimers = function() {
            if (!monitorTable || !tbody) {
                return;
            }

            const rows = Array.from(tbody.rows).filter(function(row) {
                return row.querySelector('td');
            });

            const serverOffset = getServerOffset();

            rows.forEach(function(row) {
                const timerCell = row.cells[4];
                if (!timerCell) {
                    return;
                }

                const statusCell = row.cells[3];
                const isPaused = statusCell ? statusCell.querySelector('.control-status-pill.paused') !== null : false;
                if (isPaused) {
                    return;
                }

                const endsAtEl = row.querySelector('[data-ends-at]');
                const durationEl = row.querySelector('[data-duration]');
                const bonusEl = row.querySelector('[data-bonus]');

                if (!endsAtEl || !durationEl) {
                    return;
                }

                const endsAt = parseInt(endsAtEl.dataset.endsAt || '0', 10);
                const duration = Math.max(0, parseInt(durationEl.dataset.duration || '0', 10));
                const bonus = Math.max(0, parseInt(bonusEl ? bonusEl.dataset.bonus || '0' : '0', 10));

                if (duration <= 0 || endsAt <= 0) {
                    timerCell.textContent = 'No limit';
                    return;
                }

                const remaining = Math.max(0, endsAt - (Math.floor(Date.now() / 1000) + serverOffset));
                timerCell.textContent = formatTime(remaining + bonus);
            });
        };

        const updateMonitorTable = function(sessions) {
            if (!monitorTable || !tbody) {
                return;
            }

            const sessionMap = new Map();
            (sessions || []).forEach(function(session) {
                const key = buildRowKey(session.student_name, session.student_class);
                sessionMap.set(key, session);
            });

            const rows = Array.from(tbody.rows).filter(function(row) {
                return row.querySelector('td');
            });

            rows.forEach(function(row) {
                const nameCell = row.cells[0];
                const classCell = row.cells[1];
                if (!nameCell || !classCell) {
                    return;
                }

                const key = buildRowKey(nameCell.textContent, classCell.textContent);
                const session = sessionMap.get(key);

                if (!session) {
                    row.style.opacity = '0.35';
                    return;
                }

                row.style.opacity = '1';
                const isPaused = (parseInt(session.timer_paused ?? 0, 10) === 1);
                const bonus = Math.max(0, parseInt(session.time_bonus_seconds ?? 0, 10));
                const endsAt = parseInt(session.ends_at ?? 0, 10);
                const duration = Math.max(0, parseInt(session.duration_seconds ?? 0, 10));
                const currentIndex = parseInt(session.current_index ?? 0, 10);
                const total = Math.max(0, parseInt(session.total_questions ?? 0, 10));

                const statusCell = row.cells[3];
                if (statusCell) {
                    const existingPill = statusCell.querySelector('.control-status-pill');
                    const existingBadge = statusCell.querySelector('.control-badge.bonus');

                    let pill = existingPill;
                    if (!pill) {
                        pill = document.createElement('span');
                        pill.className = 'control-status-pill ' + (isPaused ? 'paused' : 'active');
                        statusCell.appendChild(pill);
                    } else {
                        pill.className = 'control-status-pill ' + (isPaused ? 'paused' : 'active');
                        pill.textContent = isPaused ? 'Paused' : 'Active';
                    }

                    if (bonus > 0) {
                        if (!existingBadge) {
                            const badge = document.createElement('span');
                            badge.className = 'control-badge bonus';
                            badge.textContent = '+' + bonus + 's';
                            statusCell.appendChild(badge);
                        } else {
                            existingBadge.textContent = '+' + bonus + 's';
                        }
                    } else if (existingBadge) {
                        existingBadge.remove();
                    }
                }

                const timerCell = row.cells[4];
                if (timerCell) {
                    let endsAtEl = timerCell.querySelector('[data-ends-at]');
                    if (!endsAtEl) {
                        endsAtEl = document.createElement('span');
                        endsAtEl.className = 'monitor-timer-meta';
                        endsAtEl.setAttribute('data-ends-at', '');
                        endsAtEl.setAttribute('data-duration', '');
                        endsAtEl.setAttribute('data-bonus', '');
                        endsAtEl.style.display = 'none';
                        timerCell.appendChild(endsAtEl);
                    }
                    endsAtEl.dataset.endsAt = String(endsAt);
                    endsAtEl.dataset.duration = String(duration);
                    endsAtEl.dataset.bonus = String(bonus);
                }

                const questionCell = row.cells[5];
                if (questionCell) {
                    questionCell.textContent = (currentIndex + 1) + ' / ' + total;
                }
            });

            const countCell = monitorTable ? monitorTable.closest('.table-card')?.querySelector('.table-head p') : null;
            if (countCell) {
                countCell.textContent = sessionMap.size + ' active session' + (sessionMap.size === 1 ? '' : 's');
            }
        };

        const commandTypeSelect = document.querySelector('#targeted-form select[name="command_type"]');
        const secondsSelect = document.querySelector('#targeted-seconds');
        const payloadInput = document.querySelector('#targeted-payload');

        function updateTargetedPayload() {
            if (!commandTypeSelect || !secondsSelect || !payloadInput) {
                return;
            }

            const type = String(commandTypeSelect.value || '');
            const seconds = type === 'add_time' ? Math.max(0, parseInt(secondsSelect.value || '0', 10) || 0) : 0;
            const payload = type === 'add_time' ? { seconds: seconds } : {};
            payloadInput.value = JSON.stringify(payload);
        }

        if (commandTypeSelect && secondsSelect && payloadInput) {
            commandTypeSelect.addEventListener('change', updateTargetedPayload);
            secondsSelect.addEventListener('change', updateTargetedPayload);
            updateTargetedPayload();
        }

        const confirmForms = Array.from(document.querySelectorAll('form[data-confirm]'));
        confirmForms.forEach(function(form) {
            form.addEventListener('submit', function(event) {
                const message = form.getAttribute('data-confirm');
                if (!message) {
                    return;
                }

                event.preventDefault();

                if (window.AppWarning && typeof window.AppWarning.confirm === 'function') {
                    window.AppWarning.confirm(message).then(function(approved) {
                        if (approved) {
                            submitControlForm(form);
                        }
                    });
                    return;
                }

                if (window.confirm(message)) {
                    submitControlForm(form);
                }
            });
        });

        const submitControlForm = function(form) {
            const action = form.getAttribute('action') || '/admin/master-controls/action';
            const formData = new FormData(form);
            const button = form.querySelector('button[type="submit"]');
            const originalText = button ? button.textContent : '';

            if (button) {
                button.disabled = true;
                button.textContent = 'Sending...';
            }

            fetch(action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(function(response) {
                    const contentType = response.headers.get('content-type') || '';
                    const isJson = contentType.indexOf('application/json') !== -1;
                    return response[isJson ? 'json' : 'text']().catch(function() {
                        return null;
                    }).then(function(payload) {
                        return {
                            ok: response.ok,
                            payload: payload,
                            isJson: isJson
                        };
                    });
                })
                .then(function(result) {
                    if (!result.ok) {
                        const message = (result.payload && typeof result.payload === 'object' && result.payload.message)
                            ? result.payload.message
                            : 'Server error. Please try again.';
                        showToast(message, 'error');
                        return;
                    }

                    if (!result.isJson || !result.payload || typeof result.payload !== 'object') {
                        showToast('Unexpected server response. Please try again.', 'error');
                        return;
                    }

                    showToast(result.payload.message || 'Action completed.', 'success');
                })
                .catch(function(error) {
                    console.error('Master control action failed:', error);
                    showToast('Unable to reach the server. Please try again.', 'error');
                })
                .finally(function() {
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                });
        };

        const connectAdminStream = function() {
            const url = new URL('/admin/stream', window.location.origin);
            const eventSource = new EventSource(url.toString());

            eventSource.onopen = function() {
                showToast('Live monitor connected.', 'success');
            };

            eventSource.addEventListener('monitor_update', function(event) {
                let data = {};
                try {
                    data = JSON.parse(event.data || '{}');
                } catch (e) {
                    data = {};
                }

                if (Array.isArray(data.sessions)) {
                    updateMonitorTable(data.sessions);
                }

                if (typeof data.global_timer_paused === 'boolean') {
                    const pauseBadge = document.querySelector('.timer-controls .control-badge.paused');
                    const pauseButton = document.querySelector('input[value="pause_timer"]');
                    const resumeButton = document.querySelector('input[value="resume_timer"]');

                    if (data.global_timer_paused) {
                        if (pauseBadge) {
                            pauseBadge.style.display = 'inline-flex';
                        }
                        if (pauseButton) {
                            pauseButton.disabled = true;
                            pauseButton.textContent = 'Timers Paused';
                            pauseButton.classList.add('ghost');
                        }
                        if (resumeButton) {
                            resumeButton.disabled = false;
                        }
                    } else {
                        if (pauseBadge) {
                            pauseBadge.style.display = 'none';
                        }
                        if (pauseButton) {
                            pauseButton.disabled = false;
                            pauseButton.textContent = 'Pause All Timers';
                            pauseButton.classList.remove('ghost');
                        }
                        if (resumeButton) {
                            resumeButton.disabled = true;
                        }
                    }
                }
            });

            eventSource.addEventListener('global_control', function(event) {
                let data = {};
                try {
                    data = JSON.parse(event.data || '{}');
                } catch (e) {
                    data = {};
                }

                if (data.control_key === 'global_timer_pause') {
                    const pauseBadge = document.querySelector('.timer-controls .control-badge.paused');
                    const pauseButton = document.querySelector('input[value="pause_timer"]');
                    const resumeButton = document.querySelector('input[value="resume_timer"]');

                    if (data.control_value === '1') {
                        if (pauseBadge) {
                            pauseBadge.style.display = 'inline-flex';
                        }
                        if (pauseButton) {
                            pauseButton.disabled = true;
                            pauseButton.textContent = 'Timers Paused';
                            pauseButton.classList.add('ghost');
                        }
                        if (resumeButton) {
                            resumeButton.disabled = false;
                        }
                    } else {
                        if (pauseBadge) {
                            pauseBadge.style.display = 'none';
                        }
                        if (pauseButton) {
                            pauseButton.disabled = false;
                            pauseButton.textContent = 'Pause All Timers';
                            pauseButton.classList.remove('ghost');
                        }
                        if (resumeButton) {
                            resumeButton.disabled = true;
                        }
                    }
                }
            });

            eventSource.onerror = function() {
                eventSource.close();
                setTimeout(connectAdminStream, 3000);
            };
        };

        const monitorTimerInterval = window.setInterval(tickMonitorTimers, 1000);

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', connectAdminStream);
        } else {
            connectAdminStream();
        }
    })();
</script>

<style>
    .mc-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 10000;
        padding: 12px 18px;
        border-radius: 10px;
        color: #fff;
        font-weight: 700;
        font-size: 0.9rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.25);
        opacity: 0;
        transform: translateY(12px);
        transition: opacity 0.24s ease, transform 0.24s ease;
        max-width: 420px;
    }

    .mc-toast.success {
        background: #166534;
        opacity: 1;
        transform: translateY(0);
    }

    .mc-toast.error {
        background: #991b1b;
        opacity: 1;
        transform: translateY(0);
    }

    .control-card button[disabled] {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .monitor-timer-meta {
        display: none;
    }
</style>

<?php loadPartial('end') ?>
