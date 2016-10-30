<?php

namespace ZuoYeah\Command\Worker;


use Gram\Utility\Helper\DebugHelper;
use Gram\Utility\Helper\ThrowHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Entity\ErrorCode;
use ZuoYeah\Service\BookImageService;
use ZuoYeah\Service\TaskService;

class UploadBookPageImageCommand extends Command
{
    protected function configure()
    {
        $this->setName('worker:upload:book:page:image')
            ->setDescription('上传打标书本图片')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                '图片路径或目录'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path', '');
        ThrowHelper::ifEmpty($path, '路径不能为空', ErrorCode::COMMON_NOT_EMPTY);

        $files = [];
        if (is_dir($path)) {
            $dir_handle = opendir($path);
            while ($file = readdir($dir_handle)) {
                if ($file != "." && $file != "..") {
                    $files[] = $path . '/' . $file;
                }
            }
        } else {
            $files = [$path];
        }

        $bookImageService = new BookImageService();

        foreach ($files as $file) {
            DebugHelper::log($file);

            $bookImageService->uploadMarkedBookImage($file);
        }
    }
}