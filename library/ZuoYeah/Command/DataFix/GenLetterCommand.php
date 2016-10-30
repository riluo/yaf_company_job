<?php
namespace ZuoYeah\Command\DataFix;

use Gram\Gearman\GearmanFactory;
use Gram\Utility\Helper\ArrayHelper;
use Gram\Utility\Helper\ThrowHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZuoYeah\Entity\Answer;
use ZuoYeah\Entity\AnswerResult;
use ZuoYeah\Entity\Clazz;
use ZuoYeah\Entity\ErrorCode;
use ZuoYeah\Entity\Question;
use ZuoYeah\Entity\SchoolAdmin;
use ZuoYeah\Entity\Search\AnswerSearch;
use ZuoYeah\Entity\Search\ClazzSearch;
use ZuoYeah\Entity\Search\SchoolAdminSearch;
use ZuoYeah\Entity\Search\StudentSearch;
use ZuoYeah\Entity\Search\TaskSearch;
use ZuoYeah\Entity\Search\TeacherQuestionTagSearch;
use ZuoYeah\Entity\Search\TeacherSearch;
use ZuoYeah\Entity\Student;
use ZuoYeah\Entity\Subject;
use ZuoYeah\Entity\Task;
use ZuoYeah\Entity\TaskQuestion;
use ZuoYeah\Entity\TaskStudent;
use ZuoYeah\Entity\Teacher;
use ZuoYeah\Entity\TeacherClazz;
use ZuoYeah\Entity\TeacherQuestionTag;
use ZuoYeah\Entity\TeacherTag;
use ZuoYeah\Entity\Term;
use ZuoYeah\Entity\TermBook;
use ZuoYeah\Gearman\MessageWorker;
use ZuoYeah\Gearman\TaskStudentWorker;
use ZuoYeah\Service\AnswerResultService;
use ZuoYeah\Service\AnswerService;
use ZuoYeah\Service\ClazzService;
use ZuoYeah\Service\SchoolAdminService;
use ZuoYeah\Service\SchoolService;
use ZuoYeah\Service\StatService;
use ZuoYeah\Service\StudentService;
use ZuoYeah\Service\GenUsersService;
use ZuoYeah\Service\TagService;
use ZuoYeah\Service\TaskPolyFitService;
use ZuoYeah\Service\TaskQuestionService;
use ZuoYeah\Service\TaskService;
use ZuoYeah\Service\TaskStudentService;
use ZuoYeah\Service\TeacherClazzService;
use ZuoYeah\Service\TeacherQuestionTagService;
use ZuoYeah\Service\TeacherService;
use ZuoYeah\Service\TeacherTagService;
use ZuoYeah\Service\TermBookService;
use ZuoYeah\Service\TermService;

class GenLetterCommand extends Command
{
    private $school;
    private $orgSchool;
    private $orgTerms;
    private $terms;
    private $teacherNum = 2;
    private $avgStudentNum = 10;
    private $classes = [];
    private $teachers = [];
    private $orgTasks = [];
    private $tasks = [];
    private $students = [];
    private $schoolAdmin;
    private $map = ['tasks' => [], 'classes' => [], 'students' => [], 'teachers' => []];

    protected function configure()
    {
        $this->setName('datafix:genletter')
            ->setDescription('创建账号邮件')
            ->addArgument(
                'schoolId',
                InputArgument::REQUIRED,
                '目标学校Id'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schoolService = new SchoolService();
        $teacherService = new TeacherService();
        $teacherClazzService = new TeacherClazzService();
        $clazzService = new ClazzService();
        $studentService = new StudentService();
        $schoolAdminService = new SchoolAdminService();

        $schoolId = $input->getArgument('schoolId');
        $school = $schoolService->get($schoolId);
        ThrowHelper::ifNull($school, '目标学校不存在', ErrorCode::COMMON_NOT_EXIST);

        echo Date('h:i:s ') . '处理结果 ' . PHP_EOL;
        echo '邮件模板 ' . PHP_EOL;
        echo '＊＊＊：' . PHP_EOL;
        echo '　　＊＊＊学校账号分配如下：' . PHP_EOL;
        echo '    教师账号密码：' . PHP_EOL;


        $search = new TeacherSearch();
        $search->schoolId = $schoolId;
        $search->pageSize = 1000;
        $teachers = $teacherService->findPagedBySearch($search)->items;

        foreach ($teachers as $teacher) {
            $classes = $teacherClazzService->findAllByTeacherId($schoolId, $teacher->id);
            $classIds = ArrayHelper::mapKey($classes, 'classId');
            $classes = $clazzService->getAll($classIds);
            echo '        ' . $teacher->realName . ':'
                . $teacher->userName . ','
                . substr($teacher->userName, -6, 6) . ','
                . Subject::cnName($teacher->subject)
                . '(' . implode(',', array_map(function ($class) {
                    return $class->grade . $class->title;
                }, $classes)) . ')'
                . PHP_EOL;

        }
        echo '    学生账号密码：' . PHP_EOL;

        $search = new ClazzSearch();
        $search->schoolId = $schoolId;
        $search->pageSize = 1000;
        $classes = $clazzService->findPagedBySearch($search)->items;

        foreach ($classes as $class) {
            echo '        ' . $class->grade . $class->title . ':' . PHP_EOL;

            $students = $studentService->findAllByClass($schoolId, $class->id);
            foreach ($students as $student) {
                /** @var Student $student */
                echo '            ' . $student->realName . ':' . $student->userName . ',' . $student->defaultPassword()
                    . PHP_EOL;
            }
        }
        echo '    学校管理员：' . PHP_EOL;
        echo '        登陆地址：http://zuoye.tiplus.cn/school/home/index' . PHP_EOL;
        $search = new SchoolAdminSearch();
        $search->schoolId = $schoolId;
        $search->pageSize = 1000;
        $admins = $schoolAdminService->findPagedBySearch($search)->items;

        foreach ($admins as $admin) {
            echo '        账号密码：' . $admin->userName . ',' . substr('86420' . $admin->id, -6, 6) . PHP_EOL;

        }
    }
}