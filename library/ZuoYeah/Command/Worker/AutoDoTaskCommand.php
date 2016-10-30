<?php
namespace ZuoYeah\Command\Worker;

use DOMPDF;
use Gram\Gearman\GearmanFactory;
use Gram\Utility\Helper\ThrowHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Entity\AnswerStruct\Image;
use ZuoYeah\Entity\Catalog;
use ZuoYeah\Entity\ErrorCode;
use ZuoYeah\Entity\Question;
use ZuoYeah\Entity\QuestionType;
use ZuoYeah\Entity\Subject;
use ZuoYeah\Entity\SubQuestion;
use ZuoYeah\Entity\TaskStudent;
use ZuoYeah\Service\AnswerService;
use ZuoYeah\Service\BookQuestionService;
use ZuoYeah\Service\CatalogQuestionService;
use ZuoYeah\Service\CatalogService;
use ZuoYeah\Service\QuestionService;
use ZuoYeah\Service\StudentService;
use ZuoYeah\Service\TaskQuestionService;
use ZuoYeah\Service\TaskService;
use ZuoYeah\Service\TaskStudentService;

class AutoDoTaskCommand extends Command
{

    protected $site = '';
    protected $taskQuestions = [];
    protected $questions = [];
    protected $bookQuestions = [];
    protected $taskStudents = [];
    protected $students = [];
    protected $answers = [];
    /** @var  Client */
    protected $client;
    protected $task;

    protected function configure()
    {
        $this->setName('worker:task:autodo')
            ->setDescription('自动做题')
            ->addArgument(
                'taskId',
                InputArgument::REQUIRED,
                '作业Id'
            );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $taskId = $input->getArgument('taskId');
        $this->initData($taskId);
        foreach ($this->taskStudents as $taskStudent) {
            $this->doTask($taskStudent);
        }

        $answerService = new AnswerService();
        $this->answers = $answerService->findAllByTaskId($taskId);

        foreach ($this->taskStudents as $taskStudent) {
            $this->setScoreTask($taskStudent);
        }
    }

    function initData($taskId)
    {
        $taskService = new TaskService();
        $taskStudentsService = new TaskStudentService();
        $taskQuestionService = new TaskQuestionService();
        $questionService = new QuestionService();
        $bookQuestionService = new BookQuestionService();
        $studentService = new StudentService();

        $this->site = \Yaf\Application::app()->getConfig()->site->toArray()['siteUrl'];
        $this->task = $taskService->get($taskId);
        ThrowHelper::ifNull($this->task, '作业不存在', ErrorCode::COMMON_NOT_EXIST);
        $this->taskStudents = $taskStudentsService->findAllByTask($this->task->schoolId, $this->task->id);
        $this->taskQuestions = $taskQuestionService->findByTask($taskId);
        $questionIds = array_map(function ($question) {
            return $question->questionId;
        }, $this->taskQuestions);
        $this->questions = $questionService->getAll($questionIds);
        $studentIds = array_map(function ($taskStudent) {
            return $taskStudent->studentId;
        }, $this->taskStudents);

        $this->bookQuestions = $bookQuestionService->findByQuestionIds($questionIds);

        $this->students = $studentService->getAll($studentIds);
        $this->client = new Client();
    }


    /**
     * @param $taskStudent TaskStudent
     */
    function doTask($taskStudent)
    {
        foreach ($this->taskQuestions as $taskQuestion) {
            foreach ($taskQuestion->subIndexes as $index) {
                $done = $this->doAnswer($taskStudent, $taskQuestion->questionId, $index);
                echo json_encode($done) . PHP_EOL;
            }
        }

        $submited = $this->submit($taskStudent);
        echo json_encode($submited) . PHP_EOL;
    }


    /**
     * @param $taskStudent TaskStudent
     */
    function setScoreTask($taskStudent)
    {
        foreach (array_filter($this->answers, function ($answer) use ($taskStudent) {
            return $answer->studentId == $taskStudent->studentId;
        }) as $answer) {
            foreach ($answer->subItems->all() as $subItem) {
                $done = $this->setScore($answer, $answer->questionId, $subItem->index, $taskStudent->studentId);
                echo json_encode($done) . PHP_EOL;
            }

        }
    }


