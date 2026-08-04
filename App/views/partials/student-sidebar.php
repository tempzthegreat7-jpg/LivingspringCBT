<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$studentNavLinks = [
    ['href' => '/student/dashboard', 'label' => 'Dashboard', 'hint' => 'Overview', 'icon' => 'fa-th-large'],
    ['href' => '/student/question-set', 'label' => 'Start Task', 'hint' => 'Pick Subject', 'icon' => 'fa-play-circle-o'],
    ['href' => '/student/results', 'label' => 'Results', 'hint' => 'By Date', 'icon' => 'fa-line-chart'],
    ['href' => '/student/login-log', 'label' => 'Login Log', 'hint' => 'Protected View', 'icon' => 'fa-lock'],
    ['href' => '/student/resume', 'label' => 'Resume', 'hint' => 'Continue Task', 'icon' => 'fa-history'],
    ['href' => '/student/corrections', 'label' => 'Correction', 'hint' => 'Failed/Incomplete', 'icon' => 'fa-check-square-o'],
    ['href' => '/student/feedback', 'label' => 'Feedback', 'hint' => 'Share Thoughts', 'icon' => 'fa-commenting-o'],
];
?>
<aside>
    <div class="sidebar-top">
        <p><a href="/logout" class="sidebar-logout"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a></p>
        <h2>Student Panel</h2>
    </div>

    <nav class="teacher-nav" aria-label="Student navigation">
        <?php foreach ($studentNavLinks as $link): ?>
            <?php
            $targetPath = parse_url($link['href'], PHP_URL_PATH) ?: $link['href'];
            $isCurrent = $currentPath === $targetPath;
            ?>
            <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>" class="teacher-nav-link <?= $isCurrent ? 'current' : '' ?>">
                <span class="teacher-nav-icon"><i class="fa <?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                <span class="teacher-nav-copy">
                    <span class="teacher-nav-text"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="teacher-nav-hint"><?= htmlspecialchars($link['hint'], ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
