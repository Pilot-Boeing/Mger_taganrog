<?php
require_once __DIR__ . '/includes/bootstrap.php';
session_start_secure();
$_SESSION = [];
session_destroy();
redirect('login.php');
