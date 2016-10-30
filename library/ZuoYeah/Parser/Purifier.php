<?php
namespace ZuoYeah\Parser;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Class Purifier
 * @package Gram\Parser
 */
class Purifier
{
    /**
     * @var HTMLPurifier
     */
    protected $purifier;

    /**
     *
     */
    function __construct()
    {
        $this->purifier = new HTMLPurifier($this->createConfig());
    }

    /**
     * @return HTMLPurifier_Config
     */
    protected function createConfig()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed',
            'b,i,u,strong,em,br,' .
            'p,span[style],ol[style],ul[style],li,sub,sup,' .
            'table[border],caption,col,colgroup,thead,tbody,tfoot,tr,td[rowspan|colspan],th[rowspan|colspan],'.
            'img[align|border|height|src|width|style]');
        $config->set('HTML.SafeIframe', false);
        $config->set('HTML.SafeEmbed', false);
        //$config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        //$config->set('AutoFormat.RemoveSpansWithoutAttributes', true);
        //$config->set('AutoFormat.RemoveEmpty', true);
        return $config;
    }


    /**
     * 清洗html结构
     *
     * @param $content
     *
     * @return string
     */
    function purify($content)
    {
        return $this->purifier->purify($content);
    }
}