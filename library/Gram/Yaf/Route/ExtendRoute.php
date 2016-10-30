<?php
namespace Gram\Yaf\Route;

/**
 * Class ExtendRoute
 * @package Gram\Yaf\Route
 */
class ExtendRoute extends RouteBase
{
    /**
     * @var string
     */
    protected $match;

    /**
     * @var array
     */
    protected $route = [];
    /**
     * @var array Conditions for this route's URL parameters
     */
    protected $conditions = [];
    /**
     * @var array Key-value array of URL parameters
     */
    protected $params = [];
    /**
     * @var array value array of URL parameter names
     */
    protected $paramNames = [];
    /**
     * @var array key array of URL parameter names with + at the end
     */
    protected $paramNamesPath = [];
    /**
     * @var bool Whether or not this route should be matched in a case-sensitive manner
     */
    protected $caseSensitive = true;

    /**
     * <p><b>\Yaf\Route_Interface::route()</b> is the only method that a custom route should implement.</p><br/>
     * <p>if this method return TRUE, then the route process will be end. otherwise,\Yaf\Router will call next route in
     * the route stack to route request.</p><br/>
     * <p>This method would set the route result to the parameter request, by calling
     * \Yaf\Request_Abstract::setControllerName(), \Yaf\Request_Abstract::setActionName() and
     * \Yaf\Request_Abstract::setModuleName().</p><br/>
     * <p>This method should also call \Yaf\Request_Abstract::setRouted() to make the request routed at last.</p>
     *
     * @link http://www.php.net/manual/en/yaf-route-interface.route.php
     *
     * @param \Yaf\Request_Abstract $request
     *
     * @return bool
     */
    function route($request)
    {
        if (!$this->matches($request->getRequestUri())) {
            return false;
        }

        $request->setModuleName($this->getModuleName());
        $request->setControllerName($this->getControllerName());
        $request->setActionName($this->getActionName());
        $request->setParam(array_merge($_GET, $this->params));
        $request->setRouted();

        return true;
    }

    /**
     * <p><b>\Yaf\Route_Interface::assemble()</b> - assemble a request<br/>
     * <p>this method returns a url according to the argument info, and append query strings to the url according to
     * the argument query.</p>
     * <p>a route should implement this method according to its own route rules, and do a reverse progress.</p>
     *
     * @link http://www.php.net/manual/en/yaf-route-interface.assemble.php
     *
     * @param array $info
     * @param array $query
     *
     * @return bool
     */
    function assemble(array $info, array $query = null)
    {
        return false;
    }


    /**
     * 获取模块名称
     * @return string|null
     */
    protected function getModuleName()
    {
        return $this->getParamValue('module');
    }

    /**
     * 获取控制器名称
     * @return string|null
     */
    protected function getControllerName()
    {
        return $this->getParamValue('controller');
    }

    /**
     * @return string
     */
    protected function getActionName()
    {
        return $this->getParamValue('action');
    }

    /**
     * 从route中解析或默认参数中取值
     *
     * @param $paramName
     *
     * @return null
     */
    private function getParamValue($paramName)
    {
        if (empty($this->params[$paramName])) {
            return !empty($this->route[$paramName])
                ? $this->route[$paramName]
                : null;
        }

        return $this->params[$paramName];
    }

    /**
     * Matches URI?
     *
     * Parse this route's pattern, and then compare it to an HTTP resource URI
     * This method was modeled after the techniques demonstrated by Dan Sosedoff at:
     *
     * http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
     *
     * @param  string $resourceUri A Request URI
     *
     * @return bool
     */
    protected function matches($resourceUri)
    {
        //Convert URL params into regex patterns, construct a regex for this route, init params
        $patternAsRegex = preg_replace_callback(
            '#:([\w]+)\+?#',
            [$this, 'matchesCallback'],
            str_replace(')', ')?', (string)$this->match)
        );
        if (substr($this->match, -1) === '/') {
            $patternAsRegex .= '?';
        }
        $regex = '#^' . $patternAsRegex . '$#';
        if ($this->caseSensitive === false) {
            $regex .= 'i';
        }
        //Cache URL params' names and values if this route matches the current HTTP request
        if (!preg_match($regex, $resourceUri, $paramValues)) {
            return false;
        }
        foreach ($this->paramNames as $name) {
            if (isset($paramValues[$name])) {
                if (isset($this->paramNamesPath[$name])) {
                    $this->params[$name] = explode('/', urldecode($paramValues[$name]));
                } else {
                    $this->params[$name] = urldecode($paramValues[$name]);
                }
            }
        }

        return true;
    }

    /**
     * Convert a URL parameter (e.g. ":id", ":id+") into a regular expression
     *
     * @param  array $m URL parameters
     *
     * @return string       Regular expression for URL parameter
     */
    protected function matchesCallback($m)
    {
        $this->paramNames[] = $m[1];
        if (isset($this->conditions[$m[1]])) {
            return '(?P<' . $m[1] . '>' . $this->conditions[$m[1]] . ')';
        }
        if (substr($m[0], -1) === '+') {
            $this->paramNamesPath[$m[1]] = 1;

            return '(?P<' . $m[1] . '>.+)';
        }

        return '(?P<' . $m[1] . '>[^/]+)';
    }

}