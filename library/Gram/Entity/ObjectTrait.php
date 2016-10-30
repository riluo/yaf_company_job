<?php
namespace Gram\Entity;

use Gram\Utility\Collection\CollectionBase;

/**
 * Class EntityTrait
 * @package Gram\Entity
 */
trait ObjectTrait
{
    /**
     * @var array
     */
    private static $supportTypes = [
        'boolean',
        'integer',
        'int',
        'float',
        'string',
        'array',
        '\ArrayObject',
        '\DateTime'
    ];

    /**
     * @return string
     */
    static function dateTimeFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return static::disassemble($this);
    }

    /**
     * 从数组组装对象
     *
     * @param array $row
     *
     * @return static
     */
    static function assemble(array $row)
    {
        $result = new static;
        $metadata = static::metadata();
        foreach ($row as $key => $value) {
            if (!property_exists($result, $key)) {
                continue;
            }
            if (array_key_exists($key, $metadata)) {
                $converter = $metadata[$key];
                if (is_array($converter)) {
                    $value = self::castAssemblerValue($converter, $row, $key);
                } elseif (is_string($converter)) {
                    $value = self::castTypedValue($value, $converter);
                }
            }
            $result->{$key} = $value;
        }
        return $result;
    }

    /**
     * 将对象转换成数组
     *
     * @param mixed $entity
     * @param array $properties
     *
     * @return array
     */
    static function disassemble($entity, $properties = [])
    {
        $result = [];
        if (empty($properties)) {
            $result = get_object_vars($entity);
        } else {
            foreach ($properties as $p) {
                if (!property_exists($entity, $p)) {
                    continue;
                }
                $result[$p] = $entity->{$p};
            }
        }

        $metadata = static::metadata();
        foreach ($result as $key => &$value) {
            if (array_key_exists( $key,$metadata)) {
                $converter = $metadata[$key];
                if (is_array($converter)) {
                    $value = self::castDisassemblerValue($converter, $entity, $key);
                }
            }
            static::recursiveCastValue($value);
        }
        return $result;
    }

    /**
     * @param $value
     * @param $type
     *
     * @return mixed|\ArrayObject|\DateTime
     */
    static protected function castTypedValue($value, $type)
    {
        if (in_array($type, self::$supportTypes)) {
            switch ($type) {
                case '\DateTime':
                    $value = new \DateTime($value);
                    break;
                case '\ArrayObject':
                    $value = new \ArrayObject($value, \ArrayObject::ARRAY_AS_PROPS);
                    break;
                case 'boolean':
                    $value = boolval($value);
                    break;
                default:
                    settype($value, $type);
                    break;
            }
        }
        return $value;
    }

    /**
     * @param array $converter
     */
    protected static function validConverter(array $converter)
    {
        if (count($converter) != 2) {
            throw new \LogicException('对象的getter和setter必须成对出现');
        }
    }


    /**
     * @param array $converter
     * @param array $row
     * @param string $name
     * @return mixed
     */
    protected static function castAssemblerValue(array $converter, array $row, $name)
    {
        self::validConverter($converter);
        list($assembler, $disassembler) = $converter;
        return call_user_func($assembler, $row, $name);
    }

    /**
     * @param array $converter
     * @param mixed $entity
     * @param string $name
     * @return mixed
     */
    protected static function castDisassemblerValue(array $converter, $entity, $name)
    {
        self::validConverter($converter);
        list($assembler, $disassembler) = $converter;
        return call_user_func($disassembler, $entity, $name);
    }

    /**
     * 类型的递归转换
     *
     * @param $value
     */
    protected static function recursiveCastValue(&$value)
    {
        if ($value instanceof ObjectInterface) {
            $value = call_user_func([get_class($value), 'disassemble'], $value);
        } elseif ($value instanceof CollectionBase) {
            $value = $value->getArrayCopy();
            foreach ($value as &$item) {
                static::recursiveCastValue($item);
            }
        } elseif ($value instanceof \DateTime) {
            $value = $value->format(static::dateTimeFormat());
        } elseif ($value instanceof \ArrayObject) {
            $value = $value->getArrayCopy();
        }
    }
}