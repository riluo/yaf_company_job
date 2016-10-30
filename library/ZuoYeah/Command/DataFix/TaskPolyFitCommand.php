<?php
namespace ZuoYeah\Command\DataFix;

use Gram\Gearman\GearmanFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Entity\Search\StudentSearch;
use ZuoYeah\Gearman\MessageWorker;
use ZuoYeah\Gearman\TaskStudentWorker;
use ZuoYeah\Service\StatService;
use ZuoYeah\Service\StudentService;
use ZuoYeah\Service\TaskPolyFitService;
use ZuoYeah\Service\TaskService;

class TaskPolyFitCommand extends Command
{
    protected function configure()
    {
        $this->setName('datafix:polyfit:init')
            ->setDescription('初始化学生的成长趋势数据')
            ->addArgument(
                'studentId',
                InputArgument::OPTIONAL,
                '学生Id'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $studentService = new StudentService();
        $statService = new StatService();
        $studentId = $input->getArgument('studentId');
        if (empty($studentId)) {
            throw new \Exception('请输入参数');
        }

        if ($studentId == 'all') {
            $this->processAll();
        } else {
            $student = $studentService->get($studentId);
            $subjects = $this->getSubjects($student->classId);
            foreach($subjects as $subject){
                $statService->calTaskPolyFit($student,$subject);
            }
        }
    }

    private $subjectMap = [];

    function getSubjects($classId)
    {
        $taskService = new TaskService();

        if(!isset($this->subjectMap[$classId])){
            $this->subjectMap[$classId] = $taskService->findSubjectsByClassId($classId);
        }
        return $this->subjectMap[$classId];
    }

    function processPage($search, $findByPage, $processItem)
    {
        $search->pageSize = 100;
        echo Date('Ymd H:i:s') . ' page start ' . $search->pageIndex . PHP_EOL;
        $pages = $findByPage($search);
        while (true) {
            foreach ($pages->items as $item) {
                echo Date('Ymd H:i:s') . ' item start ' . $item->id . PHP_EOL;
                $processItem($item);
                echo Date('Ymd H:i:s') . ' item end ' . $item->id . PHP_EOL;

            }
            if ($search->pageIndex >= $pages->totalPage - 1) {
                echo Date('Ymd H:i:s') . ' page completed ' . PHP_EOL;
                break;
            }

            $search->pageIndex++;
            echo Date('Ymd H:i:s') . ' page start ' . $search->pageIndex . PHP_EOL;
            $pages = $findByPage($search);
        }
    }

    function processAll()
    {
        $search = new StudentSearch();
        $this->processPage(
            $search,
            function ($search) {
                $studentService = new StudentService();
                return $studentService->findPagedBySearch($search);
            },
            function ($student) {
                $statService = new StatService();
                $subjects = $this->getSubjects($student->classId);
                foreach($subjects as $subject){
                    $statService->calTaskPolyFit($student,$subject);
                }
            });
    }
}