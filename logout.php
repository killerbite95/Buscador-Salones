<?php
require_once 'auth.php';
require_once 'security.php';
setSecurityHeaders();
doLogout();
header('Location: login.php');
exit;
