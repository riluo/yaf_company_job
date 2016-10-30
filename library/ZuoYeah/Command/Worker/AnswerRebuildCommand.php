<?php
namespace ZuoYeah\Command\Worker;

use Gram\Gearman\GearmanFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Entity\Answer;
use ZuoYeah\Entity\AnswerBase;
use ZuoYeah\Entity\AnswerItem;
use ZuoYeah\Entity\AnswerResult;
use ZuoYeah\Entity\Search\SearchBase;
use ZuoYeah\Entity\Subject;
use ZuoYeah\Service\AnswerService;
use ZuoYeah\Service\AnswerResultService;
use ZuoYeah\Service\ReviseService;

class AnswerRebuildCommand extends Command
{
    /** @var  AnswerService */
    protected $answerService;
    /** @var  ReviseService */
    protected $reviseService;
    /** @var  AnswerResultService */
    protected $answerResultService;

    protected function configure()
    {
        $this->setName('worker:answer:rebuild')
            ->setDescription('答案拆分');

        $this->answerService = new AnswerService();
        $this->reviseService = new ReviseService();

        $this->answerResultService = new AnswerResultService();

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        foreach ([$this->answerResultService, $this->answerService, $this->reviseService] as $service) {
            $search = new SearchBase();
            $search->pageSize = 100;
            $pages = $service->findAllByPage($search);
            while (true) {
                foreach ($pages->items as $answer) {
                    /** @var AnswerBase $answer */
                    /** @var AnswerItem $answerItem */
                    $answerItem = array_shift($answer->subItems->all());
                    $answer->idx = $answerItem->index;
                    $answer->text = $answerItem->answer->text;
                    $answer->images = $answerItem->answer->images;
                    $answer->markAudios = $answerItem->mark->audios;
                    $answer->markImages = $answerItem->mark->images;
                    $answer->comment = $answerItem->mark->comment;
                    $answer->commentTime = $answerItem->mark->commentTime;
                    $answer->score = $answerItem->score;
                    $answer->marked = $answer->status==Answer::STATUS_MARKED;
                    $answer->revised = $answerItem->revised;
                    if(isset($answer->isOk)){
                        $answer->isOk = $answerItem->isOk;
                    }

                    echo json_encode($answer);
                    echo PHP_EOL;
                    $service->update($answer);
                }
                if ($search->pageIndex >= $pages->totalPage - 1) {
                    break;
                }
                break;

                $search->pageIndex++;
                $pages = $this->answerService->findAllByPage($search);
            }
        }


    }


}