<?php
require 'config.php';
$_SESSION = [];
session_destroy();
setcookie('pg_token', '', ['expires' => time() - 3600, 'path' => '/']);
header('Location: login.php');
