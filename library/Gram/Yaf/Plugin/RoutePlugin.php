<?php
namespace Gram\Yaf\Plugin;

use Respect\Validation\Rules\ReadableTest;
use Yaf\Config\Ini;
use Yaf\Dispatcher;
use Yaf\Plugin_Abstract;

/**
 * Class RoutePlugin
 * @package Gram\Yaf\Plugin
 */
class RoutePlugin extends Plugin_Abstract
{
    /**
     * @var array
     */
    protected static $types = [
        'regex',
        'simple',
        'rewrite',
        'supervar',
        'map'
    ];

    /**
     * @var array|void
     */
    protected $routes = [];

    /**
     * @param string $iniFile
     * @param string $section
     */
    function __construct($iniFile, $section = null)
    {
        $this->routes = $this->prepareRoutes($iniFile, $section);
    }

    /**
     * @param string $iniFile
     * @param string $section
     *
     * @return array|void
     */
    protected function prepareRoutes($iniFile, $section = null)
    {
        $ini = new Ini($iniFile, $section);
        if (isset($ini->route)) {
            return $ini->route->toArray();
        }

        return [];
    }

    /**
     * @param \Yaf\Request_Abstract  $request
     * @param \Yaf\Response_Abstract $response
     */
    function routerStartup(\Yaf\Request_Abstract $request, \Yaf\Response_Abstract $response)
    {
        $router = Dispatcher::getInstance()->getRouter();
        foreach ($this->routes as $name => $params) {
            if (empty($params['type'])) {
                throw new \LogicException('路由的type配置节不能为空');
            }
            if (in_array($params['type'], self::$types)) {
                $router->addConfig([$name => $params]);
            } else {
                $className = array_shift($params);
                $route = $this->makeRoute($className, $params);
                $router->addRoute($name, $route);
            }
        }
    }

    /**
     * @param $className
     * @param $params
     */
    protected function makeRoute($className, $params)
    {
        $route = new $className;
        if (method_exists($route, 'init')) {
            $route->init($params);
        }
        return $route;
    }
}