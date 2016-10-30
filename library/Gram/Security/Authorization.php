<?php
namespace Gram\Security;

use Gram\Security\Principal\Identity;

class Authorization
{
    /**
     * 通配符
     */
    const WILDCARDS = '*';
    const DENY_ACCESS = 'deny';
    const ALLOW_ACCESS = 'allow';
    const USERS_MODE = 'users';
    const ROLES_MODE = 'roles';
    const ANONYMOUS_USER = '?';
    const ALL_USER = '*';

    /**
     * @var array 权限配置列表
     */
    protected $rules;

    /**
     * 权限配置列表，格式如下：
     * [
     *     'Index.Home.index.allow.roles' => 'Administrator,User',
     *     'Index.Home.index.deny.users' => '*',
     *     'Admin.*.allow.roles' => 'Administrator',
     *     'Admin.*.deny.users' => '*'
     * ]
     *
     * @param array $rules
     */
    function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * 验证是否有相应的授权
     *
     * @param $module
     * @param $controller
     * @param $action
     * @return bool
     */
    function check($module, $controller, $action)
    {
        $rule = $this->getMatchRule($module, $controller, $action);
        if (is_null($rule)) {
            return true;
        }
        return $this->applyRule($rule);
    }

    /**
     * 获取匹配的规则
     *
     * @param $module
     * @param $controller
     * @param $action
     * @return array|null
     */
    protected function getMatchRule($module, $controller, $action)
    {
        if (!empty($this->rules[$module][$controller][$action])) {
            return $this->rules[$module][$controller][$action];
        }
        if (!empty($this->rules[$module][$controller][self::WILDCARDS])) {
            return $this->rules[$module][$controller][self::WILDCARDS];
        }
        if (!empty($this->rules[$module][$controller])) {
            return $this->rules[$module][$controller];
        }
        if (!empty($this->rules[$module][self::WILDCARDS])) {
            return $this->rules[$module][self::WILDCARDS];
        }
        if (!empty($this->rules[$module])) {
            return $this->rules[$module];
        }
        if (!empty($this->rules[self::WILDCARDS])) {
            return $this->rules[self::WILDCARDS];
        }
        return null;
    }

    /**
     * 应用规则，验证是否有相应的授权
     *
     * @param array $rule
     * @return bool
     */
    protected function applyRule(array $rule)
    {
        $user = $this->getUser();
        foreach ($rule as $mode => $value) {
            $match = $this->matchRuleByRole($value, $user)
                || $this->matchRuleByUser($value, $user);
            if ($match === true) {
                return $mode === self::ALLOW_ACCESS ? true : false;
            }
        }
        return true;
    }

    /**
     * 匹配角色,
     *   成功：true
     *   失败：false
     *
     * @param $rule
     * @param $user
     * @return bool
     */
    protected function matchRuleByRole($rule, $user)
    {
        if (is_null($user)) {
            return false;
        }
        if (!$user instanceof Identity) {
            throw new \InvalidArgumentException('待验证的用户类型错误');
        }
        if (isset($rule[self::ROLES_MODE])) {
            $roles = $rule[self::ROLES_MODE];
            $roles = explode(',', $roles);
            foreach ($roles as $role) {
                if ($user->isInRole($role)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 应用用户匹配，
     *   匹配成功：true
     *   匹配失败：false
     *
     * @param $rule
     * @param $user
     * @return bool
     */
    protected function matchRuleByUser($rule, $user)
    {
        if (isset($rule[self::USERS_MODE])) {
            $users = trim($rule[self::USERS_MODE]);
            if ($users === self::ALL_USER) {
                return true;
            }
            if ($users == self::ANONYMOUS_USER) {
                return is_null($user);
            }
        }
        return false;
    }

    /**
     * @return Identity|null
     */
    protected function getUser()
    {
        return Authentication::getUser();
    }
}