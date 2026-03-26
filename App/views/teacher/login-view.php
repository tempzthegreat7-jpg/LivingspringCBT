<?php loadPartial('student-head') ?>

<div class="transition transition-1 is-active"></div>

<div class="teacher-login-container">

    <h2>Login</h2>
    <a href="/roles" class="leave-room-link leave-room-link-icon" aria-label="Back to roles">&leftarrow;</a>
    <?php $lockSeconds = max(0, (int) ($lockSeconds ?? 0)); ?>
    <?php $attemptsLeft = isset($attemptsLeft) ? max(0, (int) $attemptsLeft) : null; ?>

    <?php
    $formErrors = $errors ?? [];
    $loginWarning = '';
    $passwordWarning = '';
    $flashLoginWarning = Session::getFlashMesssge('error_message');

    if (isset($formErrors['name']) && stripos((string) $formErrors['name'], 'incorrect name/password') !== false) {
        $loginWarning = 'Incorrect Name/Password';
        unset($formErrors['name']);
    }
    if (isset($formErrors['password']) && (string) $formErrors['password'] === 'Password must be at least 6 characters') {
        $passwordWarning = (string) $formErrors['password'];
        unset($formErrors['password']);
    }
    ?>

    <?php loadPartial('errors', [
        'errors' => $formErrors
    ]) ?>

    <form action="/teacher/login" method="POST">
        <?= csrfField() ?>
        <label for="name">Name:</label>
        <select name="name" id="name" class="select" required <?= $lockSeconds > 0 ? 'disabled' : '' ?>>
            <option value="">Select Teacher</option>
            <?php foreach (($teachers ?? []) as $teacher): ?>
                <?php
                $teacherName = (string) ($teacher['name'] ?? '');
                $isActive = (int) ($teacher['is_active'] ?? 1) === 1;
                $isSelected = ($selectedName ?? '') === $teacherName;
                ?>
                <option value="<?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?>" <?= $isSelected ? 'selected' : '' ?>>
                    <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?><?= $isActive ? '' : ' (Inactive)' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" class="select" placeholder="Enter Password" <?= $lockSeconds > 0 ? 'disabled' : '' ?> />
        <?php if ($flashLoginWarning): ?>
            <h4 class="helper-note helper-note-warning"><?= htmlspecialchars($flashLoginWarning, ENT_QUOTES, 'UTF-8') ?></h4>
        <?php endif; ?>
        <?php if ($lockSeconds > 0): ?>
            <h4 class="helper-note helper-note-warning" id="loginLockoutMessage">
                Too many failed attempts. Try again in <strong id="loginLockoutCountdown"><?= $lockSeconds ?></strong>s.
            </h4>
        <?php elseif ($attemptsLeft !== null): ?>
            <h4 class="helper-note helper-note-warning">
                Incorrect Name/Password, You have <strong><?= (int) $attemptsLeft ?></strong> chances remaining.
            </h4>
        <?php elseif ($loginWarning !== ''): ?>
            <h4 class="helper-note helper-note-warning"><?= htmlspecialchars($loginWarning, ENT_QUOTES, 'UTF-8') ?></h4>
        <?php elseif ($passwordWarning !== ''): ?>
            <h4 class="helper-note helper-note-warning"><?= htmlspecialchars($passwordWarning, ENT_QUOTES, 'UTF-8') ?></h4>
        <?php endif; ?>
        <h4 class="helper-note">Forgot your password? No worries, It happens, Contact An Administrator for A Reset.</h4>
        <button class="button" id="teacherLoginButton" <?= $lockSeconds > 0 ? 'disabled' : '' ?>>Login</button>
    </form>
</div>

<?php if ($lockSeconds > 0): ?>
    <script>
        (function() {
            const countdownNode = document.getElementById('loginLockoutCountdown');
            const nameSelect = document.getElementById('name');
            const passwordInput = document.getElementById('password');
            const loginButton = document.getElementById('teacherLoginButton');
            let remaining = <?= $lockSeconds ?>;

            if (!countdownNode || !nameSelect || !passwordInput || !loginButton) {
                return;
            }

            const timer = setInterval(function() {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(timer);
                    countdownNode.textContent = '0';
                    nameSelect.disabled = false;
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
