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
use ZuoYeah\Entity\Search\StudentSearch;
use ZuoYeah\Entity\Search\TaskSearch;
use ZuoYeah\Entity\Search\TeacherQuestionTagSearch;
use ZuoYeah\Entity\Student;
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

class GenUsersCommand extends Command
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
        $this->setName('datafix:genusers')
            ->setDescription('创建用户数据')
            ->addArgument(
                'orgSchoolId',
                InputArgument::REQUIRED,
                '复制源学校Id'
            )
            ->addArgument(
                'schoolId',
                InputArgument::REQUIRED,
                '目标学校Id'
            )
            ->addArgument(
                'teacherNum',
                InputArgument::REQUIRED,
                '教师数量'
            )
            ->addArgument(
                'avgStudentNum',
                InputArgument::REQUIRED,
                '每班学生数量'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schoolService = new SchoolService();

        $orgSchoolId = $input->getArgument('orgSchoolId');
        $this->orgSchool = $schoolService->get($orgSchoolId);
        ThrowHelper::ifNull($this->orgSchool, '源学校不存在', ErrorCode::COMMON_NOT_EXIST);
        $schoolId = $input->getArgument('schoolId');
        $this->school = $schoolService->get($schoolId);
        ThrowHelper::ifNull($this->school, '目标学校不存在', ErrorCode::COMMON_NOT_EXIST);

        $this->teacherNum = $input->getArgument('teacherNum');
        if (empty($this->teacherNum)) {
            $this->teacherNum = 2;
        }
        $this->avgStudentNum = $input->getArgument('avgStudentNum');
        if (empty($this->avgStudentNum)) {
            $this->avgStudentNum = 10;
        }

        $this->genTerm();
        $this->bindBook();
        $this->genClass();
        $this->genStudent();
        $this->genTeacher();
        $this->bindTeacher();
        $this->genSchoolAdmin();
        $this->genTasks();
        $this->genTaskQuestions();
        $this->genTaskStudents();
        $this->genAnswers();
        $this->genAnswerResults();
        $this->calPolyFit();
        $this->genTeacherTags();

        echo Date('h:i:s ') . '处理结果 ' . PHP_EOL;
        echo '邮件模板 ' . PHP_EOL;
        echo '＊＊＊：' . PHP_EOL;
        echo '　　＊＊＊学校账号分配如下：' . PHP_EOL;
        echo '    教师账号密码：' . PHP_EOL;
        for ($i = 0; $i < $this->teacherNum; $i++) {
            $teacher = $this->teachers[$i];
            echo '        ' . $teacher->realName . ':' . $teacher->userName . ',' . substr('000000' . $teacher->id, -6, 6)
                . '(' . $this->classes[$i * 2]->title . ',' . $this->classes[$i * 2 + 1]->title . ')'
                . PHP_EOL;

        }
        echo '    学生账号密码：' . PHP_EOL;

        for ($i = 0; $i < $this->teacherNum * 2; $i++) {
            $class = $this->classes[$i];
            echo '        ' . $class->title . ':' . PHP_EOL;

            $students = ArrayHelper::fetchItemsByKey($this->students, 'classId', $class->id);

            foreach ($students as $student) {
                /** @var Student $student */
                echo '            ' . $student->realName . ':' . $student->userName . ',' . $student->defaultPassword()
                    . PHP_EOL;
            }
        }
        echo '    学校管理员：' . PHP_EOL;
        echo '        登陆地址：http://zuoye.tiplus.cn/school/home/index' . PHP_EOL;
        echo '        账号密码：' . $this->schoolAdmin->userName . ',' . substr('86420' . $this->schoolAdmin->id, -6, 6) . PHP_EOL;
    }


    public function genTerm()
    {
        echo Date('h:i:s ') . '生成学期' . PHP_EOL;
        $termService = new TermService();
        $this->orgTerms = $termService->findAllBySuggest($this->orgSchool->id, '', 100);
        $this->terms = array_values($termService->findAllBySuggest($this->school->id, '', 100));
        foreach ($this->orgTerms as $orgTerm) {
            $term = $this->mapTerm($orgTerm->id);
            if (!$term) {
                $term = new Term();
                $term->grade = $orgTerm->grade;
                $term->term = $orgTerm->term;
                $term->schoolId = $this->school->id;
                $termService->create($term);
                $this->terms[] = $term;
                echo Date('h:i:s ') . '新建学期 ' . json_encode($term) . PHP_EOL;
            }
        }
    }

    private function mapTerm($orgTermId)
    {
        $orgTerm = $this->orgTerms[$orgTermId];
        $term = ArrayHelper::fetchByKeys($this->terms, ['grade', 'term'], [$orgTerm->grade, $orgTerm->term]);
        return $term;
    }

    public function bindBook()
    {
        echo Date('h:i:s ') . '绑定书本 ' . PHP_EOL;

        $termBookService = new TermBookService();
        foreach ($this->orgTerms as $orgTerm) {
            $books = $termBookService->findAllByTermId($orgTerm->id);
            $term = $this->mapTerm($orgTerm->id);
            $termBookService->deleteByTermId($term->id);
            foreach ($books as $book) {
                $bookTerm = new TermBook();
                $bookTerm->bookId = $book->id;
                $bookTerm->termId = $term->id;
                $bookTerm->subject = $book->subject;
                $bookTerm->bookType = $book->bookType;
                $termBookService->create($bookTerm);
            }
        }
    }

    public function genClass()
    {
        echo Date('h:i:s ') . '生成班级 ' . PHP_EOL;

        $classService = new ClazzService();
        $term = $this->terms[0];
        $classes = $classService->findAllByTermIds([$term->id]);

        $classCount = count($classes);

        for ($i = 0; $i < $this->teacherNum * 2; $i++) {
            $class = new Clazz();
            $class->title = ($classCount + $i + 1) . '班';
            $class->schoolId = $this->school->id;
            $class->termId = $term->id;
            $class->enrollYear = Date('Y');
            $class->grade = $term->grade;
            $classService->create($class);
            $this->classes[] = $class;
        }

        echo Date('h:i:s ') . '新建班级数 ' . ($this->teacherNum * 2) . PHP_EOL;

    }

    public function genStudent()
    {
        echo Date('h:i:s ') . '生成学生 ' . PHP_EOL;
        $studentService = new StudentService();
        foreach ($this->classes as $class) {
            $studentNames = $this->randRealName($this->avgStudentNum);
            foreach ($studentNames as $studentName) {
                $student = new Student();
                $student->realName = $studentName;
                $student->classId = $class->id;
                $student->schoolId = $class->schoolId;
                $student->userName = 'init' . $student->realName . rand(1, 100000);
                $studentService->create($student);
                $student->userName = str_pad((string)$student->id, 6, '0', STR_PAD_LEFT);
                $studentService->update($student);
                $this->students[] = $student;
            }
        }

        echo Date('h:i:s ') . '创建学生数 ' . count($this->students) . PHP_EOL;
    }

    public function genTeacher()
    {
        echo Date('h:i:s ') . '生成教师 ' . PHP_EOL;

        $teacherService = new TeacherService();
        $teacherNames = $this->randRealName($this->teacherNum);

        for ($i = 0; $i < $this->teacherNum; $i++) {
            $teacher = new Teacher();
            $teacher->schoolId = $this->school->id;
            $teacher->mobile = '11111111111';
            $teacher->password = '1';
            $teacher->realName = $teacherNames[$i];
            $teacher->subject = 'GS002';
            $teacher->userName = 'init' . $teacher->realName . rand(1, 100000);
            $teacherService->create($teacher);
            $teacher->userName = str_pad((string)$teacher->id, 6, '0', STR_PAD_LEFT);;
            $teacher->password = substr('000000' . $teacher->id, -6, 6);
            $teacherService->update($teacher);
            $this->teachers[] = $teacher;
        }
        echo Date('h:i:s ') . '生成教师数 ' . count($this->teachers) . PHP_EOL;

    }

    public function bindTeacher()
    {
        echo Date('h:i:s ') . '绑定教师班级 ' . PHP_EOL;

        $teacherClazzService = new TeacherClazzService();
        foreach ($this->teachers as $key => $teacher) {
            foreach ([0, 1] as $i) {
                $class = $this->classes[$key * 2 + $i];
                $teacherClass = new TeacherClazz();
                $teacherClass->schoolId = $this->school->id;
                $teacherClass->classId = $class->id;
                $teacherClass->teacherId = $teacher->id;
                $teacherClass->termId = $class->termId;
                $teacherClass->subject = $teacher->subject;
                $teacherClazzService->create($teacherClass);
            }
        }
    }

    public function genSchoolAdmin()
    {
        echo Date('h:i:s ') . '生成学校管理员 ' . PHP_EOL;
        $schoolAdminService = new SchoolAdminService();
        $schoolAdmin = new SchoolAdmin();
        $schoolAdmin->schoolId = $this->school->id;
        $schoolAdmin->realName = $this->randRealName(2)[0];
        $schoolAdmin->roles = '班级,学生,教材,教师';
        $schoolAdmin->password = '1';
        $schoolAdmin->userName = 'init' . $schoolAdmin->realName . rand(1, 100000);
        $schoolAdminService->create($schoolAdmin);
        $schoolAdmin->userName = str_pad((string)$schoolAdmin->id, 6, '0', STR_PAD_LEFT);;
        $schoolAdmin->password = substr('86420' . $schoolAdmin->id, -6, 6);
        $schoolAdminService->update($schoolAdmin);
        $this->schoolAdmin = $schoolAdmin;
    }

    public function genTasks()
    {
        echo Date('h:i:s ') . '生成作业 ' . PHP_EOL;

        $taskService = new TaskService();
        $taskSearch = new TaskSearch();
        $studentService = new StudentService();

        $taskSearch->schoolId = $this->orgSchool->id;
        $taskSearch->pageSize = 1000;
        $this->orgTasks = $taskService->findPagedBySearch($taskSearch)->items;
        $orgClassIds = ArrayHelper::mapKey($this->orgTasks, 'classId');
        echo Date('h:i:s ') . '班级 ' . json_encode($orgClassIds) . PHP_EOL;

        foreach ($this->classes as $i => $class) {
            /** @var Clazz $class */
            $orgClassId = $orgClassIds[$i % count($orgClassIds)];
            $orgTeacherId = ArrayHelper::fetchByKey($this->orgTasks, 'classId', $orgClassId)->teacherId;
            $teacher = $this->teachers[(int)floor($i / 2)];

            $orgTasks = array_values(ArrayHelper::fetchItemsByKeys(
                $this->orgTasks,
                ['classId', 'teacherId'],
                [$orgClassId, $orgTeacherId]));

            $this->map['classes'][$class->id] = $orgClassId;
            $this->map['teachers'][$teacher->id] = $orgTeacherId;

            $orgStudents = $studentService->findAllByClass($this->orgSchool->id, $orgClassId);
            $orgStudentIds = ArrayHelper::mapKey($orgStudents);
            $students = ArrayHelper::fetchItemsByKey($this->students, 'classId', $class->id);
            shuffle($students);
            foreach ($students as $j => $student) {
                $orgStudentId = $orgStudentIds[$j % count($orgStudentIds)];
                $this->map['students'][$student->id] = $orgStudentId;
            }

            echo Date('h:i:s ') . '学生数 ' . count($students) . PHP_EOL;

            $orgTasks = array_reverse($orgTasks);
            $taskCount = count($orgTasks);
            foreach ($orgTasks as $j => $orgTask) {
                /** @var Task $task */
                $task = clone $orgTask;
                $task->id = 0;
                $task->schoolId = $this->school->id;
                $task->createTime = new \DateTime(Date('Y-m-d', strtotime('today -' . ($taskCount - $j) . ' days')));
                $task->beginTime = new \DateTime(Date('Y-m-d', strtotime('today -' . ($taskCount - $j) . ' days')));
                $task->endTime = new \DateTime(Date('Y-m-d', strtotime('today -' . ($taskCount - $j - 1) . ' days')));
                $task->classId = $class->id;
                $task->teacherId = $teacher->id;
                $task->termId = $this->terms[0]->id;
                $task->title = $class->title . Date('Y年m月d日', strtotime('today -' . ($taskCount - $j) . ' days')) . '作业';
                $taskService->save($task);
                $this->tasks[] = $task;
                $this->map['tasks'][$task->id] = $orgTask->id;
            }
            echo Date('h:i:s ') . '作业数 ' . $taskCount . PHP_EOL;

        }
    }

    public function genTaskQuestions()
    {
        echo Date('h:i:s ') . '生成作业题目 ' . PHP_EOL;

        $taskQuestionService = new TaskQuestionService();
        foreach ($this->map['tasks'] as $taskId => $orgTaskId) {
            $orgQuestions = $taskQuestionService->findByTask($orgTaskId);
            foreach ($orgQuestions as $orgQuestion) {
                $question = clone $orgQuestion;
                /** @var TaskQuestion $question */
                $question->id = 0;
                $question->taskId = $taskId;
                $taskQuestionService->create($question);
                echo Date('h:i:s ') . '生成作业题目 ' . $question->id . PHP_EOL;

            }
        }
    }

    public function genTaskStudents()
    {
        echo Date('h:i:s ') . '生成学生作业 ' . PHP_EOL;
        $taskStudentService = new TaskStudentService();
        $taskService = new TaskService();
        foreach ($this->map['tasks'] as $taskId => $orgTaskId) {

            /** @var Task $task */
            $task = ArrayHelper::fetchByKey($this->tasks, 'id', $taskId);
            $task->completedCount = 0;
            $task->markedCount = 0;
            $task->marked = true;
            $task->avgScore = 0;
            $task->studentCount = 0;
            $scores = 0;

            $orgStudents = $taskStudentService->findAllByTask($this->orgSchool->id, $orgTaskId);
            foreach ($orgStudents as $orgStudent) {
                $studentIds = array_filter($this->map['students'], function ($studentId) use ($orgStudent) {
                    return $studentId == $orgStudent->studentId;
                });

                foreach ($studentIds as $studentId => $orgStudentId) {
                    $studentInfo = ArrayHelper::fetchByKey($this->students, 'id', $studentId);
                    if ($studentInfo->classId != $task->classId) {
                        continue;
                    }
                    $student = clone $orgStudent;
                    /** @var TaskStudent $student */
                    $student->id = 0;
                    $student->taskId = $taskId;
                    $student->studentId = $studentId;
                    $student->schoolId = $this->school->id;
                    $student->termId = $this->terms[0]->id;
                    $taskStudentService->create($student);
                    $task->studentCount++;
                    if ($student->status != TaskStudent::STATUS_DEFAULT) {
                        $task->completedCount++;
                    }

                    if ($student->status == TaskStudent::STATUS_MARKED) {
                        $task->markedCount++;
                        $scores += $student->avgScore;
                    } else {
                        $task->marked = false;
                    }
                    echo Date('h:i:s ') . '生成学生作业 ' . $student->id . PHP_EOL;
                }
            }

            if ($task->studentCount > 0) {
                if ($task->markedCount > 0) {
                    $task->avgScore = round($scores / $task->markedCount, 1);
                }
                $taskService->update($task);
            } else {
                $taskService->delete($task);
            }
            echo Date('h:i:s ') . '更新作业信息 ' . $task->id . ' ' . $orgTaskId . PHP_EOL;
        }
    }

    public function genAnswers()
    {
        echo Date('h:i:s ') . '生成作答 ' . PHP_EOL;

        $answerService = new AnswerService();
        foreach ($this->map['tasks'] as $taskId => $orgTaskId) {
            $orgAnswers = $answerService->findAllByTaskId($orgTaskId);
            foreach ($orgAnswers as $orgAnswer) {
                $studentIds = array_filter($this->map['students'], function ($studentId) use ($orgAnswer) {
                    return $studentId == $orgAnswer->studentId;
                });

                foreach ($studentIds as $studentId => $orgStudentId) {
                    $answer = clone $orgAnswer;
                    /** @var Answer $answer */
                    $answer->id = 0;
                    $answer->taskId = $taskId;
                    $answer->studentId = $studentId;
                    $answer->schoolId = $this->school->id;
                    $answer->termId = $this->terms[0]->id;
                    $answerService->create($answer);
                }
            }
            echo Date('h:i:s ') . '答案数 ' . count($orgAnswers) . PHP_EOL;

        }
    }


    public function genAnswerResults()
    {
        echo Date('h:i:s ') . '生成作答结果 ' . PHP_EOL;

        $answerResultService = new AnswerResultService();
        foreach ($this->map['students'] as $studentId => $orgStudentId) {
            $answerSearch = new AnswerSearch();
            $answerSearch->schoolId = $this->orgSchool->id;
            $answerSearch->studentId = $orgStudentId;
            $answerSearch->pageSize = 10000;
            $orgAnswerResults = $answerResultService->findPagedBySearch($answerSearch)->items;
            foreach ($orgAnswerResults as $orgAnswerResult) {

                $answer = clone $orgAnswerResult;
                /** @var AnswerResult $answerResult */
                $answer->id = 0;
                $answer->schoolId = $this->school->id;
                $answer->studentId = $studentId;
                $answer->termId = $this->terms[0]->id;
                $answerResultService->create($answer);
            }
            echo Date('h:i:s ') . '答案数 ' . count($orgAnswerResults) . PHP_EOL;

        }
    }

    public function calPolyFit()
    {
        echo Date('h:i:s ') . '计算趋势 ' . PHP_EOL;

        $statService = new StatService();
        foreach ($this->map['students'] as $studentId => $orgStudentId) {
            $student = ArrayHelper::fetchByKey($this->students, 'id', $studentId);
            $statService->calTaskPolyFit($student, 'GS002');
            echo Date('h:i:s ') . '计算趋势 ' . $studentId . PHP_EOL;

        }
    }

    public function randRealName($n)
    {
        $array = explode("\n", self::$realNames);
        return array_map(function ($i) use ($array) {
            return $array[$i];
        }, $n == 1 ? [array_rand($array, $n)] : array_rand($array, $n));
    }


    private function genTeacherTags()
    {
        echo Date('h:i:s ') . '生成收藏 ' . PHP_EOL;

        $teacherTagService = new TeacherTagService();
        $teacherQuestionTagService = new TeacherQuestionTagService();
        foreach ($this->map['teachers'] as $teacherId => $orgTeacherId) {

            //未分类
            $search = new TeacherQuestionTagSearch();
            $search->tagId = 0;
            $search->teacherId = $orgTeacherId;
            $search->pageSize = 10000;
            $questions = $teacherQuestionTagService->findPagedBySearch($search)->items;
            foreach ($questions as $orgQuestion) {
                $question = clone $orgQuestion;
                /** @var TeacherQuestionTag $question */
                $question->id = 0;
                $question->teacherId = $teacherId;
                $question->schoolId = $this->school->id;
                $question->tagId = 0;
                $teacherQuestionTagService->setTag($question);
            }
            echo Date('h:i:s ') . '未分类题目数 ' . count($questions) . PHP_EOL;


            $orgTags = $teacherTagService->findByTeacherId($this->orgSchool->id, $orgTeacherId);
            foreach ($orgTags as $orgTag) {
                $tag = new TeacherTag();
                /** @var TeacherTag $tag */
                $tag->schoolId = $this->school->id;
                $tag->teacherId = $teacherId;
                $tag->title = $orgTag->title;
                $teacherTagService->create($tag);

                $search = new TeacherQuestionTagSearch();
                $search->tagId = $orgTag->id;
                $search->teacherId = $orgTeacherId;
                $search->pageSize = 10000;
                $questions = $teacherQuestionTagService->findPagedBySearch($search)->items;
                foreach ($questions as $orgQuestion) {
                    $question = clone $orgQuestion;
                    /** @var TeacherQuestionTag $question */
                    $question->id = 0;
                    $question->teacherId = $teacherId;
                    $question->schoolId = $this->school->id;
                    $question->tagId = $tag->id;
                    $teacherQuestionTagService->setTag($question);
                }
                echo Date('h:i:s ') . $tag->title . '题目数 ' . count($questions) . PHP_EOL;

            }


            echo Date('h:i:s ') . '标签数 ' . count($orgTags) . PHP_EOL;
        }
    }


    static $realNames = <<< EOF
