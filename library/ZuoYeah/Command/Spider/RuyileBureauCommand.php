<?php
namespace ZuoYeah\Command\Spider;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Service\TagService;
use ZuoYeah\Spider\Knowbox\CatalogSpider;
use ZuoYeah\Spider\Ruyile\ProvinceSpider;

class RuyileBureauCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('spider:ruyile:bureau')
            ->setDescription('如意了教育局')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cs = new ProvinceSpider();
        $cs->gather();
    }

}