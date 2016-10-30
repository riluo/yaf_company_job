<?php
namespace ZuoYeah\Command\Spider;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Spider\Ssx101\BookService;

class Ssx101BookCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('spider:ssx101:book_save')
            ->setDescription('101随身学课本导入')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                '文件保存路径'
            )
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                '要处理的目录'
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
        $path = $input->getArgument('path');
        $id = $input->getArgument('id');
        //$type = $input->getArgument('type');

        $bookService = new BookService();
        $book = $bookService->loadContent($path, $id);
        echo json_encode($book, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        //$output->writeln('OK');
    }
}