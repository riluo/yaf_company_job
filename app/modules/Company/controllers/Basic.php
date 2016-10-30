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
class BasicController extends BaseController
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
        echo 'eee';
        return false;
    }

    public function createAction()
    {
        $comapnyInfo = new \ZuoYeah\Entity\Company();
        $comapnyInfo->name = "测试公司名称";

        $do = $this->CompanyService->create($comapnyInfo);

        if($do) {
            echo 'success';
            exit;
        } 
    }

}