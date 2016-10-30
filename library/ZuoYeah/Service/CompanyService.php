<?php
namespace ZuoYeah\Service;

use Gram\Domain\Service\ServiceBase;
use SebastianBergmann\Exporter\Exception;
use ZuoYeah\Entity\PageResult;
use ZuoYeah\Repository\CompanyRepository;

class CompanyService extends ServiceBase
{
    /**
     * @var CompanyRepository
     */
    protected $repository;

    function __construct($repository = null)
    {
        if (is_null($repository)) {
            $repository = new CompanyRepository();
        }
        $this->setRepository($repository);
    }


    /**
     * 根据企业名称获取信息
     * @param $name
     * @return Admin
     */
    function findByname($name){
        return $this->repository->findByname($name);
    }


    /**
     * 判断企业名称是否存在
     * @param $name
     * @return bool
     */
    function existname($name){
        return $this->repository->existname($name);
    }
}