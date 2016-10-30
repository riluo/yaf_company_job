<?php
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../'));
define('CONFIG_PATH', APPLICATION_PATH . '/conf');

$app = new \Yaf\Application(CONFIG_PATH . '/app.ini');
$app->bootstrap()->run();