    function getAnswer($studentId, $questionId, $taskId, $index)
    {

        $question = $this->questions[$questionId];
        /** @var SubQuestion $subQuestion */
        $subQuestion = $question->subItems[$index];

        $answer = ['images' => [], 'text' => ''];

        $score = $this->getScore($questionId, $taskId, $index, $studentId);

        echo 'score:' . json_encode($score) . PHP_EOL;


        if (QuestionType::isObjectiveType($subQuestion->type)) {
            if ($score == 100) {
                $answer['text'] = $subQuestion->answer;
            } else {
                if ($subQuestion->type == QuestionType::JUDGMENT) {
                    foreach ([['A', 'B'], ['对', '错'], ['T', 'F'], ['是', '否'], ['正确', '错误']] as $pair) {
                        if (in_array($subQuestion->answer, $pair)) {
                            foreach ($pair as $isRight) {
                                if ($subQuestion->answer != $isRight) {
                                    $answer['text'] = $isRight;
                                    return $answer;
                                }
                            }
                        }
                    }
                }

                if ($subQuestion->type == QuestionType::SINGLE_CHOICE) {
                    $answerIndex = rand(0, $subQuestion->optionCount - 1);
                    $c = chr(65 + $answerIndex);
                    while ($c == $subQuestion->answer) {
                        $answerIndex = rand(0, $subQuestion->optionCount - 1);
                        $c = chr(65 + $answerIndex);
                    }
                    $answer['text'] = $c;
                    return $answer;
                }

                if ($subQuestion->type == QuestionType::MULTIPLE_CHOICE) {
                    //多选题，暂时先只选一个答案
                    $answerIndex = rand(0, $subQuestion->optionCount - 1);
                    $c = chr(65 + $answerIndex);
                    while ($c == $subQuestion->answer) {
                        $answerIndex = rand(0, $subQuestion->optionCount - 1);
                        $c = chr(65 + $answerIndex);
                    }

                    $answer['text'] = $c;
                    return $answer;
                }
            }
        } else {
//            if ($score == 100) {
//                $img = $this->gemAnswerImage($subQuestion->answer, $studentId, $questionId);
//            } else {
//                $img = $this->gemAnswerImage($subQuestion->body, $studentId, $questionId);
//            }
            $img = new Image();
            $img->url = 'http://file.tiplus.cn/answer/0/'.rand(1,5).'.jpg';
            $img->width = 900;
            $img->height = 600;
            if ($img) {
                $answer['images'][] = $img;
            }
        }

        return $answer;
    }


    function gemAnswerImage($html,$studentId)
    {
        $fonts = ['钟齐吴嘉睿手写字,WuJiaRui', 'HYYaYaJ,立夏手写体', '国祥手写体', '默陌信笺手写体,momo_xinjian'];
        $fontIndex = $studentId % count($fonts);

        $tmpPath = sys_get_temp_dir();
        $file = tempnam($tmpPath, 'answer');

        $htmlFile = $file . '.html';
        $originPath = $file . '.jpg';
        $realPath = $file . '1.jpg';

        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body style="font-family:'
            . $fonts[$fontIndex]
            . '">'
            . '<div style="width:500px;">'
            . $html
            . '</div></body></html>';
        file_put_contents($htmlFile, $html);

        $cmd = "wkhtmltoimage '${htmlFile}' '${originPath}'";
        exec($cmd);

//        $sep = rand(50, 90);
//        $colorize = rand(60, 100);
//        $cmd = "convert '${originPath}' -sepia-tone ${sep}% \\( '${originPath}' -fill \\#FFFFFF -colorize ${colorize}% +noise Random -colorspace gray -alpha on -channel A -evaluate Set 100 \\) -compose overlay -composite '${realPath}'";
//        exec($cmd);

        ThrowHelper::ifEmpty(getimagesize($originPath),'图片不合法',ErrorCode::COMMON_LOGICAL_ERROR);
        $image = new Imagick($originPath);
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        for ($i = $width - 1; $i > 0; $i--) {

            for ($j = $height - 1; $j > 0; $j--) {
                $color = $image->getImagePixelColor($i, $j)->getColor();
                if ($color['r'] != 255 || $color['g'] != 255 || $color['b'] != 255) {
                    $width = $i + 5;
                    $image->cropImage($width, $height, 0, 0);
                    break 2;
                }
            }
        }

//        $rotate = rand(10, 60);
//        $image->rotateImage('white', $rotate);
//        $image->waveImage(rand(2, 8), rand(50, 100));
//        $image->rotateImage('white', -$rotate);
//        $image->addNoiseImage(Imagick::NOISE_IMPULSE,Imagick::CHANNEL_ALL);
        $image->cropImage($width, $height, 0, 0);
        $image->setImageFormat("jpeg");

//        $image->getImageBlob();

        $img = new Image();
        $img->url = 'data:image/png;base64,' . base64_encode($image->getImageBlob());
        $img->width = $image->getImageWidth();
        $img->height = $image->getImageHeight();
        return $img;
    }

