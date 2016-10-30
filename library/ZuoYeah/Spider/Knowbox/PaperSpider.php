<?php
namespace ZuoYeah\Spider\Knowbox;

use ZuoYeah\Entity\Tag;
use ZuoYeah\Service\TagService;

class PaperSpider
{
    /**
     * @var string
     */
    protected static $urlWithoutToken = 'http://api.knowbox.cn/v1_tiku/paper/get?source=androidTeacher&version=1300&channel=Umeng&type=0&city=0&time=0&token=';
    /**
     * @var string
     */
    protected $token = '';

    protected $subject = '';

    /**
     * @var TagService
     */
    protected $tagService;

    function __construct($subject, $token)
    {
        $this->token = $token;
        $this->subject = $subject;

        $this->tagService = new TagService();
    }

    function gather()
    {
        $data = file_get_contents(self::$urlWithoutToken . $this->token);
        $json = json_decode($data, true);
        $list = $json['data'];
        $this->process($list);
    }

    protected function process(array $items)
    {
        foreach ($items as $item) {
            $this->tagService->saveTag(
                $this->subject,
                $item['name']
            );

            $qs = new PaperQuestionSpider(
                $this->subject,
                $this->token,
                $item['name']
            );

            $qs->gather($item['paperId']);

        }
    }
}