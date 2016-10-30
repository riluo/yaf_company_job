<?php
namespace Gram\Domain\Service;

use Gram\Domain\Entity\EntityInterface;

/**
 * Interface ServiceInterface
 * @package Gram\Domain
 */
interface ServiceInterface
{
    /**
     * 根据给定的主键获取对象
     *
     * @param $id
     * @return mixed
     */
    function get($id);

    /**
     * 根据给定的主键集合获取对象
     *
     * @param array $ids
     * @return mixed
     */
    function getAll(array $ids);

    /**
     * 创建对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function create(EntityInterface $entity);

    /**
     * 更新对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function update(EntityInterface $entity, $partialUpdate = false);

    /**
     * 删除对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function delete(EntityInterface $entity);
}