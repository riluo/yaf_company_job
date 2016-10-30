<?php
namespace Gram\Logger;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

class MonologManager
{
    protected static $handlers = [];

    /**
     * @param HandlerInterface $handler
     */
    static function appendHandler(HandlerInterface $handler)
    {
        self::$handlers[] = $handler;
    }

    /**
     * @param $name
     *
     * @return Logger
     */
    static function getLogger($name)
    {
        $logger = new Logger($name);
        array_walk(self::$handlers, function ($handler) use ($logger) {
            $logger->pushHandler($handler);
        });
        return $logger;
    }
}