<?php
namespace Gram\Utility\Collection;

/**
 * Class CollectionBase
 * @package Gram\Utility\Collection
 */
abstract class CollectionBase extends \ArrayObject implements \JsonSerializable
{
    /**
     * @param array $items
     */
    function __construct(array $items = [])
    {
        parent::__construct($items, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @param int $index1
     * @param int $index2
     *
     * @return bool
     */
    function exchangeIndex($index1, $index2)
    {
        if (!$this->offsetExists($index1)) {
            throw new \InvalidArgumentException(sprintf('The index "%s" does not exist in this sequence.', $index1));
        }
        if (!$this->offsetExists($index2)) {
            throw new \InvalidArgumentException(sprintf('The index "%s" does not exist in this sequence.', $index2));
        }
        $a = $this->offsetGet($index1);
        $b = $this->offsetGet($index2);
        $this->offsetSet($index2, $a);
        $this->offsetSet($index1, $b);

        return true;
    }

    /**
     * @param int $index
     *
     * @return mixed|null
     */
    function get($index)
    {
        return $this->offsetExists($index) ? $this->offsetGet($index) : null;
    }

    /**
     * @return mixed|null
     */
    function first()
    {
        return $this->get(0);
    }

    /**
     * @return array
     */
    function all()
    {
        return $this->getArrayCopy();
    }

    /**
     * @return mixed|null
     */
    function last()
    {
        $index = count($this) - 1;
        if ($index < 0) {
            return null;
        }

        return $this->get($index);
    }

    /**
     * 清空集合
     */
    function clear()
    {
        for ($i = 0, $c = count($this); $i < $c; $i++) {
            $this->offsetUnset($i);
        }
    }

    /**
     * @return bool
     */
    function isEmpty()
    {
        return count($this) == 0;
    }

    /**
     * @param callable $callback
     */
    function each(\Closure $callback)
    {
        foreach ($this as $item) {
            $callback($item);
        }
    }

    /**
     * @param callable $callback
     *
     * @return array
     */
    function filter(\Closure $callback)
    {
        return array_filter($this->all(), $callback);
    }

    /**
     * @param callable $callback
     *
     * @return array
     */
    function map(\Closure $callback)
    {
        return array_map($callback, $this->all());
    }

    /**
     * @param callable $callback
     *
     * @return mixed
     */
    function reduce(\Closure $callback)
    {
        return array_reduce($this->all(), $callback);
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize()
    {
        return $this->all();
    }
}