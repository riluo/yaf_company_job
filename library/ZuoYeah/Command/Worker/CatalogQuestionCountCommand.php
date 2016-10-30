<?php
namespace ZuoYeah\Command\Worker;

use Gram\Gearman\GearmanFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Entity\Catalog;
use ZuoYeah\Entity\Subject;
use ZuoYeah\Service\CatalogQuestionService;
use ZuoYeah\Service\CatalogService;

class CatalogQuestionCountCommand extends Command
{
    /** @var  CatalogService */
    protected $catalogService;
    /** @var  CatalogQuestionService */
    protected $catalogQuestionService;

    protected function configure()
    {
        $this->setName('worker:catalog:questionCount')
            ->setDescription('统计知识点题目数');

        $this->catalogService = new CatalogService();
        $this->catalogQuestionService = new CatalogQuestionService();

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $levels = Catalog::levels();
        $subjects = Subject::codes();

        foreach ($levels as $level) {
            foreach ($subjects as $subject) {
                $this->cal(0, $level, $subject);
            }
        }
    }

    function cal($parentId, $level = '', $subject = '')
    {
        if ($parentId == 0) {
            echo "开始 $subject $level" . PHP_EOL;
            $children = $this->catalogService->findAll($subject, $level, 1);
        } else {
            $children = $this->catalogService->findAllByParentId($parentId);
        }

        $total = 0;
        foreach ($children as $catalog) {
            echo "开始 " . $catalog->title . PHP_EOL;

            $catalog->questionCount = $this->catalogQuestionService->calQuestionCount($catalog->id);

            $count =  $this->cal($catalog->id);
            $catalog->questionCount += $count['questionCount'];
            $catalog->childrenCount = $count['childrenCount'];
            $this->catalogService->update($catalog);

            $total += $catalog->questionCount;

            echo $catalog->title . ' ' . $catalog->questionCount . PHP_EOL;
        }

        return ['questionCount'=>$total,'childrenCount'=>count($children)];
    }
}