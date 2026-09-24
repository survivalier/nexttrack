<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';

tracker_logout();
header('Location: login.php');
exit;