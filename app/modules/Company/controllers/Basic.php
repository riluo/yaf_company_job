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
        //echo $userName;
        //随机用户名
        echo $this->generate_username(10);
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

    public function generate_username( $length = 6 ) {
        // 密码字符集，可任意添加你需要的字符 
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $name = '';
        for ( $i = 0; $i < $length; $i++ )
        {
            // 这里提供两种字符获取方式
            // 第一种是使用substr 截取$chars中的任意一位字符；
            // 第二种是取字符数组$chars 的任意元素
            // $name .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $name .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $name;
    }

}