陈韵
黄慧宇
高宇欣
刘天成
翟雪婷
孟令钧
张宁
侯世瑞
李泽钰
姜佳怡
陈宏扬
涂泽锟
申雪
翁宇轩
徐欣欣
杜雪艳
刘浩梁
王家乐
刘秀娟
江雨露
宋智豪
华舒展
卜庆涛
姜瑜琪
秦天啸
孙雅欣
崔俊杰
魏雅雯
张佳
孙馨萍
杨玉萌
刘睿
周中同
单俊
王金乐
王柏栋
赵群艺
刘彤
管利斌
况成文
梁孔鲁
贾博然
李玥
朱昕怡
赵小龙
章烨
朱玷宽
赵嘉慧
于金壮
李德泽
高顿
李菲桐
张雨晨
孙启圣
张家昇
张广骏
曲美欣
戴颖
李陟扬
徐晓禾
李醒
关永浩
刘骏豪
柳玥
李晓
王凯乐
李承泽
肖佳怡
魏紫怡
褚加超
于笑
林建杰
石佳琦
万骏毅
褚浩冉
傅小萌
李欣钰
车恩旭
王雨辰
王奕力
聂腾
褚加琦
刘云亭
陈思韵
肖博熙
李思妤
吴焯炫
支坤芳
邹东乔
蔡昊
邹魏徽
刘粤港
林智达
赖珈琦
彭智盛
张文衔
卢颖琳
柯东君
何十一
钟文杰
叶永臻
林睿熙
陈鸿焜
冼锦明
吴彦哲
何崇源
钟欣潼
邱丞宇
汪锐文
陈颖莉
沈沁潼
郑锫琳
丘锐敏
杨康锐
梅春生
宋粲毅
余若溪
李想
吴洛妍
萧欣妍
刘凯明
庄经纬
吕林蔚
周婷
陈达
王雨凡
李秉璋
雷佳敏
马明聪
任骥鹏
邵玥瑄
王一达
刘雨阳
韩昕瑞
杨政
张华鹏
周琦瑒
刘博瀚
薛敬仁
李硕
任晓杰
靳济源
刘采熙
王豫蒙
龙宇杰
何青青
支鑫
周鼎翰
张菁怡
周春霖
杨贺得
姚烨
郭俊怡
翟子豪
汪竹昕
赵宇航
李子杰
李佳成
张兆歆
韩琪
杨天煜
李念欣
李钰轩
赵夏欣
吴骏
陈小雨
王楚楚
翁梅
张杉
陆格
徐雪松
陈骏杰
闫 岩
张睿涵
董佳玉
杨誉
沈欣雨
王雪凝
王文琦
陈佳怡
贾子博
张雯景
张馨悦
胡奕成
王子祥
杨若晗
邵煜
李逸卿
孙宜中
康婧茹
赵琦峰
张莹嘉
续晨曦
谢柠忆
王昭霖
卢思铭
张凯琳
吴欣燃
王嘉
付雨薇
谭军博
邓杰
秦泽轩
杨奇坤
张璨
王梓
张骧原
翁楠
杨烨
韩唯栋
唐尧
裴子龙
杨禛
刘添潇
李尚烨
蔡舸
张毅
王鹏越
黄嘉骏
肖函宇
郭垚志
王子硕
马恩泽
张驰州
向嘉晨
仇悦
郁妍
刘宇豪
田文彤
韩雨莘
吴晗
炎志浩
张一卓
李若然
程一铭
孙仲辉
刘丽滢
田彤
张子静
隋煜宁
孟羽轩
王思文
张智超
郝天亮
谢佳怡
韩佳钰
谭亚卿
雷宇翔
王艺桐
郑雪杉
赵彬宇
陈思彤
于德轩
袁美靖
裴绍泽
王子铭
张雨萌
张一弓
薛雨彤
方博
马海岳
陈赛
于庆泽
张亮
胡晓艺
崔可欣
李扬
王佳琪
李嘉睿
孙恬琦
张心田
陈东煜
孟祥顺
纪柄羽
刘若萱
王晓枫
李如意
王鑫乐
李兴磊
邱杰艺
王琪媛
刘福江
尹艺颖
任雯
王心怡
刘雪婷
赵逸飞
张琦
曲晓彤
李婧怡
王祺东
韩令旗
郭玮慈
李娴
王建辉
杨重鑫
刘奕显
高天宇
任鹏飞
王一凡
刘晨曦
彭一鑫
刘岩聪
张家豪
王琨
张文昊
宋珂
张中华
刘帅帅
刘乘龙
王诗宇
郭珈琳
管怡然
李晟嘉
赵昊天
刘庭语
王子涵
陈星宇
田玉熙
宋蕊含
李文涛
刘宇飞
贺子俊
饶睿
扈春锦
闫颂
王璐萌
李昀翰
李梓彤
仝铭轩
刘佳瑜
吴景麒
万泰辛
张鑫智
佟梦禹轩
李思昂
邹心彤
张鐘元
王展华
王韵
李涵琪
周梦圆
宋紫彤
郭英婕
孙雨彤
吕佳聪
杨京晶
黄铭轩
张皓然
陈天豪
贾灿光
王田雨
李宗玺
胡海涛
侯雨欣
吴雨涵
李子涵
胡子轩
李澎博
董慧聪
冯霁晖
侯伟嘉
于洋
张雪林
王晶
孙佳琪
罗亚辉
王浩天
孙雨凯
李鸣昊
梁澳
白彤宇
谷家鑫
郭富源
黄思宇
李弘泽
龙星桦
吴京晶
徐瑞岭
姚柯羽
张子涵
吴婧婷
陈泽莹
李思怡
刘子萱
孙璐瑶
陶金婧
王佳芮
王子巍
吴森
吴壮
张浩然
张越
张中洋
安福珩
翟金玺
方欣
郭天依
侯金阳
宋壮
孙剑旭
王宇泽
许文琦
赵从凯
林子骏
陈虹桦
陈积贤
陈亿
陈宇燊
陈钰霖
成渝
丁涵
范剑侨
冯雅诗
郭浩宇
胡文定
胡芷榕
黄佳达
黄思婷
李承道
李思蔓
李昕霖
梁柏漳
刘雅婕
陆韵思
满菘航
苏芊羽
童隽煜
涂殷
汪墨锦
吴企越
吴彦雨
吴雨桐
徐志华
徐卓锐
杨海林
杨涛鸣
尹泺迩
袁梓轩
张雅晴
赵炜程
钟熙悦
朱睿珑
易子芊
殷待寒
卞靠子
孙颂其
尤常贵
周沛方
卞宪振
危席搏
王育
蔡练研
赖泰军
乐渭玉
陆锦麦
李倍赐
孙钦灏
韩翰官
袁丹敬
孙升翀
孙玻楠
傅臻羿
鲁谦毅
宁缅标
危廷杰
郭颢珂
金竞修
陈剑童
曹孰利
侯理轩
孟湃来
云表思
孔山灏
张若弘
苏可百
马剑
章湘东
池贤羿
陆伙壮
杨辅妙
吕灼祥
蔡航维
孙睿祥
凌献兴
刘悲栋
王隆佑
鲁颖珑
周利童
梁申
曹学致
陈厚和
唐奕武
区孝悦
饶登界
杨晶幸
魏辨赫
蔡谷郎
钟斑北
柯牵益
EOF;
}