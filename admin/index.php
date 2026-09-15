<?php

// Gerbang admin: bila cookie token valid langsung ke
// dashboard, sebaliknya proteksi.php melempar ke login.
$root = '../';
require_once __DIR__ . '/partials/proteksi.php';

header('Location: dashboard.php');
exit;
