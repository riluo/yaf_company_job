<?php
namespace Gram\DBAL\Driver;

/**
 * Class Driver
 * @package Gram\DBAL
 */
class MySqlDriver extends \Doctrine\DBAL\Driver\PDOMySql\Driver
{
    /**
     * @var \Doctrine\DBAL\Driver\Connection|\Doctrine\DBAL\Driver\PDOConnection
     */
    protected static $connection;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param array $params
     * @param null  $username
     * @param null  $password
     * @param array $driverOptions
     *
     * @return \Doctrine\DBAL\Driver\Connection|\Doctrine\DBAL\Driver\PDOConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        $this->params = func_get_args();

        if (is_null(self::$connection)) {
            self::$connection = parent::connect($params, $username, $password, $driverOptions);
        }
        return self::$connection;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Connection|\Doctrine\DBAL\Driver\PDOConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    function reconnect()
    {
        list($params, $username, $password, $driverOptions) = $this->params;
        return parent::connect($params, $username, $password, $driverOptions);
    }
}