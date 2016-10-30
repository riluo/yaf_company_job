<?php
namespace ZuoYeah\Repository;

use Gram\Domain\Entity\EntityInterface;
use Gram\Domain\Repository\Dbal\DbalRepository;

use ZuoYeah\Entity\PageResult;
use ZuoYeah\Entity\Company;

class CompanyRepository extends DbalRepository
{
    protected function tableName()
    {
        return 'yy_company';
    }

    /**
     * @param Company $entity
     *
     * @return array
     */
    protected function disassemble($entity)
    {
        return Company::disassemble($entity);
    }


    /**
     * @param array $row
     *
     * @return EntityInterface
     */
    protected function assemble(array $row)
    {
        return Company::assemble($row);
    }

    /**
     * 根据用公司名获取信息
     * @param $name
     * @return mixed|null
     */
    function findByname($name){
        $query = $this->query()
            ->andWhere("name = :name")
            ->setParameter(':name', $name);
        return $this->fetch($query);
    }

    /**
     * 判断用户名是否存在
     * @param $name
     * @return bool
     */
    function existname($name){
        $query = $this->query()->where('name=:name')->setParameter(':name',$name);
        return $this->fetchCount($query)>0;
    }


}