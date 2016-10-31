<?php
use Gram\Utility\Helper\ArrayHelper;
use Gram\Utility\Helper\StringHelper;
use Gram\Utility\Helper\ThrowHelper;
use Gram\Yaf\Controller\BaseController;
use Gram\Yaf\Controller\ControllerBase;
use ZuoYeah\Entity\Company;
use ZuoYeah\Service\CompanyService;

/**
 * Class AnswerController
 */
class CategoryController extends BaseController
{
    /**
     * @var \ZuoYeah\Service\CompanyService
     */
    protected $CompanyService;
   


    function init()
    {
        $this->CompanyService = new CompanyService();
    }

    public function indexAction()
    {
        echo 'This is a Job category';
        return false;
    }

}