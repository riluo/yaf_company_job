<?php
namespace Gram\Yaf\Plugin;

use Gram\Security\Exception\UnauthorizedAccessException;
use SebastianBergmann\Exporter\Exception;
use Yaf\Config\Ini;
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;
use Gram\Security\Authorization;
use ZuoYeah\Entity\ErrorCode;

class AuthorizationPlugin extends Plugin_Abstract
{
    /**
     * @var \Gram\Security\Authorization
     */
    protected $auth;

    /**
     * @param string $iniFile
     * @param string $section
     */
    function __construct($iniFile, $section = null)
    {
        $rules = $this->prepareConfig($iniFile, $section);
        $this->auth = new Authorization($rules);
    }

    /**
     * @param string $iniFile
     * @param string $section
     * @return array|void
     */
    protected function prepareConfig($iniFile, $section = null)
    {
        $configs = new Ini($iniFile, $section);
        return $configs->toArray();
    }

    /**
     * @param Request_Abstract $request
     * @param Response_Abstract $response
     * @throws UnauthorizedAccessException
     * @return null
     */
    function preDispatch(Request_Abstract $request, Response_Abstract $response)
    {
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        if (false === $this->auth->check($module, $controller, $action)) {
            throw new UnauthorizedAccessException($module, $controller, $action,'no access right',ErrorCode::COMMON_NOT_ACCESS_RIGHT);
        }
    }

}