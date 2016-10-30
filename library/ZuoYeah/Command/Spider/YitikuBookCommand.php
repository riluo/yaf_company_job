<?php
namespace ZuoYeah\Command\Spider;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Service\TagService;
use ZuoYeah\Spider\Knowbox\BookSpider;
use ZuoYeah\Spider\Knowbox\CatalogSpider;
use ZuoYeah\Spider\Yitiku\BookVersionSpider;

class YitikuBookCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('spider:yitiku:book')
            ->setDescription('易题库书本导入')
            ->addArgument(
                'subject',
                InputArgument::REQUIRED,
                '学科代码'
            )
            ->addArgument(
                'preUrl',
                InputArgument::REQUIRED,
                '请求前缀 如 gzshuxue'
            )
            ->addArgument(
                'userName',
                InputArgument::REQUIRED,
                '用户名'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                '密码'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subject = $input->getArgument('subject');
        $preUrl = $input->getArgument('preUrl');
        $userName = $input->getArgument('userName');
        $password = $input->getArgument('password');
        $form_params = [
            'chklogin' => 'www.yitiku.cn',
            'remember' => '',
            'account' => $userName,
            'password' => $password];

        $client = new Client(['cookies' => true]);
        $client->get('http://www.yitiku.cn/Tiku/User/index');
        $client->post('http://www.yitiku.cn/Tiku/User/login?zhanghao', ['form_params' => $form_params]);

        $cs = new BookVersionSpider($subject, $preUrl, $client);
        $cs->gather();
    }

}