<?php
namespace Gram\Security\Encryption;

/**
 * Class Encrypter
 * @package Gram\Security\Encryption
 */
class Encrypter
{

    /**
     * Encrypt data
     *
     * This method will encrypt data using a given key, vector, and cipher.
     * By default, this will encrypt data using the RIJNDAEL/AES 256 bit cipher. You
     * may override the default cipher and cipher mode by passing your own desired
     * cipher and cipher mode as the final key-value array argument.
     *
     * @param  string $data     The unencrypted data
     * @param  string $key      The encryption key
     * @param  string $iv       The encryption initialization vector
     * @param  array  $settings Optional key-value array with custom algorithm and mode
     * @return string
     */
    public static function encrypt($data, $key, $iv, $settings = array())
    {
        if ($data === '' || !extension_loaded('mcrypt')) {
            return $data;
        }

        //Merge settings with defaults
        $defaults = array(
            'algorithm' => MCRYPT_RIJNDAEL_256,
            'mode' => MCRYPT_MODE_CBC
        );
        $settings = array_merge($defaults, $settings);

        //Get module
        $module = mcrypt_module_open($settings['algorithm'], '', $settings['mode'], '');

        //Validate IV
        $ivSize = mcrypt_enc_get_iv_size($module);
        if (strlen($iv) > $ivSize) {
            $iv = substr($iv, 0, $ivSize);
        }

        //Validate key
        $keySize = mcrypt_enc_get_key_size($module);
        if (strlen($key) > $keySize) {
            $key = substr($key, 0, $keySize);
        }

        //Encrypt value
        mcrypt_generic_init($module, $key, $iv);
        $res = @mcrypt_generic($module, $data);
        mcrypt_generic_deinit($module);

        return $res;
    }

    /**
     * Decrypt data
     *
     * This method will decrypt data using a given key, vector, and cipher.
     * By default, this will decrypt data using the RIJNDAEL/AES 256 bit cipher. You
     * may override the default cipher and cipher mode by passing your own desired
     * cipher and cipher mode as the final key-value array argument.
     *
     * @param  string $data     The encrypted data
     * @param  string $key      The encryption key
     * @param  string $iv       The encryption initialization vector
     * @param  array  $settings Optional key-value array with custom algorithm and mode
     * @return string
     */
    public static function decrypt($data, $key, $iv, $settings = array())
    {
        if ($data === '' || !extension_loaded('mcrypt')) {
            return $data;
        }

        //Merge settings with defaults
        $defaults = array(
            'algorithm' => MCRYPT_RIJNDAEL_256,
            'mode' => MCRYPT_MODE_CBC
        );
        $settings = array_merge($defaults, $settings);

        //Get module
        $module = mcrypt_module_open($settings['algorithm'], '', $settings['mode'], '');

        //Validate IV
        $ivSize = mcrypt_enc_get_iv_size($module);
        if (strlen($iv) > $ivSize) {
            $iv = substr($iv, 0, $ivSize);
        }

        //Validate key
        $keySize = mcrypt_enc_get_key_size($module);
        if (strlen($key) > $keySize) {
            $key = substr($key, 0, $keySize);
        }

        //Decrypt value
        mcrypt_generic_init($module, $key, $iv);
        $decryptedData = @mdecrypt_generic($module, $data);
        $res = rtrim($decryptedData, "\0");
        mcrypt_generic_deinit($module);

        return $res;
    }
}