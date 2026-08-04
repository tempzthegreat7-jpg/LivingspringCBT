<?php

loadPartial('student-head')
?>
<div class="transition transition-1 is-active"></div>
<div class="welcome-container welcome-layout">
    <div class="welcome-main">
        <h1 class="primary-logo">
            Livingspring <span>Computer-Based Test</span>
        </h1>
        <p class="welcome-subtext">Our First Ever Custom-made Digitalized Examination Platform, for Both Students and Teachers.</p>
        <div class="welcome-actions">
            <a href="/roles" class="button welcome-cta">Continue</a>
            <button type="button" class="welcome-ghost-btn" id="openCreditsOverlay">Project Credits</button>
        </div>
        <details class="welcome-release-note" open>
            <summary>What&apos;s New in v2.6.0</summary>
            <ul>
                <li>Better exam flow with review, flagging, autosave, and safer submission.</li>
                <li>Smarter calculator with scientific mode, memory, history, and keyboard input.</li>
                <li>New student login tracking for both admins and students.</li>
                <li>New admin exam insights by subject and class, including scores and time taken.</li>
                <li>Fixed the repeated no-active-exams popup across the student panel.</li>
            </ul>
        </details>
    </div>
</div>

<div class="credits-overlay" id="creditsOverlay" aria-hidden="true">
    <div class="credits-overlay-backdrop" id="creditsOverlayBackdrop"></div>
    <div class="welcome-credits credits-overlay-card" role="dialog" aria-modal="true" aria-labelledby="creditsTitle">
        <button type="button" class="credits-close-btn" id="closeCreditsOverlay" aria-label="Close project credits">&times;</button>
        <p class="credit-title" id="creditsTitle">Project Credits</p>
        <div class="credit-hero">
            <p class="credit-team-name">Livingspring Tech Team</p>
            <p class="credit-team-tagline">Design. Build. Deliver.</p>
        </div>
        <div class="credit-grid">
            <div class="credit-item">
                <p class="credit-role">Application Concept & Interface Structure</p>
                <p class="credit-member">Mr. Afelumo Adura <span>(Admin)</span></p>
            </div>
            <div class="credit-item">
                <p class="credit-role">Interface Design & Application Development</p>
                <p class="credit-member">Somachi Odunze <span>(SS1)</span></p>
            </div>
        </div>
        <p class="credit-version-label">Application Version</p>
        <p class="credit-version">Version 2.6.0</p>
    </div>
</div>

<script>
    (() => {
        const overlay = document.getElementById('creditsOverlay');
        const openBtn = document.getElementById('openCreditsOverlay');
        const closeBtn = document.getElementById('closeCreditsOverlay');
        const backdrop = document.getElementById('creditsOverlayBackdrop');

        if (!overlay || !openBtn || !closeBtn || !backdrop) {
            return;
        }

        const openOverlay = () => {
            overlay.classList.add('open');
            overlay.setAttribute('aria-hidden', 'false');
        };

        const closeOverlay = () => {
            overlay.classList.remove('open');
            overlay.setAttribute('aria-hidden', 'true');
        };

        openBtn.addEventListener('click', openOverlay);
        closeBtn.addEventListener('click', closeOverlay);
        backdrop.addEventListener('click', closeOverlay);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && overlay.classList.contains('open')) {
                closeOverlay();
            }
        });
    })();
</script>

<?php loadPartial('end') ?>
