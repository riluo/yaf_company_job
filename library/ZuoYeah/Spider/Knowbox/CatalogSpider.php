<?php
namespace ZuoYeah\Spider\Knowbox;

use ZuoYeah\Entity\Catalog;
use ZuoYeah\Entity\Tag;
use ZuoYeah\Service\CatalogService;
use ZuoYeah\Service\TagService;

class CatalogSpider
{
    /**
     * @var string
     */
    protected static $urlWithoutToken = 'http://api.knowbox.cn/v1_tiku/knowledge/list?source=androidTeacher&version=1003&token=';
    /**
     * @var string
     */
    protected $token = '';

    protected $subject = '';

    protected $level = '';
    protected $continueCatalogId = 0;

    /**
     * @var TagService
     */
    protected $tagService;
    /**
     * @var CatalogService
     */
    protected $catalogService;

    function __construct($subject, $token,$level, $continueCatalogId = 0)
    {
        $this->token = $token;
        $this->subject = $subject;
        $this->level = $level;
        $this->continueCatalogId = $continueCatalogId;

        $this->tagService = new TagService();
        $this->catalogService = new CatalogService();
    }

    function gather()
    {
        $data = file_get_contents(self::$urlWithoutToken . $this->token);
        $json = json_decode($data, true);
        $list = $json['data']['list'];
        $this->process($list);

        echo  date('H:i:s ') . '处理完毕'. PHP_EOL;
    }

    protected function process(array $items, $parentId = 0)
    {
        foreach ($items as $item) {

            echo  date('H:i:s ') . '开始处理：' . $item['knowledgeName'] . PHP_EOL;
            $origin = $item['knowID'] . '@knowbox';

            $catalog = $this->catalogService->findByOrigin($origin);

            if(empty($catalog) && $parentId==0){
                //第一级知识点一样的话，直接认为是相同的
                $catalog = $this->catalogService->findFirstLevelByTitle(
                    $this->subject,$this->level,$item['knowledgeName']);
            }

            if (empty($catalog)) {
                $catalog = new Catalog();
                $catalog->title = $item['knowledgeName'];
                $catalog->subject = $this->subject;
                $catalog->origin = $origin;
                $catalog->level = $this->level;
                $catalog->depth = $item['level'];
                $catalog->parentId = $parentId;
                $catalog->orderId = $item['orderNum'];

                $this->catalogService->create($catalog);

                $this->tagService->saveTag(
                    $this->subject,
                    $item['knowledgeName']
                );

            } else if ($catalog->id < $this->continueCatalogId) {
                continue;
            }


            if (isset($item['list']) && !empty($item['list'])) {
                $this->process($item['list'],$catalog->id);
            } else {


                $qs = new CatalogQuestionSpider(
                    $this->subject,
                    $this->token,
                    $item['knowledgeName'],
                    $catalog->id,
                    $this->level
                );
                $qs->gather($item['knowID']);
            }
        }
    }
}