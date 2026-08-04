<?php loadPartial('question-head') ?>

<div class="heading">
    <a class="leave-room-link" href="/student/question-set">&leftarrow; Back to Subjects</a>
    <div class="info">
        <p>Subject: <span class="subject"><?= ucfirst($subject) ?></span></p>
    </div>
</div>

<div class="questions-container">
    <?php if (!empty($timed_out ?? false)) : ?>
        <p class="timeout-notice">Time is up. Your exam was submitted automatically.</p>
    <?php endif; ?>
    <p class="question">Test Completed</p>
    <p class="question">Score: <?= $score ?> / <?= $total ?></p>
    <div class="buttons">
        <a class="button" href="/student/correction?index=0" aria-disabled="true">View Correction</a>
        <a class="button button-primary" href="/student/question-set">Choose Another Subject</a>
    </div>
</div>

<?php loadPartial('end') ?>