<?php
namespace ZuoYeah\Entity;

use Gram\Domain\Entity\EntityBase;
use Gram\Domain\Entity\EntityInterface;
use Gram\Domain\Entity\LifeCycleInterface;
use Gram\Domain\Entity\ValidatorInterface;

use Respect\Validation\Validator as V;

/**
 * Class Admin
 * @package ZuoYeah\Entity
 */
class Company extends EntityBase implements EntityInterface, LifeCycleInterface, ValidatorInterface
{

    /**
     * @var int
     */
    public $id = 0;
    /**
     * 名称
     * @var string
     */
    public $name = '';


    /**
     * @return mixed
     */
    function onCreate()
    {
        $this->caeateTime = new \DateTime();
        $this->updateTime = new \DateTime();
    }

    /**
     * @return mixed
     */
    function onUpdate()
    {
        $this->updateTime = new \DateTime();
    }

    /**
     * @return mixed
     */
    function onDelete()
    {

    }

    function validate()
    {
        parent::validate();
        $adminValidator = V::attribute("name", V::string()->notEmpty());
        $adminValidator->assert($this);
    }

}