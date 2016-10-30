<?php
namespace ZuoYeah\Command\Worker;

use Gram\Gearman\GearmanFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Gearman\ProcessQueueWorker;

class PringQuestionsCommand extends Command
{
    protected function configure()
    {
        $this->setName('worker:processqueue:printquestions')
            ->setDescription('处理打印命令');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $agent = new ProcessQueueWorker();
        $worker = GearmanFactory::createWorker();
        $worker->addFunction('processqueue.printquestions', [$agent, 'doPrintQuestions']);
        $worker->addFunction('processqueue.exportword', [$agent, 'doExportWord']);
        $worker->addFunction('processqueue.teacherexportword', [$agent, 'doExportWordMulti']);
        printf('%s(pid:%d) is running%s', $this->getName(), getmypid(), PHP_EOL);
        while ($worker->work());
    }
}