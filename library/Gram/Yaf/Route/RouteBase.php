<?php
namespace Gram\Yaf\Route;

use Yaf\Route_Interface;

/**
 * Class RouteBase
 * @package Gram\Yaf\Route
 */
abstract class RouteBase implements Route_Interface
{
    /**
     * @param array $config
     */
    function init(array $config)
    {
        foreach ($config as $key => $value) {
            if (!property_exists($this, $key)) {
                continue;
            }
            $this->{$key} = $value;
        }
    }
}