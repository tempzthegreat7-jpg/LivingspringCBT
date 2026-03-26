<?php
$adminUserName = trim((string) (Session::get('user')['name'] ?? 'Administrator'));
?>
<header class="admin-header">
    <h2>Administration Panel</h2>
    <div class="admin-header-actions">
        <p class="admin-label">Administrator</p>
    </div>
</header>