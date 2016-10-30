<?php
namespace Gram\Yaf\Plugin;

use Yaf\Plugin_Abstract;

/**
 * Class RoutePlugin
 * @package Gram\Yaf\Plugin
 */
class AutoParamPlugin extends Plugin_Abstract
{
    /**
     * @param \Yaf\Request_Abstract  $request
     * @param \Yaf\Response_Abstract $response
     */
    function routerShutdown(\Yaf\Request_Abstract $request, \Yaf\Response_Abstract $response)
    {
        if (in_array($request->getMethod(), ['PUT', 'POST', 'DELETE'])) {
            $params = array_merge($_GET, $_POST, $request->getParams());
        } else {
            $params = array_merge($_GET, $request->getParams());
        }

        $request->setParam($params);
    }
}