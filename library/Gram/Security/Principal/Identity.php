<?php
namespace Gram\Security\Principal;

class Identity
{
    protected $version;
    protected $name;
    protected $roles;

    /**
     * @param $name
     * @param $version
     * @param array $roles
     */
    function __construct($name, array $roles, $version = '')
    {
        $this->version = $version;
        $this->name = $name;
        $this->roles = $roles;
    }

    /**
     * @return array
     */
    function getRoles()
    {
        return $this->roles;
    }

    /**
     * @return mixed
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    function getVersion()
    {
        return $this->version;
    }

    /**
     * 确定当前 Principal 是否属于指定的角色。
     *
     * @param string $role 要检查基成员资格的角色的名称
     * @return bool
     */
    function isInRole($role)
    {
        return in_array(trim($role), $this->roles);
    }
}