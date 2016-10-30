<?php
namespace ZuoYeah\Command\Spider;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Service\TagService;
use ZuoYeah\Spider\Knowbox\BookSpider;
use ZuoYeah\Spider\Knowbox\CatalogSpider;

class KnowboxBookCommand extends Command
{
    protected function configure()
    {
        ignore_user_abort();
        ini_set('memory_limit', -1);

        $this
            ->setName('spider:knowbox:book')
            ->setDescription('作业盒子书本导入')
            ->addArgument(
                'subject',
                InputArgument::REQUIRED,
                '学科代码'
            )
            ->addArgument(
                'token',
                InputArgument::REQUIRED,
                '作业盒子Token'
            )
            ->addArgument(
                'continueBookId',
                InputArgument::OPTIONAL,
                '开始继续抓取的书本id'
            )
            ->addArgument(
                'continueContentId',
                InputArgument::OPTIONAL,
                '开始继续抓取的目录id'
            )
//            ->addArgument(
//                'type',
//                InputArgument::REQUIRED,
//                'TEXTBOOK或EXERCISE'
//            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subject = $input->getArgument('subject');
        $token = $input->getArgument('token');
        $continueBookId = $input->getArgument('continueBookId');
        $continueContentId = $input->getArgument('continueContentId');
        if (empty($continueBookId)) {
            $continueBookId = 0;
        }
        if (empty($continueContentId)) {
            $continueContentId = 0;
        }

        $cs = new BookSpider($subject, $token, $continueBookId, $continueContentId);
        $cs->gather();
    }

}