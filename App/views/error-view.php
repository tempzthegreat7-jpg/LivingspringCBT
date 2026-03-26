<?php loadPartial('student-head', ['extraCss' => '/css/error.css']) ?>

<main class="error-page">
    <section class="error-card">
        <p class="error-code">Oops....<?= $code ?? 404 ?></p>
        <h1 class="error-title"><?= $title ?? 'Something went wrong' ?></h1>
        <p class="error-message">
            <?= $message ?? 'An unexpected error occurred. Please try again.' ?>
        </p>
        <div class="error-actions">
            <a href="/" class="error-link">Go to Home</a>
            <a href="/roles" class="error-link">Choose Role</a>
        </div>
    </section>
</main>

<?php loadPartial('end') ?>
