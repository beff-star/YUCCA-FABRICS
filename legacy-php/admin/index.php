<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_user() && (current_user()['role'] ?? '') === 'admin') redirect('admin/dashboard.php');
redirect('auth/login.php');
