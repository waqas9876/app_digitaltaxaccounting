<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
if (isAdminLoggedIn()) { header('Location: /admin/dashboard.php'); } else { header('Location: /admin/login.php'); }
exit;
