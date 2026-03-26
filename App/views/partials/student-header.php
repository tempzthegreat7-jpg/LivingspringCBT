<?php
$studentSession = Session::get('student') ?? [];
$displayName = trim((string) ($studentSession['name'] ?? 'Student'));
$classLabel = strtoupper(trim((string) ($studentSession['class'] ?? 'SS3')));
$nameParts = array_filter(preg_split('/\s+/', $displayName));
$profileInitials = '';
foreach (array_slice($nameParts, 0, 2) as $namePart) {
    $profileInitials .= strtoupper(substr((string) $namePart, 0, 1));
}
if ($profileInitials === '') {
    $profileInitials = 'S';
}
?>
<header>
    <h2>Livingspring CBT</h2>
    <div class="header-actions">
        <div class="profile">
            <div class="header-avatar" aria-hidden="true"><?= htmlspecialchars($profileInitials, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="profile-bio">
                <p class="name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="role">Student | <?= htmlspecialchars($classLabel, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
</header>
