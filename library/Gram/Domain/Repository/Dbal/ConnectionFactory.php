<?php
namespace Gram\Domain\Repository\Dbal;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Gram\DBAL\Logging\MonologLogger;

/**
 * Class ConnectionFactory
 * @package Gram\Domain\Dbal
 */
class ConnectionFactory
{
    const DEFAULT_NAME = 'default';

    static protected $container = [];

    /**
     * 注册数据库配置信息
     *
     * $params结构如下：
array(
    'dbname' => 'mydb',
    'user' => 'user',
    'password' => 'secret',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
);
     *
     * @param string $name   配置名称
     * @param array  $params 配置信息
     */
    static function register($name, array $params = [])
    {
        if (func_num_args() == 1) {
            $params = $name;
            $name = self::DEFAULT_NAME;
        }
        self::$container[$name] = $params;
    }

    /**
     * @param string $name
     *
     * @return \Doctrine\DBAL\Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    static function getConnection($name = null)
    {
        if (is_null($name)) {
            $name = self::DEFAULT_NAME;
        }
        $config = new Configuration();
        $config->setSQLLogger(new MonologLogger());
        //$config->setResultCacheImpl(new ArrayCache());

        return DriverManager::getConnection(self::$container[$name], $config);
    }
}