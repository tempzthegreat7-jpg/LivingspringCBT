<?php loadPartial('student-head') ?>

<div class="transition transition-1 is-active"></div>

<main class="role-page">
    <h1 class="primary-logo">Livingspring <span>Computer-Based Test</span></h1>

    <section class="role-container maintenance-layout">
        <div class="prompt-question maintenance-copy">
            <span class="maintenance-kicker">Update In Progress</span>
            <p>We&apos;ll be back shortly.</p>
            <span><?= htmlspecialchars((string) ($message ?? 'We are updating the platform. Please check back shortly.'), ENT_QUOTES, 'UTF-8') ?></span>
            <small class="maintenance-note">Student and teacher access will reopen automatically once the update is complete.</small>
        </div>

        <div class="prompt-answer maintenance-actions">
            <a href="/admin/login" class="role-card role-card-teacher">
                <strong>Admin Access</strong>
                <small>Continue to admin sign-in</small>
            </a>
        </div>
    </section>
</main>

<?php loadPartial('end') ?>
