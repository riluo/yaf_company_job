<?php
namespace Gram\Yaf\Controller;

use Gram\Logger\MonologManager;
use Yaf\Dispatcher;
use Yaf\Controller_Abstract;
use Gram\Yaf\Extension\Flash;
use ZuoYeah\Entity\ErrorCode;
use ZuoYeah\Entity\PageResult;

/**
 * Class BaseController
 * @package Gram\Yaf\Controller
 */
abstract class ControllerBase extends Controller_Abstract
{
    /**
     * 自动将配置节site.*的内容覆给页面变量
     */
    function init()
    {
        $cfg = $this->getConfig();
        if (isset($cfg->site)) {
            $this->getView()->assign($cfg->site->toArray());
        }
    }

    /**
     * @return bool
     */
    function isAjax()
    {
        return $this->getRequest()->isXmlHttpRequest();
    }

    /**
     * @return \Yaf\Application
     */
    function getApplication()
    {
        return \Yaf\Application::app();
    }

    /**
     * 获取Yaf的配置
     *
     * @return \Yaf\Config_Abstract
     */
    function getConfig()
    {
        return \Yaf\Application::app()->getConfig();
    }

    /**
     * @param $key
     * @param $default
     *
     * @return mixed
     */
    function getQuery($key, $default)
    {
        return $this->getRequest()->getQuery($key, $default);
    }

    /**
     * @param        $msg
     * @param string $type
     */
    function flash($msg, $type = Flash::TYPE_INFO)
    {
        Flash::getInstance()->next(['msg' => $msg, 'type' => $type]);
    }

    /**
     * @param        $msg
     * @param string $type
     */
    function flashNow($msg, $type = Flash::TYPE_INFO)
    {
        Flash::getInstance()->now(['msg' => $msg, 'type' => $type]);
    }

    /**
     * @return Flash
     */
    function getFlashes()
    {
        return Flash::getInstance();
    }

    function renderEmptyObject()
    {
        $this->renderApi(new \stdClass());
    }

    /**
     * @param mixed $data
     * @param bool $removeKeysFromItems
     * @param string $errorKey
     */
    function renderApi($data=[], $removeKeysFromItems = true,$errorKey = 'msg')
    {
        if ($removeKeysFromItems && method_exists($data, 'removeKeysFromItems')) {
            $data->removeKeysFromItems();
        }

        $executeInfo = ob_get_clean();
        if ($data instanceof \Exception) {
            $jsonData = [
                'code' => $data->getCode() ? $data->getCode() : ErrorCode::EXCEPTION_UNHANDLED,
                'data' => null,
                $errorKey  => $data->getMessage()
            ];
        } else {
            $jsonData = [
                'code' => 0,
                'data' => $data,
                $errorKey  => null
            ];
        }

        if(!empty($executeInfo)){
            $jsonData['executeInfo']= $executeInfo;
        }

        $json = json_encode($jsonData, JSON_UNESCAPED_UNICODE);

        if($data instanceof \Exception){
            $log = MonologManager::getLogger(__CLASS__);
            $log->addDebug($this->getRequest()->getRequestUri());
            if (!empty($_POST)) {
                $log->addDebug(json_encode($_POST));
            }
            $log->addDebug($json);
        }

        header('Content-Type:text/json;charset=UTF-8');
        $callback = $this->getRequest()->getQuery('callback');
        if ($callback) {
            echo $callback . "(" . $json . ");";
        } else {
            echo $json;
        }

        Dispatcher::getInstance()->disableView();
    }

    /**
     * @param string $msg
     * @param int    $code
     */
    function renderApiError($msg, $code = 1)
    {
        $this->renderApi(new \Exception($msg, $code));
    }
}