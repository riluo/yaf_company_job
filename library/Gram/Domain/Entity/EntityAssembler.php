<?php
namespace Gram\Domain\Entity;

/**
 * Class EntityTrait
 * @package Gram\Domain\Entity
 */
trait EntityAssembler
{


    /**
     * 从数组组装对象
     *
     * @param array $row
     *
     * @return static
     */
    static function assemble(array $row, array $types = [])
    {
        $instance = new static;
        foreach ($row as $key => $value) {
            if (!property_exists($instance, $key)) {
                continue;
            }

            if (in_array($key, ['createTime', 'updateTime'])) {
                $value = new \DateTime($value);
            }

            $instance->{$key} = $value;
        }
        return $instance;
    }

    /**
     * 将对象转换成数组
     *
     * @param EntityInterface $entity
     * @param array           $properties
     *
     * @return array
     */
    static function disassemble(EntityInterface $entity, $properties = [])
    {
        if (empty($properties)) {
            return (array)$entity;
        }
        $result = [];
        foreach ($properties as $property) {
            if (!property_exists($entity, $property)) {
                continue;
            }
            $result[$property] = $entity->{$property};
        }
        return $result;
    }
}