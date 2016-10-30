<?php
namespace Gram\Utility\Helper;

class ArrayHelper
{
    static function fetch(array $array, $keyValue, $default = null)
    {
        return isset($array[$keyValue]) ? $array[$keyValue] : $default;
    }

    static function fetchByKey(array $array, $key, $value, $default = null)
    {
        $filter_function = function ($item) use ($key, $value) {
            return self::getValueFromItem($item, $key) == $value;
        };
        $filter_arr = array_filter($array, $filter_function);
        $item = array_shift($filter_arr);

        if (!$item) {
            $item = $default;
        }
        return $item;
    }

    static function fetchItemsByKey(array $array, $key, $value)
    {
        return array_filter($array, function ($item) use ($key, $value) {
            return self::getValueFromItem($item, $key) == $value;
        });
    }

    static function getValueFromItem($item, $key)
    {
        if (is_array($key)) {
            if (empty($key)) {
                return $item;
            }
            return self::getValueFromItem(self::getValueFromItem($item, array_shift($key)), $key);
        }

        if (is_array($item)) {
            return $item[$key];
        }
        return $item->$key;
    }

    static function mapKey(array $items, $key = 'id')
    {
        return array_values(array_unique(array_map(function ($item) use ($key) {
            return self::getValueFromItem($item, $key);
        }, $items)));
    }


    static function mapValues(array $items, $key = 'id')
    {
        return array_values(array_map(function ($item) use ($key) {
            return self::getValueFromItem($item, $key);
        }, $items));
    }


    static function fetchByKeys(array $array, $keys, $values, $default = null)
    {
        self::ensureArray($keys);
        self::ensureArray($values);

        $item = array_shift(array_filter($array, function ($item) use ($keys, $values) {
            foreach ($keys as $i => $key) {
                $v = self::getValueFromItem($item, $key);
                if ($v != $values[$i]) {
                    return false;
                }
            }
            return true;
        }));

        if (!$item) {
            $item = $default;
        }
        return $item;
    }

    static function ensureArray(&$keys)
    {
        if (!is_array($keys)) {
            $keys = explode(',', $keys);
        }
    }

    static function select(array $array, $keys)
    {
        self::ensureArray($keys);

        return array_values(array_map(function ($item) use ($keys) {
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = self::getValueFromItem($item, $key);
            }
            return $result;
        }, $array));
    }

    static function fetchItemsByKeys(array $array, $keys, $values)
    {
        return array_filter($array, function ($item) use ($keys, $values) {
            foreach ($keys as $i => $key) {
                $v = self::getValueFromItem($item, $key);
                if ($v != $values[$i]) {
                    return false;
                }
            }
            return true;
        });
    }

    static function sortBySortedArray(&$items, $sortedArray, $sortedKey, $itemKey = '')
    {
        if (empty($itemKey)) {
            $itemKey = $sortedKey;
        }
        usort($items, function ($item1, $item2) use ($sortedArray, $sortedKey, $itemKey) {
            $index1 = 0;
            $value1 = self::getValueFromItem($item1, $itemKey);
            foreach ($sortedArray as $sortedItem) {
                if (self::getValueFromItem($sortedItem, $sortedKey) == $value1) {
                    break;
                }
                $index1++;
            }

            $index2 = 0;
            $value2 = self::getValueFromItem($item2, $itemKey);
            foreach ($sortedArray as $sortedItem) {
                if (self::getValueFromItem($sortedItem, $sortedKey) == $value2) {
                    break;
                }
                $index2++;
            }

            return $index1 > $index2 ? 1 : -1;
        });
    }

    static function sortBySortedValues(&$items, $itemKey, $sortedValues)
    {
        usort($items, function ($item1, $item2) use ($sortedValues, $itemKey) {
            $index1 = 0;
            $value1 = self::getValueFromItem($item1, $itemKey);
            foreach ($sortedValues as $sortedItem) {
                if ($sortedItem == $value1) {
                    break;
                }
                $index1++;
            }

            $index2 = 0;
            $value2 = self::getValueFromItem($item2, $itemKey);
            foreach ($sortedValues as $sortedItem) {
                if ($sortedItem == $value2) {
                    break;
                }
                $index2++;
            }

            return $index1 > $index2 ? 1 : -1;
        });

        return $items;
    }

    /**
     * 按键值排序
     * @param $items
     * @param $key
     * @param string $order
     */
    static function sortByKey(&$items, $key, $order = 'asc')
    {
        usort($items, function ($item1, $item2) use ($key, $order) {
            $value1 = self::getValueFromItem($item1, $key);

            $value2 = self::getValueFromItem($item2, $key);


            return $value1 > $value2
                ? (strtolower($order) == 'desc' ? -1 : 1)
                : (strtolower($order) == 'desc' ? 1 : -1);
        });
    }


    static function mergeAll($json1, $json2)
    {
        if (empty($json1)) return $json2;
        if (isset($json1[0]) || isset($json2[0])) {
            return array_merge($json1, $json2);
        }
        foreach ($json2 as $key => $value) {
            if (is_array($value)) {
                if (empty($json1[$key])) {
                    $json1[$key] = [];
                }
                $json1[$key] = self::mergeAll($json1[$key], $value);
            } else {
                $json1[$key] = $value;
            }
        }
        return $json1;
    }


    public static function autoConvert(&$items)
    {
        foreach ($items as &$item) {
            foreach ($item as $k => $v) {
                if (StringHelper::endWith($k, 'Rate')) {
                    $item[$k] = round($item[$k], 1);
                }
                if (StringHelper::contains($k, 'Avg') || StringHelper::contains($k, 'avg')) {
                    $item[$k] = floatval($item[$k]);
                    continue;
                }

                if (StringHelper::endWith($k, 'Count') || StringHelper::endWith($k, 'Id')) {
                    $item[$k] = intval($item[$k]);
                }
            }
        }
        return $items;
    }

}