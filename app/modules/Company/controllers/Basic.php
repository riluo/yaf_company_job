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
        $site = \Yaf\Application::app()->getConfig()->site->toArray()['siteUrl'];
        $domainName = str_replace('.test.com','',$_SERVER['HTTP_HOST']);
        if($domainName != 'www' && strlen($domainName) > 0) {
            $userName = $domainName;
        }
        echo $userName;
        //随机用户名
        echo mt_rand(10, 99) . uniqid() . mt_rand(0, 9);
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