    function fixHash($key, $min, $max)
    {
        $key = md5($key);
        $chars = str_split($key);
        $nums = array_map(function ($char) {
            return ord($char);
        }, $chars);
        $sum = array_sum($nums);
        return intval($min + ($max - $min) * ($sum % 100) / 100);
    }

    function getScore($questionId, $taskId, $index, $studentId)
    {

        $question = $this->questions[$questionId];
        /** @var SubQuestion $subQuestion */
        $subQuestion = $question->subItems[$index];

        $key = $questionId . $taskId . $index . $studentId;
        $hash = $this->fixHash($key, 0, 100);//每个作答分布到0到100的一个值

        $rightKey = $questionId . $taskId . $index;
        $rightHash = $this->fixHash($rightKey, 20, 80);//答对阈值

        $halfRightKey = $questionId . $index . $taskId;
        $halfRightHash = $this->fixHash($halfRightKey, 10, $rightHash);//半对阈值

        echo ($hash) . PHP_EOL;
        echo ($rightHash) . PHP_EOL;
        echo ($halfRightHash) . PHP_EOL;
        if (QuestionType::isObjectiveType($subQuestion->type)) {
            if ($hash > $rightHash) {
                return 100;
            } else {
                return 0;
            }
        } else {
            if ($hash > $rightHash) {
                return 100;
            } else if ($hash > $halfRightHash) {
                return 50;
            } else {
                return 0;
            }
        }
    }


    function doAnswer($taskStudent, $questionId, $index)
    {
        $api = $this->site . '/api/v1/answer/do';
        $answer = $this->getAnswer($taskStudent->studentId, $questionId, $taskStudent->taskId, $index);
        echo json_encode($api) . PHP_EOL;
//        return;
        /** @var Question $question */
        return $this->client->post($api,
            ['form_params' =>
                [
                    'tokenUserId' => $taskStudent->studentId,
                    'tokenUserType' => 'STUDENT',
                    'taskStudentId' => $taskStudent->id,
                    'questionId' => $questionId,
                    'index' => $index,
                    'answer' => json_encode($answer)
                ]])->getBody()->getContents();
    }


    function submit($taskStudent)
    {
        $api = $this->site . '/api/v1/task/submit';
        /** @var Question $question */
        return $this->client->post($api,
            ['form_params' =>
                [
                    'tokenUserId' => $taskStudent->studentId,
                    'tokenUserType' => 'STUDENT',
                    'taskStudentId' => $taskStudent->id
                ]])->getBody()->getContents();
    }


    function setScore($answer, $questionId, $index, $studentId)
    {
        $api = $this->site . '/api/v1/answer/markScore';
        $score = $this->getScore($questionId, $this->task->id, $index, $studentId);
        echo json_encode($score) . PHP_EOL;
        /** @var Question $question */
        return $this->client->post($api,
            ['form_params' =>
                [
                    'tokenUserId' => $this->task->teacherId,
                    'tokenUserType' => 'TEACHER',
                    'answerId' => $answer->id,
                    'index' => $index,
                    'score' => $score
                ]])->getBody()->getContents();
    }


}