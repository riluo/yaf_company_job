<?php
namespace Gram\Yaf\Route;

use Yaf\Dispatcher;

/**
 * Class Restful2Route
 * @package Gram\Yaf\Route
 */
class RestfulRoute extends ExtendRoute
{
    /**
     * X-HTTP-Method-Override头信息的方法重写
     */
    const HTTP_METHOD_OVERRIDE = 'HTTP_X_HTTP_METHOD_OVERRIDE';
    /**
     * Form表单的方法重写
     */
    const METHOD_OVERRIDE = '_method';

    /**
     * @return string
     */
    protected function getMethod()
    {
        $request = Dispatcher::getInstance()->getRequest();
        $methodOverride = $request->getParam(self::METHOD_OVERRIDE);
        if (!empty($methodOverride)) {
            return strtoupper($methodOverride);
        }
        $methodOverride = $request->getServer(self::HTTP_METHOD_OVERRIDE);
        if (!empty($methodOverride)) {
            return strtoupper($methodOverride);
        }

        return $request->getMethod();
    }

    /**
     * @return string
     */
    protected function getActionName()
    {
        return strtolower($this->getMethod()) . ucfirst(parent::getActionName());
    }
}