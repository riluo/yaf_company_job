<?php
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../'));
define('CONFIG_PATH', APPLICATION_PATH . '/conf');
//require APPLICATION_PATH . '/vendor/autoload.php';

$yaf = new \Yaf\Application(CONFIG_PATH . '/app.ini');
$yaf->bootstrap();

$app = new Symfony\Component\Console\Application();
//$app->add(new \ZuoYeah\Command\Spider\Sxs101DownCommand());
//$app->add(new \ZuoYeah\Command\Spider\Sxs101UnzipCommand());
//$app->add(new \ZuoYeah\Command\Spider\Ssx101BookCommand());
$app->add(new \ZuoYeah\Command\Spider\KnowboxCatalogCommand());
$app->add(new \ZuoYeah\Command\Spider\KnowboxBookCommand());
$app->add(new \ZuoYeah\Command\Spider\KnowboxPaperCommand());
$app->add(new \ZuoYeah\Command\Spider\RuyileBureauCommand());
$app->run();