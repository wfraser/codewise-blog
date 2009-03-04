<?php

header("Content-Type: text/plain");

echo "\$_SERVER: ";
var_dump($_SERVER);

echo "\$_GET: ";
var_dump($_GET);

echo "\$_POST: ";
var_dump($_POST);

echo "\$_FILE: ";
var_dump($_FILE);

echo "\$_SESSION: ";
var_dump($_SESSION);

echo "\$_ENV: ";
var_dump($_ENV);

?>
