<?php
namespace ZuoYeah\Spider\Ruyile;

use Gram\Utility\Helper\StringHelper;
use SebastianBergmann\Exporter\Exception;
use ZuoYeah\Entity\Bureau;
use ZuoYeah\Entity\Tag;
use ZuoYeah\Service\BureauService;
use ZuoYeah\Service\TagService;

class TownSpider
{
    /**
     * @var string
     */
    protected static $url = 'http://www.ruyile.com/jylb.aspx?id=';

    /**
     * @var BureauService
     */
    protected $bureauService;

    function __construct()
    {
        $this->bureauService = new BureauService();
    }

    function gather($cityId,$page=1)
    {
        $data = file_get_contents(self::$url.($cityId-1).'&p='.$page);
        $data =$this->string_get_inner($data,'<div class="xxlb">','<div class="dqlb">');
        $this->process(explode('<div class="sk">',$data),$cityId);
        if(strpos($data,'下一页')!==false){
            $this->gather($cityId,$page+1);
        };
    }

    protected function process(array $items,$cityId)
    {
        foreach ($items as $item) {
            $bureau = new Bureau();
            $bureau->parentId = $cityId;
            $bureau->id = intval($this->string_get_inner($item,'id=','"'))+1;
            if(!$bureau->id ){
                continue;
            }

            if($bureau->id == $cityId){
                continue;
            }

            $bureau->title = $this->string_get_inner($item,'blank">','</a>');
            if(!$bureau->title ){
                continue;
            }
            $bureau->depth=3;
            $bureau->zipCode = $this->string_get_inner($item,'邮编：','<br');
            try{
                $this->bureauService->create($bureau);
            }catch(\Exception $ex){
            }
        }
    }

    /**
     * 获取关键字中间的字符串
     */
    function string_get_inner($str, $prefix, $suffix) {
        $tokens = explode($prefix, $str);
        if (!isset($tokens[1])) {
            return '';
        }

        $tokens = explode($suffix, $tokens[1]);

        if (!isset($tokens[0])) {
            return '';
        }

        //过滤空白，包括全角空格
        return trim(str_replace('　', ' ', $tokens[0]));
    }
}