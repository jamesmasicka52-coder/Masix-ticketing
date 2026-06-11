<?php
require_once __DIR__ . '/auth.php';
echo "authenticate_user result:\n";
$res = authenticate_user('admin', 'admin123');
var_export($res);
echo "\n";
