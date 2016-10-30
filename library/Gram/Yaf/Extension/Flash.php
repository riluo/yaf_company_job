<?php
namespace Gram\Yaf\Extension;

use \Yaf\Session;

/**
 * Class Flash
 * @package Gram\Web
 */
class Flash implements \ArrayAccess, \IteratorAggregate, \Countable
{
    const SESSION_KEY = 'flash';
    const TYPE_DANGER = 'danger';
    const TYPE_WARNING = 'warning';
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';

    private static $instance;
    private $flashes;
    private $session;

    public function __construct()
    {
        $session = $this->session = Session::getInstance();
        if ($session->has($this::SESSION_KEY)) {
            $this->flashes = $session->get($this::SESSION_KEY);
            $session->del($this::SESSION_KEY);
        } else {
            $this->flashes = array();
        }
        $session->set($this::SESSION_KEY, array());

        self::$instance = $this;
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function now($data)
    {
        array_push($this->flashes, $data);
    }

    public function next($data)
    {
        $nextFlashes = $this->session->get($this::SESSION_KEY);
        array_push($nextFlashes, $data);
        $this->session->set($this::SESSION_KEY, $nextFlashes);
    }

    /**
     * Array Access: Offset Exists
     */
    public function offsetExists($offset)
    {
        return isset($this->flashes[$offset]);
    }

    /**
     * Array Access: Offset Get
     */
    public function offsetGet($offset)
    {
        return isset($this->flashes[$offset]) ? $this->flashes[$offset] : null;
    }

    /**
     * Array Access: Offset Set
     */
    public function offsetSet($offset, $value)
    {
        $this->flashes[$offset] = $value;
    }

    /**
     * Array Access: Offset Unset
     */
    public function offsetUnset($offset)
    {
        unset($this->flashes[$offset]);
    }

    /**
     * Iterator Aggregate: Get Iterator
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->flashes);
    }

    /**
     * Countable: Count
     */
    public function count()
    {
        return count($this->flashes);
    }
}