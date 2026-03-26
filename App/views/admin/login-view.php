<?php loadPartial('admin-head') ?>
<main class="admin-login-page">
    <section class="admin-login-card">
        <?php $lockSeconds = max(0, (int) ($lockSeconds ?? 0)); ?>
        <?php $attemptsLeft = isset($attemptsLeft) ? max(0, (int) $attemptsLeft) : null; ?>
        <p class="panel-tag">Protected Route</p>
        <h1>Admin Login</h1>
        <p class="subtitle">Only users with the <strong>admin</strong> role can access this panel.</p>

        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($lockSeconds > 0): ?>
            <div class="errors" id="adminLockoutMessage">
                Too many failed attempts. Try again in <strong id="adminLockoutCountdown"><?= $lockSeconds ?></strong>s.
            </div>
        <?php endif; ?>
        <?php if ($lockSeconds <= 0 && $attemptsLeft !== null): ?>
            <div class="errors">
                Attempts left before lockout: <strong><?= $attemptsLeft ?></strong>
            </div>
        <?php endif; ?>

        <?php loadPartial('errors', [
            'errors' => $errors ?? []
        ]) ?>

        <form action="/admin/login" method="POST">
            <?= csrfField() ?>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" class="select" placeholder="Enter password" <?= $lockSeconds > 0 ? 'disabled' : '' ?> />

            <button class="button" id="adminLoginButton" type="submit" <?= $lockSeconds > 0 ? 'disabled' : '' ?>>Login to Admin</button>
        </form>
    </section>
</main>
<?php if ($lockSeconds > 0): ?>
    <script>
        (function() {
            const countdownNode = document.getElementById('adminLockoutCountdown');
            const passwordInput = document.getElementById('password');
            const loginButton = document.getElementById('adminLoginButton');
            let remaining = <?= $lockSeconds ?>;

            if (!countdownNode || !passwordInput || !loginButton) {
                return;
            }

            const timer = setInterval(function() {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(timer);
                    countdownNode.textContent = '0';
                    passwordInput.disabled = false;
                    loginButton.disabled = false;
                    return;
                }

                countdownNode.textContent = String(remaining);
            }, 1000);
        })();
    </script>
<?php endif; ?>
<?php loadPartial('end') ?>
