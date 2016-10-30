<?php
namespace Gram\Domain\Repository;

use Gram\Domain\Entity\EntityInterface;

interface RepositoryInterface
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
    function update(EntityInterface $entity);

    /**
     * 删除对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function delete(EntityInterface $entity);
}