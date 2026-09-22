<?php
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$currentPath = parse_url($requestUri, PHP_URL_PATH) ?: '';
$currentQuery = parse_url($requestUri, PHP_URL_QUERY) ?: '';
parse_str($currentQuery, $queryParams);
$activeTab = $queryParams['tab'] ?? 'create';
$adminNavLinks = [
    ['href' => '/admin/dashboard', 'label' => 'Dashboard', 'hint' => 'System Overview', 'icon' => 'fa-tachometer'],
    ['href' => '/admin/teachers?tab=create', 'label' => 'Create User', 'hint' => 'Add Accounts', 'icon' => 'fa-user-plus'],
    ['href' => '/admin/teachers?tab=manage', 'label' => 'Manage Users', 'hint' => 'Edit & Remove', 'icon' => 'fa-users'],
    ['href' => '/admin/students?tab=manage', 'label' => 'Students', 'hint' => 'Manage Login', 'icon' => 'fa-id-badge'],
    ['href' => '/admin/notifications', 'label' => 'Notifications', 'hint' => 'Broadcast Updates', 'icon' => 'fa-bell'],
    ['href' => '/admin/teacher-messages', 'label' => 'Teacher Messages', 'hint' => 'Direct Replies', 'icon' => 'fa-comments'],
    ['href' => '/admin/audit', 'label' => 'Audit Log', 'hint' => 'Track Changes', 'icon' => 'fa-shield'],
    ['href' => '/admin/student-logins', 'label' => 'Student Logins', 'hint' => 'Track Access', 'icon' => 'fa-sign-in'],
    ['href' => '/admin/exam-insights', 'label' => 'Exam Insights', 'hint' => 'Timeline & Trends', 'icon' => 'fa-area-chart'],
];
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-top">
        <p class="panel-tag">Admin Zone</p>
        <h2>Hi, Livingspring</h2>
    </div>

    <nav class="admin-nav admin-nav-hover" aria-label="Admin navigation">
        <div class="admin-nav-group">
            <button class="admin-nav-group-toggle" type="button" aria-label="Overview menu">
                <span class="admin-nav-group-icon"><i class="fa fa-bars" aria-hidden="true"></i></span>
                <span class="admin-nav-group-title">Overview</span>
            </button>
            <div class="admin-nav-group-links" aria-label="Overview submenu">
                <?php
                $link = $adminNavLinks[0];
                $isCurrent = $currentPath === '/admin/dashboard';
                ?>
                <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                    <span class="admin-nav-icon"><i class="fa <?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                    <span class="admin-nav-copy">
                        <span class="admin-nav-text"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="admin-nav-hint"><?= htmlspecialchars($link['hint'], ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </a>
                <?php $isCurrent = $currentPath === '/admin/master-controls'; ?>
                <a href="/admin/master-controls" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                    <span class="admin-nav-icon"><i class="fa fa-sliders" aria-hidden="true"></i></span>
                    <span class="admin-nav-copy">
                        <span class="admin-nav-text">Master Controls</span>
                        <span class="admin-nav-hint">Session Overrides</span>
                    </span>
                </a>
            </div>
        </div>

        <div class="admin-nav-group">
            <button class="admin-nav-group-toggle" type="button" aria-label="Users menu">
                <span class="admin-nav-group-icon"><i class="fa fa-bars" aria-hidden="true"></i></span>
                <span class="admin-nav-group-title">Users</span>
            </button>
            <div class="admin-nav-group-links" aria-label="Users submenu">
                <?php foreach ([$adminNavLinks[1], $adminNavLinks[2], $adminNavLinks[3]] as $link): ?>
                    <?php
                    $isCurrent = false;
                    if (strpos($link['href'], '/admin/teachers?tab=create') !== false) {
                        $isCurrent = $currentPath === '/admin/teachers' && $activeTab === 'create';
                    } elseif (strpos($link['href'], '/admin/teachers?tab=manage') !== false) {
                        $isCurrent = $currentPath === '/admin/teachers' && $activeTab === 'manage';
                    } elseif (strpos($link['href'], '/admin/students?tab=manage') !== false) {
                        $isCurrent = $currentPath === '/admin/students';
                    }
                    ?>
                    <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                        <span class="admin-nav-icon"><i class="fa <?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                        <span class="admin-nav-copy">
                            <span class="admin-nav-text"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="admin-nav-hint"><?= htmlspecialchars($link['hint'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-nav-group">
            <button class="admin-nav-group-toggle" type="button" aria-label="Communication menu">
                <span class="admin-nav-group-icon"><i class="fa fa-bars" aria-hidden="true"></i></span>
                <span class="admin-nav-group-title">Communication</span>
            </button>
            <div class="admin-nav-group-links" aria-label="Communication submenu">
                <?php foreach ([$adminNavLinks[4], $adminNavLinks[5]] as $link): ?>
                    <?php
                    $isCurrent = false;
                    if ($link['href'] === '/admin/notifications') {
                        $isCurrent = $currentPath === '/admin/notifications';
                    } elseif ($link['href'] === '/admin/teacher-messages') {
                        $isCurrent = $currentPath === '/admin/teacher-messages';
                    }
                    ?>
                    <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                        <span class="admin-nav-icon"><i class="fa <?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                        <span class="admin-nav-copy">
                            <span class="admin-nav-text"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="admin-nav-hint"><?= htmlspecialchars($link['hint'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-nav-group">
            <button class="admin-nav-group-toggle" type="button" aria-label="Feedback menu">
                <span class="admin-nav-group-icon"><i class="fa fa-bars" aria-hidden="true"></i></span>
                <span class="admin-nav-group-title">Feedback</span>
            </button>
            <div class="admin-nav-group-links" aria-label="Feedback submenu">
                <?php
                $feedbackPaths = ['/admin/feedback', '/admin/feedback/ratings', '/admin/feedback/list', '/admin/feedback/analytics'];
                $isCurrent = in_array($currentPath, $feedbackPaths, true);
                ?>
                <a href="/admin/feedback/ratings" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                    <span class="admin-nav-icon"><i class="fa fa-star" aria-hidden="true"></i></span>
                    <span class="admin-nav-copy">
                        <span class="admin-nav-text">Ratings</span>
                        <span class="admin-nav-hint">Teacher rating overview</span>
                    </span>
                </a>
                <?php $isCurrent = $currentPath === '/admin/feedback/list'; ?>
                <a href="/admin/feedback/list" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                    <span class="admin-nav-icon"><i class="fa fa-commenting-o" aria-hidden="true"></i></span>
                    <span class="admin-nav-copy">
                        <span class="admin-nav-text">Feedback</span>
                        <span class="admin-nav-hint">Anonymous student notes</span>
                    </span>
                </a>
            </div>
        </div>

        <div class="admin-nav-group">
            <button class="admin-nav-group-toggle" type="button" aria-label="System menu">
                <span class="admin-nav-group-icon"><i class="fa fa-bars" aria-hidden="true"></i></span>
                <span class="admin-nav-group-title">System</span>
            </button>
            <div class="admin-nav-group-links" aria-label="System submenu">
                <?php
                foreach ([$adminNavLinks[6], $adminNavLinks[7], $adminNavLinks[8]] as $link):
                    $isCurrent = false;
                    if ($link['href'] === '/admin/audit') {
                        $isCurrent = $currentPath === '/admin/audit';
                    } elseif ($link['href'] === '/admin/student-logins') {
                        $isCurrent = $currentPath === '/admin/student-logins';
                    } elseif ($link['href'] === '/admin/exam-insights') {
                        $isCurrent = $currentPath === '/admin/exam-insights';
                    }
                ?>
                    <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                        <span class="admin-nav-icon"><i class="fa <?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                        <span class="admin-nav-copy">
                            <span class="admin-nav-text"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="admin-nav-hint"><?= htmlspecialchars($link['hint'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
                <?php $isCurrent = $currentPath === '/admin/future-plans'; ?>
                <a href="/admin/future-plans" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                    <span class="admin-nav-icon"><i class="fa fa-lightbulb-o" aria-hidden="true"></i></span>
                    <span class="admin-nav-copy">
                        <span class="admin-nav-text">Future Plans</span>
                        <span class="admin-nav-hint">Roadmap Notes</span>
                    </span>
                </a>
            </div>
        </div>

        <div class="admin-nav-group">
            <button class="admin-nav-group-toggle" type="button" aria-label="Exams menu">
                <span class="admin-nav-group-icon"><i class="fa fa-bars" aria-hidden="true"></i></span>
                <span class="admin-nav-group-title">Exams</span>
            </button>
            <div class="admin-nav-group-links" aria-label="Exams submenu">
                <?php $isCurrent = $currentPath === '/admin/exams'; ?>
                <a href="/admin/exams" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                    <span class="admin-nav-icon"><i class="fa fa-book" aria-hidden="true"></i></span>
                    <span class="admin-nav-copy">
                        <span class="admin-nav-text">Activate Exam</span>
                        <span class="admin-nav-hint">Set Live Subject</span>
                    </span>
                </a>
                <?php $isCurrent = $currentPath === '/admin/exams/banks'; ?>
                <a href="/admin/exams/banks" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                    <span class="admin-nav-icon"><i class="fa fa-list" aria-hidden="true"></i></span>
                    <span class="admin-nav-copy">
                        <span class="admin-nav-text">Exam Banks</span>
                        <span class="admin-nav-hint">View Question Banks</span>
                    </span>
                </a>
            </div>
        </div>
    </nav>

    <p><a class="logout-link" href="/logout"><i class="fa fa-sign-out" aria-hidden="true"></i> Log Out</a></p>
</aside>