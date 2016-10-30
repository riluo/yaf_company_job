<?php
namespace Gram\Utility\Helper;

class DebugHelper
{
    static function log($msg)
    {
        if (!is_string($msg)) {
            $msg = json_encode($msg);
        }
        echo Date('H:i:s ') . $msg . PHP_EOL;
    }
}