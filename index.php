<?php
require_once __DIR__.'/includes/bootstrap.php';
header('Location: '.(is_logged_in()?'dashboard/index.php':'auth/login.php')); exit;
