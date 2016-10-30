<?php
namespace ZuoYeah\Command\Spider;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Spider\Ssx101\ZipService;

class Sxs101UnzipCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('spider:ssx101:unzip')
            ->setDescription('101随身学解压')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                '文件保存路径'
            )
//            ->addArgument(
//                'property',
//                InputArgument::REQUIRED,
//                '要采集的分类'
//            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $zipService = new ZipService();
        $zipService->unzipAll($path);
        $output->writeln('OK');
    }
}