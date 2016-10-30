<?php
namespace Gram\Utility\Helper;

class ThrowHelper
{
    /**
     * @param mixed  $value
     * @param string $message
     * @param int    $code
     */
    static function ifNull($value, $message, $code = 0)
    {
        if (is_null($value)) {
            throw new \InvalidArgumentException($message, $code);
        }
    }

    /**
     * @param mixed  $value
     * @param string $message
     * @param int    $code
     */
    static function ifEmpty($value, $message, $code = 0)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException($message, $code);
        }
    }

    /**
     * @param bool   $condition
     * @param string $message
     * @param int    $code
     */
    static function ifTrue($condition, $message, $code = 0)
    {
        if ($condition) {
            throw new \LogicException($message, $code);
        }
    }

    /**
     * @param bool   $condition
     * @param string $message
     * @param int    $code
     */
    static function ifFalse($condition, $message, $code = 0)
    {
        if (!$condition) {
            throw new \LogicException($message, $code);
        }
    }

    /**
     * @param mixed  $needle
     * @param array  $haystack
     * @param string $message
     * @param int    $code
     */
    static function ifNotInArray($needle, array $haystack, $message, $code = 0)
    {
        self::ifFalse(in_array($needle, $haystack), $message, $code);
    }
}