<?php
namespace ZuoYeah\Yaf\Plugin;

use Gram\Security\Authentication;
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;
use Gram\Utility\Helper\ThrowHelper;
use ZuoYeah\Entity\ErrorCode;
use ZuoYeah\Entity\UserType;
use ZuoYeah\Service\DeviceService;
use ZuoYeah\Service\SchoolAdminService;
use ZuoYeah\Service\StudentService;
use ZuoYeah\Service\TeacherService;

class TokenPlugin extends Plugin_Abstract
{
    /**
     * @var \ZuoYeah\Service\DeviceService
     */
    protected $deviceService;

    /**
     * @var \ZuoYeah\Service\StudentService
     */
    protected $studentService;

    function __construct($deviceService = null, $studentService = null)
    {
        $this->deviceService = is_null($deviceService)
            ? new DeviceService()
            : $deviceService;
        $this->studentService = is_null($studentService)
            ? new StudentService()
            : $studentService;
    }


    public function routerShutdown(Request_Abstract $request, Response_Abstract $response)
    {
        if ( substr( $request->getModuleName(),0,4) !== 'Api_') {
            return;
        }

        $token = $this->getToken($request);
        if (is_null($token)) {

            //自动给家长及学生通用接口赋值 此处只为模拟测试，不影响正常业务
            if ($request->getParam('tokenUserType') == UserType::STUDENT) {
                $request->setParam("studentId", $request->getParam('tokenUserId'));
            } elseif ($request->getParam('tokenUserType') == UserType::GUARDIAN) {
                if (!empty($request->getParam('studentId'))) {
                    if (!$this->isWhite($request)) {
                        ThrowHelper::ifFalse(
                            $this->studentService->hasGuardian($request->getParam('studentId')
                                , $request->getParam('tokenUserId'))
                            , '不是指定学生的家长', ErrorCode::COMMON_LOGICAL_ERROR);
                    }
                }
            }

            if(!$request->getParam('tokenUserId')){
                $request->setParam('tokenUuid',null);
                $request->setParam('tokenUserId',0);
                $request->setParam('tokenUserType','');
            }
            else{
                if(!Authentication::isAuthenticated()){
                    $request->setParam('tokenUuid', null);
                    $request->setParam('tokenUserId', 0);
                    $request->setParam('tokenUserType','');
                }
            }

            return;
        }

        $device = $this->deviceService->findByToken($token);
        if(is_null($device) || !$device->isEnabled()) {
            if ($this->isWhite($request)) {
                $request->setParam('tokenUuid',null);
                $request->setParam('tokenUserId',0);
                $request->setParam('tokenUserType','');
                return;
            }
        }

        ThrowHelper::ifNull($device, '登录状态已经失效', ErrorCode::TOKEN_IS_INVALID);
        ThrowHelper::ifFalse($device->isEnabled(), '登录状态已过期，请重新登录', ErrorCode::TOKEN_OUT_OF_DATE);

        //自动给家长及学生通用接口赋值
        if ($device->userType == UserType::STUDENT) {
            $request->setParam("studentId", $device->userId);
        } elseif ($device->userType == UserType::GUARDIAN) {
            if (!empty($request->getParam('studentId'))) {
                if (!$this->isWhite($request)) {
                    ThrowHelper::ifFalse(
                        $this->studentService->hasGuardian($request->getParam('studentId'), $device->userId)
                        , '不是指定学生的家长', ErrorCode::COMMON_LOGICAL_ERROR);
                }
            }
        }

        $request->setParam(array_merge([
            'token' => $device->token,
            'tokenUuid' => $device->uuid,
            'tokenUserId' => $device->userId,
            'tokenUserType' => $device->userType
        ], $request->getParams()));
    }

    function isWhite(Request_Abstract $request){
        $whitList =  \Yaf\Application::app()->getConfig()->api->whitList->toArray();
        $path = $request->getControllerName() . '/' . $request->getActionName();

        return in_array($path, $whitList);
    }

    /**
     * @param Request_Abstract $request
     *
     * @return mixed
     */
    function getToken(Request_Abstract $request)
    {
        $token = $request->getServer('HTTP_TOKEN');
        if (is_null($token)) {
            $token = $request->getQuery('TOKEN');
            if (is_null($token)) {
                $token = $request->getPost('TOKEN');
                return $token;
            }
            return $token;
        }
        return $token;
    }
}