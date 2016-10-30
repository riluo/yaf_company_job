<?php
namespace Gram\DBAL;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Driver\PDOException;
use Gram\DBAL\Driver\MySqlDriver;

/**
 * {@inheritdoc}
 */
class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * {@inheritdoc}
     */
    public function executeQuery($query, array $params = array(), $types = array(), QueryCacheProfile $qcp = null)
    {
        try {
            return parent::executeQuery($query, $params, $types, $qcp);
        } catch (\Exception $ex) {
            if ($this->isMySqlServerHasGoneAwayException($ex->getPrevious())) {
                if (!$this->reconnect()) {
                    throw new \Exception('Reconnect MySQL Server has failed', $ex);
                }
                return parent::executeQuery($query, $params, $types, $qcp);
            } else {
                throw $ex;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        try {
            return parent::query();
        } catch (\Exception $ex) {
            if ($this->isMySqlServerHasGoneAwayException($ex->getPrevious())) {
                if (!$this->reconnect()) {
                    throw new \Exception('Reconnect MySQL Server has failed', $ex);
                }
                return parent::query();
            } else {
                throw $ex;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeUpdate($query, array $params = array(), array $types = array())
    {
        try {
            return parent::executeUpdate($query, $params, $types);
        } catch (\Exception $ex) {
            if ($this->isMySqlServerHasGoneAwayException($ex->getPrevious())) {
                if (!$this->reconnect()) {
                    throw new \Exception('Reconnect MySQL Server has failed', $ex);
                }
                return parent::executeUpdate($query, $params, $types);
            } else {
                throw $ex;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
        try {
            return parent::exec($statement);
        } catch (\Exception $ex) {
            if ($this->isMySqlServerHasGoneAwayException($ex->getPrevious())) {
                if (!$this->reconnect()) {
                    throw new \Exception('Reconnect MySQL Server has failed', $ex);
                }
                return parent::exec($statement);
            } else {
                throw $ex;
            }
        }
    }

    /**
     * @param $exception
     *
     * @return bool
     */
    public function isMySqlServerHasGoneAwayException($exception)
    {
        if (is_null($exception) || !($exception instanceof PDOException)) {
            return false;
        }

        return $exception->getErrorCode() == 2006
        && $exception->getSQLState() == 'HY000'
        && $exception->getMessage() == 'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away';
    }

    /**
     * @return bool
     */
    public function reconnect()
    {
        if ($this->_driver instanceof MySqlDriver) {
            $this->_conn = $this->_driver->reconnect();
        } else {
            $this->close();
            $this->connect();
        }
        return true;
    }
}