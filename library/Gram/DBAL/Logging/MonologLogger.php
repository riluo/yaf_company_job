<?php
namespace Gram\DBAL\Logging;

use Doctrine\DBAL\Logging\SQLLogger;
use Gram\Logger\MonologManager;

class MonologLogger implements SQLLogger
{
    protected function log()
    {
        return MonologManager::getLogger(__CLASS__);
    }

    /**
     * Logs a SQL statement somewhere.
     *
     * @param string     $sql    The SQL to be executed.
     * @param array|null $params The SQL parameters.
     * @param array|null $types  The SQL parameter types.
     *
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->log()->addDebug(json_encode(array('sql' => $sql, 'params' => $params, 'types' => $types)));
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery()
    {
    }

}