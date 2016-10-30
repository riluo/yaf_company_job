<?php
namespace Gram\Domain\Service;

use Gram\Domain\Entity\EntityInterface;
use Gram\Domain\Repository\RepositoryInterface;

/**
 * Class ServiceBase
 * @package Gram\Domain\Service
 */
abstract class ServiceBase implements ServiceInterface
{

    /**
     * @var \Gram\Domain\Repository\RepositoryInterface
     */
    protected $repository;

    /**
     * 获取仓储实例
     *
     * @return \Gram\Domain\Repository\RepositoryInterface
     */
    function getRepository()
    {
        return $this->repository;
    }

    /**
     * 设置仓储实例
     *
     * @param \Gram\Domain\Repository\RepositoryInterface $repository
     */
    function setRepository(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 根据给定的主键获取对象
     *
     * @param $id
     * @return mixed
     */
    function get($id)
    {
        return $this->getRepository()->get($id);
    }

    /**
     * 根据给定的主键集合获取对象
     *
     * @param array $ids
     * @return mixed
     */
    function getAll(array $ids)
    {
        return $this->getRepository()->getAll($ids);
    }

    /**
     * 创建对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function create(EntityInterface $entity)
    {
        return $this->getRepository()->create($entity);
    }

    /**
     * 更新对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function update(EntityInterface $entity,$partialUpdate = false)
    {
        return $this->getRepository()->update($entity,$partialUpdate);
    }

    /**
     * 删除对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function delete(EntityInterface $entity)
    {
        return $this->getRepository()->delete($entity);
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->repository, $method), $args);
    }

}