<?php

namespace Gram\Security\Encryption;

class Cookies
{
    /**
     * Encode secure cookie value
     *
     * This method will create the secure value of an HTTP cookie. The
     * cookie value is encrypted and hashed so that its value is
     * secure and checked for integrity when read in subsequent requests.
     *
     * @param string $value     The insecure HTTP cookie value
     * @param int    $expires   The UNIX timestamp at which this cookie will expire
     * @param string $secret    The secret key used to hash the cookie value
     * @param int    $algorithm The algorithm to use for encryption
     * @param int    $mode      The algorithm mode to use for encryption
     * @return string
     */
    public static function encodeSecureCookie($value, $expires, $secret, $algorithm, $mode)
    {
        $key = hash_hmac('sha1', (string) $expires, $secret);
        $iv = self::getIv($expires, $secret);
        $secureString = base64_encode(
            Encrypter::encrypt(
                $value,
                $key,
                $iv,
                array(
                    'algorithm' => $algorithm,
                    'mode' => $mode
                )
            )
        );
        $verificationString = hash_hmac('sha1', $expires . $value, $key);
        return implode('|', array($expires, $secureString, $verificationString));
    }

    /**
     * Decode secure cookie value
     *
     * This method will decode the secure value of an HTTP cookie. The
     * cookie value is encrypted and hashed so that its value is
     * secure and checked for integrity when read in subsequent requests.
     *
     * @param string $value     The secure HTTP cookie value
     * @param string $secret    The secret key used to hash the cookie value
     * @param int    $algorithm The algorithm to use for encryption
     * @param int    $mode      The algorithm mode to use for encryption
     * @return bool|string
     */
    public static function decodeSecureCookie($value, $secret, $algorithm, $mode)
    {
        if ($value) {
            $value = explode('|', $value);
            if (count($value) === 3 && ((int) $value[0] === 0 || (int) $value[0] > time())) {
                $key = hash_hmac('sha1', $value[0], $secret);
                $iv = self::getIv($value[0], $secret);
                $data = Encrypter::decrypt(
                    base64_decode($value[1]),
                    $key,
                    $iv,
                    array(
                        'algorithm' => $algorithm,
                        'mode' => $mode
                    )
                );
                $verificationString = hash_hmac('sha1', $value[0] . $data, $key);
                if ($verificationString === $value[2]) {
                    return $data;
                }
            }
        }

        return false;
    }

    /**
     * Set HTTP cookie header
     *
     * This method will construct and set the HTTP `Set-Cookie` header. Slim
     * uses this method instead of PHP's native `setcookie` method. This allows
     * more control of the HTTP header irrespective of the native implementation's
     * dependency on PHP versions.
     *
     * This method accepts the Slim_Http_Headers object by reference as its
     * first argument; this method directly modifies this object instead of
     * returning a value.
     *
     * @param  array  $header
     * @param  string $name
     * @param  string $value
     */
    public static function setCookieHeader(&$header, $name, $value)
    {
        //Build cookie header
        if (is_array($value)) {
            $domain = '';
            $path = '';
            $expires = '';
            $secure = '';
            $httpOnly = '';
            if (isset($value['domain']) && $value['domain']) {
                $domain = '; domain=' . $value['domain'];
            }
            if (isset($value['path']) && $value['path']) {
                $path = '; path=' . $value['path'];
            }
            if (isset($value['expires'])) {
                if (is_string($value['expires'])) {
                    $timestamp = strtotime($value['expires']);
                } else {
                    $timestamp = (int) $value['expires'];
                }
                if ($timestamp !== 0) {
                    $expires = '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
                }
            }
            if (isset($value['secure']) && $value['secure']) {
                $secure = '; secure';
            }
            if (isset($value['httpOnly']) && $value['httpOnly']) {
                $httpOnly = '; HttpOnly';
            }
            $cookie = sprintf('%s=%s%s', urlencode($name), urlencode((string) $value['value']), $domain . $path . $expires . $secure . $httpOnly);
        } else {
            $cookie = sprintf('%s=%s', urlencode($name), urlencode((string) $value));
        }

        //Set cookie header
        if (!isset($header['Set-Cookie']) || $header['Set-Cookie'] === '') {
            $header['Set-Cookie'] = $cookie;
        } else {
            $header['Set-Cookie'] = implode("\n", array($header['Set-Cookie'], $cookie));
        }
    }

    /**
     * Delete HTTP cookie header
     *
     * This method will construct and set the HTTP `Set-Cookie` header to invalidate
     * a client-side HTTP cookie. If a cookie with the same name (and, optionally, domain)
     * is already set in the HTTP response, it will also be removed. Slim uses this method
     * instead of PHP's native `setcookie` method. This allows more control of the HTTP header
     * irrespective of PHP's native implementation's dependency on PHP versions.
     *
     * This method accepts the Slim_Http_Headers object by reference as its
     * first argument; this method directly modifies this object instead of
     * returning a value.
     *
     * @param  array  $header
     * @param  string $name
     * @param  array  $value
     */
    public static function deleteCookieHeader(&$header, $name, $value = array())
    {
        //Remove affected cookies from current response header
        $cookiesOld = array();
        $cookiesNew = array();
        if (isset($header['Set-Cookie'])) {
            $cookiesOld = explode("\n", $header['Set-Cookie']);
        }
        foreach ($cookiesOld as $c) {
            if (isset($value['domain']) && $value['domain']) {
                $regex = sprintf('@%s=.*domain=%s@', urlencode($name), preg_quote($value['domain']));
            } else {
                $regex = sprintf('@%s=@', urlencode($name));
            }
            if (preg_match($regex, $c) === 0) {
                $cookiesNew[] = $c;
            }
        }
        if ($cookiesNew) {
            $header['Set-Cookie'] = implode("\n", $cookiesNew);
        } else {
            unset($header['Set-Cookie']);
        }

        //Set invalidating cookie to clear client-side cookie
        self::setCookieHeader($header, $name, array_merge(array('value' => '', 'path' => null, 'domain' => null, 'expires' => time() - 100), $value));
    }

    /**
     * Parse cookie header
     *
     * This method will parse the HTTP request's `Cookie` header
     * and extract cookies into an associative array.
     *
     * @param  string
     * @return array
     */
    public static function parseCookieHeader($header)
    {
        $cookies = array();
        $header = rtrim($header, "\r\n");
        $headerPieces = preg_split('@\s*[;,]\s*@', $header);
        foreach ($headerPieces as $c) {
            $cParts = explode('=', $c, 2);
            if (count($cParts) === 2) {
                $key = urldecode($cParts[0]);
                $value = urldecode($cParts[1]);
                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }

    /**
     * Generate a random IV
     *
     * This method will generate a non-predictable IV for use with
     * the cookie encryption
     *
     * @param  int    $expires The UNIX timestamp at which this cookie will expire
     * @param  string $secret  The secret key used to hash the cookie value
     * @return string Hash
     */
    private static function getIv($expires, $secret)
    {
        $data1 = hash_hmac('sha1', 'a'.$expires.'b', $secret);
        $data2 = hash_hmac('sha1', 'z'.$expires.'y', $secret);

        return pack("h*", $data1.$data2);
    }
}
