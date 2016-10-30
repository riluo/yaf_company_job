<?php
namespace ZuoYeah\Command\Worker;

use Gram\Gearman\GearmanFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Gearman\MessageWorker;
use ZuoYeah\Gearman\TaskStudentWorker;

class MarkCompleteCommand extends Command
{
    protected function configure()
    {
        $this->setName('worker:mark:complete')
            ->setDescription('批改完成事件处理');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $agent = new TaskStudentWorker();
        $worker = GearmanFactory::createWorker();
        $worker->addFunction('mark.complete', [$agent, 'doMarkComplete']);
        printf('%s(pid:%d) is running%s', $this->getName(), getmypid(), PHP_EOL);
        while ($worker->work());
    }
}