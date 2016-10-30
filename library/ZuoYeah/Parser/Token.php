<?php
namespace ZuoYeah\Parser;

/**
 * Class Token
 * @package ZuoYeah\Resolver
 */
class Token
{
    private $type;
    private $value;

    private $rawType;

    /**
     * @param string $type
     * @param string $value
     */
    function __construct($type, $value)
    {
        $this->setType($type);
        $this->setValue($value);
    }

    /**
     * @return string
     */
    function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    function setType($type)
    {
        $this->rawType = $type;
        $this->type = strip_tags($type);
    }

    /**
     * @return string
     */
    function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    function raw()
    {
        return $this->rawType . $this->value;
    }

    /**
     * @return string
     */
    function __toString()
    {
        return sprintf('%s%s', $this->getType(), $this->getValue());
    }
}