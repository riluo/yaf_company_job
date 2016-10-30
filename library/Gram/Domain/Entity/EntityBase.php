<?php
namespace Gram\Domain\Entity;

use Gram\Utility\Collection\CollectionBase;
use Gram\Utility\Helper\StringHelper;

/**
 * Class EntityBase
 * @package Gram\Domain\Entity
 */
abstract class EntityBase implements EntityInterface, \JsonSerializable
{
    private $updatedKeys = [];

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
    protected function metadata()
    {
        return [
            'id' => 'integer',
            'updateTime' => '\DateTime',
            'createTime' => '\DateTime'
        ];
    }

    function __construct()
    {
        $this->keyUpdated('updateTime');
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
        $entity = new static;
        $md = $entity->metadata();

        foreach ($row as $key => $value) {
            if (!property_exists($entity, $key)) {
                continue;
            }

            if (array_key_exists($key, $md)) {
                $value = self::castValue($value, $md[$key]);
            }

            $entity->{$key} = $value;
        }
        return $entity;
    }

    /**
     * @param $value
     * @param $type
     *
     * @return mixed|\ArrayObject|\DateTime
     */
    static protected function castValue($value, $type)
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
     * 将对象转换成数组
     *
     * @param EntityInterface $entity
     * @param array $properties
     *
     * @return array
     */
    static function disassemble(EntityInterface $entity, $properties = [])
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
        foreach ($result as $key => &$value) {
            static::recursiveCastValue($value);
        }

        unset($result['updatedKeys']);

        return $result;
    }

    /**
     * 类型的递归转换
     *
     * @param $value
     */
    protected static function recursiveCastValue(&$value)
    {
        if ($value instanceof EntityBase) {
            $value = call_user_func([get_class($value), 'disassemble'], $value);
        } elseif ($value instanceof CollectionBase) {
            $value = $value->getArrayCopy();
            array_walk($value, function (&$v) {
                static::recursiveCastValue($v);
            });
        } elseif ($value instanceof \DateTime) {
            $value = $value->format(static::dateTimeFormat());
        } elseif ($value instanceof \ArrayObject) {
            $value = $value->getArrayCopy();
        }
    }

    /**
     * 从对象列表获取部分字段
     *
     * @param      $items
     * @param      $keys
     * @param bool $idKey
     *
     * @return array
     */
    static function getPartField($items, $keys, $idKey = false)
    {
        $entity = new static;
        $outItems = [];
        foreach ($items as $k => $item) {
            $outItem = [];
            foreach ($keys as $key) {
                if (property_exists($entity, $key)) {
                    $outItem[$key] = $item->{$key};
                }
            }
            if ($idKey) {
                $outItems[$item->id] = $outItem;
            } else {
                $outItems[] = $outItem;
            }
        }
        return $outItems;
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return static::disassemble($this);
    }

    function keyUpdated($keys)
    {
        if (is_string($keys)) {
            $keys = explode(',', $keys);
        }

        $this->updatedKeys = array_unique(array_merge($this->updatedKeys, $keys));
    }

    public function getUpdateKeys()
    {
        return $this->updatedKeys;
    }

    public function __call($name, $arguments)
    {
        if (StringHelper::endWith($name, 'Set')) {
            $field = substr($name, 0, -3);
            if (isset($this->$field)) {
                $this->$field = $arguments[0];
                $this->keyUpdated([$field, 'UpdateTime']);
            }
        }
    }
}