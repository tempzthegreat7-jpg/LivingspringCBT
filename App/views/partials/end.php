<script src="/app.js"></script>
<?php if (Session::has('student')): ?>
    <script>
        (() => {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';
            let redirecting = false;

            const pingStudentSession = () => {
                fetch('/student/session/ping', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrfToken
                    }
                })
                    .then((response) => response.json().catch(() => null).then((payload) => ({
                        ok: response.ok,
                        payload: payload
                    })))
                    .then(({ ok, payload }) => {
                        if (ok || redirecting) {
                            return;
                        }

                        redirecting = true;
                        const message = payload && payload.message
                            ? String(payload.message)
                            : 'Nice try. This account is already in use. Duplicate access is being watched.';

                        if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                            window.AppWarning.alert(message).finally(() => {
                                window.location.href = String((payload && payload.redirect) || '/student/names');
                            });
                            return;
                        }

                        window.location.href = String((payload && payload.redirect) || '/student/names');
                    })
                    .catch(() => {});
            };

            pingStudentSession();
            window.setInterval(pingStudentSession, 30000);
        })();
    </script>
<?php endif; ?>
</body>

</html>
