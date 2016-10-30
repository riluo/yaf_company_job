<?php
namespace Gram\Domain\Repository;

use Gram\Domain\Entity\EntityBase;
use Gram\Domain\Entity\EntityInterface;
use Gram\Domain\Entity\LifeCycleInterface;
use Gram\Domain\Entity\ValidatorInterface;

abstract class RepositoryBase implements RepositoryInterface
{
    /**
     * 将对象转换成数据库数组
     *
     * @param EntityInterface $entity
     * @return array
     */
    abstract protected function disassemble($entity);

    /**
     * 将数组组装成领域对象
     *
     * @param array $row
     * @return EntityInterface
     */
    abstract protected function assemble(array $row);

    /**
     * 创建对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function create(EntityInterface $entity)
    {
        if ($entity instanceof LifeCycleInterface) {
            $entity->onCreate();
        }
        if ($entity instanceof ValidatorInterface) {
            $entity->validate();
        }
        return $this->createInner($entity);
    }

    /**
     * 创建对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    abstract protected function createInner(EntityInterface $entity);

    /**
     * 更新对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function update(EntityInterface $entity, $partialUpdate = false)
    {
        if ($entity instanceof LifeCycleInterface) {
            $entity->onUpdate();
        }
        if ($entity instanceof ValidatorInterface) {
            $entity->validate();
        }

        $updateKeys = [];
        if ($partialUpdate && $entity instanceof EntityBase) {
            $updateKeys = $entity->getUpdateKeys();
        }

        return $this->updateInner($entity, $updateKeys);
    }

    /**
     * 更新对象
     *
     * @param EntityInterface $entity
     * @param array $updateKeys
     * @return bool
     */
    abstract protected function updateInner(EntityInterface $entity, $updateKeys = []);

    /**
     * 删除对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function delete(EntityInterface $entity)
    {
        if ($entity instanceof LifeCycleInterface) {
            $entity->onDelete();
        }
        return $this->deleteInner($entity);
    }

    /**
     * 删除对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    abstract protected function deleteInner(EntityInterface $entity);
}