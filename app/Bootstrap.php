<?php
use Yaf\Loader;
use Yaf\Session;
use Yaf\Registry;
use Yaf\Dispatcher;
use Yaf\Application;
use Yaf\Bootstrap_Abstract;

use Gram\Domain\Repository\Dbal\ConnectionFactory;

use Gram\Yaf\Plugin\RoutePlugin;
use Gram\Yaf\Plugin\AutoParamPlugin;
use Gram\Yaf\Plugin\AuthorizationPlugin;

use Gram\Logger\MonologManager;
use Monolog\Handler\StreamHandler;

use Gram\Security\Authentication;

use ZuoYeah\Yaf\Plugin\AntiCrawlPlugin;

/**
 * Class Bootstrap
 */
class Bootstrap extends Bootstrap_Abstract
{
    /**
     * 初始化composer autoload
     */
    function _initComposerAutoload()
    {
        $autoload = APPLICATION_PATH . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            Loader::import($autoload);
        }
    }

    /**
     * 初始化Session
     */
    function _initSession()
    {
        Session::getInstance()->start();
    }

    /**
     * 初化配置，并注册到Registry
     */
    function _initConfig()
    {
        $config = Application::app()->getConfig();
        Registry::set('config', $config);
    }


    /**
     * 初始化数据库连接
     *
     * @param Dispatcher $dispatcher
     */
    function _initDbal(Dispatcher $dispatcher)
    {
        $config = Registry::get('config');
        if (!empty($config->mysql)) {
            $params = array_merge(
                $config->mysql->toArray(),
                [
                    'driver' => 'pdo_mysql',
                    'wrapperClass' => '\Gram\DBAL\Connection',
                    'charset' => 'utf8',
                    'driverOptions' => array(1002=>'SET NAMES utf8'),
                    'driverClass' => '\Gram\DBAL\Driver\MySqlDriver'
                ]
            );
            ConnectionFactory::register($params);
        }
    }


    /**
     * 初始化日志模块
     *
     * @param Dispatcher $dispatcher
     */
    function _initLogger(Dispatcher $dispatcher)
    {
        $config = Registry::get('config');
        if (!empty($config->log)) {
            $path = $config->log->path . date('Y-m-d') . '.log';
            $handler = new StreamHandler($path, intval($config->log->level), true, 0777);
            MonologManager::appendHandler($handler);
        }
    }

    /**
     * 初始化cookie加密算法
     *
     * @param Dispatcher $dispatcher
     */
    function _initCookieSecret(Dispatcher $dispatcher)
    {
        $config = Registry::get('config');
        if (!empty($config->security->secret)) {
            Authentication::initSecret($config->security->secret);
        }

    }


    /**
     * 注册插件
     *
     * @param Dispatcher $dispatcher
     */
    function _initPlugins(Dispatcher $dispatcher)
    {
        //初始化参数自动填充插件
        $dispatcher->registerPlugin(new AutoParamPlugin());

        $config = Registry::get('config');

        //初始化请求频率检测插件
        //$path = CONFIG_PATH . '/crawl.ini';
        //if (file_exists($path)) {
        //    $dispatcher->registerPlugin(
        //        new AntiCrawlPlugin($config->redis->toArray(),$path, 'anticrawl')
        //    );
        //}



        //初始化路由插件
        $path = CONFIG_PATH . '/route.ini';
        if (file_exists($path)) {
            $dispatcher->registerPlugin(
                new RoutePlugin($path, 'route')
            );
        }
    }
}