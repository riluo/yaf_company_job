<?php
namespace ZuoYeah\Yaf\Plugin;

use Gram\Security\Authentication;
use Gram\Security\Exception\UnauthorizedAccessException;
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;
use Gram\Utility\Helper\ThrowHelper;
use ZuoYeah\Entity\ErrorCode;
use ZuoYeah\Entity\UserBase;
use ZuoYeah\Service\DeviceService;
use ZuoYeah\Service\GuardianService;
use ZuoYeah\Service\SchoolAdminService;
use ZuoYeah\Service\StudentService;
use ZuoYeah\Service\TeacherService;

class LoginInfoPlugin extends Plugin_Abstract
{
    /**
     * @var \ZuoYeah\Service\StudentService
     */
    protected $studentService;
    /**
     * @var \ZuoYeah\Service\GuardianService
     */
    protected $guardianService;
    /**
     * @var \ZuoYeah\Service\TeacherService
     */
    protected $teacherService;
    /**
     * @var \ZuoYeah\Service\SchoolAdminService
     */
    protected $schoolAdminService;

    function __construct($studentService = null, $teacherService = null, $schoolAdminService = null, $guardianService = null)
    {
        $this->studentService = is_null($studentService)
            ? new StudentService()
            : $studentService;
        $this->teacherService = is_null($teacherService)
            ? new TeacherService()
            : $teacherService;
        $this->schoolAdminService = is_null($schoolAdminService)
            ? new SchoolAdminService()
            : $schoolAdminService;
        $this->guardianService = is_null($guardianService)
            ? new GuardianService()
            : $guardianService;
    }


    public function routerShutdown(Request_Abstract $request, Response_Abstract $response)
    {
        if ($request->getModuleName() == 'School' && Authentication::isAuthenticated()) {
            $userName = Authentication::getUser()->getName();
            $admin = $this->schoolAdminService->findByUserName($userName);
            $this->checkUserVersion($admin, $request);
            $request->setParam('schoolId', $admin->schoolId);
            $request->setParam('schoolAdminId', $admin->id);
            return;
        }

        if ($request->getModuleName() == 'Student' && Authentication::isAuthenticated()) {
            $userName = Authentication::getUser()->getName();
            $student = $this->studentService->findByUserName($userName);
            $this->checkUserVersion($student, $request);
            $request->setParam('schoolId', $student->schoolId);
            $request->setParam('studentId', $student->id);
            return;
        }

        if ($request->getModuleName() == 'Guardian' && Authentication::isAuthenticated()) {
            $mobile = Authentication::getUser()->getName();
            $guardian = $this->guardianService->findByMobile($mobile);
            $this->checkUserVersion($guardian, $request);
            if (!empty($request->getParam('studentId'))) {
                ThrowHelper::ifFalse($this->studentService->hasGuardian($request->getParam('studentId')
                    , $guardian->id), '不是指定学生的家长', ErrorCode::COMMON_LOGICAL_ERROR);
            }

            $request->setParam('guardianId', $guardian->id);
            return;
        }


        if ($request->getModuleName() == 'Teacher' && Authentication::isAuthenticated()) {
            $userName = Authentication::getUser()->getName();
            $teacher = $this->teacherService->findByUserName($userName);
            $this->checkUserVersion($teacher, $request);
            $request->setParam('schoolId', $teacher->schoolId);
            $request->setParam('teacherId', $teacher->id);
            return;
        }
    }

    /**
     * @param $userInfo UserBase
     * @param Request_Abstract $request
     * @throws UnauthorizedAccessException
     */
    function checkUserVersion($userInfo, Request_Abstract $request)
    {
        $version = Authentication::getUser()->getVersion();
        if (!empty($version) && $userInfo->version() != $version) {

            $module = $request->getModuleName();
            $controller = $request->getControllerName();
            $action = $request->getActionName();
            if ($action != 'login' && $action != 'logout') {
                throw new UnauthorizedAccessException($module, $controller, $action, 'no access right', ErrorCode::COMMON_NOT_ACCESS_RIGHT);
            }
        }
    }
}