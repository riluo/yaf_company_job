<?php
namespace Gram\Security\Encryption;

interface EncryptInterface
{
    /**
     * Encrypt the given value.
     *
     * @param  string $value
     *
     * @return string
     */
    function encrypt($value);

    /**
     * Decrypt the given value.
     *
     * @param  string $value
     *
     * @return string
     */
    function decrypt($value);

    /**
     * Set the encryption mode.
     *
     * @param  string $mode
     *
     * @return void
     */
    function setMode($mode);

    /**
     * Set the encryption cipher.
     *
     * @param  string $cipher
     *
     * @return void
     */
    function setCipher($cipher);
}