<?php
namespace Gram\Entity;

/**
 * Interface EntityInterface
 * @package Gram\Entity
 */
interface EntityInterface extends ObjectInterface
{
    /**
     * @return string
     */
    static function tableName();

    /**
     * @return string
     */
    static function primaryKey();

    /**
     * 根据给定的主键获取对象
     *
     * @param $id
     * @return mixed
     */
    static function find($id);

    /**
     * 根据给定的主键集合获取对象
     *
     * @param array $ids
     * @return mixed
     */
    static function findAll(array $ids);

    /**
     * 创建对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
     function insert();

    /**
     * 更新对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
     function update();

    /**
     * 删除对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
     function delete();
}