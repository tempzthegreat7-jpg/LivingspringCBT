(function () {
    'use strict';

    if (window.ControlStreamInstance) {
        return;
    }

    const csrfMeta = document.querySelector('meta[name="csrf-token"]");
    const csrfToken = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';

    let role = 'guest';
    let identifier = '';

    if (window.__cbtControlRole && window.__cbtControlIdentifier) {
        role = String(window.__cbtControlRole || 'guest');
        identifier = String(window.__cbtControlIdentifier || '');
    } else if (csrfToken) {
        const sessionStudent = document.body.dataset.studentSession || '';
        const sessionTeacher = document.body.dataset.teacherSession || '';

        if (sessionTeacher !== '') {
            role = 'teacher';
            identifier = sessionTeacher;
        } else if (sessionStudent !== '') {
            role = 'student';
            identifier = sessionStudent;
        }
    }

    if (role === 'guest' || identifier === '') {
        return;
    }

    let reconnectDelay = 1000;
    const maxReconnectDelay = 30000;
    let eventSource = null;
    let isHandlingForceSubmit = false;

    const buildUrl = function () {
        const url = new URL('/admin/stream', window.location.origin);
        url.searchParams.set('role', role);
        url.searchParams.set('session_identifier', identifier);
        return url.toString();
    };

    const acknowledge = function (commandId) {
        if (!commandId || commandId <= 0) {
            return;
        }

        fetch('/admin/master-controls/presence?action=ack', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'command_id=' + encodeURIComponent(commandId)
        }).catch(function () {});
    };

    const showAdminOverrideOverlay = function (message) {
        const overlayId = 'cbt-admin-override';
        let overlay = document.getElementById(overlayId);
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = overlayId;
            overlay.className = 'cbt-admin-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            overlay.innerHTML = `
                <div class="cbt-admin-overlay-backdrop"></div>
                <div class="cbt-admin-overlay-card" role="dialog" aria-modal="true">
                    <div class="cbt-admin-overlay-head">
                        <span class="cbt-admin-overlay-icon" aria-hidden="true">&#9888;</span>
                        <h2 class="cbt-admin-overlay-title">Administrator Notice</h2>
                    </div>
                    <p class="cbt-admin-overlay-message"></p>
                    <div class="cbt-admin-overlay-actions">
                        <span class="cbt-admin-overlay-countdown"></span>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        const messageEl = overlay.querySelector('.cbt-admin-overlay-message');
        const countdownEl = overlay.querySelector('.cbt-admin-overlay-countdown');
        if (messageEl) {
            messageEl.textContent = String(message || 'An administrator has modified this session.');
        }

        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden', 'false');

        let remaining = 5;
        if (countdownEl) {
            countdownEl.textContent = 'Redirecting in ' + remaining + 's...';
        }

        const timer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(timer);
                if (countdownEl) {
                    countdownEl.textContent = 'Redirecting...';
                }
                return;
            }
            if (countdownEl) {
                countdownEl.textContent = 'Redirecting in ' + remaining + 's...';
            }
        }, 1000);

        overlay._adminTimer = timer;
        overlay._adminAction = null;
    };

    const closeAdminOverrideOverlay = function () {
        const overlay = document.getElementById('cbt-admin-overlay');
        if (!overlay) {
            return;
        }

        if (overlay._adminTimer) {
            clearInterval(overlay._adminTimer);
        }

        overlay.classList.remove('active');
        overlay.setAttribute('aria-hidden', 'true');

        if (typeof overlay._adminAction === 'function') {
            overlay._adminAction();
            overlay._adminAction = null;
        }
    };

    const handleForceSubmit = function () {
        if (isHandlingForceSubmit) {
            return;
        }
        isHandlingForceSubmit = true;

        showAdminOverrideOverlay('Your exam has been force-submitted by the administrator. Your answers have been saved.');

        const overlay = document.getElementById('cbt-admin-overlay');
        if (overlay) {
            overlay._adminAction = function () {
                const form = document.getElementById('examForm');
                const timeUpField = document.getElementById('timeUpField');

                if (form && timeUpField) {
                    timeUpField.value = '1';
                    form.submit();
                } else if (form) {
                    form.submit();
                } else {
                    window.location.reload();
                }
            };
        }
    };

    const handleTerminateSession = function () {
        showAdminOverrideOverlay('Your session has been terminated by the administrator. You will be redirected to login.');

        const overlay = document.getElementById('cbt-admin-overlay');
        if (overlay) {
            overlay._adminAction = function () {
                window.location.href = '/student/names';
            };
        }
    };

    const handlePauseTimer = function () {
        const timerEl = document.getElementById('examTimer');
        if (timerEl) {
            timerEl.classList.add('admin-paused');
            timerEl.dataset.adminPaused = '1';
        }

        if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
            window.AppWarning.alert('The administrator has paused your exam timer.', { title: 'Timer Paused', variant: 'warning' });
        }
    };

    const handleResumeTimer = function () {
        const timerEl = document.getElementById('examTimer');
        if (timerEl) {
            timerEl.classList.remove('admin-paused');
            delete timerEl.dataset.adminPaused;
        }

        if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
            window.AppWarning.alert('The administrator has resumed your exam timer.', { title: 'Timer Resumed', variant: 'success' });
        }
    };

    const handleAddTime = function (payload) {
        const seconds = Math.max(0, parseInt((payload && payload.seconds ? payload.seconds : 0), 10) || 0);
        if (seconds <= 0) {
            return;
        }

        const currentBonus = Math.max(0, parseInt((window.__cbtTimeBonusSeconds || 0), 10) || 0);
        window.__cbtTimeBonusSeconds = currentBonus + seconds;

        const timerEl = document.getElementById('examTimer');
        if (timerEl) {
            const currentEndTime = parseInt(timerEl.dataset.endTime || '0', 10);
            const newEndTime = currentEndTime + seconds;
            timerEl.dataset.endTime = String(newEndTime);

            const bonusEl = timerEl.querySelector('.timer-bonus');
            if (bonusEl) {
                const existingBonus = parseInt(bonusEl.dataset.bonus || '0', 10);
                bonusEl.dataset.bonus = String(existingBonus + seconds);
                bonusEl.textContent = '+' + formatTimeShort(existingBonus + seconds);
                bonusEl.style.display = 'inline';
            } else {
                const bonusSpan = document.createElement('span');
                bonusSpan.className = 'timer-bonus';
                bonusSpan.dataset.bonus = String(seconds);
                bonusSpan.textContent = '+' + formatTimeShort(seconds);
                bonusSpan.style.display = 'inline';
                bonusSpan.style.marginLeft = '8px';
                bonusSpan.style.fontSize = '0.8rem';
                bonusSpan.style.fontWeight = '700';
                bonusSpan.style.color = '#166534';
                bonusSpan.style.background = '#dcfce7';
                bonusSpan.style.padding = '2px 8px';
                bonusSpan.style.borderRadius = '999px';
                timerEl.appendChild(bonusSpan);
            }
        }

        const endsAtOverride = document.getElementById('endsAtOverrideField');
        if (endsAtOverride) {
            const currentOverride = parseInt(endsAtOverride.value || '0', 10);
            endsAtOverride.value = String(currentOverride + seconds);
        }

        if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
            window.AppWarning.alert('The administrator added ' + formatTimeShort(seconds) + ' to your exam timer.', { title: 'Time Added', variant: 'success' });
        }
    };

    const formatTimeShort = function (seconds) {
        const safe = Math.max(0, seconds);
        const minutes = Math.floor(safe / 60);
        const secs = safe % 60;

        if (minutes > 0 && secs > 0) {
            return minutes + 'm ' + secs + 's';
        }

        if (minutes > 0) {
            return minutes + ' minute' + (minutes === 1 ? '' : 's');
        }

        return secs + ' second' + (secs === 1 ? '' : 's');
    };

    const connect = function () {
        if (eventSource) {
            eventSource.close();
        }

        const url = buildUrl();
        eventSource = new EventSource(url);

        eventSource.onopen = function () {
            reconnectDelay = 1000;
        };

        eventSource.addEventListener('force_submit', function (event) {
            const data = {};
            try {
                data = JSON.parse(event.data || '{}');
            } catch (e) {
                data = {};
            }
            acknowledge(data.command_id);
            handleForceSubmit();
        });

        eventSource.addEventListener('terminate_session', function (event) {
            const data = {};
            try {
                data = JSON.parse(event.data || '{}');
            } catch (e) {
                data = {};
            }
            acknowledge(data.command_id);
            handleTerminateSession();
        });

        eventSource.addEventListener('pause_timer', function (event) {
            const data = {};
            try {
                data = JSON.parse(event.data || '{}');
            } catch (e) {
                data = {};
            }
            acknowledge(data.command_id || 0);
            handlePauseTimer();
        });

        eventSource.addEventListener('resume_timer', function (event) {
            const data = {};
            try {
                data = JSON.parse(event.data || '{}');
            } catch (e) {
                data = {};
            }
            acknowledge(data.command_id || 0);
            handleResumeTimer();
        });

        eventSource.addEventListener('add_time', function (event) {
            const data = {};
            try {
                data = JSON.parse(event.data || '{}');
            } catch (e) {
                data = {};
            }
            acknowledge(data.command_id);
            handleAddTime(data.payload || {});
        });

        eventSource.addEventListener('global_control', function (event) {
            const data = {};
            try {
                data = JSON.parse(event.data || '{}');
            } catch (e) {
                data = {};
            }

            if (data.control_key === 'global_timer_pause') {
                if (data.control_value === '1') {
                    handlePauseTimer();
                } else {
                    handleResumeTimer();
                }
            }
        });

        eventSource.onerror = function () {
            eventSource.close();
            setTimeout(function () {
                connect();
            }, reconnectDelay);
            reconnectDelay = Math.min(reconnectDelay * 2, maxReconnectDelay);
        };
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', connect);
    } else {
        connect();
    }

    window.ControlStreamInstance = {
        connect: connect,
        close: function () {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
        }
    };
})();
