<?php
namespace ZuoYeah\Command\Worker;

use Gram\Gearman\GearmanFactory;
use Gram\Utility\Helper\ThrowHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Entity\Catalog;
use ZuoYeah\Entity\ErrorCode;
use ZuoYeah\Entity\Subject;
use ZuoYeah\Service\CatalogQuestionService;
use ZuoYeah\Service\CatalogService;
use ZuoYeah\Service\DelayEventService;

class ProcessDelayEventCommand extends Command
{
    protected function configure()
    {
        $this->setName('worker:process:delayevent')
            ->setDescription('处理定时消息')
            ->addArgument(
                'processId',
                InputArgument::REQUIRED,
                '进程Id');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processId = $input->getArgument('processId');
        ThrowHelper::ifEmpty($processId, '进程Id不存在', ErrorCode::COMMON_NOT_EMPTY);
        $delayEventService = new DelayEventService();
        $delayEventService->process($processId);
    }

}