<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$navLinks = [
    ['href' => '/teacher', 'label' => 'Dashboard', 'hint' => 'Overview', 'icon' => 'fa-th-large'],
    ['href' => '/teacher/add-question', 'label' => 'Set Questions', 'hint' => 'Create Bank', 'icon' => 'fa-plus-square-o'],
    ['href' => '/teacher/check-question', 'label' => 'Check Question', 'hint' => 'Review & Edit', 'icon' => 'fa-search'],
    ['href' => '/teacher/performance', 'label' => 'Performance', 'hint' => 'Exam Records', 'icon' => 'fa-line-chart'],
    ['href' => '/teacher/notify-admin', 'label' => 'Messages', 'hint' => 'Chat with Admin', 'icon' => 'fa-envelope'],
    ['href' => '/teacher/profile', 'label' => 'Profile', 'hint' => 'Account Details', 'icon' => 'fa-user-circle-o'],
];
?>
<aside>
    <div class="sidebar-top">
        <p><a href="/logout" class="sidebar-logout"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a></p>
        <h2>Teacher's Panel</h2>
    </div>

    <nav class="teacher-nav" aria-label="Teacher navigation">
        <?php foreach ($navLinks as $link): ?>
            <?php $isCurrent = $currentPath === $link['href']; ?>
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
