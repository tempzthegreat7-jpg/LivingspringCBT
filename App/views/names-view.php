<?php
$classOptions = ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];
$namesByClass = is_array($namesByClass ?? null) ? $namesByClass : [];
foreach ($classOptions as $classLabel) {
    if (!isset($namesByClass[$classLabel]) || !is_array($namesByClass[$classLabel])) {
        $namesByClass[$classLabel] = [];
    }
}
?>

<?php loadPartial('student-head') ?>
<div class="transition transition-1 is-active"></div>

<h1 class="primary-logo">Livingspring <span>Computer-Based Test</span></h1>

<div class="student-name-container">
    <a href="/roles" class="leave-room-link leave-room-link-icon" aria-label="Back to roles">&leftarrow;</a>
    <?php if ($message = Session::getFlashMesssge('error_message')): ?>
        <div class="errors"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form action="/student/names" method="POST" id="studentLoginForm">
        <?= csrfField() ?>
        <p>Select Your Class</p>
        <select name="student_class" id="student_class" class="select" required>
            <?php foreach ($classOptions as $classLabel): ?>
                <option value="<?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <p>Select Name</p>
        <select name="student_name" id="student_name" class="select" required></select>
        <input type="hidden" name="student_password" id="student_password" value="" />

        <button class="button" id="studentLoginButton">Confirm</button>
    </form>

</div>

<div class="task-loading-overlay" id="studentPasswordOverlay" aria-hidden="true">
    <div class="task-loading-card" role="dialog" aria-modal="true" aria-labelledby="studentPasswordTitle">
        <p class="task-loading-title" id="studentPasswordTitle">Enter Password</p>
        <p class="task-loading-subtitle">Type your student password to continue.</p>
        <input type="password" class="select" id="studentPasswordEntry" placeholder="Student password" minlength="6" />
        <div class="student-password-actions">
            <button type="button" class="button" id="submitStudentPassword">Continue</button>
            <button type="button" class="button student-password-cancel" id="cancelStudentPassword">Cancel</button>
        </div>
    </div>
</div>

<script>
    const namesByClass = <?= json_encode($namesByClass, JSON_UNESCAPED_SLASHES) ?>;
    const classSelect = document.getElementById('student_class');
    const nameSelect = document.getElementById('student_name');
    const studentLoginForm = document.getElementById('studentLoginForm');
    const studentLoginButton = document.getElementById('studentLoginButton');
    const overlay = document.getElementById('studentPasswordOverlay');
    const hiddenPasswordInput = document.getElementById('student_password');
    const passwordEntry = document.getElementById('studentPasswordEntry');
    const submitPasswordBtn = document.getElementById('submitStudentPassword');
    const cancelPasswordBtn = document.getElementById('cancelStudentPassword');

    const renderNamesForClass = (className) => {
        const names = namesByClass[className] || [];
        nameSelect.innerHTML = '';

        if (names.length === 0) {
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'No active student login for this class';
            nameSelect.appendChild(emptyOption);
            return;
        }

        names.forEach((name) => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            nameSelect.appendChild(option);
        });
    };

    classSelect.addEventListener('change', (event) => {
        renderNamesForClass(event.target.value);
    });

    renderNamesForClass(classSelect.value);

    const openPasswordOverlay = () => {
        if (!overlay || !passwordEntry) {
            return;
        }
        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden', 'false');
        setTimeout(() => {
            passwordEntry.focus();
        }, 0);
    };

    const closePasswordOverlay = () => {
        if (!overlay || !passwordEntry || !hiddenPasswordInput) {
            return;
        }
        overlay.classList.remove('active');
        overlay.setAttribute('aria-hidden', 'true');
        passwordEntry.value = '';
        hiddenPasswordInput.value = '';
    };

    if (studentLoginForm && studentLoginButton && submitPasswordBtn) {
        studentLoginForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const selectedName = String(nameSelect ? nameSelect.value || '' : '');
            if (!selectedName) {
                if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                    window.AppWarning.alert('Select a student name first.');
                }
                return;
            }
            openPasswordOverlay();
        });

        submitPasswordBtn.addEventListener('click', () => {
            if (!hiddenPasswordInput || !passwordEntry) {
                return;
            }

            const enteredPassword = String(passwordEntry.value || '').trim();
            if (enteredPassword.length < 6) {
                if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                    window.AppWarning.alert('Password must be at least 6 characters.');
                }
                return;
            }

            hiddenPasswordInput.value = enteredPassword;
            studentLoginButton.disabled = true;
            submitPasswordBtn.disabled = true;

            fetch(studentLoginForm.getAttribute('action') || '/student/names', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: new FormData(studentLoginForm)
                })
                .then((response) => response.json())
                .then((payload) => {
                    studentLoginButton.disabled = false;
                    submitPasswordBtn.disabled = false;

                    if (!payload || payload.ok !== true) {
                        const message = payload && payload.message ? String(payload.message) : 'Unable to log in right now.';
                        if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                            window.AppWarning.alert(message);
                        }
                        return;
                    }

                    window.location.href = String(payload.redirect || '/student/question-set');
                })
                .catch(() => {
                    studentLoginButton.disabled = false;
                    submitPasswordBtn.disabled = false;
                    studentLoginForm.submit();
                });
        });
    }

    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', () => {
            closePasswordOverlay();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closePasswordOverlay();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && overlay && overlay.classList.contains('active')) {
            closePasswordOverlay();
        }
    });
</script>
<?php loadPartial('end') ?>
