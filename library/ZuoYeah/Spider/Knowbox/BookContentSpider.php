<?php
namespace ZuoYeah\Spider\Knowbox;

use ZuoYeah\Entity\BookContent;
use ZuoYeah\Service\BookContentService;

class BookContentSpider
{
    /**
     * @var string
     */
    protected static $urlWithoutToken = 'http://api.knowbox.cn/v1_tiku/teaching-assist/coursesection?source=androidTeacher&version=1300&channel=Umeng&token={token}&teachingassist_id={assistId}';
    /**
     * @var string
     */
    protected $token = '';

    protected $subject = '';
    protected $bookId = '';
    protected $continueContentId = 0;

    /**
     * @var BookContentService
     */
    protected $bookContentService;

    function __construct($subject, $token, $bookId,$continueContentId = 0)
    {
        $this->token = $token;
        $this->subject = $subject;
        $this->bookId = $bookId;
        $this->continueContentId = $continueContentId;

        $this->bookContentService = new BookContentService();
    }

    function gather($assistId)
    {
        $url = str_replace('{token}', $this->token, self::$urlWithoutToken);
        $url = str_replace('{assistId}', $assistId, $url);
        $data = file_get_contents($url);
        $json = json_decode($data, true);
        $list = $json['data']['list'];
        $contentId = $this->bookContentService->findRoot($this->bookId)->id;
        $this->process($list,$contentId);
    }

    protected function process(array $items, $parentId = 0)
    {
        foreach ($items as $item) {
            echo date('H:i:s ') . '开始处理：'. $item['sectionName'] .PHP_EOL;
            $origin = $item['courseSectionID'] . '@knowbox';
            $content = $this->bookContentService->findByOrigin($origin);
            if (empty($content)) {
                $content = new BookContent();
                $content->bookId = $this->bookId;
                $content->depth = $item['level'];
                $content->isIndex = true;
                $content->origin = $origin;
                $content->parentId = $parentId;
                $content->title = $item['sectionName'];
                $content->catalogId = 0;
                $content->page = 0;
                $content->orderId = $item['orderNum'];
                $this->bookContentService->create($content);
            } else if ($content->id < $this->continueContentId) {
                continue;
            }

            if (isset($item['list']) && !empty($item['list'])) {
                $this->process($item['list'], $content->id);
            } else {
                $qs = new BookQuestionSpider(
                    $this->subject,
                    $this->token,
                    $item['sectionName'],
                    $content->id,
                    $this->bookId
                );
                $qs->gather($item['courseSectionID']);
            }

            unset($item);
            unset($content);
        }
    }
}