<?php
namespace ZuoYeah\Entity;

/**
 * 错误代码类
 *
 * 注意，错误代码均以6位长度区分，系统错误第1位为1，业务错误第1位为2，接下来2位为模块，后3位为具体错误代码
 *
 * @package ZuoYeah\Entity
 */
class ErrorCode
{
    /**
     * 未处理的异常
     */
    const EXCEPTION_UNHANDLED = 101001;

    /**
     * 参数不能为空
     */
    const COMMON_NOT_EMPTY = 201001;
    /**
     * 对象已经存在
     */
    const COMMON_EXIST_CONFLICT = 201002;
    /**
     * 对象不存在
     */
    const COMMON_NOT_EXIST = 201404;
    /**
     * 逻辑错误
     */
    const COMMON_LOGICAL_ERROR = 201003;

    /**
     * 没权限
     */
    const COMMON_NOT_ACCESS_RIGHT = 201004;

    /**
     * 请求太频繁
     */
    const COMMON_FREQUENCY = 201006;

    /**
     * 图片合并失败
     */
    const COMMON_IMAGE_MERGE_FAIL = 201005;
    /**
     * 没有文件被上传
     */
    const UPLOAD_NO_FILE = 202001;

    /**
     * 作业标识不能为空
     */
    const TASK_ID_IS_EMPTY = 203001;

    /**
     * 验证码已失效
     */
    const CAPTCHA_IS_INVALID = 204001;

    /**
     * 验证码已过期
     */
    const CAPTCHA_OUT_OF_DATE = 204002;

    /**
     * 用户帐号密码错误
     */
    const USER_PASSWORD_ERROR = 205001;

    /**
     * TOKEN已过期
     */
    const TOKEN_OUT_OF_DATE = 206001;

    /**
     * TOKEN已失效
     */
    const TOKEN_IS_INVALID = 206002;

    /**
     * 未教任何班级
     */
    const TEACHER_NOT_TEACH_CLASS = 207001;

    /**
     * 学期未绑定课本
     */
    const TERM_NOT_BIND_BOOK = 208001;

    /**
     * 作业已提交
     */
    const TASK_SUBMITED = 209001;

}