<?php
use Gram\Security\Authentication;
use Gram\Security\Principal\Identity;
use Gram\Yaf\Controller\BaseController;
use Gram\Yaf\Controller\ControllerBase;
use ZuoYeah\Entity\Device;
use ZuoYeah\Gearman\MessageWorker;

use ZuoYeah\Entity\Company;
use ZuoYeah\Service\CompanyService;


class HomeController extends BaseController
{

    function init()
    {
        $this->CompanyService = new CompanyService();
    }

    function indexAction()
    {
        $params = 'hello yaf'; 
        	 
        $this->getView()->assign("content", $params);
    }
}