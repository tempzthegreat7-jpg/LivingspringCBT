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
            <button class="admin-nav-group-toggle" type="button" aria-label="System menu">
                <span class="admin-nav-group-icon"><i class="fa fa-bars" aria-hidden="true"></i></span>
                <span class="admin-nav-group-title">System</span>
            </button>
            <div class="admin-nav-group-links" aria-label="System submenu">
                <?php
                $link = $adminNavLinks[6];
                $isCurrent = $currentPath === '/admin/audit';
                ?>
                <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link <?= $isCurrent ? 'current' : '' ?>">
                    <span class="admin-nav-icon"><i class="fa <?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
                    <span class="admin-nav-copy">
                        <span class="admin-nav-text"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="admin-nav-hint"><?= htmlspecialchars($link['hint'], ENT_QUOTES, 'UTF-8') ?></span>
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
