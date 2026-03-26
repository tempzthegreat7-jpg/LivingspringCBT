<?php loadPartial('student-head') ?>

<div class="transition transition-1 is-active"></div>

<main class="role-page">
    <h1 class="primary-logo">Livingspring <span>Computer-Based Test</span></h1>

    <section class="role-container">
        <div class="prompt-question">
            <p>Select Your Role</p>
            <span>Continue to the right portal and access the tools designed for your workflow.</span>
        </div>

        <div class="prompt-answer">
            <a href="/student/names" class="role-card role-card-student">
                <strong>Student</strong>
                <small>Start test session</small>
            </a>
            <a href="/teacher/subject" class="role-card role-card-teacher">
                <strong>Teacher</strong>
                <small>Manage question bank</small>
            </a>
        </div>
    </section>
</main>

<?php loadPartial('end') ?>