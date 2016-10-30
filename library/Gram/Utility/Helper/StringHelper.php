<?php
namespace Gram\Utility\Helper;

class StringHelper
{
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     *
     * @return bool
     */
    public static function endWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ((string)$needle === substr($haystack, -strlen($needle))) return true;
        }

        return false;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     *
     * @return bool
     */
    public static function startWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) === 0) return true;
        }

        return false;
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     *
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) !== false) return true;
        }

        return false;
    }


    /**
     * 获取关键字中间的字符串
     * @param $str
     * @param $prefix
     * @param $suffix
     * @return string
     */
    public static function getInner($str, $prefix, $suffix)
    {
        $tokens = explode($prefix, $str);
        if (!isset($tokens[1])) {
            return '';
        }

        $tokens = explode($suffix, $tokens[1]);

        if (!isset($tokens[0])) {
            return '';
        }

        //过滤空白，包括全角空格
        return trim(str_replace('　', ' ', $tokens[0]));
    }
}