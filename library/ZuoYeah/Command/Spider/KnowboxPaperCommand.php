<?php
namespace ZuoYeah\Command\Spider;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Service\TagService;
use ZuoYeah\Spider\Knowbox\CatalogSpider;
use ZuoYeah\Spider\Knowbox\PaperSpider;

class KnowboxPaperCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('spider:knowbox:paper')
            ->setDescription('作业盒子试卷导入')
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

        $cs = new PaperSpider($subject,$token);
        $cs->gather();
    }

}