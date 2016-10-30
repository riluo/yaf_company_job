<?php
namespace ZuoYeah\Yaf\Plugin;

use Doctrine\Common\Cache\RedisCache;
use Gram\Security\Authentication;
use Gram\Utility\Helper\ArrayHelper;
use Gram\Utility\Helper\StringHelper;
use Symfony\Component\HttpFoundation\Request;
use Yaf\Config\Ini;
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;
use Gram\Utility\Helper\ThrowHelper;
use Yaf\Session;
use ZuoYeah\Entity\ErrorCode;

class AntiCrawlPlugin extends Plugin_Abstract
{


    public $options = ['5' => 10, '60' => 100];
    public $_redis;
    public $_redisOption;

    /**
     * 通配符
     */
    const WILDCARDS = '*';

    /**
     * @var array 权限配置列表
     */
    protected $rules;


    function __construct($redisOption, $iniFile, $section = null)
    {

        $this->rules = $this->prepareConfig($iniFile, $section);
        $this->initRedis($redisOption);

    }

    function initRedis($redisOption)
    {
        $this->_redisOption = $redisOption;

        if (empty($redisOption['host'])) {
            $this->_redisOption['host'] = '127.0.0.1';
        }

        if (empty($redisOption['port'])) {
            $this->_redisOption['port'] = 6379;
        }
        if (empty($redisOption['timeout'])) {
            $this->_redisOption['timeout'] = 2.5;
        }
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

    public function routerShutdown(Request_Abstract $request, Response_Abstract $response)
    {
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        $rule = $this->getRule($module, $controller, $action);

        if (empty($rule['enabled']) || empty($rule['options'])) {
            return;
        }

        $this->options = $rule['options'];

        if (!extension_loaded('redis')) {
            return;
        }

        $this->_redis = new \Redis();
        $ok = @$this->_redis->connect(
            $this->_redisOption['host'],
            $this->_redisOption['port'],
            $this->_redisOption['timeout']);

        if (empty($ok)) {
            return;
        }


        $key = $this->gemKey($request);

        $this->checkFrequency($key);
    }

    protected function gemKey(Request_Abstract $request)
    {
        $uri = $request->getRequestUri();
        $key = $uri;

        $module = $request->getModuleName();
        if(StringHelper::startWith($module,"Api_")){
            $token = $this->getToken($request);
        }
        else{
            if (isset($_COOKIE[Authentication::SESSION_NAMESPACE])) {
                $auth = $_COOKIE[Authentication::SESSION_NAMESPACE];
            }
        }

        if(!empty($token)){
            $key =  $key . $token;
        }
        else if(!empty($auth)){
            $key =  $key . $auth;
        }
        else{
            $ip = $this->getRealIp();
            $key =  $key . $ip;
        }

        $key = md5($key);

        return $key;
    }

    /**
     * 获取匹配的规则
     *
     * @param $module
     * @param $controller
     * @param $action
     * @return array|null
     */
    protected function getRule($module, $controller, $action)
    {
        $rule = [];

        if (!empty($this->rules[self::WILDCARDS])) {
            $rule = $this->mergeAll($rule, $this->rules[self::WILDCARDS]);
        }

        if (!empty($this->rules[$module])) {
            $rule = $this->mergeAll($rule, $this->rules[$module]);
        }

        if (!empty($this->rules[$module][self::WILDCARDS])) {
            $rule = $this->mergeAll($rule, $this->rules[$module][self::WILDCARDS]);
        }

        if (!empty($this->rules[$module][$controller])) {
            $rule = $this->mergeAll($rule, $this->rules[$module][$controller]);
        }

        if (!empty($this->rules[$module][$controller][self::WILDCARDS])) {
            $rule = $this->mergeAll($rule, $this->rules[$module][$controller][self::WILDCARDS]);
        }

        if (!empty($this->rules[$module][$controller][$action])) {
            $rule = $this->mergeAll($rule, $this->rules[$module][$controller][$action]);
        }
        return $rule;
    }

    function getRealIp()
    {
        $ip = false;
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("/^(10|172.16|192.168)./", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    function checkFrequency($identity)
    {
        $frequency = $this->getCache($identity);
        if (empty($frequency)) {

            $frequency = ['t' => time()];
            foreach ($this->options as $key => $v) {
                $frequency[$key] = 0;
            }
        }

        $safe = $this->checkFrequencyDetail($frequency);

        $this->setCache($identity, $frequency);
        ThrowHelper::ifFalse($safe, '请求太频繁,请稍后重试!', ErrorCode::COMMON_FREQUENCY);
    }

    function checkFrequencyDetail(&$frequency)
    {
        $time = time();
        $second = $time - $frequency['t'];

        if ($second < 0) {
            return false;
        }

        foreach ($this->options as $key => $limit) {
            if (empty($frequency[$key])) {
                $frequency[$key] = 0;
            }
            $frequency[$key]++;
            $frequency[$key] = $frequency[$key] * ($key - $second) / $key;
            if ($frequency[$key] <= 0) {
                $frequency[$key] = 1;
            }

            if ($frequency[$key] > $limit) {
                $frequency['t'] = $time + $key;//超过多少秒限制,就禁止多少秒
                return false;
            }
        }

        $frequency['t'] = $time;

        return true;
    }

    function getCache($key)
    {
        $redisCache = new RedisCache();
        $redisCache->setRedis($this->_redis);
        $cache = @$redisCache->fetch('anticraw_' . $key);
        return json_decode($cache, true);
    }

    function setCache($key, $value)
    {
        $redisCache = new RedisCache();
        $redisCache->setRedis($this->_redis);
        $redisCache->save('anticraw_' . $key, json_encode($value), 3600 * 24);
    }


    /**
     * @param Request_Abstract $request
     *
     * @return mixed
     */
    function getToken(Request_Abstract $request)
    {
        $token = $request->getServer('HTTP_TOKEN');
        if (is_null($token)) {
            $token = $request->getQuery('TOKEN');
            if (is_null($token)) {
                $token = $request->getPost('TOKEN');
                return $token;
            }
            return $token;
        }
        return $token;
    }

    function mergeAll($json1, $json2)
    {
        if (empty($json1)) return $json2;
        if (isset($json1[0]) || isset($json2[0])) {
            return array_merge($json1, $json2);
        }
        foreach ($json2 as $key => $value) {
            if (is_array($value)) {
                if (empty($json1[$key])) {
                    $json1[$key] = [];
                }
                $json1[$key] = self::mergeAll($json1[$key], $value);
            } else {
                $json1[$key] = $value;
            }
        }
        return $json1;
    }
}