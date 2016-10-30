<?php
namespace ZuoYeah\Spider\Ruyile;

use Gram\Utility\Helper\StringHelper;
use ZuoYeah\Entity\Bureau;
use ZuoYeah\Entity\Tag;
use ZuoYeah\Service\BureauService;
use ZuoYeah\Service\TagService;

class CitySpider
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

    function gather($provinceId)
    {
        $data = file_get_contents(self::$url.($provinceId-1));
        $data =$this->string_get_inner($data,'<strong>城市</strong>','/div></div>');
        $this->process(explode('a><a',$data),$provinceId);
    }

    protected function process(array $items,$provinceId)
    {
        foreach ($items as $item) {
            $bureau = new Bureau();
            $bureau->parentId = $provinceId;
            $bureau->id = intval($this->string_get_inner($item,'id=','"'))+1;
            if($bureau->id == $provinceId){
                continue;
            }
            $bureau->depth=2;
            $bureau->title = $this->string_get_inner($this->string_get_inner($item,'id=','/'),'>','<');

            try{
                $this->bureauService->create($bureau);
            }catch(\Exception $ex){
            }
            $city = new TownSpider();
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