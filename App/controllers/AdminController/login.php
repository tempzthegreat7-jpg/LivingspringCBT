<?php

$lockSeconds = 0;
$lockUntil = (int) (Session::get('admin_login_lock_until') ?? 0);
$maxLoginAttempts = 5;
$attemptsLeft = null;
if ($lockUntil > time()) {
    $lockSeconds = $lockUntil - time();
} else {
    Session::clear('admin_login_lock_until');
    $failedAttempts = max(0, (int) (Session::get('admin_login_failed_attempts') ?? 0));
    $attemptsLeft = $failedAttempts > 0 ? max(0, $maxLoginAttempts - $failedAttempts) : null;
}

loadView('admin/login', [
    'lockSeconds' => $lockSeconds,
    'attemptsLeft' => $attemptsLeft
]);
