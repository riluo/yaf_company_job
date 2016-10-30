<?php
namespace ZuoYeah\Spider\Ruyile;

use Gram\Utility\Helper\StringHelper;
use ZuoYeah\Entity\Bureau;
use ZuoYeah\Entity\Tag;
use ZuoYeah\Service\BureauService;
use ZuoYeah\Service\TagService;

class ProvinceSpider
{
    /**
     * @var string
     */
    protected static $url = 'http://www.ruyile.com/jylb.aspx';

    /**
     * @var BureauService
     */
    protected $bureauService;

    function __construct()
    {
        $this->bureauService = new BureauService();
    }

    function gather()
    {
        $data = file_get_contents(self::$url);
        $data =$this->string_get_inner($data,'<div class="dqlb">','/div></div>');
        $this->process(explode('a><a',$data));
    }

    protected function process(array $items)
    {
        foreach ($items as $item) {
            $bureau = new Bureau();

            $bureau->depth=1;
            $bureau->parentId = 1;
            $bureau->id = intval($this->string_get_inner($item,'id=','"'))+1;
            $bureau->title = $this->string_get_inner($this->string_get_inner($item,'id=','/'),'>','<');

            try{
                $this->bureauService->create($bureau);
            }catch(\Exception $ex){
            }
            $city = new CitySpider();
            $city->gather($bureau->id);
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