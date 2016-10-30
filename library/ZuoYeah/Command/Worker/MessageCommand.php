<?php
namespace ZuoYeah\Command\Worker;

use Gram\Gearman\GearmanFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Gearman\MessageWorker;

class MessageCommand extends Command
{
    protected function configure()
    {
        $this->setName('worker:message:push')
            ->setDescription('处理消息分发命令');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $agent = new MessageWorker();
        $worker = GearmanFactory::createWorker();
        $worker->addFunction('message.push', [$agent, 'doMessagePush']);
        printf('%s(pid:%d) is running%s', $this->getName(), getmypid(), PHP_EOL);
        while ($worker->work()) ;
    }
}