<?php
namespace ZuoYeah\Command\Spider;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Service\TagService;
use ZuoYeah\Spider\Knowbox\CatalogSpider;

class KnowboxCatalogCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('spider:knowbox:catalog')
            ->setDescription('作业盒子知识点导入')
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
                'level',
                InputArgument::REQUIRED,
                '年级类型'
            )
            ->addArgument(
                'continueCatalogId',
                InputArgument::OPTIONAL,
                '开始继续抓取的知识点id'
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
        $level = $input->getArgument('level');

        $continueCatalogId = $input->getArgument('continueCatalogId');
        if (empty($continueCatalogId)) {
            $continueCatalogId = 0;
        }

        $cs = new CatalogSpider($subject,$token,$level,$continueCatalogId);
        $cs->gather();
    }

}