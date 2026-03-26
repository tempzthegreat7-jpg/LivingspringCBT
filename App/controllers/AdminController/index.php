<?php

$user = Session::get('user');
$role = strtolower($user['role'] ?? '');

if (!Session::has('user')) {
    redirect('/admin/login');
}

if ($role === 'admin') {
    redirect('/admin/dashboard');
}

redirect('/teacher');

