<?php
namespace ZuoYeah\Spider\Knowbox;

use ZuoYeah\Entity\Question;
use ZuoYeah\Entity\QuestionOrigin;
use ZuoYeah\Entity\QuestionType;
use ZuoYeah\Entity\SubQuestion;
use ZuoYeah\Parser\Purifier;
use ZuoYeah\Service\QuestionService;

class QuestionSpider
{
    protected static $urlTemplate = '';
    protected static $imagePattern = '/<img.*?src=[\'"](?P<src>.*?)[\'"].*?>/';
    protected $subject = '';
    protected $token = '';
    protected $tag = '';
    /**
     * @var Purifier
     */
    protected $purifier;
    protected $service;

    function __construct($subject, $token, $tag)
    {
        $this->subject = $subject;
        $this->token = $token;
        $this->tag = $tag;

        $this->purifier = new Purifier();
        $this->service = new QuestionService();
    }

    function gather($knowId)
    {
        $pageNum = 0;
        $totalPageNum = 0;
        $fp = fopen('error.log', 'a');
        do {
            $url = $this->getUrl($knowId, $pageNum);
            $data = file_get_contents($url);
            $json = json_decode($data, true);
            unset($data);
            if ($totalPageNum == 0) {
                $totalPageNum = (int)$json['data']['totalPageNum'];
            }
            $list = $json['data']['list'];
            if (is_array($list)) {
                $this->process($list);
            } else {
                fwrite($fp, $knowId . '@' . $url . ':' . json_encode($list));
            }
            $pageNum++;
        } while ($pageNum < $totalPageNum);
        fclose($fp);
    }

    protected function getUrl($knowId, $pageNum)
    {
        throw new \Exception('请重载本方法@' );
    }

    protected function getType($questionType)
    {
        switch ($questionType) {
            case 0:
                return QuestionType::SINGLE_CHOICE;
            case 1:
                return QuestionType::MULTIPLE_CHOICE;
            case 2:
                return QuestionType::SHORT_ANSWER;
            case 5://完形
                return QuestionType::SHORT_ANSWER;
            case 6://阅读题 归为简答题
                return QuestionType::SHORT_ANSWER;
            case 8://作文
                return QuestionType::COMPOSITION;
            default:
                throw new \Exception('题型未能正确转换@' . $questionType);
        }
    }

    protected function process(array $items)
    {
        foreach ($items as $item) {
           $this->processItem($item);
        }
    }

    protected function processItem($item){
        $origin = $item['questionID'] . '@knowbox';
        echo date('H:i:s ') . '开始处理：'. $origin .PHP_EOL;
        $exist = $this->service->findByOrigin($origin);
        if (!empty($exist)) {
            $sub = $exist->subItems->first();
            if (!in_array($this->tag, $sub->tags)) {
                array_push($sub->tags, $this->tag);
            }
            $sub->body = $this->addHostToImageSrc($sub->body);
            $sub->answer = $this->addHostToImageSrc($sub->answer);
            $sub->explanation = $this->addHostToImageSrc($sub->explanation);
            $this->service->update($exist);
            return $exist;
        }

        $question = new Question();
        $sub = new SubQuestion();
        $sub->body = $this->purify($question, $item['content']);
        $sub->answer = $this->purify($question, $item['rightAnswer']);
        $sub->explanation = $this->purify($question, $item['answerExplain']);
        $sub->tags = [$this->tag];
        $question->subject = $this->subject;
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
        }else if(!$sub->answer){
            $sub->answer = '略';
        }

        $question->originType = QuestionOrigin::EXERCISE;
        $question->origin = $origin;
        $question->subItems->append($sub);
        try
        {
            $this->service->create($question);
        }
        catch(\Exception $ex){
            echo json_encode($item);
            return null;
        }
        return $question;
    }

    protected function purify(Question $question, $content)
    {
        $content = $this->purifier->purify($content);
        return $this->downloadImages($question, $content);
    }

    protected function downloadImages(Question $question, $content)
    {
        return preg_replace_callback(
            self::$imagePattern,
            function ($matches) use ($question) {
                $dir = $question->getResourcePath();
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }

                $src = $matches['src'];
                $file = $dir . '/' . pathinfo($src, PATHINFO_BASENAME);
                exec(sprintf('wget %s -O %s', $src, $file));

                list($width, $height, $type, $attr) = getimagesize($file);
                return sprintf(
                    '<img src="http://7xkadt.com2.z0.glb.qiniucdn.com/%s" width="%dpx" height="%dpx" />',
                    $file,
                    $width,
                    $height
                );
            },
            $content
        );
    }

    protected function addHostToImageSrc($content)
    {
        return preg_replace_callback(
            self::$imagePattern,
            function ($matches) {
                $src = $matches['src'];
                $info = parse_url($src);
                if (!isset($info['host']) || empty($info['host'])) {
                    return str_replace($src, 'http://7xkadt.com2.z0.glb.qiniucdn.com' . $info['path'], $matches[0]);
                } else {
                    return $matches[0];
                }
            },
            $content
        );
    }
}
