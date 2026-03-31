<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_NAME', 'MoneyFlow');
define('SITE_URL', 'http://localhost/moneyflow/');

require_once 'db.php';
?>