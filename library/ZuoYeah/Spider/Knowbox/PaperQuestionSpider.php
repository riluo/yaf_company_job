<?php
namespace ZuoYeah\Spider\Knowbox;

use ZuoYeah\Entity\Question;
use ZuoYeah\Entity\QuestionOrigin;
use ZuoYeah\Entity\QuestionType;
use ZuoYeah\Entity\SubQuestion;
use ZuoYeah\Parser\Purifier;
use ZuoYeah\Service\QuestionService;

class PaperQuestionSpider extends QuestionSpider
{
    protected static $urlTemplate = 'http://api.knowbox.cn/v1_tiku/paper/get-questions?source=androidTeacher&version=1300&channel=Umeng&token={token}&paper_id={paperId}&question_type=-1&collect=0&out=0&page_size=10&page_num={pageNum}';

    protected function getUrl($paperId, $pageNum)
    {
        $url = str_replace('{token}', $this->token, self::$urlTemplate);
        $url = str_replace('{paperId}', $paperId, $url);
        return str_replace('{pageNum}', $pageNum, $url);
    }

}
