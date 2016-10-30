<?php
namespace Gram\Security\Exception;


class UnauthorizedAccessException extends \Exception
{
    protected $moduleName;

    protected $controllerName;

    protected $actionName;

    /**
     * @param string $moduleName
     * @param string $controllerName
     * @param string $actionName
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     */
    function __construct($moduleName, $controllerName, $actionName,
                         $message = '', $code = 0, \Exception $previous = null)
    {
        $this->moduleName = $moduleName;
        $this->controllerName = $controllerName;
        $this->actionName = $actionName;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }
}