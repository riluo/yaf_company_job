<?php
namespace Gram\Security;

use Gram\Security\Encryption\Cookies;
use Gram\Security\Principal\Identity;

/**
 * Class Authentication
 * @package Gram\Security
 */
class Authentication
{
    const SESSION_NAMESPACE = 'auth';
    static $secret = "tiplus_secret";
    static $algorithm = MCRYPT_RIJNDAEL_256;
    static $mode = MCRYPT_MODE_CBC;


    /**
     * 初始化加密密钥
     * @param $secret
     */
    static function initSecret($secret)
    {
        self::$secret = $secret;
    }

    /**
     * 持久化用户登陆的授权票据
     *
     * @param Identity $identity
     * @param bool $rememberMe
     */
    static function login(Identity $identity, $rememberMe = false, $path = '/')
    {
        $data = ['name' => $identity->getName(), 'roles' => $identity->getRoles(), 'version' => $identity->getVersion()];
        $json = json_encode($data);
        $value = Cookies::encodeSecureCookie($json, !$rememberMe ? time() + 3600 * 24 : time() + 3600 * 24 * 365, self::$secret, self::$algorithm, self::$mode);
        setcookie(self::SESSION_NAMESPACE, urlencode($value), $rememberMe ? time() + 3600 * 24 * 365 : 0, $path, null, false, !$rememberMe);
    }

    /**
     * 清除用户登陆的票据
     */
    static function logout($path = '/')
    {
        setcookie(self::SESSION_NAMESPACE, '', strtotime('-1 days'), $path, null, false, true);
    }

    /**
     * 检查用户是否已经登陆
     *
     * @return bool
     */
    static function isAuthenticated()
    {
        if (!isset($_COOKIE[self::SESSION_NAMESPACE])) {
            return false;
        }
        $authInfo = urldecode($_COOKIE[self::SESSION_NAMESPACE]);
        return !!Cookies::decodeSecureCookie($authInfo, self::$secret, self::$algorithm, self::$mode);
    }

    /**
     * 获取当前已经登陆的用户票据
     *
     * @return \Gram\Security\Principal\Identity|null
     */
    static function getUser()
    {
        if (!isset($_COOKIE[self::SESSION_NAMESPACE])) {
            return null;
        }
        $authInfo = urldecode($_COOKIE[self::SESSION_NAMESPACE]);
        $cookie = Cookies::decodeSecureCookie($authInfo, self::$secret, self::$algorithm, self::$mode);
        if (!$cookie) {
            return null;
        }

        $json = json_decode($cookie, true);
        if(!isset($json['version'])){
            $json['version'] = '';
        }
        $identity = new Identity($json['name'], $json['roles'], $json['version']);
        return $identity;
    }
}