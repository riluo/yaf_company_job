<?php
namespace ZuoYeah\Spider\Knowbox;

use ZuoYeah\Entity\BookQuestion;
use ZuoYeah\Entity\Question;
use ZuoYeah\Entity\QuestionOrigin;
use ZuoYeah\Entity\QuestionType;
use ZuoYeah\Entity\SubQuestion;
use ZuoYeah\Parser\Purifier;
use ZuoYeah\Service\BookQuestionService;
use ZuoYeah\Service\QuestionService;

class BookQuestionSpider extends QuestionSpider
{
    protected static $urlTemplate = 'http://api.knowbox.cn/v1_tiku/course-section/question?source=androidTeacher&version=1300&channel=Umeng&token={token}}&coursesection_id={sectionId}&question_type=-1&collect=0&out=0&page_size=10&page_num={pageNum}';


    /**
     * @var BookQuestionService
     */
    protected $bookQuestionService;
    protected $contentId;
    protected $bookId;
    protected $pageNum = 0;

    function __construct($subject, $token, $tag,$contentId,$bookId)
    {

        parent::__construct($subject,$token,$tag);
        $this->contentId = $contentId;
        $this->bookId = $bookId;
        $this->bookQuestionService = new BookQuestionService();
    }


    protected function getUrl($sectionId, $pageNum)
    {
        $this->pageNum = $pageNum;
        $url = str_replace('{token}', $this->token, self::$urlTemplate);
        $url = str_replace('{sectionId}', $sectionId, $url);
        return str_replace('{pageNum}', $pageNum, $url);
    }



    protected function process(array $items)
    {
        foreach ($items as $k => $item) {
            $origin = $item['questionID'] . '@knowboxBook';
            echo date('H:i:s ') . '开始处理：'. $origin .PHP_EOL;
            $question = $this->service->findByOrigin($origin);
            if (!empty($question)) {
                $sub = $question->subItems->first();
                if (!in_array($this->tag, $sub->tags)) {
                    array_push($sub->tags, $this->tag);
                }

                if($item['questionNo']){
                    $sub->number = $item['questionNo'];
                }
                else{
                    $sub->number = $this->pageNum*10+$k+1;
                }

                $sub->body = $this->addHostToImageSrc($sub->body);
                $sub->answer = $this->addHostToImageSrc($sub->answer);
                $sub->explanation = $this->addHostToImageSrc($sub->explanation);
                $this->service->update($question);
            }
            else{
                $question = new Question();
                $sub = new SubQuestion();

                if($item['questionNo']){
                    $sub->number = $item['questionNo'];
                }
                else{
                    $sub->number = $this->pageNum*10+$k+1;
                }

                $sub->body = $this->purify($question, $item['content']);
                $sub->answer = $this->purify($question, $item['rightAnswer']);
                $sub->explanation = $this->purify($question, $item['answerExplain']);
                $sub->tags = [$this->tag];
                $sub->type = $this->getType($item['questionType']);
                if ($sub->isChoice()) {
                    $sub->optionCount = 4;
                    for ($i = 66; $i < 80; $i++) {
                        if (strpos($sub->body, chr($i) . '．') > 0
                            || strpos($sub->body, chr($i) . '.') > 0
                            || strpos($sub->body, chr($i) . '<') > 0
                        ) {
                            $sub->optionCount = $i - 64;
                        } else {
                            break;
                        }
                    }

//                    if( $sub->optionCount != 4){
//                        echo json_encode($item);
//                    }

                }else if(!$sub->answer){
                    $sub->answer = '略';
                }
                $question->originType = QuestionOrigin::EXERCISE;
                $question->origin = $origin;
                $question->subject = $this->subject;
                $question->subItems->append($sub);
                try
                {
                    $this->service->create($question);
                }
                catch(\Exception $ex){
                    echo json_encode($item);
                    continue;
                }
            }

            $q = $this->bookQuestionService->findByQuestionIds([$question->id]);
            if(empty($q)){
                $bookQuestion = new BookQuestion();
                $bookQuestion->bookId=$this->bookId;
                $bookQuestion->contentId = $this->contentId;
                if($item['questionNo']){
                    $bookQuestion->number = $item['questionNo'];
                    $bookQuestion->orderId = $item['questionNo'];
                }
                else{
                    $bookQuestion->number = $this->pageNum*10+$k+1;
                    $bookQuestion->orderId = $this->pageNum*10+$k;
                }
                $bookQuestion->page = 0;
                $bookQuestion->questionId = $question->id;
                $this->bookQuestionService->createRaw($bookQuestion);
            }
        }
    }

}
