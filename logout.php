<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

logoutClient();
header('Location: /login.php');
exit;
