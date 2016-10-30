<?php
namespace ZuoYeah\Spider\Knowbox;

use ZuoYeah\Entity\CatalogQuestion;
use ZuoYeah\Entity\Question;
use ZuoYeah\Entity\QuestionOrigin;
use ZuoYeah\Entity\QuestionType;
use ZuoYeah\Entity\SubQuestion;
use ZuoYeah\Parser\Purifier;
use ZuoYeah\Service\CatalogQuestionService;
use ZuoYeah\Service\QuestionService;

class CatalogQuestionSpider extends QuestionSpider
{
    protected static $urlTemplate = 'http://api.knowbox.cn/v1_tiku/knowledge/question?source=androidTeacher&version=1003&token={token}&knowledge_id={knowID}&question_type=-1&collect=0&out=0&page_size=500&page_num={pageNum}';

    /**
     * @var CatalogQuestionService
     */
    protected $catalogQuestionService;
    protected $catalogId;
    protected $level;
    protected $pageNum = 0;

    function __construct($subject, $token, $tag, $catalogId, $level)
    {

        parent::__construct($subject, $token, $tag);
        $this->catalogId = $catalogId;
        $this->level = $level;
        $this->catalogQuestionService = new CatalogQuestionService();
    }


    protected function getUrl($knowId, $pageNum)
    {
        $url = str_replace('{token}', $this->token, self::$urlTemplate);
        $url = str_replace('{knowID}', $knowId, $url);
        return str_replace('{pageNum}', $pageNum, $url);
    }


    protected function process(array $items)
    {
        foreach ($items as $k => $item) {
            $question = $this->processItem($item);
            if (!$question) {
                continue;
            }

            $q = $this->catalogQuestionService->findByQuestionId($this->catalogId, $question->id);
            if (empty($q)) {
                $catalogQuestion = new CatalogQuestion();
                $catalogQuestion->types = $question->types;
                $catalogQuestion->catalogId = $this->catalogId;
                $catalogQuestion->questionId = $question->id;
                $catalogQuestion->subject = $this->subject;
                $catalogQuestion->level = $this->level;
                $this->catalogQuestionService->create($catalogQuestion);
            }
        }
    }

}
